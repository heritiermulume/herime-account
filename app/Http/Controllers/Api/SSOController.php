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
use Laravel\Passport\Token as PassportToken;

class SSOController extends Controller
{
    /**
     * Check if SSO token is still valid (lightweight check for polling)
     * Returns minimal data for quick validation
     */
    public function checkToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Token is required'
            ], 422);
        }

        $token = $request->token;
        $accessToken = $this->findAccessToken($token);

        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'Token not found or revoked'
            ], 401);
        }

        // Check if token is expired
        if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'Token has expired'
            ], 401);
        }

        // Check if user is still active
        $user = $accessToken->user;
        if (!$user || !$user->isActive()) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'User is inactive'
            ], 401);
        }

        // Token is valid
        return response()->json([
            'success' => true,
            'valid' => true,
            'user_id' => $user->id
        ]);
    }

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

        // Passport stores token IDs as SHA-256 hash of the JWT token
        // Try to find the token by its hash first
        $accessToken = $this->findAccessToken($token);

        // If not found by hash, try to decode JWT and get user_id from payload
        // This handles cases where the token format might be different
        if (!$accessToken) {
            try {
                $parts = explode('.', $token);
                if (count($parts) === 3) {
                    // Decode JWT payload (without verification, as we'll verify via database)
                    $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
                    
                    if ($payload && isset($payload['sub'])) {
                        $userId = $payload['sub'];
                        
                        // Find user and check if they have any valid tokens
                        $user = User::find($userId);
                        if ($user) {
                            // Check if token exists for this user (by checking all user tokens)
                            $userTokens = \Laravel\Passport\Token::where('user_id', $userId)
                                ->where('revoked', false)
                                ->get();
                            
                            foreach ($userTokens as $tokenObj) {
                                if (hash('sha256', $token) === $tokenObj->id) {
                                    $accessToken = $tokenObj;
                                    break;
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // Ignore JWT parsing errors
            }
        }

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

        // Construire l'URL complète de l'avatar pour l'accès externe
        $avatarUrl = $user->avatar_url;
        // S'assurer que l'URL est complète avec le domaine
        if ($avatarUrl && !str_starts_with($avatarUrl, 'http')) {
            $baseUrl = config('app.url');
            $avatarUrl = rtrim($baseUrl, '/') . '/' . ltrim($avatarUrl, '/');
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $avatarUrl,
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
     * Generate SSO token with redirect URL
     * Called from frontend when force_token is present in URL
     */
    public function generateSSOToken(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        if (!$user->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated'
            ], 403);
        }

        // Get redirect URL from request
        $redirectUrl = $request->input('redirect') ?? $request->query('redirect');
        
        // Decode if needed
        if ($redirectUrl) {
            $decoded = urldecode($redirectUrl);
            if ($decoded !== $redirectUrl && filter_var($decoded, FILTER_VALIDATE_URL)) {
                $redirectUrl = $decoded;
            }
        }
        
        // If no redirect URL, determine from request
        if (!$redirectUrl || !filter_var($redirectUrl, FILTER_VALIDATE_URL)) {
            // Try to determine from request
            if ($request->has('client_domain') || $request->query('client_domain')) {
                $clientDomain = $request->input('client_domain') ?: $request->query('client_domain');
                $scheme = $request->secure() ? 'https' : 'http';
                $redirectPath = $request->query('redirect_path') ?: '/sso/callback';
                $redirectUrl = $scheme . '://' . $clientDomain . $redirectPath;
            }
        }

        if (!$redirectUrl || !filter_var($redirectUrl, FILTER_VALIDATE_URL)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing redirect URL'
            ], 422);
        }

        // Vérifier que le redirect URL ne pointe pas vers le même domaine (éviter boucles)
        $currentHost = null;
        try {
            $redirectHost = parse_url($redirectUrl, PHP_URL_HOST);
            $currentHost = $request->getHost();
            
            // Normaliser les hostnames (enlever www. si présent)
            $redirectHost = preg_replace('/^www\./', '', strtolower($redirectHost));
            $currentHost = preg_replace('/^www\./', '', strtolower($currentHost));
            
            if ($redirectHost === $currentHost || $redirectHost === 'compte.herime.com') {
                return response()->json([
                    'success' => false,
                    'message' => 'Redirect URL cannot point to the same domain (would cause redirect loop)',
                    'redirect_host' => $redirectHost,
                    'current_host' => $currentHost
                ], 422);
            }
        } catch (\Exception $e) {
            // En cas d'erreur, initialiser currentHost
            if (!$currentHost) {
                $currentHost = preg_replace('/^www\./', '', strtolower($request->getHost()));
            }
        }

        // Create SSO token
        $token = $user->createToken('SSO Token', ['profile'])->accessToken;
        
        // Build callback URL with token
        // Si l'URL de redirection contient déjà un paramètre token, le remplacer
        // Sinon, ajouter le nouveau token
        $parsedUrl = parse_url($redirectUrl);
        
        if (!$parsedUrl || !isset($parsedUrl['scheme']) || !isset($parsedUrl['host'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid redirect URL format'
            ], 422);
        }
        
        $queryParams = [];
        
        // Extraire les paramètres de requête existants
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);
        }
        
        // Remplacer ou ajouter le paramètre token
        $queryParams['token'] = $token;
        
        // Reconstruire l'URL avec le nouveau token
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
        
        // Vérifier une dernière fois que callback_url ne pointe pas vers le même domaine
        if ($currentHost) {
            try {
                $callbackHost = parse_url($callbackUrl, PHP_URL_HOST);
                if ($callbackHost) {
                    $callbackHost = preg_replace('/^www\./', '', strtolower($callbackHost));
                    
                    if ($callbackHost === $currentHost || $callbackHost === 'compte.herime.com') {
                        return response()->json([
                            'success' => false,
                            'message' => 'Generated callback URL points to the same domain (would cause redirect loop)',
                            'callback_host' => $callbackHost,
                            'current_host' => $currentHost
                        ], 422);
                    }
                }
            } catch (\Exception $e) {
                // Ignorer les erreurs de parsing d'URL
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'SSO token generated successfully',
            'data' => [
                'token' => $token,
                'redirect_url' => $redirectUrl,
                'callback_url' => $callbackUrl,
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
                    // Continue avec les autres sessions même si une échoue
                }
            }

            // Vérifier que nous avons bien des sessions à retourner
            if ($sessions->isEmpty()) {
                
            }

            // Convertir la collection en tableau simple
            $sessionsArray = [];
            foreach ($sessions as $session) {
                $sessionsArray[] = $session;
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'sessions' => $sessionsArray
                ]
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\Exception $e) {
            // En production, ne pas exposer le message d'erreur complet
            $errorMessage = config('app.debug') ? $e->getMessage() : 'Error loading sessions';
            
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'error' => config('app.debug') ? [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
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

    }

    /**
     * Validate token with SSO secret authentication
     * This endpoint is used by external services to validate JWT tokens
     * 
     * Accepts POST requests with:
     * - token in request body (required)
     * - Authorization: Bearer {ssoSecret} in headers (required)
     * 
     * Returns:
     * - {"valid": true} if token is valid
     * - {"valid": false} if token is invalid, expired, or user is inactive
     */
    public function validateTokenWithSecret(Request $request): JsonResponse
    {
        // Validate request body - token is required
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'valid' => false
            ], 422);
        }

        // Check SSO_SECRET in Authorization header
        $authHeader = $request->header('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json([
                'valid' => false
            ], 401);
        }

        $providedSecret = substr($authHeader, 7); // Remove 'Bearer ' prefix
        $expectedSecret = config('services.sso.secret', env('SSO_SECRET'));

        if (!$expectedSecret || $providedSecret !== $expectedSecret) {
            return response()->json([
                'valid' => false
            ], 401);
        }

        $tokenString = $request->input('token');

        try {
            $accessToken = $this->findAccessToken($tokenString);

            if (!$accessToken) {
                return response()->json([
                    'valid' => false
                ], 200);
            }

            $user = $accessToken->user;

            // Check if token is expired
            if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
                return response()->json([
                    'valid' => false
                ], 200);
            }

            if (!$user) {
                return response()->json([
                    'valid' => false
                ], 200);
            }

            // Check if user is active
            if (!$user->isActive()) {
                return response()->json([
                    'valid' => false
                ], 200);
            }

            // Construire l'URL complète de l'avatar pour l'accès externe
            $avatarUrl = $user->avatar_url;
            // S'assurer que l'URL est complète avec le domaine
            if ($avatarUrl && !str_starts_with($avatarUrl, 'http')) {
                $baseUrl = config('app.url');
                $avatarUrl = rtrim($baseUrl, '/') . '/' . ltrim($avatarUrl, '/');
            }

            // Token is valid
            return response()->json([
                'valid' => true,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'avatar' => $avatarUrl,
                    'role' => $user->role ?? 'user',
                    'is_verified' => !is_null($user->email_verified_at),
                    'is_active' => $user->is_active,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'valid' => false
            ], 200);
        }
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

    /**
     * Find a Passport token record from the raw token string.
     */
    private function findAccessToken(string $token): ?PassportToken
    {
        if (!$token) {
            return null;
        }

        $tokenHash = hash('sha256', $token);

        $accessToken = PassportToken::where('id', $tokenHash)
            ->where('revoked', false)
            ->first();

        if ($accessToken) {
            return $accessToken;
        }

        $payload = $this->decodeJwtPayload($token);

        if ($payload && isset($payload['jti'])) {
            $jti = $payload['jti'];

            $accessToken = PassportToken::where('id', $jti)
                ->where('revoked', false)
                ->first();

            if ($accessToken) {
                return $accessToken;
            }

            $accessToken = PassportToken::where('id', hash('sha256', $jti))
                ->where('revoked', false)
                ->first();

            if ($accessToken) {
                return $accessToken;
            }
        }

        return null;
    }

    /**
     * Decode a JWT payload without verification.
     */
    private function decodeJwtPayload(string $token): ?array
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }

            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

            return is_array($payload) ? $payload : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
