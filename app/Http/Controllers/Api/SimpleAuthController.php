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

class SimpleAuthController extends Controller
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

        // Log the user in
        Auth::login($user);

        // Create user session
        $this->createUserSession($user, $request);

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
        // Mark all previous sessions as inactive
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
