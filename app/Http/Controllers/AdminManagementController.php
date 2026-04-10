<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminManagementController extends Controller
{
    /**
     * List all admins.
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'admins' => User::whereIn('role', ['admin', 'superadmin', 'super_admin'])->get(),
        ]);
    }

    /**
     * Store a new admin.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:6',
            'role' => 'nullable|string|in:admin,superadmin',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['role'] = $validated['role'] ?? 'admin';

        $admin = User::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Admin created successfully.',
            'admin' => $admin,
        ], 201);
    }

    /**
     * Update an admin's password.
     */
    public function updatePassword(Request $request, $id)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:8',
        ]);

        $admin = User::whereIn('role', ['admin', 'superadmin', 'super_admin'])->findOrFail($id);
        $admin->update([
            'password' => Hash::make($validated['password'])
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully.',
        ]);
    }

    /**
     * Remove an admin account.
     */
    public function destroy(Request $request, $id)
    {
        $admin = User::whereIn('role', ['admin', 'superadmin', 'super_admin'])->findOrFail($id);
        
        // Prevent self-deletion
        if ($request->user() && $request->user()->id == $id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot revoke your own administrative access.',
            ], 403);
        }

        $admin->delete();

        return response()->json([
            'success' => true,
            'message' => 'Administrative access revoked successfully.',
        ]);
    }
}
