<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Web\LoginController;

// Route principale - affiche l'application Vue.js
Route::get('/', function () {
    return view('welcome');
});

// Route de login avec gestion du SSO force_token
Route::get('/login', [LoginController::class, 'show']);

// Route de logout avec gestion du paramètre redirect
Route::get('/logout', [LoginController::class, 'logout']);

// Route SSO de redirection côté serveur (contourne JavaScript/Vue Router)
// IMPORTANT: Cette route doit être définie AVANT la route de fallback
// Pas de middleware auth ici - on vérifie l'auth dans le contrôleur pour supporter session ET token
Route::get('/sso/redirect', [App\Http\Controllers\Web\SSORedirectController::class, 'redirect']);

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
        abort(404);
    }
    return view('welcome');
})->where('any', '^(?!api|sso).*');
