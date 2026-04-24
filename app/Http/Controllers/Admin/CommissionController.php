<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommissionController extends Controller
{
    /**
     * List commissions for admin review.
     */
    public function index(Request $request)
    {
        $query = Commission::with(['order.orderDetail', 'influencer']);

        if ($request->filled('influencer_id')) {
            $query->where('influencer_id', $request->influencer_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $commissions = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $commissions
        ]);
    }

    /**
     * Process a payout for an influencer.
     */
    public function processPayout(Request $request)
    {
        $validated = $request->validate([
            'influencer_id' => 'required|exists:users,id',
            'commission_ids' => 'required|array',
            'commission_ids.*' => 'exists:commissions,id',
            'payment_method' => 'required|string',
            'reference' => 'required|string',
        ]);

        try {
            return DB::transaction(function () use ($validated) {
                $commissions = Commission::whereIn('id', $validated['commission_ids'])
                    ->where('influencer_id', $validated['influencer_id'])
                    ->where('status', 'earned') // Only earned commissions can be paid
                    ->lockForUpdate()
                    ->get();

                if ($commissions->isEmpty()) {
                    throw new \Exception('No eligible earned commissions found for payout.');
                }

                $totalAmount = $commissions->sum('amount');

                $payout = Payout::create([
                    'influencer_id' => $validated['influencer_id'],
                    'amount' => $totalAmount,
                    'payment_method' => $validated['payment_method'],
                    'reference' => $validated['reference'],
                    'status' => 'paid',
                ]);

                // Update commissions
                Commission::whereIn('id', $commissions->pluck('id'))
                    ->update([
                        'status' => 'paid',
                        'payout_id' => $payout->id
                    ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Payout processed successfully',
                    'data' => $payout
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get summary of commissions grouped by influencer.
     */
    public function summary()
    {
        $summary = User::role('influencer')
            ->withCount([
                'influencerCommissions as total_count',
                'influencerCommissions as pending_amount' => function($query) {
                    $query->where('status', 'pending')->select(DB::raw('SUM(amount)'));
                },
                'influencerCommissions as earned_amount' => function($query) {
                    $query->where('status', 'earned')->select(DB::raw('SUM(amount)'));
                },
                'influencerCommissions as paid_amount' => function($query) {
                    $query->where('status', 'paid')->select(DB::raw('SUM(amount)'));
                }
            ])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }
}
