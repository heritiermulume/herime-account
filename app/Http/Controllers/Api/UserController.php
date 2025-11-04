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
        
        \Log::info('Profile API response', [
            'user_id' => $user->id,
            'avatar' => $user->avatar,
            'avatar_url' => $userData['avatar_url'],
            'last_login_at' => $userData['last_login_at']
        ]);
        
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
            'phone' => 'sometimes|nullable|string|max:20',
            'company' => 'sometimes|nullable|string|max:255',
            'position' => 'sometimes|nullable|string|max:255',
            'bio' => 'sometimes|nullable|string|max:1000',
            'location' => 'sometimes|nullable|string|max:255',
            'website' => 'sometimes|nullable|url|max:255',
            'avatar' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Récupérer tous les champs fillable disponibles
        $fillableFields = ['name', 'phone', 'company', 'position', 'bio', 'location', 'website'];
        $data = [];
        
        // Récupérer tous les champs fournis dans la requête
        foreach ($fillableFields as $field) {
            // Vérifier si le champ est présent dans la requête (même si vide)
            if ($request->exists($field)) {
                $value = $request->input($field);
                // Convertir les chaînes vides en null pour les champs nullable
                if ($field === 'name') {
                    // Pour 'name', garder la valeur telle quelle (obligatoire)
                    $data[$field] = $value;
                } else {
                    // Pour les autres champs nullable, convertir les chaînes vides en null
                    $data[$field] = ($value === '' || $value === null) ? null : $value;
                }
            }
        }
        
        // Log pour debug
        \Log::info('Profile update data:', $data);

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
                
                \Log::info('Avatar stored in private storage (compressed)', [
                    'filename' => $filename,
                    'avatar_path' => $avatarPath,
                    'original_size' => $originalSize,
                    'final_size' => $finalSize,
                    'compressed' => $originalSize > $finalSize,
                    'mime' => $file->getMimeType()
                ]);
            } catch (\Exception $e) {
                \Log::error('Error uploading avatar:', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
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
        
        \Log::info('Profile update API response', [
            'user_id' => $user->id,
            'avatar' => $user->avatar,
            'avatar_filename' => $user->avatar_filename,
            'avatar_url' => $userData['avatar_url']
        ]);

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
            'preferences.notifications' => 'sometimes|array',
            'preferences.notifications.email' => 'sometimes|boolean',
            'preferences.notifications.sms' => 'sometimes|boolean',
            'preferences.notifications.push' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Fusionner avec les préférences existantes pour préserver les valeurs non modifiées
        $currentPreferences = $user->preferences ?? [];
        $newPreferences = array_merge($currentPreferences, $request->preferences);
        
        // Log pour debug
        \Log::info('Preferences update:', [
            'current' => $currentPreferences,
            'new' => $request->preferences,
            'merged' => $newPreferences
        ]);

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

        \Log::info('Account deactivated by user', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'reason' => $request->reason
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Votre compte a été désactivé avec succès. Un administrateur peut le réactiver si nécessaire.'
        ]);
    }
}
