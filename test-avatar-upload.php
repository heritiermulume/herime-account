<?php
/**
 * Script de test pour l'upload et l'affichage d'avatar
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use App\Services\ImageService;

echo "=== TEST AVATAR UPLOAD ===\n\n";

// 1. Créer un utilisateur de test s'il n'existe pas
$user = User::firstOrCreate(
    ['email' => 'test-avatar@example.com'],
    [
        'name' => 'Test Avatar',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]
);

echo "1. Utilisateur test: ID {$user->id}, Email: {$user->email}\n";

// 2. Créer une image de test
$testImagePath = storage_path('app/private/avatars/test_' . time() . '.png');
$testImageDir = dirname($testImagePath);
if (!is_dir($testImageDir)) {
    mkdir($testImageDir, 0755, true);
}

// Créer une image PNG simple (1x1 pixel rouge)
$image = imagecreatetruecolor(100, 100);
$red = imagecolorallocate($image, 255, 0, 0);
imagefill($image, 0, 0, $red);
imagepng($image, $testImagePath);
imagedestroy($image);

echo "2. Image de test créée: {$testImagePath}\n";
echo "   Size: " . filesize($testImagePath) . " bytes\n";

// 3. Simuler un upload
$filename = basename($testImagePath);
$user->avatar = $filename;
$user->save();

echo "3. Avatar sauvegardé dans DB: {$filename}\n";

// 4. Vérifier le stockage
$avatarPath = 'avatars/' . $filename;
$exists = Storage::disk('private')->exists($avatarPath);
echo "4. Vérification stockage:\n";
echo "   Path: {$avatarPath}\n";
echo "   Exists: " . ($exists ? 'YES' : 'NO') . "\n";

if (!$exists) {
    // Copier le fichier dans le bon dossier
    Storage::disk('private')->put($avatarPath, file_get_contents($testImagePath));
    echo "   ✅ Fichier copié dans storage\n";
}

// 5. Vérifier avatar_url
$avatarUrl = $user->avatar_url;
echo "5. Avatar URL: {$avatarUrl}\n";

// 6. Tester la route API (simulation)
echo "\n6. Test de la route API:\n";
echo "   URL: /api/user/avatar/{$user->id}\n";
echo "   Expected: Image file\n";

// 7. Vérifier que le fichier peut être lu
try {
    $fileContent = Storage::disk('private')->get($avatarPath);
    echo "7. Lecture fichier: SUCCESS (" . strlen($fileContent) . " bytes)\n";
} catch (\Exception $e) {
    echo "7. Lecture fichier: ERROR - " . $e->getMessage() . "\n";
}

echo "\n=== TEST TERMINÉ ===\n";
echo "Pour tester dans le navigateur:\n";
echo "1. Se connecter avec test-avatar@example.com / password\n";
echo "2. Aller sur /profile\n";
echo "3. Vérifier que l'avatar s'affiche\n";
echo "4. URL attendue: {$avatarUrl}\n";

