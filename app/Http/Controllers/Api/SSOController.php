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
        
        $sessions = $user->sessions()
            ->orderBy('last_activity', 'desc')
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'device_name' => $session->device_name ?? 'Unknown Device',
                    'platform' => $session->platform ?? 'Unknown',
                    'browser' => $session->browser ?? 'Unknown',
                    'ip_address' => $session->ip_address,
                    'is_current' => $session->is_current ?? false,
                    'last_activity' => $session->last_activity ? $session->last_activity->toISOString() : null,
                    'created_at' => $session->created_at ? $session->created_at->toISOString() : null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'sessions' => $sessions
            ]
        ]);
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
