<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Mail\PasswordResetMail;

class PasswordResetController extends Controller
{
    /**
     * Send password reset link
     */
    public function sendResetLink(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ], [
            'email.required' => 'L\'adresse email est requise.',
            'email.email' => 'L\'adresse email doit être valide.',
            'email.exists' => 'Cette adresse email n\'existe pas dans notre système.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier que l'utilisateur est actif
        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Cette adresse email n\'existe pas dans notre système.'
            ], 404);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Votre compte est désactivé. Veuillez contacter l\'administrateur.'
            ], 403);
        }

        // Générer un token de réinitialisation
        $token = Str::random(64);
        
        // Supprimer les anciens tokens pour cet email
        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        // Créer un nouveau token
        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        // Construire l'URL de réinitialisation
        $resetUrl = config('app.url') . '/reset-password?token=' . $token . '&email=' . urlencode($request->email);

        // Log pour debug

        // Envoyer l'email avec le lien de réinitialisation
        try {
            // Extraire prénom et nom si possible
            $firstName = null;
            $lastName = null;
            if (!empty($user->name)) {
                $parts = preg_split('/\s+/', trim($user->name));
                if ($parts && count($parts) > 0) {
                    $firstName = $parts[0];
                    if (count($parts) > 1) {
                        $lastName = implode(' ', array_slice($parts, 1));
                    }
                }
            }

            Mail::to($request->email)->send(new PasswordResetMail($resetUrl, $firstName, $lastName));
        } catch (\Exception $e) {
            
            // En cas d'erreur d'envoi, on retourne quand même un succès pour ne pas révéler 
            // si l'email existe ou non (bonne pratique de sécurité)
            // Mais on log l'erreur pour le diagnostic
        }

        return response()->json([
            'success' => true,
            'message' => 'Un lien de réinitialisation a été envoyé à votre adresse email.',
            'data' => [
                // En développement, on peut retourner l'URL pour faciliter les tests
                // En production, supprimer cette partie
                'reset_url' => config('app.debug') ? $resetUrl : null,
            ]
        ]);
    }

    /**
     * Reset password
     */
    public function reset(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'email.required' => 'L\'adresse email est requise.',
            'email.email' => 'L\'adresse email doit être valide.',
            'email.exists' => 'Cette adresse email n\'existe pas dans notre système.',
            'token.required' => 'Le token de réinitialisation est requis.',
            'password.required' => 'Le mot de passe est requis.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier le token
        $passwordReset = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$passwordReset) {
            return response()->json([
                'success' => false,
                'message' => 'Token de réinitialisation invalide ou expiré.'
            ], 400);
        }

        // Vérifier que le token n'est pas expiré (24 heures)
        $tokenAge = Carbon::parse($passwordReset->created_at)->diffInHours(now());
        if ($tokenAge > 24) {
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();
            
            return response()->json([
                'success' => false,
                'message' => 'Le token de réinitialisation a expiré. Veuillez demander un nouveau lien.'
            ], 400);
        }

        // Vérifier le token
        if (!Hash::check($request->token, $passwordReset->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Token de réinitialisation invalide.'
            ], 400);
        }

        // Mettre à jour le mot de passe
        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé.'
            ], 404);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Supprimer le token utilisé
        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        // Révoquer tous les tokens existants de l'utilisateur pour forcer une nouvelle connexion
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.'
        ]);
    }
}

