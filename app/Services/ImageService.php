<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageService
{
    /**
     * Compress and save image
     * 
     * @param UploadedFile $file
     * @param string $disk
     * @param string $path
     * @param int $maxSizeBytes Maximum file size in bytes (default: 1MB)
     * @param int $quality JPEG quality (1-100)
     * @return string Path to saved file
     */
    public static function compressAndSave(UploadedFile $file, string $disk, string $path, int $maxSizeBytes = 1048576, int $quality = 85): string
    {
        $originalSize = $file->getSize();
        
        // Si le fichier est déjà sous la limite, le sauvegarder tel quel
        if ($originalSize <= $maxSizeBytes) {
            // S'assurer que le dossier existe
            Storage::disk($disk)->makeDirectory(dirname($path));
            return $file->storeAs(dirname($path), basename($path), $disk);
        }
        
        // Lire l'image
        $imageData = file_get_contents($file->getRealPath());
        $image = @imagecreatefromstring($imageData);
        
        if (!$image) {
            throw new \Exception('Impossible de lire l\'image');
        }
        
        // Obtenir les dimensions originales
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Calculer la qualité initiale basée sur la taille
        $currentQuality = $quality;
        $targetSize = $maxSizeBytes;
        
        // Essayer différentes qualités jusqu'à atteindre la taille cible
        $attempts = 0;
        $maxAttempts = 10;
        
        while ($attempts < $maxAttempts) {
            // Créer une image temporaire en mémoire
            ob_start();
            
            // Détecter le type d'image
            $mimeType = $file->getMimeType();
            $extension = strtolower($file->getClientOriginalExtension());
            
            if ($extension === 'png') {
                // Pour PNG, on peut aussi réduire la qualité via compression
                imagepng($image, null, 9 - ($currentQuality / 10));
            } elseif ($extension === 'webp') {
                imagewebp($image, null, $currentQuality);
            } else {
                // JPEG par défaut
                imagejpeg($image, null, $currentQuality);
            }
            
            $compressedData = ob_get_clean();
            $compressedSize = strlen($compressedData);
            
            // Si on a atteint la taille cible ou si on ne peut plus réduire
            if ($compressedSize <= $targetSize || $currentQuality <= 20) {
                // Si toujours trop gros, réduire la résolution
                if ($compressedSize > $targetSize && $width > 800) {
                    $newWidth = 800;
                    $newHeight = (int)($height * ($newWidth / $width));
                    $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
                    
                    // Préserver la transparence pour PNG
                    if ($extension === 'png') {
                        imagealphablending($resizedImage, false);
                        imagesavealpha($resizedImage, true);
                        $transparent = imagecolorallocatealpha($resizedImage, 0, 0, 0, 127);
                        imagefill($resizedImage, 0, 0, $transparent);
                    }
                    
                    imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    imagedestroy($image);
                    $image = $resizedImage;
                    
                    // Réessayer avec la nouvelle taille
                    ob_start();
                    if ($extension === 'png') {
                        imagepng($image, null, 9);
                    } elseif ($extension === 'webp') {
                        imagewebp($image, null, $currentQuality);
                    } else {
                        imagejpeg($image, null, $currentQuality);
                    }
                    $compressedData = ob_get_clean();
                }
                
                // Sauvegarder l'image compressée
                // S'assurer que le dossier existe
                Storage::disk($disk)->makeDirectory(dirname($path));
                // Utiliser Storage::put au lieu de file_put_contents pour plus de fiabilité
                Storage::disk($disk)->put($path, $compressedData);
                
                imagedestroy($image);
                
                \Log::info('Image compressed', [
                    'original_size' => $originalSize,
                    'compressed_size' => strlen($compressedData),
                    'quality' => $currentQuality,
                    'path' => $path
                ]);
                
                return $path;
            }
            
            // Réduire la qualité pour le prochain essai
            $currentQuality -= 10;
            $attempts++;
        }
        
        // Si on n'a pas réussi à compresser suffisamment, sauvegarder quand même
        imagedestroy($image);
        return $file->storeAs(dirname($path), basename($path), $disk);
    }
    
    /**
     * Generate unique filename
     */
    public static function generateUniqueFilename(string $extension): string
    {
        return time() . '_' . uniqid() . '_' . Str::random(8) . '.' . strtolower($extension);
    }
}

