<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    /**
     * Handle login page with SSO token generation if needed
     */
    public function show(Request $request)
    {
        $redirectUrl = $this->determineRedirectUrl($request);
        
        // Détection robuste de force_token (vérifier plusieurs façons)
        $forceToken = false;
        if ($request->has('force_token')) {
            $forceTokenValue = $request->input('force_token') ?? $request->query('force_token');
            $forceToken = in_array($forceTokenValue, [1, '1', true, 'true', 'yes', 'on'], true) 
                       || $request->boolean('force_token', false);
        }

        // Vérifier l'authentification: d'abord session web, puis token API
        $isAuthenticated = Auth::check();
        $user = null;
        
        if ($isAuthenticated) {
            $user = Auth::user();
        } else {
            // Essayer avec le token API si présent dans le header Authorization
            $authHeader = $request->header('Authorization');
            if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
                $token = substr($authHeader, 7);
                try {
                    // Vérifier le token via Passport
                    $accessToken = \Laravel\Passport\Token::where('id', hash('sha256', $token))
                        ->where('revoked', false)
                        ->first();
                    
                    if ($accessToken && $accessToken->expires_at && !$accessToken->expires_at->isPast()) {
                        $user = $accessToken->user;
                        if ($user && $user->isActive()) {
                            // Connecter l'utilisateur via session web pour la suite
                            Auth::login($user);
                            $isAuthenticated = true;
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning('Error checking API token in LoginController', ['error' => $e->getMessage()]);
                }
            }
        }

        \Log::info('LoginController@show', [
            'auth_check' => $isAuthenticated,
            'auth_check_web' => Auth::check(),
            'user_id' => $user?->id,
            'force_token' => $forceToken,
            'redirect_url' => $redirectUrl,
            'query_params' => $request->all()
        ]);

        // Si l'utilisateur est déjà connecté ET force_token est présent
        if ($isAuthenticated && $user && $forceToken) {
            // Générer un token SSO et rediriger immédiatement
            // $user est déjà récupéré ci-dessus

            if (!$user->isActive()) {
                // Si le compte est désactivé, rediriger vers la page de login avec erreur
                return view('welcome', [
                    'error' => 'Votre compte a été désactivé. Veuillez contacter l\'administrateur.'
                ]);
            }

            $token = $this->generateSSOToken($user);

            // Si une URL de redirection a été détectée, rediriger directement via HTTP
            if ($redirectUrl) {
                $callbackUrl = $redirectUrl . (strpos($redirectUrl, '?') !== false ? '&' : '?') . 'token=' . urlencode($token);
                \Log::info('SSO Redirect with token', [
                    'redirect_url' => $callbackUrl,
                    'user_id' => $user->id,
                    'token_length' => strlen($token)
                ]);
                
                // Redirection HTTP directe - plus fiable qu'une redirection JavaScript
                return redirect($callbackUrl);
            }

            // Sinon, rediriger vers le dashboard local (cas où l'utilisateur se connecte directement)
            \Log::warning('SSO force_token requested but no redirect URL found', [
                'user_id' => $user->id,
                'query_params' => $request->all()
            ]);
            return redirect('/dashboard');
        }

        // Si l'utilisateur est déjà connecté SANS force_token, rediriger vers le dashboard
        if ($isAuthenticated && $user && !$forceToken) {
            // Si une URL de redirection externe est présente, mais sans force_token, rediriger vers le dashboard local
            return redirect('/dashboard');
        }

        // Si l'utilisateur n'est pas connecté, afficher la page de login Vue.js
        // L'application Vue.js gérera le reste (elle récupérera les paramètres redirect et force_token)
        return view('welcome');
    }

    /**
     * Generate SSO token for user using Passport
     */
    private function generateSSOToken(User $user): string
    {
        // Créer un token Passport pour le SSO
        $token = $user->createToken('SSO Token', ['profile'])->accessToken;
        return $token;
    }

    /**
     * Determine the redirect URL based on the origin of the authentication request
     */
    private function determineRedirectUrl(Request $request): ?string
    {
        // 1. Priorité : paramètre 'redirect' explicite dans la requête
        if ($request->has('redirect') || $request->query('redirect')) {
            $redirect = $request->input('redirect') ?: $request->query('redirect');
            
            // Laravel décode automatiquement les paramètres d'URL une fois
            // Vérifier si l'URL est valide telle quelle
            if ($redirect && strlen($redirect) <= 2000 && filter_var($redirect, FILTER_VALIDATE_URL)) {
                // Valider le schéma (seulement http/https)
                $urlParts = parse_url($redirect);
                if (isset($urlParts['scheme']) && in_array(strtolower($urlParts['scheme']), ['http', 'https'])) {
                    return $redirect;
                }
            }
            
            // Si pas valide, essayer de décoder une fois de plus (cas double encodage)
            $decodedRedirect = urldecode($redirect);
            if ($decodedRedirect !== $redirect && strlen($decodedRedirect) <= 2000 && filter_var($decodedRedirect, FILTER_VALIDATE_URL)) {
                // Valider le schéma
                $urlParts = parse_url($decodedRedirect);
                if (isset($urlParts['scheme']) && in_array(strtolower($urlParts['scheme']), ['http', 'https'])) {
                    return $decodedRedirect;
                }
            }
            
            // Ne pas logger les URLs invalides car elles peuvent contenir des données sensibles
        }

        // 2. Vérifier le paramètre 'client_domain' pour construire l'URL de callback
        if ($request->has('client_domain') || $request->query('client_domain')) {
            $clientDomain = $request->input('client_domain') ?: $request->query('client_domain');
            
            // Valider le format du domaine pour éviter les injections
            if ($clientDomain && preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?)*$/', $clientDomain)) {
                // Construire l'URL de callback standard pour le domaine client
                $scheme = $request->secure() ? 'https' : 'http';
                $redirectPath = $request->query('redirect_path') ?: '/sso/callback';
                
                // Valider redirect_path pour éviter les directory traversal
                $redirectPath = ltrim($redirectPath, '/');
                if (!preg_match('/^[a-zA-Z0-9\/\-_\.]+$/', $redirectPath)) {
                    $redirectPath = 'sso/callback';
                }
                
                return $scheme . '://' . $clientDomain . '/' . $redirectPath;
            }
        }

        // 3. Détecter depuis le header Referer
        $referer = $request->header('Referer');
        if ($referer) {
            $refererUrl = parse_url($referer);
            $currentHost = parse_url(config('app.url'), PHP_URL_HOST);
            
            // Si le referer vient d'un autre domaine que compte.herime.com
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
            
            // Si l'origin vient d'un autre domaine que compte.herime.com
            if (isset($originUrl['host']) && $originUrl['host'] !== $currentHost) {
                $scheme = $originUrl['scheme'] ?? ($request->secure() ? 'https' : 'http');
                $host = $originUrl['host'];
                $redirectPath = $request->query('redirect_path') ?: '/sso/callback';
                return $scheme . '://' . $host . $redirectPath;
            }
        }

        // 5. Si aucune origine externe détectée, retourner null pour rediriger vers le dashboard local
        return null;
    }

    /**
     * Handle logout with redirect parameter support
     */
    public function logout(Request $request)
    {
        $user = Auth::user();
        
        // Déterminer l'URL de redirection
        $redirectUrl = $this->determineRedirectUrl($request);
        
        // Si l'utilisateur est connecté, effectuer le logout
        if ($user) {
            try {
                // Marquer la session comme inactive
                if ($user->currentSession) {
                    $user->currentSession->update(['is_current' => false]);
                }
                
                // Révoquer tous les tokens Passport de l'utilisateur
                $user->tokens()->update(['revoked' => true]);
                
                \Log::info('User tokens revoked', ['user_id' => $user->id]);
            } catch (\Exception $e) {
                \Log::error('Error during logout', ['error' => $e->getMessage(), 'user_id' => $user->id]);
            }
        }
        
        // Déconnecter la session web
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        \Log::info('Logout completed', [
            'user_id' => $user?->id,
            'redirect_url' => $redirectUrl
        ]);
        
        // Si une URL de redirection est spécifiée et valide, rediriger vers celle-ci
        if ($redirectUrl && filter_var($redirectUrl, FILTER_VALIDATE_URL)) {
            // Vérifier que l'URL ne pointe pas vers le même domaine (éviter les boucles)
            try {
                $redirectHost = parse_url($redirectUrl, PHP_URL_HOST);
                $currentHost = $request->getHost();
                
                $redirectHost = preg_replace('/^www\./', '', $redirectHost);
                $currentHost = preg_replace('/^www\./', '', $currentHost);
                
                if ($redirectHost !== $currentHost && $redirectHost !== 'compte.herime.com') {
                    return redirect($redirectUrl);
                } else {
                    \Log::warning('Logout redirect blocked: same domain', [
                        'redirect_host' => $redirectHost,
                        'current_host' => $currentHost,
                        'redirect_url' => $redirectUrl
                    ]);
                }
            } catch (\Exception $e) {
                \Log::warning('Error parsing redirect URL during logout', [
                    'error' => $e->getMessage(),
                    'redirect_url' => $redirectUrl
                ]);
            }
        }
        
        // Par défaut, rediriger vers la page de login
        return redirect('/login');
    }
}
