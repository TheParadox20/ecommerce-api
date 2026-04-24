<?php

namespace App\Http\Controllers;

use App\Models\DistributorInvite;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DistributorInviteController extends Controller
{
    /**
     * (super_admin only) Issue a distributor invite.
     *
     * POST /admin/distributors/invite
     */
    public function invite(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:users,email|unique:distributor_invites,email',
            'expires_in_days' => 'nullable|integer|min:1|max:30',
        ]);

        // Revoke any existing pending invite for the same email
        DistributorInvite::where('email', $validated['email'])
            ->whereNull('accepted_at')
            ->delete();

        $token = Str::random(64);
        $expiresInDays = $validated['expires_in_days'] ?? 7;

        $invite = DistributorInvite::create([
            'email'      => $validated['email'],
            'token'      => hash('sha256', $token),
            'invited_by' => $request->user()->id,
            'expires_at' => now()->addDays($expiresInDays),
        ]);

        return response()->json([
            'success'    => true,
            'message'    => "Distributor invite sent to {$validated['email']}.",
            'invite_token' => $token,          // raw token — send via email in production
            'expires_at' => $invite->expires_at,
        ], 201);
    }

    /**
     * Validate an invite token (public) — used by the frontend before showing the form.
     *
     * GET /distributor/verify-invite/{token}
     */
    public function verify(string $token)
    {
        $invite = DistributorInvite::where('token', hash('sha256', $token))
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $invite) {
            return response()->json([
                'success' => false,
                'message' => 'This invite link is invalid or has expired.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'email'   => $invite->email,
            'expires_at' => $invite->expires_at,
        ]);
    }

    /**
     * Register as a distributor using a valid invite token.
     *
     * POST /distributor/register
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'invite_token' => 'required|string',
            'name'         => 'required|string|max:255',
            'phone'        => 'required|string|max:20|unique:users,phone',
            'password'     => 'required|string|min:8|confirmed',
            // Optional business fields
            'business_name' => 'nullable|string|max:255',
        ]);

        // Validate the token
        $invite = DistributorInvite::where('token', hash('sha256', $validated['invite_token']))
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $invite) {
            return response()->json([
                'success' => false,
                'message' => 'This invite link is invalid or has already been used.',
            ], 422);
        }

        // Create the distributor user
        $user = User::create([
            'name'     => $validated['name'],
            'phone'    => $validated['phone'],
            'email'    => $invite->email,   // email comes from the invite
            'password' => Hash::make($validated['password']),
            'role'     => 'distributor',    // legacy field kept during transition
        ]);

        // Assign Spatie role
        $user->assignRole('distributor');

        // Mark invite as accepted
        $invite->update(['accepted_at' => now()]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Distributor account created successfully.',
            'token'   => $token,
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
            ],
        ], 201);
    }

    /**
     * List all pending & accepted distributor invites (super_admin only).
     *
     * GET /admin/distributors/invites
     */
    public function index()
    {
        $invites = DistributorInvite::with('inviter:id,name,email')
            ->latest()
            ->get()
            ->map(fn ($invite) => [
                'id'         => $invite->id,
                'email'      => $invite->email,
                'invited_by' => $invite->inviter?->name,
                'status'     => $invite->isAccepted() ? 'accepted' : ($invite->expires_at->isPast() ? 'expired' : 'pending'),
                'expires_at' => $invite->expires_at,
                'accepted_at'=> $invite->accepted_at,
            ]);

        return response()->json(['success' => true, 'invites' => $invites]);
    }

    /**
     * Revoke a pending invite (super_admin only).
     *
     * DELETE /admin/distributors/invites/{id}
     */
    public function destroy($id)
    {
        $invite = DistributorInvite::findOrFail($id);

        if ($invite->isAccepted()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot revoke an already-accepted invite.',
            ], 422);
        }

        $invite->delete();

        return response()->json(['success' => true, 'message' => 'Invite revoked.']);
    }
}
