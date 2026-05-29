<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Carbon\Carbon;
use App\Models\Order;
use App\Events\OrderPaymentSuccessful;

class SalesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Order::query()
            ->with([
                'sales' => function ($query) use ($request) {
                    $this->applySaleFilters($query, $request);
                    $query->with(['product.productImages', 'productVariation']);
                },
                'orderDetail',
                'shipment',
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
            case 'shipping_asc':
                $query->orderBy('expected_shipping_date', 'asc');
                break;
            case 'shipping_desc':
                $query->orderBy('expected_shipping_date', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $orders = $query->paginate($request->get('per_page', 20));

        return response()->json($orders);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $query = Order::query()
            ->with([
                'sales' => function ($query) use ($request) {
                    $this->applySaleFilters($query, $request);
                    $query->with(['product.productImages', 'productVariation']);
                },
                'orderDetail',
                'shipment',
            ]);

        $this->applyOrderFilters($query, $request);

        $order = $this->findOrder($query, $id);

        return response()->json($order);
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
        $order = Order::findOrFail($id);

        \Illuminate\Support\Facades\DB::transaction(function () use ($order) {
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

    public function verifyPayment(string $id)
    {
        $order = Order::findOrFail($id);

        if ($order->payment_status === 'success') {
            return response()->json(['message' => 'Order is already marked as paid'], 400);
        }

        $order->update([
            'payment_status' => 'success'
        ]);

        // Trigger the exact same notification process as the automatic STK callback
        OrderPaymentSuccessful::dispatch($order);

        return response()->json([
            'success' => true,
            'message' => 'Payment verified successfully. Customer has been notified.'
        ]);
    }

    private function applyOrderFilters(Builder|Relation $query, Request $request): void
    {
        // ─── Sales Intelligence baseline filter ───────────────────────────────────
        // Only surface orders that are paid (payment_status = success) OR are
        // awaiting manual payment verification (payment_status = pending AND a
        // payment_reference/receipt code has been submitted). This hides abandoned
        // test transactions from the sales dashboard by default.
        if (!$request->filled('payment_status')) {
            $query->where(function ($q) {
                $q->where('payment_status', 'success')
                  ->orWhere(function ($sub) {
                      $sub->where('payment_status', 'pending')
                          ->whereNotNull('payment_reference');
                  });
            });
        }
        // ─────────────────────────────────────────────────────────────────────────

        if ($request->filled('id')) {
            $query->whereKey($request->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            if ($request->payment_status === 'pending') {
                // "Pending Verification" = pending status WITH a receipt code
                $query->where('payment_status', 'pending')
                      ->whereNotNull('payment_reference');
            } else {
                $query->where('payment_status', $request->payment_status);
            }
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
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
            $column = str_contains($request->get('sort'), 'shipping') ? 'expected_shipping_date' : 'created_at';
            $query->where($column, '>=', Carbon::parse($request->date_from)->startOfDay());
        }

        if ($request->filled('date_to')) {
            $column = str_contains($request->get('sort'), 'shipping') ? 'expected_shipping_date' : 'created_at';
            $query->where($column, '<=', Carbon::parse($request->date_to)->endOfDay());
        }

        if ($request->filled('shipment_status')) {
            $query->whereHas('shipment', function ($shipmentQuery) use ($request) {
                $shipmentQuery->where('status', $request->shipment_status);
            });
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
                    ->orWhere('payment_reference', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
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
