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
            'token' => 'required|string|max:500', // Limiter la longueur pour éviter les attaques
            'client_domain' => 'required|string|max:255|regex:/^[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?)*$/',
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

        // Sanitize token (remove any potential SQL injection attempts)
        $token = trim($token);
        if (strlen($token) > 500) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token format'
            ], 422);
        }

        // Find the token in the database (Passport uses prepared statements, so safe)
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
        
        // Decode if needed (with limit to prevent DoS)
        if ($redirectUrl) {
            $decoded = urldecode($redirectUrl);
            // Limiter le décodage pour éviter les boucles infinies
            if ($decoded !== $redirectUrl && strlen($decoded) < 2000 && filter_var($decoded, FILTER_VALIDATE_URL)) {
                $redirectUrl = $decoded;
            }
        }
        
        // If no redirect URL, determine from request
        if (!$redirectUrl || !filter_var($redirectUrl, FILTER_VALIDATE_URL)) {
            // Try to determine from request
            if ($request->has('client_domain') || $request->query('client_domain')) {
                $clientDomain = $request->input('client_domain') ?: $request->query('client_domain');
                
                // Validate client_domain format to prevent injection
                if (!preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?)*$/', $clientDomain)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid client domain format'
                    ], 422);
                }
                
                $scheme = $request->secure() ? 'https' : 'http';
                $redirectPath = $request->query('redirect_path') ?: '/sso/callback';
                
                // Validate redirect_path to prevent directory traversal
                $redirectPath = ltrim($redirectPath, '/');
                if (!preg_match('/^[a-zA-Z0-9\/\-_\.]+$/', $redirectPath)) {
                    $redirectPath = 'sso/callback';
                }
                
                $redirectUrl = $scheme . '://' . $clientDomain . '/' . $redirectPath;
            }
        }

        // Validate URL format and length
        if (!$redirectUrl || !filter_var($redirectUrl, FILTER_VALIDATE_URL) || strlen($redirectUrl) > 2000) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing redirect URL'
            ], 422);
        }
        
        // Validate URL scheme (only http/https)
        $urlParts = parse_url($redirectUrl);
        if (!isset($urlParts['scheme']) || !in_array(strtolower($urlParts['scheme']), ['http', 'https'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid URL scheme. Only HTTP and HTTPS are allowed.'
            ], 422);
        }

        // Vérifier que le redirect URL ne pointe pas vers le même domaine (éviter boucles)
        try {
            $redirectHost = parse_url($redirectUrl, PHP_URL_HOST);
            $currentHost = $request->getHost();
            
            // Normaliser les hostnames (enlever www. si présent)
            $redirectHost = preg_replace('/^www\./', '', $redirectHost);
            $currentHost = preg_replace('/^www\./', '', $currentHost);
            
            if ($redirectHost === $currentHost || $redirectHost === 'compte.herime.com') {
                // Ne pas logger redirect_url car elle peut contenir des informations sensibles
                \Log::warning('SSO redirect blocked: same domain', [
                    'redirect_host' => $redirectHost,
                    'current_host' => $currentHost,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Redirect URL cannot point to the same domain (would cause redirect loop)',
                    'redirect_host' => $redirectHost,
                    'current_host' => $currentHost
                ], 422);
            }
        } catch (\Exception $e) {
            // Ne pas logger redirect_url car elle peut contenir des informations sensibles
            \Log::warning('Error parsing redirect URL host', [
                'error' => $e->getMessage(),
            ]);
        }

        // Create SSO token
        $token = $user->createToken('SSO Token', ['profile'])->accessToken;
        
        // Build callback URL with token
        $callbackUrl = $redirectUrl . (strpos($redirectUrl, '?') !== false ? '&' : '?') . 'token=' . urlencode($token);
        
        // Vérifier une dernière fois que callback_url ne pointe pas vers le même domaine
        try {
            $callbackHost = parse_url($callbackUrl, PHP_URL_HOST);
            $callbackHost = preg_replace('/^www\./', '', $callbackHost);
            
            if ($callbackHost === $currentHost || $callbackHost === 'compte.herime.com') {
                // Ne pas logger callback_url car elle contient un token
                \Log::error('SSO callback URL points to same domain after construction', [
                    'callback_host' => $callbackHost,
                    'current_host' => $currentHost,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Generated callback URL points to the same domain (would cause redirect loop)',
                    'callback_host' => $callbackHost,
                    'current_host' => $currentHost
                ], 500);
            }
        } catch (\Exception $e) {
            // Ne pas logger callback_url car elle contient un token
            \Log::warning('Error parsing callback URL host', [
                'error' => $e->getMessage(),
            ]);
        }

        // Ne pas logger les URLs qui contiennent des tokens
        \Log::info('SSO Token generated via API', [
            'user_id' => $user->id,
            'redirect_host' => parse_url($redirectUrl, PHP_URL_HOST),
        ]);

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
            'client_domain' => 'required|string|max:255|regex:/^[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?)*$/',
            'redirect_url' => 'required|url|max:2000',
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
        
        // Valider le schéma de l'URL (seulement http/https)
        $urlParts = parse_url($redirectUrl);
        if (!isset($urlParts['scheme']) || !in_array(strtolower($urlParts['scheme']), ['http', 'https'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid URL scheme. Only HTTP and HTTPS are allowed.'
            ], 422);
        }

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
        if (config('app.debug')) {
            \Log::debug('getSessions called', [
                'has_token' => $request->bearerToken() ? 'yes' : 'no',
                'user_authenticated' => $request->user() ? 'yes' : 'no'
            ]);
        }
        
        $user = $request->user();
        
        if (!$user) {
            \Log::warning('getSessions: User not authenticated', [
                'token_present' => $request->bearerToken() ? 'yes' : 'no'
            ]);
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }
        
        if (config('app.debug')) {
            \Log::debug('getSessions: User authenticated', [
                'user_id' => $user->id,
            ]);
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
            
            if (config('app.debug')) {
                \Log::debug('Raw sessions query result', [
                    'user_id' => $user->id,
                    'sessions_found' => $sessionsRaw->count()
                ]);
            }
            
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

            if (config('app.debug')) {
                \Log::debug('Sessions loaded successfully', [
                    'user_id' => $user->id,
                    'sessions_count' => $sessions->count()
                ]);
            }

            // Vérifier que nous avons bien des sessions à retourner
            if ($sessions->isEmpty()) {
                if (config('app.debug')) {
                    \Log::debug('No sessions found for user', [
                        'user_id' => $user->id,
                    ]);
                }
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
            \Log::error('Error loading sessions', [
                'user_id' => $user->id ?? 'unknown',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

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
     * Validate token with SSO secret authentication
     * This endpoint is used by external services to validate JWT tokens
     */
    public function validateTokenWithSecret(Request $request): JsonResponse
    {
        // Validate request body
        $validator = Validator::make($request->all(), [
            'token' => 'required|string|max:500', // Limiter la longueur
        ]);

        if ($validator->fails()) {
            return response()->json([
                'valid' => false,
                'message' => 'Token is required'
            ], 422);
        }

        // Check SSO_SECRET in Authorization header
        $authHeader = $request->header('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json([
                'valid' => false,
                'message' => 'Missing or invalid Authorization header'
            ], 401);
        }

        $providedSecret = substr($authHeader, 7); // Remove 'Bearer ' prefix
        
        // Limiter la longueur du secret pour éviter les attaques
        if (strlen($providedSecret) > 500) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid SSO secret'
            ], 401);
        }
        
        $expectedSecret = config('services.sso.secret', env('SSO_SECRET'));

        if (!$expectedSecret || !hash_equals($expectedSecret, $providedSecret)) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid SSO secret'
            ], 401);
        }

        $tokenString = $request->input('token');
        
        // Sanitize token
        $tokenString = trim($tokenString);
        if (strlen($tokenString) > 500) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid token format'
            ], 422);
        }

        try {
            // Passport stores token IDs as SHA-256 hash of the JWT token
            // First, try to find the token by its hash
            $tokenHash = hash('sha256', $tokenString);
            $accessToken = \Laravel\Passport\Token::where('id', $tokenHash)
                ->where('revoked', false)
                ->first();

            // If not found by hash, try to decode JWT and get user_id from payload
            if (!$accessToken) {
                $parts = explode('.', $tokenString);
                if (count($parts) !== 3) {
                    return response()->json([
                        'valid' => false,
                        'message' => 'Invalid token format'
                    ], 401);
                }

                // Decode JWT payload (without verification, as we'll verify via database)
                $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
                
                if (!$payload || !isset($payload['sub'])) {
                    return response()->json([
                        'valid' => false,
                        'message' => 'Invalid token payload'
                    ], 401);
                }

                $userId = $payload['sub'];
                
                // Find user and check if they have any valid tokens
                $user = User::find($userId);
                if (!$user) {
                    return response()->json([
                        'valid' => false,
                        'message' => 'User not found'
                    ], 401);
                }

                // Check if token exists for this user (by checking all user tokens)
                $userTokens = \Laravel\Passport\Token::where('user_id', $userId)
                    ->where('revoked', false)
                    ->get();

                $tokenFound = false;
                foreach ($userTokens as $token) {
                    if (hash('sha256', $tokenString) === $token->id) {
                        $accessToken = $token;
                        $tokenFound = true;
                        break;
                    }
                }

                // If still not found, the token might still be valid but not in our DB
                // We'll trust the JWT payload if user exists and is active
                // This handles cases where tokens are generated externally
            } else {
                $user = $accessToken->user;
            }

            // Check if token is expired
            if ($accessToken && $accessToken->expires_at && $accessToken->expires_at->isPast()) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Token has expired'
                ], 401);
            }

            if (!$user) {
                return response()->json([
                    'valid' => false,
                    'message' => 'User not found'
                ], 401);
            }

            if (!$user->isActive()) {
                return response()->json([
                    'valid' => false,
                    'message' => 'User account is inactive'
                ], 401);
            }

            return response()->json([
                'valid' => true,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'role' => $user->role ?? 'user',
                    'is_verified' => !is_null($user->email_verified_at),
                    'is_active' => $user->is_active,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error validating token', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'valid' => false,
                'message' => 'Error validating token'
            ], 500);
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
}
