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
        // PROTECTION CONTRE LES BOUCLES DE REDIRECTION
        // Vérifier si on a déjà traité cette requête récemment
        $sessionKey = 'sso_redirect_attempt_' . md5($request->fullUrl());
        $redirectAttempts = $request->session()->get($sessionKey, 0);
        
        if ($redirectAttempts >= 2) {
            // Trop de tentatives, arrêter la boucle
            $request->session()->forget($sessionKey);
            return redirect('/dashboard');
        }
        
        // Détection robuste de force_token
        $forceToken = false;
        if ($request->has('force_token')) {
            $forceTokenValue = $request->input('force_token') ?? $request->query('force_token');
            $forceToken = in_array($forceTokenValue, [1, '1', true, 'true', 'yes', 'on'], true) 
                       || $request->boolean('force_token', false);
        }

        // Vérifier l'authentification
        $isAuthenticated = Auth::check();
        $user = null;
        
        if ($isAuthenticated) {
            $user = Auth::user();
        }

        // Si l'utilisateur est déjà connecté ET force_token est présent
        if ($isAuthenticated && $user && $forceToken) {
            if (!$user->isActive()) {
                return view('welcome', [
                    'error' => 'Votre compte a été désactivé. Veuillez contacter l\'administrateur.'
                ]);
            }

            // Récupérer l'URL de redirection APRÈS avoir vérifié l'authentification
            $redirectUrl = $this->determineRedirectUrl($request);
            
            // Vérifier que l'URL de redirection est valide et externe
            if (!$redirectUrl) {
                \Log::warning('SSO Redirect: No redirect URL found', [
                    'url' => $request->fullUrl(),
                    'query' => $request->query(),
                ]);
                return redirect('/dashboard');
            }
            
            // Vérifier que l'URL ne pointe pas vers le même domaine
            $redirectHost = parse_url($redirectUrl, PHP_URL_HOST);
            $currentHost = $request->getHost();
            
            $redirectHost = preg_replace('/^www\./', '', strtolower($redirectHost ?? ''));
            $currentHost = preg_replace('/^www\./', '', strtolower($currentHost));
            
            // Protection contre les boucles : ne pas rediriger vers le même domaine
            if ($redirectHost === $currentHost || $redirectHost === 'compte.herime.com') {
                return redirect('/dashboard');
            }
            
            // Vérifier que l'URL de redirection ne contient pas déjà /login
            if (strpos($redirectUrl, '/login') !== false) {
                return redirect('/dashboard');
            }

            // Incrémenter le compteur de tentatives
            $request->session()->put($sessionKey, $redirectAttempts + 1);

            // Générer le token SSO
            $token = $this->generateSSOToken($user);

            // Construire l'URL de callback avec le token
            $parsedUrl = parse_url($redirectUrl);
            if (!$parsedUrl || !isset($parsedUrl['scheme']) || !isset($parsedUrl['host'])) {
                return redirect('/dashboard');
            }
            
            $queryParams = [];
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
            }
            
            // Ajouter le token (remplacer s'il existe déjà)
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
            
            // Vérifier une dernière fois que l'URL de callback ne pointe pas vers le même domaine
            $callbackHost = parse_url($callbackUrl, PHP_URL_HOST);
            if ($callbackHost) {
                $callbackHost = preg_replace('/^www\./', '', strtolower($callbackHost));
                if ($callbackHost === $currentHost || $callbackHost === 'compte.herime.com') {
                    return redirect('/dashboard');
                }
            }
            
            // Redirection HTTP directe
            return redirect($callbackUrl);
        }

        // Si l'utilisateur est déjà connecté SANS force_token, rediriger vers le dashboard
        if ($isAuthenticated && $user && !$forceToken) {
            return redirect('/dashboard');
        }

        // Si l'utilisateur n'est pas connecté, afficher la page de login Vue.js
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
        $currentHost = $request->getHost();
        $currentHost = preg_replace('/^www\./', '', strtolower($currentHost));
        
        // 1. Priorité : paramètre 'redirect' explicite
        if ($request->has('redirect') || $request->query('redirect')) {
            $redirect = $request->input('redirect') ?: $request->query('redirect');
            
            // Laravel décode automatiquement les paramètres d'URL, mais on peut avoir un double encodage
            // Essayer de décoder plusieurs fois si nécessaire
            $maxDecodes = 3;
            $decodedRedirect = $redirect;
            for ($i = 0; $i < $maxDecodes; $i++) {
                $testDecode = urldecode($decodedRedirect);
                if ($testDecode === $decodedRedirect) {
                    break; // Plus de décodage possible
                }
                if (filter_var($testDecode, FILTER_VALIDATE_URL)) {
                    $decodedRedirect = $testDecode;
                }
            }
            
            // Utiliser l'URL décodée si elle est valide
            if ($decodedRedirect !== $redirect && filter_var($decodedRedirect, FILTER_VALIDATE_URL)) {
                $redirect = $decodedRedirect;
            }
            
            if ($redirect && filter_var($redirect, FILTER_VALIDATE_URL)) {
                // Vérifier que l'URL ne pointe pas vers le même domaine
                $redirectHost = parse_url($redirect, PHP_URL_HOST);
                if ($redirectHost) {
                    $redirectHost = preg_replace('/^www\./', '', strtolower($redirectHost));
                    if ($redirectHost !== $currentHost && $redirectHost !== 'compte.herime.com') {
                        // Vérifier que l'URL ne contient pas /login
                        if (strpos($redirect, '/login') === false) {
                            \Log::info('SSO Redirect: Using redirect URL', [
                                'url' => $redirect,
                                'host' => $redirectHost,
                            ]);
                            return $redirect;
                        }
                    }
                }
            }
        }

        // 2. Paramètre 'client_domain'
        if ($request->has('client_domain') || $request->query('client_domain')) {
            $clientDomain = $request->input('client_domain') ?: $request->query('client_domain');
            if ($clientDomain) {
                $clientDomain = preg_replace('/^www\./', '', strtolower($clientDomain));
                if ($clientDomain !== $currentHost && $clientDomain !== 'compte.herime.com') {
                    $scheme = $request->secure() ? 'https' : 'http';
                    $redirectPath = $request->query('redirect_path') ?: '/sso/callback';
                    return $scheme . '://' . $clientDomain . $redirectPath;
                }
            }
        }

        // 3. Header Referer
        $referer = $request->header('Referer');
        if ($referer) {
            $refererUrl = parse_url($referer);
            if (isset($refererUrl['host'])) {
                $refererHost = preg_replace('/^www\./', '', strtolower($refererUrl['host']));
                if ($refererHost !== $currentHost && $refererHost !== 'compte.herime.com') {
                    $scheme = $refererUrl['scheme'] ?? ($request->secure() ? 'https' : 'http');
                    $redirectPath = $request->query('redirect_path') ?: '/sso/callback';
                    return $scheme . '://' . $refererUrl['host'] . $redirectPath;
                }
            }
        }

        // 4. Header Origin
        $origin = $request->header('Origin');
        if ($origin) {
            $originUrl = parse_url($origin);
            if (isset($originUrl['host'])) {
                $originHost = preg_replace('/^www\./', '', strtolower($originUrl['host']));
                if ($originHost !== $currentHost && $originHost !== 'compte.herime.com') {
                    $scheme = $originUrl['scheme'] ?? ($request->secure() ? 'https' : 'http');
                    $redirectPath = $request->query('redirect_path') ?: '/sso/callback';
                    return $scheme . '://' . $originUrl['host'] . $redirectPath;
                }
            }
        }

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
