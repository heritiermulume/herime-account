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
        
        // S'assurer que avatar_url est inclus dans la réponse
        $user->makeVisible(['avatar', 'avatar_url']);
        
        // Forcer le calcul de avatar_url en l'ajoutant explicitement
        $userData = $user->load('currentSession')->toArray();
        $userData['avatar_url'] = $user->avatar_url;
        
        \Log::info('Profile API response', [
            'user_id' => $user->id,
            'avatar' => $user->avatar,
            'avatar_url' => $userData['avatar_url']
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
                if ($user->avatar) {
                    $oldAvatarPath = 'avatars/' . basename($user->avatar);
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
                if (isset($user->avatar_filename)) {
                    $data['avatar_filename'] = $filename;
                }
                
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
        $user->makeVisible(['avatar', 'avatar_url']);
        
        // Forcer le calcul de avatar_url en l'ajoutant explicitement
        $userData = $user->load('currentSession')->toArray();
        $userData['avatar_url'] = $user->avatar_url;
        
        \Log::info('Profile update API response', [
            'user_id' => $user->id,
            'avatar' => $user->avatar,
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
     * Delete account
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
            'confirmation' => 'required|in:DELETE',
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

        // Delete avatar if exists (dans le dossier privé)
        if ($user->avatar) {
            $avatarPath = 'avatars/' . basename($user->avatar);
            if (Storage::disk('private')->exists($avatarPath)) {
                Storage::disk('private')->delete($avatarPath);
            }
        }

        // Delete user and related data
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Account deleted successfully'
        ]);
    }
}
