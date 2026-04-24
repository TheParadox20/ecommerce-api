<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\Commission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PartnerManagementController extends Controller
{
    /**
     * List all partners with role breakdown and basic stats.
     */
    public function index(Request $request)
    {
        $status = $request->input('status');

        $query = User::role(['distributor', 'influencer'])
            ->with(['roles', 'vouchers']);

        if ($status) {
            $query->where('status', $status);
        }

        $partners = $query->get();

        // Attach statistics to each partner
        $partners->transform(function ($partner) {
            $partner->role_names = $partner->getRoleNames();
            
            // Basic performance aggregation
            $partner->total_commissions = $partner->influencerCommissions()->sum('amount');
            $partner->pending_commissions = $partner->influencerCommissions()->where('status', 'pending')->sum('amount');
            $partner->paid_commissions = $partner->influencerCommissions()->where('status', 'paid')->sum('amount');
            
            // Order count via associated vouchers
            $partner->conversions_count = Order::whereHas('voucher', function ($q) use ($partner) {
                $q->where('influencer_id', $partner->id);
            })->count();

            return $partner;
        });

        return response()->json([
            'success' => true,
            'partners' => $partners,
            'summary' => [
                'total_partners' => $partners->count(),
                'distributors' => $partners->filter(fn($p) => $p->hasRole('distributor'))->count(),
                'influencers' => $partners->filter(fn($p) => $p->hasRole('influencer'))->count(),
                'total_payouts_pending' => $partners->sum('pending_commissions'),
            ]
        ]);
    }

    /**
     * Get detailed performance for a specific partner.
     */
    public function performance($id)
    {
        $partner = User::role(['distributor', 'influencer'])->findOrFail($id);
        
        $commissions = $partner->influencerCommissions()
            ->with('order:id,slug,total,created_at')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'partner' => [
                'id' => $partner->id,
                'name' => $partner->name,
                'role' => $partner->getRoleNames()->first(),
            ],
            'performance' => [
                'total_earned' => $commissions->sum('amount'),
                'history' => $commissions
            ]
        ]);
    }

    /**
     * Approve or reject a partner application.
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,rejected'
        ]);

        $partner = User::role(['distributor', 'influencer'])->findOrFail($id);
        
        $partner->update(['status' => $validated['status']]);
        
        // Auto-generate voucher if approved
        if ($validated['status'] === 'active') {
            $partner->generateInitialVoucher();
        }

        // Notify the partner of their status change
        $partner->notify(new \App\Notifications\ApplicationStatusChanged($partner, $validated['status']));

        return response()->json([
            'success' => true,
            'message' => "Partner application marked as {$validated['status']}.",
            'partner' => $partner
        ]);
    }
}
