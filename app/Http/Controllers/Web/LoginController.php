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
                    // Ignorer les erreurs de token
                }
            }
        }

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
                // Vérifier que l'URL de redirection ne pointe pas vers le même domaine
                $redirectHost = parse_url($redirectUrl, PHP_URL_HOST);
                $currentHost = $request->getHost();
                
                // Normaliser les hostnames
                $redirectHost = preg_replace('/^www\./', '', strtolower($redirectHost ?? ''));
                $currentHost = preg_replace('/^www\./', '', strtolower($currentHost));
                
                // Si l'URL de redirection pointe vers le même domaine, ne pas rediriger (éviter boucle)
                if ($redirectHost === $currentHost || $redirectHost === 'compte.herime.com') {
                    // Rediriger vers le dashboard au lieu de créer une boucle
                    return redirect('/dashboard');
                }
                
                // Construire l'URL de callback avec le token
                $parsedUrl = parse_url($redirectUrl);
                $queryParams = [];
                
                if (isset($parsedUrl['query'])) {
                    parse_str($parsedUrl['query'], $queryParams);
                }
                
                $queryParams['token'] = $token;
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
                
                // Redirection HTTP directe - plus fiable qu'une redirection JavaScript
                return redirect($callbackUrl);
            }

            // Sinon, rediriger vers le dashboard local (cas où l'utilisateur se connecte directement)
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
            if ($redirect && filter_var($redirect, FILTER_VALIDATE_URL)) {
                return $redirect;
            }
            
            // Si pas valide, essayer de décoder une fois de plus (cas double encodage)
            $decodedRedirect = urldecode($redirect);
            if ($decodedRedirect !== $redirect && filter_var($decodedRedirect, FILTER_VALIDATE_URL)) {
                return $decodedRedirect;
            }
            
            // Log si aucune URL valide n'a été trouvée
        }

        // 2. Vérifier le paramètre 'client_domain' pour construire l'URL de callback
        if ($request->has('client_domain') || $request->query('client_domain')) {
            $clientDomain = $request->input('client_domain') ?: $request->query('client_domain');
            if ($clientDomain) {
                // Construire l'URL de callback standard pour le domaine client
                $scheme = $request->secure() ? 'https' : 'http';
                $redirectPath = $request->query('redirect_path') ?: '/sso/callback';
                return $scheme . '://' . $clientDomain . $redirectPath;
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
                // Révoquer le token actuel s'il existe (pour les requêtes API)
                try {
                    $authHeader = $request->header('Authorization');
                    if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
                        $token = substr($authHeader, 7);
                        $tokenHash = hash('sha256', $token);
                        $currentToken = \Laravel\Passport\Token::where('id', $tokenHash)
                            ->where('revoked', false)
                            ->first();
                        if ($currentToken) {
                            $currentToken->revoke();
                        }
                    }
                } catch (\Exception $e) {
                    // Si le token actuel n'est pas accessible, continuer quand même
                }
                
                // Révoquer TOUS les tokens Passport de l'utilisateur pour déconnecter tous les sites externes
                // Cette opération invalide immédiatement tous les tokens
                $user->tokens()->update(['revoked' => true]);
                
                // Supprimer TOUTES les sessions de l'utilisateur (déconnecter tous les appareils)
                // On supprime complètement les sessions plutôt que de les marquer comme inactives
                $user->sessions()->delete();
            } catch (\Exception $e) {
                // Ignorer les erreurs lors du logout, mais continuer la déconnexion
            }
        }
        
        // Déconnecter la session web
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
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
                }
            } catch (\Exception $e) {
                // Ignorer les erreurs de parsing d'URL
            }
        }
        
        // Par défaut, rediriger vers la page de login
        return redirect('/login');
    }
}
