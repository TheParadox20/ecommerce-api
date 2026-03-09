<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shipment;
use App\Models\Order;

class ShipmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Shipment::with(['orders.orderDetail', 'orders.sales'])
            ->withCount('orders')
            ->withSum('orders', 'total');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('id', $search);
        }

        $shipments = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($shipments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'notes' => 'nullable|string',
            'orders' => 'required|array|min:1',
            'orders.*.id' => 'required|exists:orders,id',
            'orders.*.latitude' => 'required|numeric',
            'orders.*.longitude' => 'required|numeric',
        ]);

        $shipment = Shipment::create([
            'status' => 'pending',
            'notes' => $data['notes'] ?? null,
        ]);

        foreach ($data['orders'] as $orderData) {
            Order::where('id', $orderData['id'])->update([
                'shipment_id' => $shipment->id,
                'latitude' => $orderData['latitude'],
                'longitude' => $orderData['longitude'],
            ]);
        }

        $shipment->load(['orders.orderDetail', 'orders.sales']);

        return response()->json([
            'success' => true,
            'message' => 'Shipment created successfully',
            'data' => $shipment,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $shipment = Shipment::with(['orders.orderDetail', 'orders.sales.product.productImages'])
            ->withCount('orders')
            ->withSum('orders', 'total')
            ->findOrFail($id);

        return response()->json($shipment);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $shipment = Shipment::findOrFail($id);

        $data = $request->validate([
            'status' => 'nullable|string|in:pending,in_transit,delivered,cancelled',
            'notes' => 'nullable|string',
        ]);

        $shipment->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Shipment updated successfully',
            'data' => $shipment,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $shipment = Shipment::findOrFail($id);

        // Unassign orders from this shipment
        Order::where('shipment_id', $shipment->id)->update(['shipment_id' => null]);

        $shipment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Shipment deleted successfully',
        ]);
    }
}
