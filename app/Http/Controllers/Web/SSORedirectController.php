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
        // DEBUG: Forcer l'exécution du contrôleur - SI VOUS VOYEZ CE MESSAGE, LE CONTRÔLEUR S'EXÉCUTE
        // Envoyons d'abord une réponse HTTP 302 directe pour tester
        // Si cela fonctionne, nous verrons la redirection
        
        \Log::info('SSO Redirect Controller EXECUTING', [
            'url' => $request->fullUrl(),
            'has_token' => $request->has('_token'),
            'session_id' => $request->session()->getId(),
            'method' => $request->method(),
            'path' => $request->path(),
            'all_queries' => $request->query->all(),
        ]);
        
        // PRIORITÉ 1: Récupérer le token depuis le paramètre _token (le plus fiable)
        // L'utilisateur vient de JavaScript avec un token dans localStorage
        $tokenString = $request->query('_token');
        $user = null;
        
        \Log::info('SSO Redirect - Starting', [
            'has_token' => !empty($tokenString),
            'token_length' => $tokenString ? strlen($tokenString) : 0,
        ]);
        
        if ($tokenString) {
            // Trouver l'utilisateur via le token Passport
            $accessToken = $this->findAccessToken($tokenString);
            
            if ($accessToken && $accessToken->user) {
                $user = $accessToken->user;
                \Log::info('SSO Redirect - User found from token', [
                    'user_id' => $user->id,
                ]);
            } else {
                \Log::warning('SSO Redirect - Token not found or invalid');
            }
        }
        
        // PRIORITÉ 2: Si pas de token, essayer la session web
        if (!$user) {
            $user = Auth::guard('web')->user();
            if ($user) {
                \Log::info('SSO Redirect - User found from session', [
                    'user_id' => $user->id,
                ]);
            }
        }
        
        // Si toujours pas d'utilisateur, rediriger vers login AVEC réponse HTTP 302 directe
        if (!$user) {
            \Log::warning('SSO Redirect - No user found, redirecting to login');
            $redirect = $request->query('redirect');
            $redirectParam = $redirect ? '?redirect=' . urlencode($redirect) . '&force_token=1' : '';
            $loginUrl = url('/login' . $redirectParam);
            return response('', 302)->header('Location', $loginUrl);
        }

        $redirectUrl = $request->query('redirect');

        if (!$redirectUrl) {
            $dashboardUrl = url('/dashboard');
            return response('', 302)->header('Location', $dashboardUrl);
        }

        try {
            // Vérifier que l'utilisateur est actif
            if (!$user->isActive()) {
                $dashboardUrl = url('/dashboard');
                return response('', 302)->header('Location', $dashboardUrl);
            }

            // Vérifier que le redirect URL ne pointe pas vers le même domaine
            $redirectHost = parse_url($redirectUrl, PHP_URL_HOST);
            $currentHost = $request->getHost();
            
            if ($redirectHost) {
                $redirectHost = preg_replace('/^www\./', '', strtolower($redirectHost));
                $currentHost = preg_replace('/^www\./', '', strtolower($currentHost));
                
                if ($redirectHost === $currentHost || $redirectHost === 'compte.herime.com') {
                    $dashboardUrl = url('/dashboard');
                    return response('', 302)->header('Location', $dashboardUrl);
                }
            }

            // Créer le token SSO
            $token = $user->createToken('SSO Token', ['profile'])->accessToken;
            
            // Construire l'URL callback avec le token
            $parsedUrl = parse_url($redirectUrl);
            
            if (!$parsedUrl || !isset($parsedUrl['scheme']) || !isset($parsedUrl['host'])) {
                $dashboardUrl = url('/dashboard');
                return response('', 302)->header('Location', $dashboardUrl);
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
                    $dashboardUrl = url('/dashboard');
                    return response('', 302)->header('Location', $dashboardUrl);
                }
            }

            \Log::info('SSO Redirect - Redirecting to callback', [
                'user_id' => $user->id,
                'callback_url' => $callbackUrl,
            ]);

            // Redirection HTTP 302 directe - contourne JavaScript et Vue Router complètement
            // Utiliser une réponse HTTP 302 avec header Location pour forcer la redirection
            return response('', 302)
                ->header('Location', $callbackUrl)
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');

        } catch (\Exception $e) {
            \Log::error('SSO Redirect Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id ?? null,
                'redirect_url' => $redirectUrl ?? null
            ]);

            $dashboardUrl = url('/dashboard');
            return response('', 302)->header('Location', $dashboardUrl);
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

