<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\ApplicationStatusChanged;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    /**
     * List all pending applications (Distributors or Influencers).
     */
    public function index(Request $request)
    {
        $query = User::where('status', 'pending');

        if ($request->has('role')) {
            $query->role($request->role);
        }

        $applications = $query->latest()->get();

        return response()->json([
            'success' => true,
            'applications' => $applications,
        ]);
    }

    /**
     * Approve an application.
     */
    public function approve($id)
    {
        $user = User::findOrFail($id);

        if ($user->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'This account is not in a pending state.',
            ], 422);
        }

        $user->update([
            'status' => 'active',
        ]);

        $user->notify(new ApplicationStatusChanged($user, 'active'));

        return response()->json([
            'success' => true,
            'message' => "Application for {$user->name} has been approved successfully.",
            'user' => $user,
        ]);
    }

    /**
     * Reject/Delete an application.
     */
    public function reject($id)
    {
        $user = User::findOrFail($id);

        if ($user->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending applications can be rejected.',
            ], 422);
        }

        // We can either delete or mark as rejected. Deleting is cleaner for interest forms.
        $user->notify(new ApplicationStatusChanged($user, 'rejected'));
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Application has been rejected and removed.',
        ]);
    }

    /**
     * Get details of a specific application.
     */
    public function show($id)
    {
        $user = User::findOrFail($id);

        return response()->json([
            'success' => true,
            'application' => $user,
        ]);
    }
}
