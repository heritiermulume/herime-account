<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
        // DEBUG: Vérifier que le contrôleur est bien appelé
        // Si on voit ce message dans les logs, le contrôleur s'exécute
        \Log::info('SSO Redirect Controller called', [
            'url' => $request->fullUrl(),
            'has_token' => $request->has('_token'),
            'session_id' => $request->session()->getId(),
            'method' => $request->method(),
            'path' => $request->path(),
        ]);
        
        // Forcer l'arrêt de toute exécution après redirection
        // Ne pas permettre au template Blade de se charger
        
        // PRIORITÉ 1: Vérifier la session web (l'utilisateur est déjà connecté via la session Laravel)
        // C'est la méthode la plus fiable car l'utilisateur vient de la page de login
        $user = Auth::guard('web')->user();
        
        \Log::info('SSO Redirect - Session check', [
            'user_from_session' => $user ? $user->id : null,
        ]);
        
        // PRIORITÉ 2: Si pas de session, essayer le token Passport depuis le paramètre _token
        if (!$user) {
            $tokenString = $request->query('_token');
            
            if ($tokenString) {
                \Log::info('SSO Redirect - Trying token authentication', [
                    'token_length' => strlen($tokenString),
                ]);
                
                $accessToken = $this->findAccessToken($tokenString);
                
                if ($accessToken && $accessToken->user) {
                    $user = $accessToken->user;
                    \Log::info('SSO Redirect - User found from token', [
                        'user_id' => $user->id,
                    ]);
                    
                    // Connecter l'utilisateur dans la session web pour les prochaines requêtes
                    Auth::guard('web')->login($user);
                } else {
                    \Log::warning('SSO Redirect - Token not found or invalid', [
                        'token_length' => strlen($tokenString),
                    ]);
                }
            }
        }
        
        // PRIORITÉ 3: Essayer Auth::user() (guard par défaut)
        if (!$user) {
            $user = Auth::user();
            \Log::info('SSO Redirect - Default guard check', [
                'user_from_default_guard' => $user ? $user->id : null,
            ]);
        }
        
        // Si toujours pas d'utilisateur, rediriger vers login
        if (!$user) {
            \Log::warning('SSO Redirect - No user found, redirecting to login', [
                'redirect_param' => $request->query('redirect'),
            ]);
            
            // Rediriger vers la page de login avec les paramètres
            // Utiliser redirect()->to() pour forcer une redirection interne
            $redirect = $request->query('redirect');
            $redirectParam = $redirect ? '?redirect=' . urlencode($redirect) . '&force_token=1' : '';
            return redirect()->to('/login' . $redirectParam);
        }

        $redirectUrl = $request->query('redirect');

        if (!$redirectUrl) {
            return redirect()->to('/dashboard')->with('error', 'Redirect URL is required');
        }

        try {
            // Vérifier que l'utilisateur est actif
            if (!$user->isActive()) {
                return redirect()->to('/dashboard')->with('error', 'Your account has been deactivated');
            }

            // Vérifier que le redirect URL ne pointe pas vers le même domaine
            $redirectHost = parse_url($redirectUrl, PHP_URL_HOST);
            $currentHost = $request->getHost();
            
            if ($redirectHost) {
                $redirectHost = preg_replace('/^www\./', '', strtolower($redirectHost));
                $currentHost = preg_replace('/^www\./', '', strtolower($currentHost));
                
                if ($redirectHost === $currentHost || $redirectHost === 'compte.herime.com') {
                    return redirect()->to('/dashboard')->with('error', 'Redirect URL cannot point to the same domain');
                }
            }

            // Créer le token SSO
            $token = $user->createToken('SSO Token', ['profile'])->accessToken;
            
            // Construire l'URL callback avec le token
            $parsedUrl = parse_url($redirectUrl);
            
            if (!$parsedUrl || !isset($parsedUrl['scheme']) || !isset($parsedUrl['host'])) {
                return redirect()->to('/dashboard')->with('error', 'Invalid redirect URL format');
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
                        return redirect()->to('/dashboard')->with('error', 'Generated callback URL points to the same domain');
                    }
            }

            \Log::info('SSO Redirect - Redirecting to callback', [
                'user_id' => $user->id,
                'callback_url' => $callbackUrl,
            ]);

            // Redirection HTTP 302 directe - contourne JavaScript et Vue Router complètement
            // Utiliser redirect()->away() pour forcer une redirection externe
            // Cela empêche Laravel d'interpréter la redirection comme interne
            return redirect()->away($callbackUrl);

        } catch (\Exception $e) {
            \Log::error('SSO Redirect Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id ?? null,
                'redirect_url' => $redirectUrl ?? null
            ]);

            return redirect()->to('/dashboard')->with('error', 'An error occurred during SSO redirect');
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

