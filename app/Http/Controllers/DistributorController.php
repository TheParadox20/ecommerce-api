<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DistributorController extends Controller
{
    /**
     * Overview of stock allocated to the authenticated distributor.
     *
     * GET /distributor/stock
     */
    public function stockOverview(Request $request)
    {
        // TODO: Implement distributor-specific stock allocation logic.
        // For now we return the current user's identity and a placeholder.
        $user = $request->user();

        return response()->json([
            'success' => true,
            'message' => 'Distributor stock overview (coming soon).',
            'distributor' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
            ],
            'stock' => [],
        ]);
    }

    /**
     * Update stock details for the distributor.
     *
     * PUT /distributor/stock/{id}
     */
    public function updateStock(Request $request, $id)
    {
        // TODO: Implement stock update logic per distributor.
        return response()->json([
            'success' => true,
            'message' => "Stock item {$id} update (coming soon).",
        ]);
    }

    /**
     * Orders allocated to this distributor.
     *
     * GET /distributor/orders
     */
    public function orders(Request $request)
    {
        // TODO: Filter orders by distributor allocation.
        return response()->json([
            'success' => true,
            'message' => 'Distributor orders (coming soon).',
            'orders'  => [],
        ]);
    }
}
