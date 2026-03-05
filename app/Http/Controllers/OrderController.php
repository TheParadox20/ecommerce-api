<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Sale;
use App\Models\OrderDetail;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'total' => 'required|numeric',
            'payment_method' => 'nullable|string',
            // 'payment_reference' => 'nullable|string',
            'sales' => 'required|array',
            'sales.*.id' => 'required|exists:products,id',
            'sales.*.quantity' => 'required|integer',
            'sales.*.price' => 'required|numeric',
            'order_details' => 'required|array',
            'order_details.full_name' => 'nullable|string',
            'order_details.phone' => 'required|string',
            'order_details.address' => 'nullable|string',
            'order_details.notes' => 'nullable|string',
        ]);
        $order = Order::create([
            // 'user_id' => $data['user_id'],
            'total' => $data['total'],
            'payment_method' => $data['payment_method'],
        ]);
        OrderDetail::create([
            'order_id' => $order->id,
            'full_name' => $data['order_details']['full_name'] ?? '',
            'phone' => $data['order_details']['phone'],
            'address' => $data['order_details']['address'],
            'notes' => $data['order_details']['notes'],
        ]);
        foreach ($data['sales'] as $sale) {
            Sale::create([
                'order_id' => $order->id,
                'product_id' => $sale['id'],
                'quantity' => $sale['quantity'],
                'price' => $sale['price'],
                'total' => $sale['price'] * $sale['quantity'],
            ]);
        }
        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data' => $order,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
