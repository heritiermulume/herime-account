<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\RecoveryCode;
use Laravel\Fortify\Fortify;

class TwoFactorController extends Controller
{
    /**
     * Get 2FA status
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Recharger l'utilisateur depuis la base de données pour s'assurer d'avoir les dernières données
        $user->refresh();
        
        $enabled = $user->two_factor_confirmed_at !== null;
        
        \Log::info('2FA Status check', [
            'user_id' => $user->id,
            'two_factor_confirmed_at' => $user->two_factor_confirmed_at,
            'enabled' => $enabled
        ]);
        
        return response()->json([
            'success' => true,
            'data' => [
                'enabled' => $enabled,
                'confirmed_at' => $user->two_factor_confirmed_at ? $user->two_factor_confirmed_at->toISOString() : null,
            ]
        ]);
    }

    /**
     * Generate QR code and secret for 2FA setup
     */
    public function generate(Request $request): JsonResponse
    {
        $user = $request->user();

        // Si la 2FA est déjà activée, on ne peut pas régénérer
        if ($user->two_factor_confirmed_at) {
            return response()->json([
                'success' => false,
                'message' => 'L\'authentification à deux facteurs est déjà activée.'
            ], 400);
        }

        // Générer un nouveau secret si nécessaire
        if (!$user->two_factor_secret) {
            $provider = app(\Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider::class);
            $user->forceFill([
                'two_factor_secret' => Fortify::currentEncrypter()->encrypt($provider->generateSecretKey()),
            ])->save();
        }

        // Générer le QR code SVG
        $qrCode = $user->twoFactorQrCodeSvg();

        return response()->json([
            'success' => true,
            'data' => [
                'qr_code_svg' => $qrCode,
            ]
        ]);
    }

    /**
     * Confirm and enable 2FA
     */
    public function confirm(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|size:6',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // Vérifier le mot de passe
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mot de passe incorrect.'
            ], 422);
        }

        // Vérifier que le secret existe
        if (!$user->two_factor_secret) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun secret 2FA trouvé. Veuillez générer un nouveau QR code.'
            ], 400);
        }

        // Vérifier le code
        $provider = app(\Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider::class);
        $valid = $provider->verify(
            Fortify::currentEncrypter()->decrypt($user->two_factor_secret),
            $request->code
        );

        if (!$valid) {
            return response()->json([
                'success' => false,
                'message' => 'Code de vérification invalide.'
            ], 422);
        }

        // Générer les codes de récupération (8 codes)
        $recoveryCodes = collect(range(1, 8))->map(function () {
            return RecoveryCode::generate();
        })->all();
        
        // Activer la 2FA
        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode($recoveryCodes)),
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Authentification à deux facteurs activée avec succès.',
            'data' => [
                'recovery_codes' => $recoveryCodes,
            ]
        ]);
    }

    /**
     * Disable 2FA
     */
    public function disable(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // Vérifier le mot de passe
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mot de passe incorrect.'
            ], 422);
        }

        // Désactiver la 2FA
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Authentification à deux facteurs désactivée avec succès.'
        ]);
    }
}

