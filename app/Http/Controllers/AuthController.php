<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Account created successfully',
            'auth_token' => $token,
            'user' => $user,
            'name' => $user->name,
            'phone' => $user->phone,
            'balance' => 0,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt(['phone' => $request->phone, 'password' => $request->password])) {
            throw ValidationException::withMessages([
                'phone' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = User::where('phone', $request->phone)->firstOrFail();
        
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'auth_token' => $token,
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        
        if ($user && $user->currentAccessToken() && method_exists($user->currentAccessToken(), 'delete')) {
            $user->currentAccessToken()->delete();
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }
}
