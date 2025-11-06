<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSession;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use App\Services\NotificationService;
use App\Mail\NewLoginMail;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;

class SimpleAuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request): JsonResponse
    {
        // Check if registration is enabled
        $registrationEnabled = SystemSetting::get('registration_enabled', '1') === '1';
        if (!$registrationEnabled) {
            return response()->json([
                'success' => false,
                'message' => 'L\'inscription est actuellement désactivée. Veuillez contacter un administrateur.'
            ], 403);
        }

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

        // Log the user in
        Auth::login($user);

        // Create user session
        $this->createUserSession($user, $request);

        // Envoyer un email de nouvelle connexion si activé
        try {
            $parts = $user->name ? preg_split('/\s+/', trim($user->name)) : [];
            $firstName = $parts[0] ?? null;
            $lastName = isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : null;
            $device = $request->userAgent();
            $ip = $request->ip();
            $time = now()->toDateTimeString();
            NotificationService::sendForEvent($user, 'suspicious_logins', new NewLoginMail($firstName, $lastName, $ip, $device, $time));
        } catch (\Exception $e) {
            // Ignorer les erreurs d'envoi d'email
        } 

        // Create access token for API authentication
        $token = $user->createToken('API Token')->accessToken;

        // Forcer l'inclusion de avatar_url
        $user->makeVisible(['avatar', 'avatar_url']);
        $userData = $user->load('currentSession')->toArray();
        $userData['avatar_url'] = $user->avatar_url;
        
        return response()->json([
            'success' => true,
            'message' => 'Compte créé avec succès',
            'data' => [
                'user' => $userData,
                'authenticated' => true,
                'access_token' => $token,
                'token_type' => 'Bearer'
            ]
        ], 201);
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        // Vérifier si l'utilisateur est déjà connecté et qu'on demande un token SSO
        if (Auth::check() && ($request->has('force_token') || $request->query('force_token'))) {
            // Utilisateur déjà connecté, générer token SSO immédiatement
            $user = Auth::user();
            
            if (!$user->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Votre compte a été désactivé. Veuillez contacter l\'administrateur.'
                ], 403);
            }

            $token = $this->generateSSOToken($user);
            
            // Détecter l'origine de l'appel d'authentification
            $redirectUrl = $this->determineRedirectUrl($request);
            
            // Si c'est une requête API (Accept: application/json), retourner JSON
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Token SSO généré avec succès',
                    'data' => [
                        'token' => $token,
                        'callback_url' => $redirectUrl ? $redirectUrl . '?token=' . $token : null,
                    ]
                ]);
            }
            
            // Sinon, rediriger (pour les requêtes web)
            if ($redirectUrl) {
                $redirectUrl .= (strpos($redirectUrl, '?') !== false ? '&' : '?') . 'token=' . $token;
                return redirect($redirectUrl);
            }
            
            // Par défaut, rediriger vers le dashboard local
            return redirect('/dashboard');
        }

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
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'Votre compte a été désactivé. Veuillez contacter l\'administrateur.'
            ], 403);
        }

        // Vérifier si la 2FA est activée
        $user->refresh();
        
        // Utiliser la méthode Fortify pour vérifier si la 2FA est activée
        $has2FAEnabled = $user->hasEnabledTwoFactorAuthentication();
        
        if ($has2FAEnabled) {
            // La 2FA est activée, on doit demander le code
            // Générer un jeton temporaire stocké côté serveur (stateless API)
            $twoFactorToken = Str::random(60);
            Cache::put('2fa:'.$twoFactorToken, $user->id, now()->addMinutes(5));

            // Déconnecter l'utilisateur jusqu'à ce que le code 2FA soit vérifié
            Auth::logout();
            
            return response()->json([
                'success' => false,
                'requires_two_factor' => true,
                'two_factor_token' => $twoFactorToken,
                'message' => 'Veuillez entrer le code d\'authentification à deux facteurs.'
            ], 200);
        }

        // Update last login info
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'last_login_user_agent' => $request->userAgent(),
        ]);

        // Create user session
        $this->createUserSession($user, $request);

        // Recharger l'utilisateur pour s'assurer d'avoir les dernières données
        $user->refresh();
        
        // Create access token for API authentication
        $token = $user->createToken('API Token')->accessToken;

        // Vérifier si on doit rediriger vers un domaine externe après connexion
        $redirectUrl = $this->determineRedirectUrl($request);
        if ($redirectUrl && !$request->wantsJson() && !$request->expectsJson()) {
            // C'est une requête web depuis un domaine externe, générer un token SSO et rediriger
            $ssoToken = $this->generateSSOToken($user);
            $redirectUrl .= (strpos($redirectUrl, '?') !== false ? '&' : '?') . 'token=' . $ssoToken;
            return redirect($redirectUrl);
        }

        // Forcer l'inclusion de avatar_url et last_login_at
        $user->makeVisible(['avatar', 'avatar_url', 'last_login_at', 'is_active']);
        $userData = $user->load('currentSession')->toArray();
        $userData['avatar_url'] = $user->avatar_url;
        
        // S'assurer que last_login_at est bien inclus
        if (!isset($userData['last_login_at'])) {
            $userData['last_login_at'] = $user->last_login_at ? $user->last_login_at->toISOString() : null;
        }
        
        // Si une redirection externe est nécessaire, l'inclure dans la réponse JSON
        $responseData = [
            'user' => $userData,
            'authenticated' => true,
            'access_token' => $token,
            'token_type' => 'Bearer'
        ];

        if ($redirectUrl) {
            $ssoToken = $this->generateSSOToken($user);
            $responseData['sso_redirect_url'] = $redirectUrl . '?token=' . $ssoToken;
            $responseData['sso_token'] = $ssoToken;
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => $responseData
        ]);
    }

    /**
     * Verify 2FA code and complete login
     */
    public function verifyTwoFactor(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'two_factor_token' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez entrer un code de 6 chiffres.',
                'errors' => $validator->errors()
            ], 422);
        }

        // Récupérer l'ID de l'utilisateur depuis le cache via le jeton
        $userId = Cache::pull('2fa:'.$request->two_factor_token);
        
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Code expiré ou session expirée. Veuillez vous reconnecter.'
            ], 401);
        }

        $user = User::find($userId);

        if (!$user || $user->email !== $request->email) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé ou email incorrect.'
            ], 401);
        }

        if (!$user->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Votre compte a été désactivé. Veuillez contacter l\'administrateur.'
            ], 403);
        }

        // Vérifier le code 2FA
        if (!$user->two_factor_secret) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun secret 2FA trouvé.'
            ], 400);
        }

        $provider = app(TwoFactorAuthenticationProvider::class);
        $valid = $provider->verify(
            Fortify::currentEncrypter()->decrypt($user->two_factor_secret),
            $request->code
        );

        if (!$valid) {
            // Vérifier aussi les codes de récupération
            $recoveryCodes = json_decode(
                Fortify::currentEncrypter()->decrypt($user->two_factor_recovery_codes ?? '[]'),
                true
            ) ?? [];
            
            if (!in_array($request->code, $recoveryCodes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Code de vérification invalide.'
                ], 422);
            }
            
            // Code de récupération utilisé, le remplacer
            $recoveryCodes = array_values(array_diff($recoveryCodes, [$request->code]));
            $user->forceFill([
                'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode($recoveryCodes)),
            ])->save();
        }

        // Code valide, finaliser la connexion
        Auth::login($user);

        // Update last login info
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'last_login_user_agent' => $request->userAgent(),
        ]);

        // Create user session
        $this->createUserSession($user, $request);

        // Recharger l'utilisateur pour s'assurer d'avoir les dernières données
        $user->refresh();
        
        // Create access token for API authentication
        $token = $user->createToken('API Token')->accessToken;

        // Vérifier si on doit rediriger vers un domaine externe après connexion
        $redirectUrl = $this->determineRedirectUrl($request);

        // Forcer l'inclusion de avatar_url et last_login_at
        $user->makeVisible(['avatar', 'avatar_url', 'last_login_at', 'is_active']);
        $userData = $user->load('currentSession')->toArray();
        $userData['avatar_url'] = $user->avatar_url;
        
        // S'assurer que last_login_at est bien inclus
        if (!isset($userData['last_login_at'])) {
            $userData['last_login_at'] = $user->last_login_at ? $user->last_login_at->toISOString() : null;
        }
        
        // Si une redirection externe est nécessaire, l'inclure dans la réponse JSON
        $responseData = [
            'user' => $userData,
            'authenticated' => true,
            'access_token' => $token,
            'token_type' => 'Bearer'
        ];

        if ($redirectUrl) {
            $ssoToken = $this->generateSSOToken($user);
            $responseData['sso_redirect_url'] = $redirectUrl . '?token=' . $ssoToken;
            $responseData['sso_token'] = $ssoToken;
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Authentification à deux facteurs réussie',
            'data' => $responseData
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if ($user) {
            // Révoquer TOUS les tokens Passport de l'utilisateur pour déconnecter tous les sites externes
            $user->tokens()->update(['revoked' => true]);
            
            // Marquer TOUTES les sessions comme inactives (déconnecter tous les appareils)
            $user->sessions()->update(['is_current' => false]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Logout successful'
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié'
            ], 401);
        }
        
        try {
            // Forcer l'inclusion de avatar_url et last_login_at
            $user->makeVisible(['avatar', 'avatar_url', 'last_login_at', 'is_active']);
            
            // Charger la session actuelle si elle existe (sans erreur si elle n'existe pas)
            try {
                $user->load('currentSession');
            } catch (\Exception $e) {
                // Ignorer les erreurs de chargement de session
            }
            
            $userData = $user->toArray();
            $userData['avatar_url'] = $user->avatar_url;
            
            // S'assurer que last_login_at est bien formaté
            if ($user->last_login_at) {
                try {
                    $userData['last_login_at'] = $user->last_login_at->toISOString();
                } catch (\Exception $e) {
                    $userData['last_login_at'] = $user->last_login_at ? $user->last_login_at->format('Y-m-d\TH:i:s.u\Z') : null;
                }
            } else {
                $userData['last_login_at'] = null;
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $userData
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données utilisateur',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Create user session
     */
    private function createUserSession(User $user, Request $request): void
    {
        // Clean expired sessions first
        $this->cleanExpiredSessions($user);
        
        // Get max sessions per user setting
        $maxSessions = (int)SystemSetting::get('max_sessions_per_user', 5);
        
        // Count current active sessions
        $activeSessionsCount = $user->sessions()->where('is_current', true)->count();
        
        // If user has reached max sessions, remove oldest sessions
        if ($activeSessionsCount >= $maxSessions) {
            $sessionsToDelete = $activeSessionsCount - $maxSessions + 1;
            $oldestSessions = $user->sessions()
                ->where('is_current', true)
                ->orderBy('last_activity', 'asc')
                ->orderBy('created_at', 'asc')
                ->limit($sessionsToDelete)
                ->get();
            
            foreach ($oldestSessions as $session) {
                $session->delete();
            }
        }

        // Mark all remaining sessions as inactive
        $user->sessions()->update(['is_current' => false]);

        // Create new session
        $deviceInfo = $this->getDeviceInfo($request->userAgent());
        
        UserSession::create([
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
    }

    /**
     * Clean expired sessions for a user
     */
    private function cleanExpiredSessions(User $user): void
    {
        $timeoutHours = (int)SystemSetting::get('session_timeout', 24);
        $expiredDate = now()->subHours($timeoutHours);
        
        $user->sessions()
            ->where(function($query) use ($expiredDate) {
                $query->where('last_activity', '<', $expiredDate)
                      ->orWhere(function($q) use ($expiredDate) {
                          $q->whereNull('last_activity')
                            ->where('created_at', '<', $expiredDate);
                      });
            })
            ->delete();
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

    /**
     * Generate SSO token for user
     */
    private function generateSSOToken(User $user): string
    {
        // Créer un token Passport pour le SSO
        $token = $user->createToken('SSO Token', ['profile'])->accessToken;
        return $token;
    }

    /**
     * Determine the redirect URL based on the origin of the authentication request
     */
    private function determineRedirectUrl(Request $request): ?string
    {
        // 1. Priorité : paramètre 'redirect' explicite dans la requête
        if ($request->has('redirect') || $request->query('redirect')) {
            $redirect = $request->input('redirect') ?: $request->query('redirect');
            if ($redirect && filter_var($redirect, FILTER_VALIDATE_URL)) {
                return $redirect;
            }
        }

        // 2. Vérifier le paramètre 'client_domain' pour construire l'URL de callback
        if ($request->has('client_domain') || $request->query('client_domain')) {
            $clientDomain = $request->input('client_domain') ?: $request->query('client_domain');
            if ($clientDomain) {
                // Construire l'URL de callback standard pour le domaine client
                $scheme = $request->secure() ? 'https' : 'http';
                $redirectPath = $request->query('redirect_path') ?: '/sso/callback';
                return $scheme . '://' . $clientDomain . $redirectPath;
            }
        }

        // 3. Détecter depuis le header Referer
        $referer = $request->header('Referer');
        if ($referer) {
            $refererUrl = parse_url($referer);
            $currentHost = parse_url(config('app.url'), PHP_URL_HOST);
            
            // Si le referer vient d'un autre domaine que compte.herime.com
            if (isset($refererUrl['host']) && $refererUrl['host'] !== $currentHost) {
                $scheme = $refererUrl['scheme'] ?? ($request->secure() ? 'https' : 'http');
                $host = $refererUrl['host'];
                $redirectPath = $request->query('redirect_path') ?: '/sso/callback';
                return $scheme . '://' . $host . $redirectPath;
            }
        }

        // 4. Détecter depuis le header Origin
        $origin = $request->header('Origin');
        if ($origin) {
            $originUrl = parse_url($origin);
            $currentHost = parse_url(config('app.url'), PHP_URL_HOST);
            
            // Si l'origin vient d'un autre domaine que compte.herime.com
            if (isset($originUrl['host']) && $originUrl['host'] !== $currentHost) {
                $scheme = $originUrl['scheme'] ?? ($request->secure() ? 'https' : 'http');
                $host = $originUrl['host'];
                $redirectPath = $request->query('redirect_path') ?: '/sso/callback';
                return $scheme . '://' . $host . $redirectPath;
            }
        }

        // 5. Si aucune origine externe détectée, retourner null pour rediriger vers le dashboard local
        return null;
    }
}
