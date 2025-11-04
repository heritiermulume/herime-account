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
        $forceToken = $request->boolean('force_token', false);

        // Si l'utilisateur est déjà connecté ET force_token est présent
        if (Auth::check() && $forceToken) {
            // Générer un token SSO et rediriger immédiatement
            $user = Auth::user();

            if (!$user->isActive()) {
                // Si le compte est désactivé, rediriger vers la page de login avec erreur
                return view('welcome', [
                    'error' => 'Votre compte a été désactivé. Veuillez contacter l\'administrateur.'
                ]);
            }

            $token = $this->generateSSOToken($user);

            // Si une URL de redirection a été détectée, rediriger vers le domaine externe
            if ($redirectUrl) {
                $callbackUrl = $redirectUrl . (strpos($redirectUrl, '?') !== false ? '&' : '?') . 'token=' . $token;
                return redirect($callbackUrl);
            }

            // Sinon, rediriger vers le dashboard local (cas où l'utilisateur se connecte directement)
            return redirect('/dashboard');
        }

        // Si l'utilisateur est déjà connecté SANS force_token, rediriger vers le dashboard
        if (Auth::check() && !$forceToken) {
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
            if ($redirect && filter_var($redirect, FILTER_VALIDATE_URL)) {
                return $redirect;
            }
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
}
