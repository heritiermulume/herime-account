<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class AdminAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('admin')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé. Connexion administrateur requise.'
            ], 401);
        }

        $admin = Auth::guard('admin')->user();
        
        if (!$admin->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Compte administrateur désactivé.'
            ], 403);
        }

        return $next($request);
    }
}
