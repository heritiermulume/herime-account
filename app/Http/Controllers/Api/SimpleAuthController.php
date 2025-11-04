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
        } catch (\Throwable $e) {
            \Log::warning('Failed to queue new login email', ['error' => $e->getMessage()]);
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
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'Votre compte a été désactivé. Veuillez contacter l\'administrateur.'
            ], 403);
        }

        // Vérifier si la 2FA est activée
        $user->refresh();
        
        \Log::info('Login - 2FA check', [
            'user_id' => $user->id,
            'two_factor_confirmed_at' => $user->two_factor_confirmed_at,
            'is_null' => is_null($user->two_factor_confirmed_at),
            'has_enabled_2fa' => $user->two_factor_confirmed_at !== null
        ]);
        
        if ($user->two_factor_confirmed_at !== null) {
            // La 2FA est activée, on doit demander le code
            // Stocker temporairement l'ID de l'utilisateur en session pour la vérification 2FA
            session(['2fa_login_user_id' => $user->id]);
            
            // Déconnecter l'utilisateur jusqu'à ce que le code 2FA soit vérifié
            Auth::logout();
            
            \Log::info('Login - 2FA required', [
                'user_id' => $user->id,
                'session_stored' => session('2fa_login_user_id')
            ]);
            
            return response()->json([
                'success' => false,
                'requires_two_factor' => true,
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

        // Forcer l'inclusion de avatar_url et last_login_at
        $user->makeVisible(['avatar', 'avatar_url', 'last_login_at', 'is_active']);
        $userData = $user->load('currentSession')->toArray();
        $userData['avatar_url'] = $user->avatar_url;
        
        // S'assurer que last_login_at est bien inclus
        if (!isset($userData['last_login_at'])) {
            $userData['last_login_at'] = $user->last_login_at ? $user->last_login_at->toISOString() : null;
        }
        
        \Log::info('Login response', [
            'user_id' => $user->id,
            'last_login_at' => $user->last_login_at,
            'last_login_at_in_array' => $userData['last_login_at'] ?? 'not set'
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $userData,
                'authenticated' => true,
                'access_token' => $token,
                'token_type' => 'Bearer'
            ]
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
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez entrer un code de 6 chiffres.',
                'errors' => $validator->errors()
            ], 422);
        }

        // Récupérer l'ID de l'utilisateur depuis la session
        $userId = session('2fa_login_user_id');
        
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Session expirée. Veuillez vous reconnecter.'
            ], 401);
        }

        $user = User::find($userId);

        if (!$user || $user->email !== $request->email) {
            session()->forget('2fa_login_user_id');
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé ou email incorrect.'
            ], 401);
        }

        if (!$user->isActive()) {
            session()->forget('2fa_login_user_id');
            return response()->json([
                'success' => false,
                'message' => 'Votre compte a été désactivé. Veuillez contacter l\'administrateur.'
            ], 403);
        }

        // Vérifier le code 2FA
        if (!$user->two_factor_secret) {
            session()->forget('2fa_login_user_id');
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
        session()->forget('2fa_login_user_id');
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

        // Forcer l'inclusion de avatar_url et last_login_at
        $user->makeVisible(['avatar', 'avatar_url', 'last_login_at', 'is_active']);
        $userData = $user->load('currentSession')->toArray();
        $userData['avatar_url'] = $user->avatar_url;
        
        // S'assurer que last_login_at est bien inclus
        if (!isset($userData['last_login_at'])) {
            $userData['last_login_at'] = $user->last_login_at ? $user->last_login_at->toISOString() : null;
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $userData,
                'authenticated' => true,
                'access_token' => $token,
                'token_type' => 'Bearer'
            ]
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Mark current session as inactive
        if ($user && $user->currentSession) {
            $user->currentSession->update(['is_current' => false]);
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
        
        // Forcer l'inclusion de avatar_url et last_login_at
        $user->makeVisible(['avatar', 'avatar_url', 'last_login_at', 'is_active']);
        $userData = $user->load('currentSession')->toArray();
        $userData['avatar_url'] = $user->avatar_url;
        
        // S'assurer que last_login_at est bien formaté
        if ($user->last_login_at) {
            $userData['last_login_at'] = $user->last_login_at->toISOString();
        } else {
            $userData['last_login_at'] = null;
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $userData
            ]
        ]);
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
}
