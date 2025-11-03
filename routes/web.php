<?php

use Illuminate\Support\Facades\Route;

// Route principale - affiche l'application Vue.js
Route::get('/', function () {
    return view('welcome');
});

// Routes pour l'application Vue.js (toutes redirigent vers la vue principale)
Route::get('/login', function () {
    return view('welcome');
});

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
