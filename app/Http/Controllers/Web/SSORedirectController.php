<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Token as PassportToken;

class SSORedirectController extends Controller
{
    /**
     * Redirection SSO côté serveur
     * Détecte automatiquement la session et redirige vers le site externe
     */
    public function redirect(Request $request)
    {
        try {
            $user = null;

            // 1. Vérifier si l'utilisateur est connecté via session web
            if (Auth::guard('web')->check()) {
                $user = Auth::guard('web')->user();
            }

            // 2. Si pas de session, vérifier le token dans l'URL
            if (!$user && $request->has('_token')) {
                $tokenString = $request->input('_token');
                $accessToken = $this->findAccessToken($tokenString);

                if ($accessToken && !$accessToken->expires_at?->isPast()) {
                    $user = $accessToken->user;
                    
                    // Créer une session web pour l'utilisateur
                    if ($user && $user->isActive()) {
                        Auth::guard('web')->login($user);
                    }
                }
            }

            // 3. Si toujours pas d'utilisateur, rediriger vers login
            if (!$user || !$user->isActive()) {
                $redirectUrl = $request->query('redirect');
                if ($redirectUrl) {
                    return redirect()->route('login', ['redirect' => $redirectUrl]);
                }
                return redirect()->route('login');
            }

            // 4. Récupérer l'URL de redirection
            $redirectUrl = $request->query('redirect');

            if (!$redirectUrl) {
                return redirect('/dashboard');
            }

            // 5. Vérifier que l'URL ne pointe pas vers le même domaine
            $redirectHost = parse_url($redirectUrl, PHP_URL_HOST);
            $currentHost = $request->getHost();

            if ($redirectHost) {
                $redirectHost = preg_replace('/^www\./', '', strtolower($redirectHost));
                $currentHost = preg_replace('/^www\./', '', strtolower($currentHost));

                if ($redirectHost === $currentHost) {
                    return redirect('/dashboard');
                }
            }

            // 6. Générer le token SSO
            $token = $user->createToken('SSO Token', ['profile'])->accessToken;

            // 7. Construire l'URL de callback avec le token
            $parsedUrl = parse_url($redirectUrl);
            if (!$parsedUrl || !isset($parsedUrl['scheme']) || !isset($parsedUrl['host'])) {
                return redirect('/dashboard');
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
            $callbackUrl .= '?' . http_build_query($queryParams);
            if (isset($parsedUrl['fragment'])) {
                $callbackUrl .= '#' . $parsedUrl['fragment'];
            }

            // 8. Rediriger vers le site externe
            return redirect($callbackUrl);

        } catch (\Exception $e) {
            \Log::error('SSO Redirect Error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return redirect('/dashboard');
        }
    }

    /**
     * Trouver un token Passport depuis un JWT
     */
    private function findAccessToken(string $token): ?PassportToken
    {
        if (!$token) {
            return null;
        }

        $tokenHash = hash('sha256', $token);
        $accessToken = PassportToken::where('id', $tokenHash)
            ->where('revoked', false)
            ->first();

        if ($accessToken) {
            return $accessToken;
        }

        $payload = $this->decodeJwtPayload($token);

        if ($payload && isset($payload['jti'])) {
            $jti = $payload['jti'];

            $accessToken = PassportToken::where('id', $jti)
                ->where('revoked', false)
                ->first();

            if ($accessToken) {
                return $accessToken;
            }

            $accessToken = PassportToken::where('id', hash('sha256', $jti))
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
