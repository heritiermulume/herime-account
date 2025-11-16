<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Passport\Token;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez vérifier les informations saisies.',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'company' => $request->company,
            'position' => $request->position,
        ]);

        // Create access token
        $token = $user->createToken('Herime SSO', ['profile'])->accessToken;

        // Create user session
        $this->createUserSession($user, $request);

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => [
                'user' => $user->load('currentSession'),
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]
        ], 201);
    }

    /**
     * Login user
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez vérifier les informations saisies.',
                'errors' => $validator->errors()
            ], 422);
        }

        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants incorrects. Veuillez vérifier votre email et mot de passe.'
            ], 401);
        }

        $user = Auth::user();

        if (!$user->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Votre compte a été désactivé. Veuillez contacter l\'administrateur.'
            ], 403);
        }

        // Update last login info
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'last_login_user_agent' => $request->userAgent(),
        ]);

        // Create access token
        $token = $user->createToken('Herime SSO', ['profile'])->accessToken;

        // Create user session
        $this->createUserSession($user, $request);

        // Vérifier si on doit générer une URL de redirection SSO
        $ssoRedirectUrl = null;
        $forceToken = $request->input('force_token') ?: $request->query('force_token');
        $redirectUrl = $request->input('redirect') ?: $request->query('redirect');
        
        // Normaliser force_token en booléen
        $forceToken = in_array($forceToken, [1, '1', true, 'true', 'yes', 'on'], true);
        
        \Log::info('AuthController@login: Checking SSO redirect', [
            'has_force_token' => $forceToken,
            'has_redirect' => !empty($redirectUrl),
            'redirect_url' => $redirectUrl,
        ]);
        
        if ($forceToken && $redirectUrl) {
            // Décoder l'URL de redirection si nécessaire
            $decodedRedirect = $redirectUrl;
            for ($i = 0; $i < 5; $i++) {
                $testDecode = urldecode($decodedRedirect);
                if ($testDecode === $decodedRedirect) {
                    break;
                }
                if (filter_var($testDecode, FILTER_VALIDATE_URL)) {
                    $decodedRedirect = $testDecode;
                }
            }
            
            // Vérifier que l'URL ne pointe pas vers le même domaine
            $redirectHost = parse_url($decodedRedirect, PHP_URL_HOST);
            $currentHost = $request->getHost();
            
            if ($redirectHost) {
                $redirectHost = preg_replace('/^www\./', '', strtolower($redirectHost));
                $currentHost = preg_replace('/^www\./', '', strtolower($currentHost));
                
                // Si c'est un domaine externe, générer l'URL de callback avec le token
                if ($redirectHost !== $currentHost && $redirectHost !== 'compte.herime.com') {
                    $parsedUrl = parse_url($decodedRedirect);
                    if ($parsedUrl && isset($parsedUrl['scheme']) && isset($parsedUrl['host'])) {
                        $queryParams = [];
                        if (isset($parsedUrl['query'])) {
                            parse_str($parsedUrl['query'], $queryParams);
                        }
                        
                        // Ajouter le token
                        $queryParams['token'] = $token;
                        $newQuery = http_build_query($queryParams);
                        
                        $ssoRedirectUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
                        if (isset($parsedUrl['port'])) {
                            $ssoRedirectUrl .= ':' . $parsedUrl['port'];
                        }
                        if (isset($parsedUrl['path'])) {
                            $ssoRedirectUrl .= $parsedUrl['path'];
                        }
                        if ($newQuery) {
                            $ssoRedirectUrl .= '?' . $newQuery;
                        }
                        if (isset($parsedUrl['fragment'])) {
                            $ssoRedirectUrl .= '#' . $parsedUrl['fragment'];
                        }
                        
                        \Log::info('AuthController: Generated SSO redirect URL on login', [
                            'user_id' => $user->id,
                            'redirect_url' => $ssoRedirectUrl,
                        ]);
                    }
                }
            }
        }

        $responseData = [
            'user' => $user->load('currentSession'),
            'access_token' => $token,
            'token_type' => 'Bearer',
        ];
        
        if ($ssoRedirectUrl) {
            $responseData['sso_redirect_url'] = $ssoRedirectUrl;
        }

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => $responseData
        ]);
    }

    /**
     * Logout user
     * Marque toutes les sessions comme inactives et invalide tous les tokens
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if ($user) {
            try {
                // Révoquer le token actuel utilisé pour cette requête
                try {
                    $authHeader = $request->header('Authorization');
                    if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
                        $token = substr($authHeader, 7);
                        $tokenHash = hash('sha256', $token);
                        $currentToken = \Laravel\Passport\Token::where('id', $tokenHash)
                            ->where('revoked', false)
                            ->first();
                        if ($currentToken) {
                            $currentToken->revoke();
                        }
                    }
                } catch (\Exception $e) {
                    // Si le token actuel n'est pas accessible, continuer quand même
                }
                
                // Révoquer TOUS les tokens Passport de l'utilisateur pour déconnecter tous les sites externes
                // Cette opération invalide immédiatement tous les tokens (y compris celui déjà révoqué ci-dessus)
                $user->tokens()->update(['revoked' => true]);
                
                // Marquer TOUTES les sessions de l'utilisateur comme inactives (au lieu de les supprimer)
                // Cela permet de garder l'historique des sessions pour l'audit
                $user->sessions()->update([
                    'is_current' => false,
                    'last_activity_at' => now()
                ]);
                
                \Log::info('AuthController: User logged out', [
                    'user_id' => $user->id,
                    'sessions_marked_inactive' => $user->sessions()->count(),
                ]);
            } catch (\Exception $e) {
                // En cas d'erreur, essayer de continuer le logout quand même
                \Log::error('AuthController: Error during logout', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie. Toutes les sessions ont été marquées comme inactives et tous les tokens ont été invalidés.'
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user->load('currentSession')
            ]
        ]);
    }

    /**
     * Refresh token
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Revoke current token
        $user->token()->revoke();
        
        // Create new token
        $token = $user->createToken('Herime SSO', ['profile'])->accessToken;

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed successfully',
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]
        ]);
    }

    /**
     * Create user session
     */
    private function createUserSession(User $user, Request $request): void
    {
        try {
            // Mark all previous sessions as inactive
            $previousSessionsCount = $user->sessions()->count();
            $user->sessions()->update(['is_current' => false]);
            
            \Log::info('AuthController: Creating user session', [
                'user_id' => $user->id,
                'previous_sessions_count' => $previousSessionsCount,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Create new session
            $deviceInfo = $this->getDeviceInfo($request->userAgent());
            
            $session = UserSession::create([
                'user_id' => $user->id,
                'session_id' => Str::random(40),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_name' => $deviceInfo['device_name'],
                'platform' => $deviceInfo['platform'],
                'browser' => $deviceInfo['browser'],
                'is_current' => true,
                'last_activity' => now(),
            ]);
            
            \Log::info('AuthController: User session created', [
                'user_id' => $user->id,
                'session_id' => $session->id,
                'total_sessions' => $user->sessions()->count(),
            ]);
        } catch (\Exception $e) {
            \Log::error('AuthController: Error creating user session', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Get device information from user agent
     */
    private function getDeviceInfo(string $userAgent): array
    {
        $platform = 'Unknown';
        $browser = 'Unknown';
        $deviceName = 'Unknown Device';

        // Platform detection
        if (strpos($userAgent, 'Windows') !== false) {
            $platform = 'Windows';
            $deviceName = 'Windows PC';
        } elseif (strpos($userAgent, 'Mac') !== false) {
            $platform = 'macOS';
            $deviceName = 'Mac';
        } elseif (strpos($userAgent, 'Linux') !== false) {
            $platform = 'Linux';
            $deviceName = 'Linux PC';
        } elseif (strpos($userAgent, 'Android') !== false) {
            $platform = 'Android';
            $deviceName = 'Android Device';
        } elseif (strpos($userAgent, 'iPhone') !== false) {
            $platform = 'iOS';
            $deviceName = 'iPhone';
        } elseif (strpos($userAgent, 'iPad') !== false) {
            $platform = 'iOS';
            $deviceName = 'iPad';
        }

        // Browser detection
        if (strpos($userAgent, 'Chrome') !== false) {
            $browser = 'Chrome';
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            $browser = 'Firefox';
        } elseif (strpos($userAgent, 'Safari') !== false) {
            $browser = 'Safari';
        } elseif (strpos($userAgent, 'Edge') !== false) {
            $browser = 'Edge';
        }

        return [
            'platform' => $platform,
            'browser' => $browser,
            'device_name' => $deviceName,
        ];
    }
}
