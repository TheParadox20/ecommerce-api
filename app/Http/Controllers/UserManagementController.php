<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    /**
     * List all users with search and pagination.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $query = User::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(20);

        // Transform to include roles from Spatie
        $users->getCollection()->transform(function ($user) {
            $user->role_names = $user->getRoleNames();
            return $user;
        });

        return response()->json([
            'success' => true,
            'users'   => $users,
        ]);
    }

    /**
     * Assign a role to a user (super_admin only).
     */
    public function assignRole(Request $request, $id)
    {
        $validated = $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        $user = User::findOrFail($id);
        
        // Sync multiple roles via Spatie
        $user->syncRoles($validated['roles']);

        // Maintain legacy role field for compatibility (pick first role or default)
        $primaryRole = $validated['roles'][0] ?? 'user';
        $legacyMap = [
            'super_admin' => 'superadmin',
            'admin' => 'admin',
            'distributor' => 'distributor',
            'influencer' => 'influencer',
            'buyer' => 'user'
        ];
        
        $user->update(['role' => $legacyMap[$primaryRole] ?? 'user']);

        return response()->json([
            'success' => true,
            'message' => "User roles updated successfully.",
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'roles' => $user->getRoleNames(),
            ],
        ]);
    }

    /**
     * Deactivate a user.
     */
    public function deactivate($id)
    {
        $user = User::findOrFail($id);
        $user->update(['status' => 'inactive']);

        return response()->json([
            'success' => true,
            'message' => 'User deactivated successfully.',
            'user' => $user,
        ]);
    }

    /**
     * Reactivate a user.
     */
    public function reactivate($id)
    {
        $user = User::findOrFail($id);
        $user->update(['status' => 'active']);

        return response()->json([
            'success' => true,
            'message' => 'User reactivated successfully.',
            'user' => $user,
        ]);
    }
}
