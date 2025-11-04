<?php
/**
 * Test de l'API AvatarController
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\AvatarController;

echo "=== TEST API AVATAR ===\n\n";

// 1. Récupérer un utilisateur avec avatar
$user = User::whereNotNull('avatar')->first();

if (!$user) {
    echo "❌ Aucun utilisateur avec avatar trouvé\n";
    exit(1);
}

echo "1. Utilisateur: ID {$user->id}, Email: {$user->email}\n";
echo "   Avatar: {$user->avatar}\n";
echo "   Avatar URL: {$user->avatar_url}\n\n";

// 2. Vérifier que le fichier existe
$avatarPath = 'avatars/' . basename($user->avatar);
if (!Storage::disk('private')->exists($avatarPath)) {
    echo "❌ Fichier avatar n'existe pas: {$avatarPath}\n";
    exit(1);
}

echo "2. Fichier existe: YES\n";
$fileSize = Storage::disk('private')->size($avatarPath);
echo "   Size: {$fileSize} bytes\n\n";

// 3. Simuler une requête authentifiée
$controller = new AvatarController();
$request = Request::create("/api/user/avatar/{$user->id}", 'GET');

// Simuler l'authentification en utilisant le user
$request->setUserResolver(function () use ($user) {
    return $user;
});

echo "3. Test de la méthode show()\n";

try {
    $response = $controller->show($request, $user->id);
    
    echo "   Status: {$response->getStatusCode()}\n";
    echo "   Headers:\n";
    foreach ($response->headers->all() as $key => $values) {
        echo "     {$key}: " . implode(', ', $values) . "\n";
    }
    
    $content = $response->getContent();
    echo "   Content length: " . strlen($content) . " bytes\n";
    echo "   Content type: " . $response->headers->get('Content-Type') . "\n";
    
    // Vérifier que c'est bien une image
    $firstBytes = substr($content, 0, 8);
    $hex = bin2hex($firstBytes);
    echo "   First bytes (hex): {$hex}\n";
    
    // PNG: 89 50 4E 47
    // JPEG: FF D8 FF
    if (strpos($hex, '89504e47') === 0) {
        echo "   ✅ Format détecté: PNG\n";
    } elseif (strpos($hex, 'ffd8ff') === 0) {
        echo "   ✅ Format détecté: JPEG\n";
    } else {
        echo "   ⚠️  Format non reconnu (peut-être une erreur)\n";
    }
    
    echo "\n✅ Test réussi !\n";
    
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Trace: " . substr($e->getTraceAsString(), 0, 500) . "\n";
    exit(1);
}

echo "\n=== TEST TERMINÉ ===\n";

