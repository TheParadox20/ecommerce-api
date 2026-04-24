<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\PartnerApplicationSubmitted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;

class RegistrationController extends Controller
{
    /**
     * Show interest as a distributor.
     */
    public function distributorSignup(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:8|confirmed',
            'business_name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'tax_id' => 'nullable|string|max:50',
            'brands_interested' => 'nullable|array',
            'brands_interested.*' => 'string',
            'products_interested' => 'nullable|string',
            'estimated_quantity' => 'nullable|string',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'status' => 'pending',
            'role' => 'distributor',
            'profile_details' => [
                'business_name' => $validated['business_name'],
                'location' => $validated['location'],
                'tax_id' => $validated['tax_id'] ?? null,
                'brands_interested' => $validated['brands_interested'] ?? [],
                'products_interested' => $validated['products_interested'] ?? null,
                'estimated_quantity' => $validated['estimated_quantity'] ?? null,
                'applied_at' => now()->toDateTimeString(),
            ],
        ]);

        $user->assignRole('distributor');

        // Notify Admins
        $admins = User::role('super_admin')->get();
        Notification::send($admins, new PartnerApplicationSubmitted($user));

        return response()->json([
            'success' => true,
            'message' => 'Application submitted successfully. Our team will review your distributor status shortly.',
        ], 201);
    }

    /**
     * Show interest as an influencer.
     */
    public function influencerSignup(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:8|confirmed',
            'social_handles' => 'required|array|min:1',
            'niche' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'status' => 'pending',
            'role' => 'influencer',
            'profile_details' => [
                'social_handles' => $validated['social_handles'],
                'niche' => $validated['niche'] ?? null,
                'applied_at' => now()->toDateTimeString(),
            ],
        ]);

        $user->assignRole('influencer');

        // Notify Admins
        $admins = User::role('super_admin')->get();
        Notification::send($admins, new PartnerApplicationSubmitted($user));

        return response()->json([
            'success' => true,
            'message' => 'Application submitted successfully. We will review your social presence and get back to you soon!',
        ], 201);
    }
}
