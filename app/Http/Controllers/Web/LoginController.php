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
        $forceToken = $request->boolean('force_token', false) || $request->has('force_token') || $request->query('force_token');

        \Log::info('LoginController@show', [
            'auth_check' => Auth::check(),
            'force_token' => $forceToken,
            'redirect_url' => $redirectUrl,
            'query_params' => $request->all()
        ]);

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

            // Si une URL de redirection a été détectée, passer à la vue pour redirection JS immédiate
            if ($redirectUrl) {
                $callbackUrl = $redirectUrl . (strpos($redirectUrl, '?') !== false ? '&' : '?') . 'token=' . $token;
                \Log::info('SSO Redirect with token', [
                    'redirect_url' => $callbackUrl,
                    'user_id' => $user->id
                ]);
                
                // Passer l'URL de redirection à la vue pour redirection JavaScript immédiate
                // Cela évite que Vue.js charge avant la redirection
                return view('welcome', [
                    'sso_redirect' => $callbackUrl
                ]);
            }

            // Sinon, rediriger vers le dashboard local (cas où l'utilisateur se connecte directement)
            \Log::warning('SSO force_token requested but no redirect URL found', [
                'user_id' => $user->id,
                'query_params' => $request->all()
            ]);
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
            // Laravel décode automatiquement les paramètres d'URL une fois
            // Si le paramètre est doublement encodé (comme dans certains cas), on le décode une fois de plus
            $decodedRedirect = urldecode($redirect);
            // Utiliser la version décodée si elle est différente et valide
            $finalRedirect = ($decodedRedirect !== $redirect && filter_var($decodedRedirect, FILTER_VALIDATE_URL)) 
                ? $decodedRedirect 
                : $redirect;
            
            if ($finalRedirect && filter_var($finalRedirect, FILTER_VALIDATE_URL)) {
                \Log::info('SSO Redirect detected', ['redirect_url' => $finalRedirect, 'original' => $redirect]);
                return $finalRedirect;
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
