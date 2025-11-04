<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SSOController extends Controller
{
    /**
     * Validate SSO token and return user info
     */
    public function validateToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'client_domain' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $token = $request->token;
        $clientDomain = $request->client_domain;

        // Find the token in the database
        $accessToken = \Laravel\Passport\Token::where('id', $token)
            ->where('revoked', false)
            ->first();

        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token'
            ], 401);
        }

        // Check if token is expired
        if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Token has expired'
            ], 401);
        }

        $user = $accessToken->user;

        if (!$user || !$user->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'User not found or inactive'
            ], 401);
        }

        // Log the SSO access
        $this->logSSOAccess($user, $clientDomain, $request);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar_url,
                    'phone' => $user->phone,
                    'company' => $user->company,
                    'position' => $user->position,
                    'last_login_at' => $user->last_login_at,
                ],
                'session' => $user->currentSession,
                'permissions' => $accessToken->scopes,
            ]
        ]);
    }

    /**
     * Create SSO session for client domain
     */
    public function createSession(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_domain' => 'required|string',
            'redirect_url' => 'required|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $clientDomain = $request->client_domain;
        $redirectUrl = $request->redirect_url;

        // Create a new token for the client domain
        $token = $user->createToken("SSO for {$clientDomain}", ['profile'])->accessToken;

        // Generate SSO URL
        $ssoUrl = $this->generateSSOUrl($clientDomain, $token, $redirectUrl);

        return response()->json([
            'success' => true,
            'data' => [
                'sso_url' => $ssoUrl,
                'token' => $token,
                'expires_in' => 3600, // 1 hour
            ]
        ]);
    }

    /**
     * Get user sessions
     */
    public function getSessions(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }
        
        try {
            // Vérifier que l'utilisateur a bien un ID
            if (!$user->id) {
                \Log::error('User has no ID', ['user' => $user]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid user'
                ], 401);
            }

            // Récupérer les sessions avec une requête simple
            $sessionsQuery = $user->sessions()
                ->orderBy('last_activity', 'desc')
                ->orderBy('created_at', 'desc');
            
            $sessionsRaw = $sessionsQuery->get();
            
            \Log::info('Raw sessions query result', [
                'user_id' => $user->id,
                'sessions_found' => $sessionsRaw->count(),
                'first_session_id' => $sessionsRaw->first()?->id
            ]);
            
            $sessions = collect([]);
            
            foreach ($sessionsRaw as $session) {
                try {
                    // Convertir les dates en format ISO 8601 de manière sécurisée
                    $lastActivity = null;
                    if ($session->last_activity) {
                        if (is_object($session->last_activity) && method_exists($session->last_activity, 'format')) {
                            $lastActivity = $session->last_activity->format('c');
                        } else {
                            $lastActivity = (string)$session->last_activity;
                        }
                    } elseif ($session->created_at) {
                        if (is_object($session->created_at) && method_exists($session->created_at, 'format')) {
                            $lastActivity = $session->created_at->format('c');
                        } else {
                            $lastActivity = (string)$session->created_at;
                        }
                    }
                    
                    $createdAt = null;
                    if ($session->created_at) {
                        if (is_object($session->created_at) && method_exists($session->created_at, 'format')) {
                            $createdAt = $session->created_at->format('c');
                        } else {
                            $createdAt = (string)$session->created_at;
                        }
                    }
                    
                    $sessions->push([
                        'id' => (int)($session->id ?? 0),
                        'device_name' => (string)($session->device_name ?? 'Unknown Device'),
                        'platform' => (string)($session->platform ?? 'Unknown'),
                        'browser' => (string)($session->browser ?? 'Unknown'),
                        'ip_address' => (string)($session->ip_address ?? 'Unknown'),
                        'is_current' => (bool)($session->is_current ?? false),
                        'last_activity' => $lastActivity,
                        'created_at' => $createdAt,
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Error mapping session', [
                        'session_id' => $session->id ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => substr($e->getTraceAsString(), 0, 500)
                    ]);
                    // Continue avec les autres sessions même si une échoue
                }
            }

            \Log::info('Sessions loaded', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'sessions_count' => $sessions->count(),
                'total_sessions_in_db' => UserSession::count(),
                'sessions_with_user_id' => UserSession::where('user_id', $user->id)->count()
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'sessions' => $sessions
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error loading sessions', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error loading sessions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Revoke a specific session
     */
    public function revokeSession(Request $request, $sessionId): JsonResponse
    {
        $user = $request->user();
        
        $session = $user->sessions()->find($sessionId);
        
        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found'
            ], 404);
        }

        $session->update(['is_current' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Session revoked successfully'
        ]);
    }

    /**
     * Revoke all sessions except current
     */
    public function revokeAllSessions(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $user->sessions()
            ->where('is_current', false)
            ->update(['is_current' => false]);

        return response()->json([
            'success' => true,
            'message' => 'All other sessions revoked successfully'
        ]);
    }

    /**
     * Log SSO access
     */
    private function logSSOAccess(User $user, string $clientDomain, Request $request): void
    {
        // Update last activity for current session
        if ($user->currentSession) {
            $user->currentSession->update([
                'last_activity' => now(),
            ]);
        }

        // Log the access (you can create a separate table for this if needed)
        \Log::info('SSO Access', [
            'user_id' => $user->id,
            'client_domain' => $clientDomain,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Generate SSO URL for client domain
     */
    private function generateSSOUrl(string $clientDomain, string $token, string $redirectUrl): string
    {
        $params = http_build_query([
            'token' => $token,
            'redirect' => $redirectUrl,
            'timestamp' => time(),
        ]);

        return "https://{$clientDomain}/sso/callback?{$params}";
    }
}
