<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\SystemSetting;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $maintenanceMode = SystemSetting::get('maintenance_mode', '0') === '1';
        
        if ($maintenanceMode) {
            // Allow access to admin routes and login
            $isAdminRoute = $request->is('admin/*') || $request->is('api/admin/*');
            $isLoginRoute = $request->is('login') || $request->is('api/login');
            $isHealthCheck = $request->is('up') || $request->is('api/health');
            // Autoriser l'accès aux paramètres publics (utilisés par le client pour gating UI)
            $isPublicSettings = $request->is('api/settings/public');
            
            // Check if user is authenticated and is a super user
            $user = $request->user();
            $isSuperUser = $user && $user->isSuperUser();
            
            if (!$isAdminRoute && !$isLoginRoute && !$isHealthCheck && !$isPublicSettings && !$isSuperUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le système est en mode maintenance. Veuillez réessayer plus tard.'
                ], 503);
            }
        }
        
        return $next($request);
    }
}
