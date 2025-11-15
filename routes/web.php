<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Web\LoginController;

// Route SSO de redirection côté serveur (contourne JavaScript/Vue Router)
// PRIORITÉ ABSOLUE: Cette route DOIT être définie EN PREMIER pour éviter qu'elle soit capturée par le fallback
// Pas de middleware auth ici - on vérifie l'auth dans le contrôleur pour supporter session ET token
Route::get('/sso/redirect', [App\Http\Controllers\Web\SSORedirectController::class, 'redirect']);

// Route principale - affiche l'application Vue.js
Route::get('/', function () {
    return view('welcome');
});

// Route de login avec gestion du SSO force_token
Route::get('/login', [LoginController::class, 'show']);

// Route de logout avec gestion du paramètre redirect
Route::get('/logout', [LoginController::class, 'logout']);

Route::get('/register', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('welcome');
});

// Route de fallback pour toutes les routes Vue.js (sauf API et sso)
// IMPORTANT: Exclure explicitement /sso/* pour éviter qu'elle soit capturée
Route::get('/{any}', function (Request $request) {
    // Double vérification : ne pas rendre le template si c'est sso/redirect
    $path = $request->path();
    if ($path === 'sso/redirect' || str_starts_with($path, 'sso/')) {
        // Si on arrive ici, c'est que la route spécifique n'a pas été trouvée
        // Retourner une erreur 404 au lieu de rendre le template
        abort(404, 'Route not found');
    }
    return view('welcome');
})->where('any', '^(?!api|sso).*');
