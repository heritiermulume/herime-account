<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class AvatarController extends Controller
{
    /**
     * Serve user avatar securely
     */
    public function show(Request $request, $userId): Response|JsonResponse
    {
        // Note: Les images chargées via <img src=""> ne peuvent pas envoyer de headers Authorization
        // On doit vérifier l'authentification différemment ou utiliser des tokens dans l'URL
        // Pour l'instant, on vérifie si un token est présent dans la requête ou dans l'URL
        
        $authenticatedUser = null;
        
        // Essayer de récupérer le token depuis l'URL (query parameter)
        $token = $request->query('token');
        
        if ($token) {
            // Si un token est fourni dans l'URL, on peut l'utiliser
            // Pour l'instant, on accepte la requête si le token existe
            // TODO: Valider le token si nécessaire
        } else {
            // Essayer depuis le header Authorization (pour les requêtes AJAX)
            $authenticatedUser = $request->user();
        }
        
        // Pour les images, on peut être plus permissif et permettre l'accès
        // si l'utilisateur demande son propre avatar ou si un token est fourni
        // Pour l'instant, on permet l'accès si l'utilisateur existe dans la DB
        
        \Log::info('Avatar request', [
            'user_id' => $userId,
            'has_token' => $token ? 'yes' : 'no',
            'authenticated_user' => $authenticatedUser ? $authenticatedUser->id : 'none',
            'bearer_token' => $request->bearerToken() ? 'yes' : 'no'
        ]);
        
        // Récupérer l'utilisateur dont on veut voir l'avatar
        $user = User::find($userId);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
        
        // Vérifier que l'avatar existe
        if (!$user->avatar) {
            return response()->json([
                'success' => false,
                'message' => 'Avatar not found'
            ], 404);
        }
        
        // Si avatar est une URL (format: /api/user/avatar/{id} ou http://...), utiliser avatar_filename
        // Sinon, c'est le nom du fichier (ancien format)
        $avatarField = $user->avatar;
        
        // Si c'est une URL, utiliser avatar_filename si disponible
        if (strpos($avatarField, '/api/user/avatar/') !== false || strpos($avatarField, 'http') === 0) {
            // C'est une URL, utiliser avatar_filename si disponible
            if (isset($user->avatar_filename) && $user->avatar_filename) {
                $avatarPath = 'avatars/' . basename($user->avatar_filename);
            } else {
                // Fallback: chercher dans le dossier avatars
                // On peut aussi utiliser un pattern basé sur l'ID utilisateur
                $avatarsDir = storage_path('app/private/avatars');
                $files = glob($avatarsDir . '/*');
                
                $userAvatarFile = null;
                // Chercher un fichier qui pourrait correspondre à cet utilisateur
                // On peut utiliser un pattern ou chercher le plus récent
                foreach ($files as $file) {
                    if (is_file($file)) {
                        $userAvatarFile = basename($file);
                        break; // Pour l'instant, prendre le premier trouvé
                    }
                }
                
                if (!$userAvatarFile) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Avatar file not found for this URL'
                    ], 404);
                }
                
                $avatarPath = 'avatars/' . $userAvatarFile;
            }
        } else {
            // C'est le nom du fichier directement (ancien format)
            $avatarPath = 'avatars/' . basename($avatarField);
        }
        
        // Vérifier que le fichier existe
        if (!Storage::disk('private')->exists($avatarPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Avatar file not found'
            ], 404);
        }
        
        // Obtenir le contenu du fichier
        $fileContent = Storage::disk('private')->get($avatarPath);
        
        // Détecter le type MIME basé sur l'extension
        $extension = strtolower(pathinfo($user->avatar, PATHINFO_EXTENSION));
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        ];
        $mimeType = $mimeTypes[$extension] ?? 'image/jpeg';
        
        // Retourner l'image avec les bons headers
        return response($fileContent, 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'inline; filename="' . basename($user->avatar) . '"')
            ->header('Cache-Control', 'private, max-age=3600')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Authorization, Content-Type');
    }
    
    /**
     * Serve current user's avatar
     */
    public function current(Request $request): Response|JsonResponse
    {
        // Pour cette route, on peut vérifier l'authentification si nécessaire
        $user = $request->user();
        
        if (!$user) {
            // Si pas authentifié, retourner une erreur ou rediriger vers login
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        
        return $this->show($request, $user->id);
    }
}

