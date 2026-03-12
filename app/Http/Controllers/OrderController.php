<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Sale;
use App\Models\OrderDetail;
use GuzzleHttp\Client;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Order::with(['orderDetail', 'sales.product']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('unassigned')) {
            $query->whereNull('shipment_id');
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        return response()->json($orders);
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
            'sales.*.variation' => 'nullable|exists:product_variations,id',
            'sales.*.quantity' => 'required|integer',
            'sales.*.price' => 'required|numeric',
            'order_details' => 'required|array',
            'order_details.full_name' => 'nullable|string',
            'order_details.phone' => 'required|string',
            'order_details.address' => 'nullable|string',
            'order_details.notes' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);
        $order = Order::create([
            // 'user_id' => $data['user_id'],
            'total' => $data['total'],
            'payment_method' => $data['payment_method'],
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
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
                'product_variation_id' => $sale['variation'] ?? null,
                'quantity' => $sale['quantity'],
                'price' => $sale['price'],
                'total' => $sale['price'] * $sale['quantity'],
            ]);
        }

        $client = new Client();
        $endpoint = 'https://api2.tiaraconnect.io/api/messaging/sendsms'; // set the Endpoint provided.
        $apiKey = config('app.TIARA_KEY');
        $from = 'TIARACONECT';
        $message = $order->slug . ' - New order placed. Total: ' . $order->total . ' KES. Please check the admin panel for details.';
        $to = '254791210705, 254701259936'; // set a valid number using format '2547********' or '2541********'

        $requestData = [  
            'to' => $to,
            'from' => $from,
            'message' => $message,
        ];
    
        try {
            $response = $client->post($endpoint, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $apiKey,
                ],
                'json' => $requestData
            ]);
    
            $responseBody = $response->getBody()->getContents();
            logger()->info("request|msisdn: $to|response: $responseBody | url: $endpoint");
        } catch (\Exception $e) {
            logger()->error("{$apiKey} :: request|msisdn: $to|error: " . $e->getMessage() . " | url: $endpoint");
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
        $order = Order::findOrFail($id);

        $data = $request->validate([
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'status' => 'nullable|string',
        ]);

        $order->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully',
            'data' => $order,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
