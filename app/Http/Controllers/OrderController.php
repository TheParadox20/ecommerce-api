<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Carbon\Carbon;
use App\Events\NewOrderPlaced;
use App\Models\Order;
use App\Models\Sale;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductVariation;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\DB;
use Exception;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Order::query()
            ->with([
                'orderDetail',
                'shipment',
                'sales' => function ($query) use ($request) {
                    $this->applySaleFilters($query, $request);
                    $query->with(['product.productImages', 'productVariation']);
                },
            ]);

        $this->applyOrderFilters($query, $request);

        $sort = $request->get('sort', 'newest');
        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'total_asc':
                $query->orderBy('total', 'asc');
                break;
            case 'total_desc':
                $query->orderBy('total', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $orders = $request->filled('per_page')
            ? $query->paginate((int) $request->get('per_page', 20))
            : $query->get();

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
            'order_details.email' => 'nullable|email', // Added
            'order_details.phone' => 'required|string',
            'order_details.address' => 'nullable|string',
            'order_details.notes' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'delivery_method' => 'nullable|string',
            'pickup_station' => 'nullable|string',
        ]);

        $token = $request->bearerToken();
        if ($token) {
            $accessToken = PersonalAccessToken::findToken($token);
            $user = $accessToken?->tokenable;

            if ($user) {
                $data['user_id'] = $user->id;
            }
        }

        try {
            $order = DB::transaction(function () use ($data) {
                // Check stock and apply pessimistic locking Before writing records
                foreach ($data['sales'] as $sale) {
                    if (!empty($sale['variation'])) {
                        $variation = ProductVariation::where('id', $sale['variation'])->lockForUpdate()->first();
                        if (!$variation || $variation->stock < $sale['quantity']) {
                            throw new Exception("Insufficient stock for product variation. Requested: {$sale['quantity']}, Available: " . ($variation ? $variation->stock : 0));
                        }
                        $variation->decrement('stock', $sale['quantity']);
                    } else {
                        $product = Product::where('id', $sale['id'])->lockForUpdate()->first();
                        if (!$product || $product->stock < $sale['quantity']) {
                            throw new Exception("Insufficient stock for product. Requested: {$sale['quantity']}, Available: " . ($product ? $product->stock : 0));
                        }
                        $product->decrement('stock', $sale['quantity']);
                    }
                }

                $order = Order::create([
                    'user_id' => $data['user_id'] ?? null,
                    'total' => $data['total'],
                    'payment_method' => $data['payment_method'],
                    'delivery_method' => $data['delivery_method'], // Corrected
                    'pickup_station' => $data['pickup_station'],  // Corrected
                    'expected_shipping_date' => Order::calculateShippingDate(), 
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                ]);

                OrderDetail::create([
                    'order_id' => $order->id,
                    'full_name' => $data['order_details']['full_name'] ?? '',
                    'email' => $data['order_details']['email'] ?? null, // Added
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

                return $order;
            });
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }

        NewOrderPlaced::dispatch($order);

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data' => $order,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $query = Order::query()
            ->with([
                'orderDetail',
                'shipment',
                'sales' => function ($query) use ($request) {
                    $this->applySaleFilters($query, $request);
                    $query->with(['product.productImages', 'productVariation']);
                },
            ]);

        $this->applyOrderFilters($query, $request);

        return response()->json($this->findOrder($query, $id));
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

    private function applyOrderFilters(Builder|Relation $query, Request $request): void
    {
        if ($request->filled('id')) {
            $query->whereKey($request->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('shipment_id')) {
            $query->where('shipment_id', $request->shipment_id);
        }

        if ($request->boolean('unassigned')) {
            $query->whereNull('shipment_id');
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->has('has_coordinates')) {
            $request->boolean('has_coordinates')
                ? $query->whereNotNull('latitude')->whereNotNull('longitude')
                : $query->where(function ($query) {
                    $query->whereNull('latitude')->orWhereNull('longitude');
                });
        }

        if ($request->filled('min_total')) {
            $query->where('total', '>=', $request->min_total);
        }

        if ($request->filled('max_total')) {
            $query->where('total', '<=', $request->max_total);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', Carbon::parse($request->date_from)->startOfDay());
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay());
        }

        if ($request->filled('product_id') || $request->filled('product_variation_id')) {
            $query->whereHas('sales', function ($salesQuery) use ($request) {
                $this->applySaleFilters($salesQuery, $request);
            });
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($query) use ($search) {
                $query->where('id', $search)
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('payment_reference', 'like', "%{$search}%")
                    ->orWhereHas('orderDetail', function ($detailQuery) use ($search) {
                        $detailQuery->where('full_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('address', 'like', "%{$search}%");
                    })
                    ->orWhereHas('sales.product', function ($productQuery) use ($search) {
                        $productQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }
    }

    private function applySaleFilters(Builder|Relation $query, Request $request): void
    {
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('product_variation_id')) {
            $query->where('product_variation_id', $request->product_variation_id);
        }
    }

    private function findOrder(Builder $query, string $id): Order
    {
        if (is_numeric($id)) {
            return $query->whereKey($id)->firstOrFail();
        }

        return $query->where('slug', $id)->firstOrFail();
    }
}
