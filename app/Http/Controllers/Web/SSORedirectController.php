<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Token;
use App\Models\User;

class SSORedirectController extends Controller
{
    /**
     * Redirection côté serveur pour SSO
     * Cette méthode génère le token et redirige directement via HTTP 302
     * Cela contourne tous les problèmes de JavaScript/Vue Router
     */
    public function redirect(Request $request)
    {
        try {
            // DEBUG: Forcer l'exécution du contrôleur - SI VOUS VOYEZ CE MESSAGE, LE CONTRÔLEUR S'EXÉCUTE
            // Envoyons d'abord une réponse HTTP 302 directe pour tester
            // Si cela fonctionne, nous verrons la redirection
            
            \Log::info('SSO Redirect Controller EXECUTING', [
                'url' => $request->fullUrl(),
                'has_token' => $request->has('_token'),
                'session_id' => $request->hasSession() ? $request->session()->getId() : 'no-session',
                'method' => $request->method(),
                'path' => $request->path(),
                'all_queries' => $request->query->all(),
            ]);
            
            // PRIORITÉ 1: Récupérer le token depuis le paramètre _token (le plus fiable)
            // L'utilisateur vient de JavaScript avec un token dans localStorage
            $tokenString = $request->query('_token');
            
            // Décoder le token si nécessaire (il peut être encodé dans l'URL)
            if ($tokenString) {
                $decodedToken = urldecode($tokenString);
                if ($decodedToken !== $tokenString && filter_var($decodedToken, FILTER_VALIDATE_URL) === false) {
                    $tokenString = $decodedToken;
                }
            }
            
            $user = null;
            
            \Log::info('SSO Redirect - Starting', [
                'has_token' => !empty($tokenString),
                'token_length' => $tokenString ? strlen($tokenString) : 0,
                'token_preview' => $tokenString ? substr($tokenString, 0, 50) . '...' : null,
            ]);
            
            if ($tokenString) {
                // Trouver l'utilisateur via le token Passport
                try {
                    $accessToken = $this->findAccessToken($tokenString);
                    
                    if ($accessToken && $accessToken->user) {
                        // Vérifier que le token n'est pas expiré
                        if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
                            \Log::warning('SSO Redirect - Token expired', [
                                'token_id' => $accessToken->id,
                                'expires_at' => $accessToken->expires_at,
                            ]);
                        } else {
                            $user = $accessToken->user;
                            \Log::info('SSO Redirect - User found from token', [
                                'user_id' => $user->id,
                                'token_id' => $accessToken->id,
                            ]);
                        }
                    } else {
                        \Log::warning('SSO Redirect - Token not found or invalid', [
                            'token_preview' => substr($tokenString, 0, 50) . '...',
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::error('SSO Redirect - Error finding access token', [
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]);
                    // Continuer sans utilisateur, on essaiera la session
                }
            }
            
            // PRIORITÉ 2: Si pas de token trouvé, essayer la session web
            if (!$user) {
                try {
                    $user = Auth::guard('web')->user();
                    if ($user) {
                        \Log::info('SSO Redirect - User found from session', [
                            'user_id' => $user->id,
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::error('SSO Redirect - Error getting user from session', [
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]);
                    // Continuer sans utilisateur
                }
            }
            
            // PRIORITÉ 3: Si toujours pas d'utilisateur, essayer de décoder le JWT pour obtenir l'user_id
            // et créer une session web temporaire
            if (!$user && $tokenString) {
                try {
                    $payload = $this->decodeJwtPayload($tokenString);
                    if ($payload && isset($payload['sub'])) {
                        $userId = $payload['sub'];
                        $user = User::find($userId);
                        if ($user && $user->isActive()) {
                            // Créer une session web temporaire pour cet utilisateur
                            Auth::guard('web')->login($user);
                            \Log::info('SSO Redirect - User found from JWT payload and session created', [
                                'user_id' => $user->id,
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error('SSO Redirect - Error creating session from JWT', [
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]);
                }
            }
            
            // Si toujours pas d'utilisateur, rediriger vers dashboard pour éviter la boucle
            if (!$user) {
                \Log::warning('SSO Redirect - No user found, redirecting to dashboard to avoid loop', [
                    'has_token' => !empty($tokenString),
                    'has_session' => $request->hasSession(),
                ]);
                
                // Nettoyer la session
                $request->session()->forget('sso_redirect_attempts');
                
                // TOUJOURS rediriger vers dashboard au lieu de login pour éviter la boucle
                // Ne JAMAIS rediriger vers /login car cela crée une boucle infinie
                return response('', 302)->header('Location', url('/dashboard'));
            }
            
            // Si on a trouvé un utilisateur, nettoyer le compteur de tentatives
            $request->session()->forget('sso_redirect_attempts');

            $redirectUrl = $request->query('redirect');

            if (!$redirectUrl) {
                return response('', 302)->header('Location', url('/dashboard'));
            }

            // Vérifier que l'utilisateur est actif
            try {
                if (!$user->isActive()) {
                    return response('', 302)->header('Location', url('/dashboard'));
                }
            } catch (\Exception $e) {
                \Log::error('SSO Redirect - Error checking user active status', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id ?? null,
                ]);
                // En cas d'erreur, rediriger vers le dashboard
                return response('', 302)->header('Location', url('/dashboard'));
            }

            // Vérifier que le redirect URL ne pointe pas vers le même domaine
            $redirectHost = parse_url($redirectUrl, PHP_URL_HOST);
            $currentHost = $request->getHost();
            
            if ($redirectHost) {
                $redirectHost = preg_replace('/^www\./', '', strtolower($redirectHost));
                $currentHost = preg_replace('/^www\./', '', strtolower($currentHost));
                
                if ($redirectHost === $currentHost || $redirectHost === 'compte.herime.com') {
                    return response('', 302)->header('Location', url('/dashboard'));
                }
            }

            // Créer le token SSO
            try {
                $token = $user->createToken('SSO Token', ['profile'])->accessToken;
                
                // Vérifier que le token est bien créé et peut être trouvé
                $tokenHash = hash('sha256', $token);
                $createdToken = Token::where('id', $tokenHash)->first();
                
                \Log::info('SSO Redirect - Token created', [
                    'user_id' => $user->id,
                    'token_hash' => substr($tokenHash, 0, 32) . '...',
                    'token_found_in_db' => $createdToken ? true : false,
                    'token_length' => strlen($token),
                ]);
                
                if (!$createdToken) {
                    \Log::warning('SSO Redirect - Token created but not found in database immediately', [
                        'user_id' => $user->id,
                        'token_hash' => substr($tokenHash, 0, 32) . '...',
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('SSO Redirect - Error creating token', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                // En cas d'erreur lors de la création du token, rediriger vers le dashboard
                return response('', 302)->header('Location', url('/dashboard'));
            }
            
            // Construire l'URL callback avec le token
            $parsedUrl = parse_url($redirectUrl);
            
            if (!$parsedUrl || !isset($parsedUrl['scheme']) || !isset($parsedUrl['host'])) {
                return response('', 302)->header('Location', url('/dashboard'));
            }
            
            $queryParams = [];
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
            }
            $queryParams['token'] = $token;
            
            $callbackUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
            if (isset($parsedUrl['port'])) {
                $callbackUrl .= ':' . $parsedUrl['port'];
            }
            if (isset($parsedUrl['path'])) {
                $callbackUrl .= $parsedUrl['path'];
            }
            if (!empty($queryParams)) {
                $callbackUrl .= '?' . http_build_query($queryParams);
            }
            if (isset($parsedUrl['fragment'])) {
                $callbackUrl .= '#' . $parsedUrl['fragment'];
            }

            // Vérifier une dernière fois que callback_url ne pointe pas vers le même domaine
            $callbackHost = parse_url($callbackUrl, PHP_URL_HOST);
            if ($callbackHost) {
                $callbackHost = preg_replace('/^www\./', '', strtolower($callbackHost));
                if ($callbackHost === $currentHost || $callbackHost === 'compte.herime.com') {
                    return response('', 302)->header('Location', url('/dashboard'));
                }
            }

            \Log::info('SSO Redirect - Redirecting to callback', [
                'user_id' => $user->id,
                'callback_url' => $callbackUrl,
            ]);

            // Redirection HTTP 302 directe - utiliser une réponse HTTP brute
            // pour éviter que le template Blade ne se charge
            return response('', 302)
                ->header('Location', $callbackUrl)
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');

        } catch (\Exception $e) {
            \Log::error('SSO Redirect Error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            // En cas d'erreur, rediriger vers le dashboard
            return response('', 302)->header('Location', url('/dashboard'));
        }
    }
    
    /**
     * Trouver un token Passport depuis un JWT
     * Utilise la même logique que SSOController::findAccessToken
     */
    private function findAccessToken(string $token): ?Token
    {
        if (!$token) {
            return null;
        }

        // Méthode 1: Hash SHA-256 du token complet
        $tokenHash = hash('sha256', $token);
        $accessToken = Token::where('id', $tokenHash)
            ->where('revoked', false)
            ->first();

        if ($accessToken) {
            return $accessToken;
        }

        // Méthode 2: Décoder le JWT et utiliser le jti
        $payload = $this->decodeJwtPayload($token);

        if ($payload && isset($payload['jti'])) {
            $jti = $payload['jti'];

            // Essayer avec le jti directement
            $accessToken = Token::where('id', $jti)
                ->where('revoked', false)
                ->first();

            if ($accessToken) {
                return $accessToken;
            }

            // Essayer avec le hash du jti
            $accessToken = Token::where('id', hash('sha256', $jti))
                ->where('revoked', false)
                ->first();

            if ($accessToken) {
                return $accessToken;
            }
        }

        return null;
    }

    /**
     * Décoder le payload d'un JWT sans vérification
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

