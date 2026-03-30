<?php

namespace App\Http\Controllers;

use App\Events\NewShipmentCreated;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Carbon\Carbon;
use App\Models\Shipment;
use App\Models\Order;

class ShipmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $orderRelation = function ($query) use ($request) {
            $this->applyOrderFilters($query, $request);
            $query->with(['orderDetail', 'sales.product.productImages', 'sales.productVariation']);
        };

        $query = Shipment::query()
            ->with(['orders' => $orderRelation])
            ->withCount(['orders as orders_count' => function ($query) use ($request) {
                $this->applyOrderFilters($query, $request);
            }])
            ->withSum(['orders as orders_sum_total' => function ($query) use ($request) {
                $this->applyOrderFilters($query, $request);
            }], 'total');

        $this->applyShipmentFilters($query, $request);

        $sort = $request->get('sort', 'newest');
        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $shipments = $query->paginate((int) $request->get('per_page', 20));

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

        NewShipmentCreated::dispatch($shipment);

        return response()->json([
            'success' => true,
            'message' => 'Shipment created successfully',
            'data' => $shipment,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $query = Shipment::query()
            ->with(['orders' => function ($query) use ($request) {
                $this->applyOrderFilters($query, $request);
                $query->with(['orderDetail', 'sales.product.productImages', 'sales.productVariation']);
            }])
            ->withCount(['orders as orders_count' => function ($query) use ($request) {
                $this->applyOrderFilters($query, $request);
            }])
            ->withSum(['orders as orders_sum_total' => function ($query) use ($request) {
                $this->applyOrderFilters($query, $request);
            }], 'total');

        $this->applyShipmentFilters($query, $request);

        $shipment = $query->findOrFail($id);

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

    private function applyShipmentFilters(Builder|Relation $query, Request $request): void
    {
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', Carbon::parse($request->date_from)->startOfDay());
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay());
        }

        if ($request->filled('min_total')) {
            $query->having('orders_sum_total', '>=', $request->min_total);
        }

        if ($request->filled('max_total')) {
            $query->having('orders_sum_total', '<=', $request->max_total);
        }

        if (
            $request->filled('order_id') ||
            $request->filled('order_status') ||
            $request->filled('product_id') ||
            $request->filled('product_variation_id') ||
            $request->filled('search') ||
            $request->has('has_coordinates')
        ) {
            $query->whereHas('orders', function ($ordersQuery) use ($request) {
                $this->applyOrderFilters($ordersQuery, $request);
            });
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($query) use ($search) {
                $query->where('id', $search)
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('orders', function ($ordersQuery) use ($search) {
                        $ordersQuery->where('id', $search)
                            ->orWhereHas('orderDetail', function ($detailQuery) use ($search) {
                                $detailQuery->where('full_name', 'like', "%{$search}%")
                                    ->orWhere('phone', 'like', "%{$search}%")
                                    ->orWhere('address', 'like', "%{$search}%");
                            });
                    });
            });
        }
    }

    private function applyOrderFilters(Builder|Relation $query, Request $request): void
    {
        if ($request->filled('order_id')) {
            $query->whereKey($request->order_id);
        }

        if ($request->filled('order_status')) {
            $query->where('status', $request->order_status);
        }

        if ($request->has('has_coordinates')) {
            $request->boolean('has_coordinates')
                ? $query->whereNotNull('latitude')->whereNotNull('longitude')
                : $query->where(function ($query) {
                    $query->whereNull('latitude')->orWhereNull('longitude');
                });
        }

        if ($request->filled('product_id') || $request->filled('product_variation_id')) {
            $query->whereHas('sales', function ($salesQuery) use ($request) {
                if ($request->filled('product_id')) {
                    $salesQuery->where('product_id', $request->product_id);
                }

                if ($request->filled('product_variation_id')) {
                    $salesQuery->where('product_variation_id', $request->product_variation_id);
                }
            });
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($query) use ($search) {
                $query->where('id', $search)
                    ->orWhereHas('orderDetail', function ($detailQuery) use ($search) {
                        $detailQuery->where('full_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('address', 'like', "%{$search}%");
                    });
            });
        }
    }
}
