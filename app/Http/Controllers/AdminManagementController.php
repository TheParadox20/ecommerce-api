<?php

namespace App\Http\Controllers;

use App\Models\Admin;
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
            'admins' => Admin::all(),
        ]);
    }

    /**
     * Store a new admin.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:admins,email',
            'phone' => 'required|string|unique:admins,phone',
            'password' => 'required|string|min:6',
            'role' => 'nullable|string|in:admin,superadmin',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $admin = Admin::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Admin created successfully.',
            'admin' => $admin,
        ], 201);
    }
}
