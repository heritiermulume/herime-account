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
        // NOUVEAU SYSTÈME: Vérifier l'authentification depuis le token dans localStorage
        // Puisque l'utilisateur vient de JavaScript, il a un token dans localStorage
        // On doit lire le token depuis l'header Authorization ou le passer en paramètre
        
        $user = null;
        $token = null;
        
        // Méthode 1: Lire le token depuis l'header Authorization (Bearer)
        if ($request->bearerToken()) {
            $token = $request->bearerToken();
        }
        
        // Méthode 2: Lire le token depuis le paramètre _token (si passé depuis JS)
        if (!$token && $request->has('_token')) {
            $token = $request->query('_token');
        }
        
        // Si on a un token, tenter de trouver l'utilisateur
        if ($token) {
            try {
                // Trouver le token dans la base de données Passport
                $accessToken = Token::where('id', hash('sha256', $token))
                    ->where('revoked', false)
                    ->first();
                
                if ($accessToken && $accessToken->user) {
                    $user = $accessToken->user;
                }
            } catch (\Exception $e) {
                // Ignorer les erreurs
            }
        }
        
        // Si toujours pas d'utilisateur, essayer la session web (pour compatibilité)
        if (!$user) {
            $user = Auth::guard('web')->user();
        }
        
        // Si toujours pas d'utilisateur, essayer Auth::user() (guard par défaut)
        if (!$user) {
            $user = Auth::user();
        }
        
        // Si toujours pas d'utilisateur, rediriger vers login
        if (!$user) {
            // Rediriger vers la page de login avec les paramètres
            $redirect = $request->query('redirect');
            $redirectParam = $redirect ? '?redirect=' . urlencode($redirect) . '&force_token=1' : '';
            return redirect('/login' . $redirectParam);
        }

        $redirectUrl = $request->query('redirect');

        if (!$redirectUrl) {
            return redirect('/dashboard')->with('error', 'Redirect URL is required');
        }

        try {
            // Vérifier que l'utilisateur est actif
            if (!$user->isActive()) {
                return redirect('/dashboard')->with('error', 'Your account has been deactivated');
            }

            // Vérifier que le redirect URL ne pointe pas vers le même domaine
            $redirectHost = parse_url($redirectUrl, PHP_URL_HOST);
            $currentHost = $request->getHost();
            
            if ($redirectHost) {
                $redirectHost = preg_replace('/^www\./', '', strtolower($redirectHost));
                $currentHost = preg_replace('/^www\./', '', strtolower($currentHost));
                
                if ($redirectHost === $currentHost || $redirectHost === 'compte.herime.com') {
                    return redirect('/dashboard')->with('error', 'Redirect URL cannot point to the same domain');
                }
            }

            // Créer le token SSO
            $token = $user->createToken('SSO Token', ['profile'])->accessToken;
            
            // Construire l'URL callback avec le token
            $parsedUrl = parse_url($redirectUrl);
            
            if (!$parsedUrl || !isset($parsedUrl['scheme']) || !isset($parsedUrl['host'])) {
                return redirect('/dashboard')->with('error', 'Invalid redirect URL format');
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
                    return redirect('/dashboard')->with('error', 'Generated callback URL points to the same domain');
                }
            }

            // Redirection HTTP 302 directe - contourne JavaScript et Vue Router complètement
            return redirect($callbackUrl);

        } catch (\Exception $e) {
            \Log::error('SSO Redirect Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id ?? null,
                'redirect_url' => $redirectUrl
            ]);

            return redirect('/dashboard')->with('error', 'An error occurred during SSO redirect');
        }
    }
}

