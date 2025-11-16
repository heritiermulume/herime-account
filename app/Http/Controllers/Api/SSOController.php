<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Laravel\Passport\Token as PassportToken;

class SSOController extends Controller
{
    /**
     * Valider un token SSO et retourner les informations utilisateur
     * Utilisé par les sites externes pour authentifier l'utilisateur
     */
    public function validateToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Token requis'
            ], 422);
        }

        $token = $request->input('token');
        $accessToken = $this->findAccessToken($token);

        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide ou expiré'
            ], 401);
        }

        // Vérifier si le token est expiré
        if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Token expiré'
            ], 401);
        }

        $user = $accessToken->user;

        if (!$user || !$user->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur inactif'
            ], 401);
        }

        // Construire l'URL complète de l'avatar
        $avatarUrl = $user->avatar_url;
        if ($avatarUrl && !str_starts_with($avatarUrl, 'http')) {
            $baseUrl = config('app.url');
            $avatarUrl = rtrim($baseUrl, '/') . '/' . ltrim($avatarUrl, '/');
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $avatarUrl,
                    'phone' => $user->phone,
                    'company' => $user->company,
                    'position' => $user->position,
                    'last_login_at' => $user->last_login_at?->toISOString(),
                ],
                'permissions' => $accessToken->scopes ?? [],
            ]
        ]);
    }

    /**
     * Vérification rapide de la validité d'un token (pour polling)
     */
    public function checkToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'valid' => false
            ], 422);
        }

        $token = $request->input('token');
        $accessToken = $this->findAccessToken($token);

        if (!$accessToken) {
            return response()->json(['valid' => false]);
        }

        if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
            return response()->json(['valid' => false]);
        }

        $user = $accessToken->user;
        if (!$user || !$user->isActive()) {
            return response()->json(['valid' => false]);
        }

        return response()->json([
            'valid' => true,
            'user_id' => $user->id
        ]);
    }

    /**
     * Valider un token avec secret SSO (pour services externes)
     */
    public function validateTokenWithSecret(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['valid' => false], 422);
        }

        // Vérifier le secret SSO
        $authHeader = $request->header('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['valid' => false], 401);
        }

        $providedSecret = substr($authHeader, 7);
        $expectedSecret = config('services.sso.secret', env('SSO_SECRET'));

        if (!$expectedSecret || $providedSecret !== $expectedSecret) {
            return response()->json(['valid' => false], 401);
        }

        $token = $request->input('token');
        $accessToken = $this->findAccessToken($token);

        if (!$accessToken) {
            return response()->json(['valid' => false]);
        }

        if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
            return response()->json(['valid' => false]);
        }

        $user = $accessToken->user;
        if (!$user || !$user->isActive()) {
            return response()->json(['valid' => false]);
        }

        // Construire l'URL complète de l'avatar
        $avatarUrl = $user->avatar_url;
        if ($avatarUrl && !str_starts_with($avatarUrl, 'http')) {
            $baseUrl = config('app.url');
            $avatarUrl = rtrim($baseUrl, '/') . '/' . ltrim($avatarUrl, '/');
        }

        return response()->json([
            'valid' => true,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'avatar' => $avatarUrl,
                'role' => $user->role ?? 'user',
                'is_verified' => !is_null($user->email_verified_at),
                'is_active' => $user->is_active,
            ]
        ]);
    }

    /**
     * Générer un token SSO pour redirection
     */
    public function generateToken(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        if (!$user->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Compte désactivé'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'redirect' => 'required|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'URL de redirection invalide',
                'errors' => $validator->errors()
            ], 422);
        }

        $redirectUrl = $request->input('redirect');

        // Vérifier que l'URL ne pointe pas vers le même domaine
        $redirectHost = parse_url($redirectUrl, PHP_URL_HOST);
        $currentHost = $request->getHost();

        if ($redirectHost) {
            $redirectHost = preg_replace('/^www\./', '', strtolower($redirectHost));
            $currentHost = preg_replace('/^www\./', '', strtolower($currentHost));

            if ($redirectHost === $currentHost) {
                return response()->json([
                    'success' => false,
                    'message' => 'L\'URL de redirection ne peut pas pointer vers le même domaine'
                ], 422);
            }
        }

        // Créer le token SSO
        $token = $user->createToken('SSO Token', ['profile'])->accessToken;

        // Construire l'URL de callback avec le token
        $parsedUrl = parse_url($redirectUrl);
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

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'redirect_url' => $redirectUrl,
                'callback_url' => $callbackUrl,
            ]
        ]);
    }

    /**
     * Trouver un token Passport depuis un JWT
     */
    private function findAccessToken(string $token): ?PassportToken
    {
        if (!$token) {
            return null;
        }

        // Méthode 1: Hash SHA-256 du token complet
        $tokenHash = hash('sha256', $token);
        $accessToken = PassportToken::where('id', $tokenHash)
            ->where('revoked', false)
            ->first();

        if ($accessToken) {
            return $accessToken;
        }

        // Méthode 2: Décoder le JWT et utiliser le jti
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
