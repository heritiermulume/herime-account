<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SimpleAuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\SSOController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('register', [SimpleAuthController::class, 'register']);
Route::post('login', [SimpleAuthController::class, 'login']);
Route::post('login/verify-2fa', [SimpleAuthController::class, 'verifyTwoFactor']);

// Password reset routes
Route::post('password/forgot', [App\Http\Controllers\Api\PasswordResetController::class, 'sendResetLink']);
Route::post('password/reset', [App\Http\Controllers\Api\PasswordResetController::class, 'reset']);

// SSO public routes
Route::post('sso/validate-token', [SSOController::class, 'validateToken']);
Route::post('sso/check-token', [SSOController::class, 'checkToken']);

// Validate token with SSO secret (for external services)
Route::post('validate-token', [SSOController::class, 'validateTokenWithSecret']);

// Avatar routes (publiques mais avec vérification dans le contrôleur)
// Note: Les images <img src=""> ne peuvent pas envoyer de headers Authorization
// On doit permettre l'accès public ou utiliser un token dans l'URL
Route::get('user/avatar/{userId}', [App\Http\Controllers\Api\AvatarController::class, 'show']);
Route::get('user/avatar', [App\Http\Controllers\Api\AvatarController::class, 'current']);

// Public settings for gating UI (registration, maintenance)
Route::get('settings/public', [App\Http\Controllers\Api\AdminController::class, 'publicSettings']);

// Protected routes
Route::group(['middleware' => 'auth:api'], function () {
    // Auth routes
    Route::post('logout', [SimpleAuthController::class, 'logout']);
    Route::get('me', [SimpleAuthController::class, 'me']);
    
    // SSO token generation (requires API auth)
    Route::post('sso/generate-token', [SSOController::class, 'generateToken']);

    // User routes
    Route::get('user/profile', [UserController::class, 'profile']);
    Route::post('user/profile', [UserController::class, 'updateProfile']);
    Route::put('user/profile', [UserController::class, 'updateProfile']);
    Route::put('user/password', [UserController::class, 'changePassword']);
    Route::put('user/preferences', [UserController::class, 'updatePreferences']);
    Route::post('user/preferences', [UserController::class, 'updatePreferences']); // POST pour compatibilité
    Route::post('user/deactivate', [UserController::class, 'deactivateAccount']);
    Route::delete('user/delete', [UserController::class, 'deleteAccount']);

    // Two-Factor Authentication routes
    Route::get('user/two-factor/status', [App\Http\Controllers\Api\TwoFactorController::class, 'status']);
    Route::post('user/two-factor/generate', [App\Http\Controllers\Api\TwoFactorController::class, 'generate']);
    Route::post('user/two-factor/confirm', [App\Http\Controllers\Api\TwoFactorController::class, 'confirm']);
    Route::post('user/two-factor/disable', [App\Http\Controllers\Api\TwoFactorController::class, 'disable']);


    // Admin routes - Accessible only to super users
    Route::prefix('admin')->middleware(['super.user'])->group(function () {
        Route::get('dashboard', [App\Http\Controllers\Api\AdminController::class, 'dashboard']);
        Route::get('profile', [App\Http\Controllers\Api\AdminController::class, 'profile']);
        Route::post('profile', [App\Http\Controllers\Api\AdminController::class, 'updateProfile']);
        
        // User management
        Route::get('users', [App\Http\Controllers\Api\AdminController::class, 'users']);
        Route::get('users/{id}', [App\Http\Controllers\Api\AdminController::class, 'userDetails']);
        Route::put('users/{id}/status', [App\Http\Controllers\Api\AdminController::class, 'updateUserStatus']);
        Route::put('users/{id}/role', [App\Http\Controllers\Api\AdminController::class, 'updateUserRole']);
        Route::put('users/{id}', [App\Http\Controllers\Api\AdminController::class, 'updateUser']);
        Route::delete('users/{id}', [App\Http\Controllers\Api\AdminController::class, 'deleteUser']);
        
        // Session management
        Route::get('sessions', [App\Http\Controllers\Api\AdminController::class, 'sessions']);
        Route::delete('sessions/{id}', [App\Http\Controllers\Api\AdminController::class, 'revokeSession']);
        
        // System settings
        Route::get('settings', [App\Http\Controllers\Api\AdminController::class, 'settings']);
        Route::put('settings', [App\Http\Controllers\Api\AdminController::class, 'updateSettings']);
    });
});

// Health check
Route::get('health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'service' => 'Herime SSO'
    ]);
});
