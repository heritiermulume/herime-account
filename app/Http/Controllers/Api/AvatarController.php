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
        // Vérifier que l'utilisateur est authentifié
        $authenticatedUser = $request->user();
        
        if (!$authenticatedUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        
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
        
        // Vérifier que le fichier existe
        $avatarPath = 'avatars/' . basename($user->avatar);
        if (!Storage::disk('private')->exists($avatarPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Avatar file not found'
            ], 404);
        }
        
        // Obtenir le contenu du fichier
        $fileContent = Storage::disk('private')->get($avatarPath);
        $mimeType = Storage::disk('private')->mimeType($avatarPath);
        
        // Retourner l'image avec les bons headers
        return response($fileContent, 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'inline; filename="' . basename($user->avatar) . '"')
            ->header('Cache-Control', 'private, max-age=3600');
    }
    
    /**
     * Serve current user's avatar
     */
    public function current(Request $request): Response|JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        
        return $this->show($request, $user->id);
    }
}

