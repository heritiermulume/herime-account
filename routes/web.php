<?php

use Illuminate\Support\Facades\Route;
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
// Pas de middleware auth ici - on vérifie l'auth dans le contrôleur pour supporter session ET token
Route::get('/sso/redirect', [App\Http\Controllers\Web\SSORedirectController::class, 'redirect']);

Route::get('/register', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('welcome');
});

// Route de fallback pour toutes les routes Vue.js (sauf API)
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '^(?!api).*');
