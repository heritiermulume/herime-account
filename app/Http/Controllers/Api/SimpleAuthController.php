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
            'phone' => 'required|string|max:20|unique:users',
            'company' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
        ], [
            'name.required' => 'Le nom complet est obligatoire.',
            'name.max' => 'Le nom ne peut pas dépasser 255 caractères.',
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email' => 'Veuillez saisir une adresse email valide.',
            'email.unique' => 'Cette adresse email est déjà utilisée par un autre compte.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
            'phone.required' => 'Le numéro de téléphone est obligatoire.',
            'phone.max' => 'Le numéro de téléphone ne peut pas dépasser 20 caractères.',
            'phone.unique' => 'Ce numéro de téléphone est déjà utilisé par un autre compte.',
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

        // Vérifier si on doit rediriger vers un domaine externe après inscription
        $redirectUrl = $this->determineRedirectUrl($request);
        
        // Créer un seul token avec les bons scopes (évite la double génération qui cause des 503)
        // Si redirection SSO nécessaire, créer un token SSO avec scope 'profile'
        // Sinon, créer un token API standard
        try {
            $tokenName = $redirectUrl ? 'SSO Token' : 'API Token';
            $scopes = $redirectUrl ? ['profile'] : [];
            $token = $user->createToken($tokenName, $scopes)->accessToken;
        } catch (\Exception $e) {
            \Log::error('SimpleAuthController: Token creation failed during registration', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la création de votre session. Veuillez réessayer dans quelques instants.',
                'error_code' => 'TOKEN_CREATION_FAILED'
            ], 500);
        }

        // Forcer l'inclusion de avatar_url
        $user->makeVisible(['avatar', 'avatar_url']);
        $userData = $user->load('currentSession')->toArray();
        $userData['avatar_url'] = $user->avatar_url;
        
        $responseData = [
            'user' => $userData,
            'authenticated' => true,
            'access_token' => $token,
            'token_type' => 'Bearer'
        ];
        
        // Si une redirection externe est nécessaire, construire l'URL de callback
        if ($redirectUrl) {
            // Construire l'URL de callback avec le token
            $parsedUrl = parse_url($redirectUrl);
            $queryParams = [];
            
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
            }
            
            $queryParams['token'] = $token;
            $newQuery = http_build_query($queryParams);
            
            $callbackUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
            if (isset($parsedUrl['port'])) {
                $callbackUrl .= ':' . $parsedUrl['port'];
            }
            if (isset($parsedUrl['path'])) {
                $callbackUrl .= $parsedUrl['path'];
            }
            if ($newQuery) {
                $callbackUrl .= '?' . $newQuery;
            }
            if (isset($parsedUrl['fragment'])) {
                $callbackUrl .= '#' . $parsedUrl['fragment'];
            }
            
            $responseData['sso_redirect_url'] = $callbackUrl;
            $responseData['sso_token'] = $token;
            
            \Log::info('SimpleAuthController: SSO redirect URL generated after registration', [
                'callback_url' => $callbackUrl,
                'user_id' => $user->id,
            ]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Compte créé avec succès',
            'data' => $responseData
        ], 201);
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        // Si l'utilisateur est déjà connecté et qu'on demande un token SSO
        if (Auth::check() && ($request->has('force_token') || $request->query('force_token'))) {
            $user = Auth::user();
            
            if (!$user->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Votre compte a été désactivé. Veuillez contacter l\'administrateur.'
                ], 403);
            }

            $redirectUrl = $this->determineRedirectUrl($request);
            
            if (!$redirectUrl) {
                return response()->json([
                    'success' => false,
                    'message' => 'URL de redirection manquante'
                ], 422);
            }

            $token = $user->createToken('SSO Token', ['profile'])->accessToken;
            $callbackUrl = $redirectUrl . (strpos($redirectUrl, '?') !== false ? '&' : '?') . 'token=' . $token;
            
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Token SSO généré avec succès',
                    'data' => [
                        'token' => $token,
                        'callback_url' => $callbackUrl,
                    ]
                ]);
            }
            
            return redirect($callbackUrl);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
            'password' => 'required|string',
        ], [
            'email.required' => 'L\'email ou le numéro de téléphone est obligatoire.',
            'password.required' => 'Le mot de passe est obligatoire.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez vérifier les informations saisies.',
                'errors' => $validator->errors()
            ], 422);
        }

        $login = $request->input('email'); // Peut être email ou téléphone
        $password = $request->input('password');

        // Déterminer si c'est un email ou un téléphone
        $fieldType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        
        // Chercher l'utilisateur
        $user = User::where($fieldType, $login)->first();
        
        // Vérifier si l'utilisateur existe et si le mot de passe est correct
        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants incorrects. Veuillez vérifier votre email/téléphone et mot de passe.'
            ], 401);
        }
        
        // Authentifier l'utilisateur manuellement
        Auth::login($user);

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
        // Pour une requête POST, les paramètres sont dans le body, pas dans la query string
        // Vérifier dans input() (body), query() (query string), et all()
        $forceToken = $request->has('force_token') 
                   || $request->input('force_token') 
                   || $request->get('force_token')
                   || $request->query('force_token')
                   || ($request->all()['force_token'] ?? null);
        
        // Normaliser force_token (peut être '1', 1, true, 'true', etc.)
        $forceTokenValue = $request->input('force_token') 
                        ?? $request->get('force_token')
                        ?? $request->query('force_token')
                        ?? ($request->all()['force_token'] ?? null);
        
        $forceToken = in_array($forceTokenValue, [1, '1', true, 'true', 'yes', 'on'], true) 
                   || ($forceTokenValue !== null && $forceTokenValue !== false && $forceTokenValue !== '');
        
        $redirectUrl = null;
        
        // Si force_token est présent, déterminer l'URL de redirection
        if ($forceToken) {
            $redirectUrl = $this->determineRedirectUrl($request);
            
            \Log::info('SimpleAuthController: Login with force_token', [
                'force_token' => $forceToken,
                'force_token_value' => $forceTokenValue,
                'redirect_url' => $redirectUrl,
                'request_all' => $request->all(),
                'request_input' => $request->input(),
                'request_query' => $request->query(),
                'has_redirect' => $request->has('redirect'),
                'redirect_input' => $request->input('redirect'),
            ]);
        } else {
            \Log::info('SimpleAuthController: Login without force_token', [
                'request_all' => $request->all(),
                'request_input' => $request->input(),
                'request_query' => $request->query(),
            ]);
        }
        
        // Forcer l'inclusion de avatar_url et last_login_at
        $user->makeVisible(['avatar', 'avatar_url', 'last_login_at', 'is_active']);
        $userData = $user->load('currentSession')->toArray();
        $userData['avatar_url'] = $user->avatar_url;
        
        // S'assurer que last_login_at est bien inclus
        if (!isset($userData['last_login_at'])) {
            $userData['last_login_at'] = $user->last_login_at ? $user->last_login_at->toISOString() : null;
        }
        
        $responseData = [
            'user' => $userData,
            'authenticated' => true,
            'access_token' => $token,
            'token_type' => 'Bearer'
        ];

        // Si une redirection externe est nécessaire, générer le token SSO
        if ($redirectUrl) {
            $ssoToken = $user->createToken('SSO Token', ['profile'])->accessToken;
            
            // Construire l'URL de callback avec le token
            $parsedUrl = parse_url($redirectUrl);
            $queryParams = [];
            
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
            }
            
            $queryParams['token'] = $ssoToken;
            $newQuery = http_build_query($queryParams);
            
            $callbackUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
            if (isset($parsedUrl['port'])) {
                $callbackUrl .= ':' . $parsedUrl['port'];
            }
            if (isset($parsedUrl['path'])) {
                $callbackUrl .= $parsedUrl['path'];
            }
            if ($newQuery) {
                $callbackUrl .= '?' . $newQuery;
            }
            if (isset($parsedUrl['fragment'])) {
                $callbackUrl .= '#' . $parsedUrl['fragment'];
            }
            
            $responseData['sso_redirect_url'] = $callbackUrl;
            $responseData['sso_token'] = $ssoToken;
            
            \Log::info('SimpleAuthController: SSO redirect URL generated', [
                'callback_url' => $callbackUrl,
                'user_id' => $user->id,
            ]);
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

        // Vérifier si on doit rediriger vers un domaine externe après vérification 2FA
        // Pour une requête POST, les paramètres sont dans le body, pas dans la query string
        $forceTokenValue = $request->input('force_token') 
                        ?? $request->get('force_token')
                        ?? $request->query('force_token')
                        ?? ($request->all()['force_token'] ?? null);
        
        $forceToken = in_array($forceTokenValue, [1, '1', true, 'true', 'yes', 'on'], true) 
                   || ($forceTokenValue !== null && $forceTokenValue !== false && $forceTokenValue !== '');
        
        $redirectUrl = null;
        
        // Si force_token est présent, déterminer l'URL de redirection
        if ($forceToken) {
            $redirectUrl = $this->determineRedirectUrl($request);
            
            \Log::info('SimpleAuthController: 2FA verification with force_token', [
                'force_token' => $forceToken,
                'force_token_value' => $forceTokenValue,
                'redirect_url' => $redirectUrl,
                'request_all' => $request->all(),
                'request_input' => $request->input(),
                'request_query' => $request->query(),
            ]);
        }

        // Forcer l'inclusion de avatar_url et last_login_at
        $user->makeVisible(['avatar', 'avatar_url', 'last_login_at', 'is_active']);
        $userData = $user->load('currentSession')->toArray();
        $userData['avatar_url'] = $user->avatar_url;
        
        // S'assurer que last_login_at est bien inclus
        if (!isset($userData['last_login_at'])) {
            $userData['last_login_at'] = $user->last_login_at ? $user->last_login_at->toISOString() : null;
        }
        
        $responseData = [
            'user' => $userData,
            'authenticated' => true,
            'access_token' => $token,
            'token_type' => 'Bearer'
        ];

        // Si une redirection externe est nécessaire, générer le token SSO
        if ($redirectUrl) {
            $ssoToken = $user->createToken('SSO Token', ['profile'])->accessToken;
            
            // Construire l'URL de callback avec le token
            $parsedUrl = parse_url($redirectUrl);
            $queryParams = [];
            
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
            }
            
            $queryParams['token'] = $ssoToken;
            $newQuery = http_build_query($queryParams);
            
            $callbackUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
            if (isset($parsedUrl['port'])) {
                $callbackUrl .= ':' . $parsedUrl['port'];
            }
            if (isset($parsedUrl['path'])) {
                $callbackUrl .= $parsedUrl['path'];
            }
            if ($newQuery) {
                $callbackUrl .= '?' . $newQuery;
            }
            if (isset($parsedUrl['fragment'])) {
                $callbackUrl .= '#' . $parsedUrl['fragment'];
            }
            
            $responseData['sso_redirect_url'] = $callbackUrl;
            $responseData['sso_token'] = $ssoToken;
            
            \Log::info('SimpleAuthController: SSO redirect URL generated after 2FA', [
                'callback_url' => $callbackUrl,
                'user_id' => $user->id,
            ]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Authentification à deux facteurs réussie',
            'data' => $responseData
        ]);
    }

    /**
     * Logout user
     * Déconnecte toutes les sessions et invalide tous les tokens immédiatement
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
                $sessionsCount = $user->sessions()->count();
                $user->sessions()->update([
                    'is_current' => false,
                    'last_activity' => now()
                ]);
                
                \Log::info('SimpleAuthController: User logged out', [
                    'user_id' => $user->id,
                    'sessions_marked_inactive' => $sessionsCount,
                    'tokens_revoked' => $user->tokens()->count(),
                ]);
            } catch (\Exception $e) {
                // En cas d'erreur, essayer de continuer le logout quand même
                \Log::error('SimpleAuthController: Error during logout', [
                    'user_id' => $user ? $user->id : null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie. Toutes les sessions ont été fermées et tous les tokens ont été invalidés.'
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
     * Determine the redirect URL based on the origin of the authentication request
     */
    private function determineRedirectUrl(Request $request): ?string
    {
        $currentHost = $request->getHost();
        $currentHost = preg_replace('/^www\./', '', strtolower($currentHost));
        
        // 1. Priorité : paramètre 'redirect' explicite
        // Pour une requête POST, vérifier dans input() (body) d'abord, puis query()
        $redirect = $request->input('redirect') 
                 ?? $request->get('redirect')
                 ?? $request->query('redirect')
                 ?? ($request->all()['redirect'] ?? null);
        
        if ($redirect) {
            
            // Laravel décode automatiquement les paramètres d'URL, mais on peut avoir un double encodage
            // Essayer de décoder plusieurs fois si nécessaire
            $maxDecodes = 5;
            $decodedRedirect = $redirect;
            for ($i = 0; $i < $maxDecodes; $i++) {
                $testDecode = urldecode($decodedRedirect);
                if ($testDecode === $decodedRedirect) {
                    break; // Plus de décodage possible
                }
                if (filter_var($testDecode, FILTER_VALIDATE_URL)) {
                    $decodedRedirect = $testDecode;
                }
            }
            
            // Utiliser l'URL décodée si elle est valide
            if ($decodedRedirect !== $redirect && filter_var($decodedRedirect, FILTER_VALIDATE_URL)) {
                $redirect = $decodedRedirect;
            }
            
            if ($redirect && filter_var($redirect, FILTER_VALIDATE_URL)) {
                // Vérifier que l'URL ne pointe pas vers le même domaine
                $redirectHost = parse_url($redirect, PHP_URL_HOST);
                if ($redirectHost) {
                    $redirectHost = preg_replace('/^www\./', '', strtolower($redirectHost));
                    if ($redirectHost !== $currentHost && $redirectHost !== 'compte.herime.com') {
                        // Vérifier que l'URL ne contient pas /login
                        if (strpos($redirect, '/login') === false) {
                            return $redirect;
                        }
                    }
                }
            }
        }

        // 2. Paramètre 'client_domain' pour construire l'URL
        if ($request->has('client_domain') || $request->query('client_domain')) {
            $clientDomain = $request->input('client_domain') ?: $request->query('client_domain');
            if ($clientDomain) {
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
            
            if (isset($originUrl['host']) && $originUrl['host'] !== $currentHost) {
                $scheme = $originUrl['scheme'] ?? ($request->secure() ? 'https' : 'http');
                $host = $originUrl['host'];
                $redirectPath = $request->query('redirect_path') ?: '/sso/callback';
                return $scheme . '://' . $host . $redirectPath;
            }
        }

        return null;
    }
}
