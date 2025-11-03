<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Admin dashboard
     */
    public function dashboard(): JsonResponse
    {
        $user = Auth::user();
        
        // Statistiques générales
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $inactiveUsers = User::where('is_active', false)->count();
        $superUsers = User::where('role', 'super_user')->count();
        
        // Sessions actives
        $activeSessions = UserSession::where('is_current', true)->count();
        $totalSessions = UserSession::count();
        
        // Utilisateurs récents (derniers 7 jours)
        $recentUsers = User::where('created_at', '>=', now()->subDays(7))->count();
        
        // Sessions par jour (derniers 7 jours)
        $sessionsByDay = UserSession::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
        ->where('created_at', '>=', now()->subDays(7))
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'total_users' => $totalUsers,
                    'active_users' => $activeUsers,
                    'inactive_users' => $inactiveUsers,
                    'super_users' => $superUsers,
                    'active_sessions' => $activeSessions,
                    'total_sessions' => $totalSessions,
                    'recent_users' => $recentUsers,
                ],
                'sessions_by_day' => $sessionsByDay,
                'current_admin' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ]
            ]
        ]);
    }

    /**
     * Admin profile
     */
    public function profile(): JsonResponse
    {
        $user = Auth::user();
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'company' => $user->company,
                'position' => $user->position,
                'role' => $user->role,
                'is_active' => $user->is_active,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]
        ]);
    }

    /**
     * Update admin profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update($request->only(['name', 'email', 'phone', 'company', 'position']));

        return response()->json([
            'success' => true,
            'message' => 'Profil mis à jour avec succès',
            'data' => $user->fresh()
        ]);
    }

    /**
     * Get all users
     */
    public function users(Request $request): JsonResponse
    {
        $query = User::query();
        
        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%");
            });
        }
        
        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }
        
        // Pagination
        $perPage = $request->get('per_page', 15);
        $users = $query->orderBy('created_at', 'desc')->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Get user details
     */
    public function userDetails($id): JsonResponse
    {
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }
        
        // Get user sessions
        $sessions = UserSession::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'sessions' => $sessions
            ]
        ]);
    }

    /**
     * Update user status
     */
    public function updateUserStatus(Request $request, $id): JsonResponse
    {
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $user->update(['is_active' => $request->is_active]);
        
        return response()->json([
            'success' => true,
            'message' => 'Statut utilisateur mis à jour avec succès',
            'data' => $user->fresh()
        ]);
    }

    /**
     * Delete user
     */
    public function deleteUser($id): JsonResponse
    {
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }
        
        // Prevent deleting super users
        if ($user->isSuperUser()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer un super utilisateur'
            ], 403);
        }
        
        $user->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Utilisateur supprimé avec succès'
        ]);
    }

    /**
     * Get all sessions
     */
    public function sessions(Request $request): JsonResponse
    {
        $query = UserSession::with('user');
        
        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_current', $request->status === 'active');
        }
        
        // Pagination
        $perPage = $request->get('per_page', 15);
        $sessions = $query->orderBy('created_at', 'desc')->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }

    /**
     * Revoke session
     */
    public function revokeSession($id): JsonResponse
    {
        $session = UserSession::find($id);
        
        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session non trouvée'
            ], 404);
        }
        
        $session->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Session révoquée avec succès'
        ]);
    }

    /**
     * Get system settings
     */
    public function settings(): JsonResponse
    {
        // For now, return basic settings
        // In a real app, these would come from a settings table or config
        $settings = [
            'app_name' => config('app.name'),
            'app_url' => config('app.url'),
            'maintenance_mode' => false,
            'registration_enabled' => true,
            'max_sessions_per_user' => 5,
            'session_timeout' => 24, // hours
        ];
        
        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    /**
     * Update system settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'maintenance_mode' => 'boolean',
            'registration_enabled' => 'boolean',
            'max_sessions_per_user' => 'integer|min:1|max:10',
            'session_timeout' => 'integer|min:1|max:168', // max 1 week
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // In a real app, you would save these to a settings table
        // For now, just return success
        
        return response()->json([
            'success' => true,
            'message' => 'Paramètres mis à jour avec succès',
            'data' => $request->all()
        ]);
    }
}