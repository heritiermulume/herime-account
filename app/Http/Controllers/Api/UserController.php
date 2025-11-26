<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Services\NotificationService;
use App\Mail\PasswordChangedMail;
use App\Mail\AccountDeactivatedMail;

class UserController extends Controller
{
    /**
     * Get user profile
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // S'assurer que avatar_url et last_login_at sont inclus dans la réponse
        $user->makeVisible(['avatar', 'avatar_url', 'last_login_at', 'is_active']);
        
        // Forcer le calcul de avatar_url en l'ajoutant explicitement
        $userData = $user->load('currentSession')->toArray();
        $userData['avatar_url'] = $user->avatar_url;
        
        // S'assurer que last_login_at est bien formaté
        if ($user->last_login_at) {
            $userData['last_login_at'] = $user->last_login_at->toISOString();
        } else {
            $userData['last_login_at'] = null;
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $userData
            ]
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'sometimes|nullable|string|max:20|unique:users,phone,' . $user->id,
            'gender' => 'sometimes|nullable|in:masculin,feminin,autre',
            'birthdate' => 'sometimes|nullable|date|before:today',
            'company' => 'sometimes|nullable|string|max:255',
            'position' => 'sometimes|nullable|string|max:255',
            'bio' => 'sometimes|nullable|string|max:1000',
            'location' => 'sometimes|nullable|string|max:255',
            'website' => 'sometimes|nullable|url|max:255',
            'avatar' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ], [
            'name.required' => 'Le nom complet est obligatoire.',
            'name.max' => 'Le nom ne peut pas dépasser 255 caractères.',
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email' => 'Veuillez saisir une adresse email valide.',
            'email.unique' => 'Cette adresse email est déjà utilisée par un autre compte.',
            'phone.max' => 'Le numéro de téléphone ne peut pas dépasser 20 caractères.',
            'phone.unique' => 'Ce numéro de téléphone est déjà utilisé par un autre compte.',
            'gender.in' => 'Veuillez sélectionner un sexe valide.',
            'birthdate.date' => 'Veuillez saisir une date valide.',
            'birthdate.before' => 'La date de naissance doit être antérieure à aujourd\'hui.',
            'company.max' => 'Le nom de l\'entreprise ne peut pas dépasser 255 caractères.',
            'position.max' => 'Le poste ne peut pas dépasser 255 caractères.',
            'bio.max' => 'La biographie ne peut pas dépasser 1000 caractères.',
            'location.max' => 'La localisation ne peut pas dépasser 255 caractères.',
            'website.url' => 'Veuillez saisir une URL valide.',
            'website.max' => 'L\'URL du site web ne peut pas dépasser 255 caractères.',
            'avatar.image' => 'Le fichier doit être une image.',
            'avatar.mimes' => 'L\'avatar doit être au format JPEG, PNG, JPG, GIF ou WEBP.',
            'avatar.max' => 'L\'avatar ne peut pas dépasser 2 Mo.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez vérifier les informations saisies.',
                'errors' => $validator->errors()
            ], 422);
        }

        // Récupérer tous les champs fillable disponibles
        $fillableFields = ['name', 'email', 'phone', 'gender', 'birthdate', 'company', 'position', 'bio', 'location', 'website'];
        $data = [];
        
        // Récupérer tous les champs fournis dans la requête
        foreach ($fillableFields as $field) {
            // Vérifier si le champ est présent dans la requête (même si vide)
            if ($request->exists($field)) {
                $value = $request->input($field);
                // Convertir les chaînes vides en null pour les champs nullable
                if (in_array($field, ['name', 'email'])) {
                    // Pour 'name' et 'email', garder la valeur telle quelle (obligatoires si présents)
                    $data[$field] = $value;
                } else {
                    // Pour les autres champs nullable, convertir les chaînes vides en null
                    $data[$field] = ($value === '' || $value === null) ? null : $value;
                }
            }
        }
        
        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            try {
                $file = $request->file('avatar');
                $originalSize = $file->getSize();
                
                // Delete old avatar if exists (dans le dossier privé)
                // Utiliser avatar_filename si disponible, sinon avatar (ancien format)
                $oldAvatarToDelete = $user->avatar_filename ?? ($user->avatar && strpos($user->avatar, '/api/user/avatar/') === false ? $user->avatar : null);
                
                if ($oldAvatarToDelete) {
                    $oldAvatarPath = 'avatars/' . basename($oldAvatarToDelete);
                    if (Storage::disk('private')->exists($oldAvatarPath)) {
                        Storage::disk('private')->delete($oldAvatarPath);
                    }
                }

                // Générer un nom unique pour éviter les collisions
                $extension = $file->getClientOriginalExtension();
                $filename = ImageService::generateUniqueFilename($extension);
                $avatarPath = 'avatars/' . $filename;
                
                // Compresser l'image si elle dépasse 1Mo (1048576 bytes)
                ImageService::compressAndSave($file, 'private', $avatarPath, 1048576, 85);
                
                // Stocker l'URL complète de l'avatar dans la DB
                // Format: /api/user/avatar/{userId}
                $baseUrl = config('app.url');
                $avatarUrl = rtrim($baseUrl, '/') . '/api/user/avatar/' . $user->id;
                $data['avatar'] = $avatarUrl;
                
                // Stocker aussi le nom du fichier pour pouvoir le retrouver
                $data['avatar_filename'] = $filename;
                
                $finalSize = Storage::disk('private')->size($avatarPath);
                
            } catch (\Exception $e) {
                // Ne pas échouer la mise à jour du profil si l'avatar échoue
            }
        }

        // Mettre à jour uniquement les champs fournis
        if (!empty($data)) {
            $user->update($data);
        }

        // Recharger l'utilisateur avec toutes les relations
        $user->refresh();
        
        // S'assurer que avatar_url est inclus dans la réponse
        $user->makeVisible(['avatar', 'avatar_url', 'avatar_filename']);
        
        // Forcer le calcul de avatar_url en l'ajoutant explicitement
        $userData = $user->load('currentSession')->toArray();
        $userData['avatar_url'] = $user->avatar_url;
        
        // S'assurer que l'URL contient bien un timestamp pour éviter le cache
        // Le frontend ajoutera le timestamp, mais on peut aussi le faire ici
        if (isset($userData['avatar_url']) && strpos($userData['avatar_url'], '/api/user/avatar/') !== false) {
            // L'URL est déjà correcte, le frontend ajoutera le timestamp
            // Mais on peut aussi l'ajouter ici si nécessaire
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'user' => $userData
            ]
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // Check current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 400);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->password)
        ]);

            // Envoyer une notification si activée
            $parts = $user->name ? preg_split('/\s+/', trim($user->name)) : [];
            $firstName = $parts[0] ?? null;
            $lastName = isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : null;
            NotificationService::sendForEvent($user, 'password_changes', new PasswordChangedMail($firstName, $lastName));

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }

    /**
     * Update user preferences
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'preferences' => 'required|array',
            'preferences.theme' => 'sometimes|in:light,dark,system',
            'preferences.language' => 'sometimes|string|max:10',
            // Préférences globales (depuis Profile.vue)
            'preferences.email_notifications' => 'sometimes|boolean',
            'preferences.marketing_emails' => 'sometimes|boolean',
            // Préférences de notifications granulaires (depuis Notifications.vue)
            'preferences.notifications' => 'sometimes|array',
            'preferences.notifications.suspicious_logins' => 'sometimes|boolean',
            'preferences.notifications.password_changes' => 'sometimes|boolean',
            'preferences.notifications.profile_changes' => 'sometimes|boolean',
            'preferences.notifications.new_features' => 'sometimes|boolean',
            'preferences.notifications.maintenance' => 'sometimes|boolean',
            'preferences.notifications.newsletter' => 'sometimes|boolean',
            'preferences.notifications.special_offers' => 'sometimes|boolean',
            'preferences.notifications.email' => 'sometimes|boolean',
            'preferences.notifications.sms' => 'sometimes|boolean',
            'preferences.notifications.push' => 'sometimes|boolean',
            // Fréquence des emails
            'preferences.email_frequency' => 'sometimes|in:immediate,daily,weekly,monthly,never',
            // Push notifications
            'preferences.push_notifications' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Fusionner en remplaçant récursivement pour que les nouvelles valeurs écrasent les anciennes
        $currentPreferences = $user->preferences ?? [];
        $newPreferences = array_replace_recursive($currentPreferences, $request->preferences);
        
        // Log pour debug

        $user->update([
            'preferences' => $newPreferences
        ]);

        // Recharger pour s'assurer que les données sont à jour
        $user->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Preferences updated successfully',
            'data' => [
                'preferences' => $user->preferences
            ]
        ]);
    }

    /**
     * Deactivate account
     */
    public function deactivateAccount(Request $request): JsonResponse
    {
        $user = $request->user();
        
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

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password is incorrect'
            ], 400);
        }

        // Deactivate account
        $user->update(['is_active' => false]);

        // Revoke all tokens
        $user->tokens()->delete();

            // Notifier la désactivation
            $parts = $user->name ? preg_split('/\s+/', trim($user->name)) : [];
            $firstName = $parts[0] ?? null;
            $lastName = isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : null;
            NotificationService::sendForEvent($user, 'account_status', new AccountDeactivatedMail($firstName, $lastName));

        return response()->json([
            'success' => true,
            'message' => 'Account deactivated successfully'
        ]);
    }

    /**
     * Delete account (désactive seulement le compte pour les utilisateurs normaux)
     * Seul l'administrateur peut définitivement supprimer un compte
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
            'reason' => 'required|string|max:1000',
        ], [
            'password.required' => 'Le mot de passe est obligatoire.',
            'reason.required' => 'La raison de la suppression est obligatoire.',
            'reason.max' => 'La raison ne peut pas dépasser 1000 caractères.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez vérifier les informations saisies.',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Le mot de passe est incorrect.'
            ], 400);
        }

        // Pour les utilisateurs normaux, on désactive seulement le compte
        // On stocke la raison de la désactivation dans les préférences
        $preferences = $user->preferences ?? [];
        $preferences['deactivation_reason'] = $request->reason;
        $preferences['deactivation_date'] = now()->toISOString();
        
        // Désactiver le compte
        $user->update([
            'is_active' => false,
            'preferences' => $preferences
        ]);

        // Revoke all tokens
        $user->tokens()->delete();

            // Notifier la désactivation (avec raison)
            $parts = $user->name ? preg_split('/\s+/', trim($user->name)) : [];
            $firstName = $parts[0] ?? null;
            $lastName = isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : null;
            NotificationService::sendForEvent($user, 'account_status', new AccountDeactivatedMail($firstName, $lastName, $request->reason));

        return response()->json([
            'success' => true,
            'message' => 'Votre compte a été désactivé avec succès. Un administrateur peut le réactiver si nécessaire.'
        ]);
    }

    /**
     * Get user sessions
     */
    public function sessions(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Récupérer toutes les sessions de l'utilisateur, triées par dernière activité
        $sessions = $user->sessions()
            ->orderBy('last_activity', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                'sessions' => $sessions
            ]
        ]);
    }

    /**
     * Revoke a specific session
     */
    public function revokeSession(Request $request, $sessionId): JsonResponse
    {
        $user = $request->user();
        
        // Trouver la session
        $session = $user->sessions()->find($sessionId);
        
        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session non trouvée'
            ], 404);
        }
        
        // Ne pas permettre de révoquer la session actuelle
        if ($session->is_current) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas révoquer votre session actuelle. Utilisez la déconnexion.'
            ], 400);
        }
        
        // Révoquer le token associé si disponible
        if ($session->token_id) {
            try {
                $token = $user->tokens()->where('id', $session->token_id)->first();
                if ($token) {
                    $token->revoke();
                    \Log::info('UserController: Token revoked for session', [
                        'user_id' => $user->id,
                        'session_id' => $session->id,
                        'token_id' => $session->token_id,
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('UserController: Error revoking token for session', [
                    'user_id' => $user->id,
                    'session_id' => $session->id,
                    'token_id' => $session->token_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        // Marquer la session comme inactive
        $session->update([
            'is_current' => false,
            'last_activity' => now()
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Session révoquée avec succès'
        ]);
    }

    /**
     * Revoke all other sessions (except current)
     */
    public function revokeAllSessions(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Marquer toutes les autres sessions comme inactives
        $count = $user->sessions()
            ->where('is_current', false)
            ->update([
                'is_current' => false,
                'last_activity' => now()
            ]);
        
        return response()->json([
            'success' => true,
            'message' => "Toutes les autres sessions ont été révoquées ($count sessions)"
        ]);
    }

    /**
     * Delete a session permanently
     */
    public function deleteSession(Request $request, $sessionId): JsonResponse
    {
        $user = $request->user();
        
        // Trouver la session
        $session = $user->sessions()->find($sessionId);
        
        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session non trouvée'
            ], 404);
        }
        
        // Ne pas permettre de supprimer la session actuelle
        if ($session->is_current) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas supprimer votre session actuelle. Utilisez la déconnexion.'
            ], 400);
        }
        
        // Révoquer le token associé si disponible
        if ($session->token_id) {
            try {
                $token = $user->tokens()->where('id', $session->token_id)->first();
                if ($token) {
                    $token->revoke();
                    \Log::info('UserController: Token revoked for deleted session', [
                        'user_id' => $user->id,
                        'session_id' => $session->id,
                        'token_id' => $session->token_id,
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('UserController: Error revoking token for deleted session', [
                    'user_id' => $user->id,
                    'session_id' => $session->id,
                    'token_id' => $session->token_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        // Supprimer la session définitivement
        $session->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Session supprimée avec succès'
        ]);
    }
}
