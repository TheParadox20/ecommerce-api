<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    /**
     * List all users with search and pagination.
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Exclude accounts with administrative roles
        $query->whereNotIn('role', ['admin', 'superadmin', 'super_admin']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'users' => $users,
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
