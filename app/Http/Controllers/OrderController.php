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
use App\Models\WebsiteSetting;
use App\Models\Commission;
use App\Notifications\CommissionEarned;
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

        $perPage = $request->filled('per_page') ? min(100, (int) $request->get('per_page')) : 20;
        $orders = $query->paginate($perPage);

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
            'shipping' => 'nullable|numeric',
            'delivery_zone' => 'nullable|string',
            'delivery_county_id' => 'nullable|exists:locations,id',
        ]);

        $orderType = 'b2c';
        $token = $request->bearerToken();
        if ($token) {
            $accessToken = PersonalAccessToken::findToken($token);
            $user = $accessToken?->tokenable;

            if ($user) {
                $data['user_id'] = $user->id;
                if ($user->hasRole('distributor')) {
                    $orderType = 'b2b';
                }
            }
        }

        try {
            $order = DB::transaction(function () use ($data, $request, $orderType) {
                // Initialize constraints tracking
                $brandAmounts = [];
                $productIds = collect($data['sales'])->pluck('id')->unique()->toArray();
                $products = \App\Models\Product::with('brand')->whereIn('id', $productIds)->get()->keyBy('id');
                
                // Track bulk items and brand limits
                foreach ($data['sales'] as $sale) {
                    $product = $products->get($sale['id']);
                    if (!$product) continue;

                    // Brand Amount Tracking
                    if ($product->brand_id && $product->brand) {
                        if (!isset($brandAmounts[$product->brand_id])) {
                            $brandAmounts[$product->brand_id] = [
                                'name' => $product->brand->name,
                                'total_amount' => 0,
                                'max_amount' => $product->brand->max_order_amount
                            ];
                        }
                        $brandAmounts[$product->brand_id]['total_amount'] += ($sale['price'] * $sale['quantity']);
                    }

                    // Bulk items MOQ Tracking
                    if (!empty($sale['variation'])) {
                        $variation = \App\Models\ProductVariation::find($sale['variation']);
                        if ($variation && $variation->is_bulk && $variation->min_order_quantity > 0) {
                            if ($sale['quantity'] < $variation->min_order_quantity) {
                                throw new Exception("Wholesale minimum order quantity for {$product->name} is {$variation->min_order_quantity}.");
                            }
                        }
                    }
                }

                // Enforce Brand Max Amounts limits
                foreach ($brandAmounts as $brandData) {
                    if ($brandData['max_amount'] > 0 && $brandData['total_amount'] > $brandData['max_amount']) {
                        throw new Exception("Maximum order limit exceeded for brand {$brandData['name']}. Limit is KES {$brandData['max_amount']}.");
                    }
                }

                $calculatedTotal = 0;
                $processedSales = [];

                // Check stock and apply pessimistic locking Before writing records
                foreach ($data['sales'] as $sale) {
                    $actualPrice = 0;
                    if (!empty($sale['variation'])) {
                        $variation = ProductVariation::where('id', $sale['variation'])->lockForUpdate()->first();
                        if (!$variation || $variation->stock < $sale['quantity']) {
                            throw new Exception("Insufficient stock for product variation. Requested: {$sale['quantity']}, Available: " . ($variation ? $variation->stock : 0));
                        }
                        $variation->decrement('stock', $sale['quantity']);
                        // Apply variation-level discount if present
                        $discount = floatval($variation->discount ?? 0);
                        $actualPrice = max(0, floatval($variation->price) - $discount);
                    } else {
                        $product = Product::where('id', $sale['id'])->lockForUpdate()->first();
                        if (!$product || $product->stock < $sale['quantity']) {
                            throw new Exception("Insufficient stock for product. Requested: {$sale['quantity']}, Available: " . ($product ? $product->stock : 0));
                        }
                        $product->decrement('stock', $sale['quantity']);
                        // Apply product-level discount if present
                        $discount = floatval($product->discount ?? 0);
                        $actualPrice = max(0, floatval($product->price) - $discount);
                    }
                    
                    $processedSales[] = [
                        'id' => $sale['id'],
                        'variation' => $sale['variation'] ?? null,
                        'quantity' => $sale['quantity'],
                        'price' => $actualPrice,
                    ];
                    
                    $calculatedTotal += ($actualPrice * $sale['quantity']);
                }

                $calculatedTotal += ($data['shipping'] ?? 0);

                // Apply voucher discount if present
                if ($request->filled('voucher_id')) {
                    $voucher = \App\Models\Voucher::find($request->voucher_id);
                    if ($voucher && $voucher->status === 'active') {
                        if ($voucher->discount_type === 'percentage') {
                            $discountAmount = $calculatedTotal * ($voucher->discount_amount / 100);
                            $calculatedTotal -= $discountAmount;
                        } elseif ($voucher->discount_type === 'fixed') {
                            $calculatedTotal -= $voucher->discount_amount;
                        }
                    }
                }
                
                $calculatedTotal = max(0, $calculatedTotal);

                $order = Order::create([
                    'user_id' => $data['user_id'] ?? null,
                    'total' => $calculatedTotal, // Securely computed total
                    'payment_method' => $data['payment_method'],
                    'delivery_method' => $data['delivery_method'] ?? null,
                    'pickup_station' => $data['pickup_station'] ?? null,
                    'expected_shipping_date' => Order::calculateShippingDate(), 
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'order_type' => $orderType,
                    'shipping' => $data['shipping'] ?? 0,
                    'delivery_zone' => $data['delivery_zone'] ?? null,
                ]);

                // Auto-save unknown town/urban center to logistics zones database for future reference
                if (!empty($data['delivery_county_id']) && !empty($data['delivery_zone'])) {
                    $countyId = $data['delivery_county_id'];
                    $townName = trim($data['delivery_zone']);
                    
                    $existingTown = \App\Models\Location::where('parent_id', $countyId)
                        ->whereRaw('LOWER(name) = ?', [mb_strtolower($townName)])
                        ->first();

                    if (!$existingTown) {
                        $parentCounty = \App\Models\Location::find($countyId);
                        \App\Models\Location::create([
                            'name' => $townName,
                            'short_name' => $townName,
                            'parent_id' => $countyId,
                            'delivery_fee' => $data['shipping'] ?? ($parentCounty ? $parentCounty->delivery_fee : null),
                        ]);
                    }
                }

                OrderDetail::create([
                    'order_id' => $order->id,
                    'full_name' => $data['order_details']['full_name'] ?? '',
                    'email' => $data['order_details']['email'] ?? null, 
                    'phone' => $data['order_details']['phone'],
                    'address' => $data['order_details']['address'],
                    'notes' => $data['order_details']['notes'],
                ]);

                foreach ($processedSales as $sale) {
                    Sale::create([
                        'order_id' => $order->id,
                        'product_id' => $sale['id'],
                        'product_variation_id' => $sale['variation'],
                        'quantity' => $sale['quantity'],
                        'price' => $sale['price'],
                        'total' => $sale['price'] * $sale['quantity'],
                    ]);
                }

                // Handle Influencer Commission
                if ($request->filled('voucher_id')) {
                    $order->update(['voucher_id' => $request->voucher_id]);
                    $order->load('voucher');
                    
                    if ($order->voucher && $order->voucher->influencer_id) {
                        $rate = WebsiteSetting::where('key', 'influencer_commission_rate')->first()?->value ?? 5;
                        $commissionAmount = ($order->total * ($rate / 100));
                        
                        $commission = Commission::create([
                            'order_id' => $order->id,
                            'influencer_id' => $order->voucher->influencer_id,
                            'amount' => $commissionAmount,
                            'status' => 'pending',
                        ]);

                        // Notify Influencer
                        $order->voucher->influencer->notify(new CommissionEarned($commission));
                    }
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

        // If order is completed, mark commission as earned
        if ($order->status === 'completed' && $order->commission) {
            $order->commission->update(['status' => 'earned']);
        }

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
        $order = Order::findOrFail($id);

        DB::transaction(function () use ($order) {
            // Delete dependent records
            $order->sales()->delete();
            $order->orderDetail()->delete();
            $order->commission()->delete();
            
            // Delete the order itself
            $order->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Order deleted successfully'
        ]);
    }

    private function applyOrderFilters(Builder|Relation $query, Request $request): void
    {
        if ($request->filled('id')) {
            $query->whereKey($request->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('order_type')) {
            $query->where('order_type', $request->order_type);
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
