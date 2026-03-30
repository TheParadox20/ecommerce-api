<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Shipment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class LogisticsController extends Controller
{
    private const HUB_LATITUDE = -1.285374;
    private const HUB_LONGITUDE = 36.825474;
    private const LOW_STOCK_THRESHOLD = 5;

    public function index(Request $request)
    {
        [$start, $end, $previousStart, $previousEnd, $granularity, $label] = $this->resolveRange($request);

        $ordersInRange = Order::with([
            'orderDetail',
            'sales.product',
            'sales.productVariation',
            'shipment',
        ])
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $ordersPreviousRange = Order::with(['sales.product', 'sales.productVariation'])
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->get();

        $shipmentsInRange = Shipment::with(['orders.orderDetail', 'orders.sales.product', 'orders.sales.productVariation'])
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $shipmentsPreviousRange = Shipment::whereBetween('created_at', [$previousStart, $previousEnd])->get();

        $products = Product::query()->with('productVariations')->get();
        $variations = ProductVariation::query()->with('product')->get();

        $trendBuckets = $this->generateBuckets($start, $end, $granularity);

        $currentRevenue = $ordersInRange->where('payment_status', 'success')->sum(fn ($order) => (float) $order->total);
        $previousRevenue = $ordersPreviousRange->where('payment_status', 'success')->sum(fn ($order) => (float) $order->total);

        $currentDiscountCost = $this->discountCostForOrders($ordersInRange);
        $previousDiscountCost = $this->discountCostForOrders($ordersPreviousRange);

        $currentDelivered = $shipmentsInRange->where('status', 'delivered')->count();
        $previousDelivered = $shipmentsPreviousRange->where('status', 'delivered')->count();

        $currentAvgDeliveryTime = $this->averageDeliveryAgeDays($shipmentsInRange);
        $previousAvgDeliveryTime = $this->averageDeliveryAgeDays($shipmentsPreviousRange);

        $shipmentStatusCounts = [
            'pending' => $shipmentsInRange->where('status', 'pending')->count(),
            'in_transit' => $shipmentsInRange->where('status', 'in_transit')->count(),
            'delivered' => $shipmentsInRange->where('status', 'delivered')->count(),
            'cancelled' => $shipmentsInRange->where('status', 'cancelled')->count(),
        ];

        $orderStatusCounts = [
            'pending' => $ordersInRange->where('status', 'pending')->count(),
            'processing' => $ordersInRange->where('status', 'processing')->count(),
            'completed' => $ordersInRange->where('status', 'completed')->count(),
            'cancelled' => $ordersInRange->where('status', 'cancelled')->count(),
        ];

        $recentShipments = $shipmentsInRange
            ->sortByDesc('created_at')
            ->take(4)
            ->map(function ($shipment) {
                return [
                    'id' => $shipment->id,
                    'status' => $shipment->status,
                    'created_at' => $shipment->created_at,
                    'orders_count' => $shipment->orders->count(),
                    'orders_sum_total' => (float) $shipment->orders->sum('total'),
                    'primary_customer' => optional($shipment->orders->first()?->orderDetail)->full_name,
                ];
            })
            ->values();

        $upcomingOrders = $ordersInRange
            ->filter(fn ($order) => in_array($order->status, ['pending', 'processing'], true))
            ->sortBy('created_at')
            ->take(5)
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'status' => $order->status,
                    'customer_name' => $order->orderDetail?->full_name,
                    'address' => $order->orderDetail?->address,
                    'due_date' => optional($order->created_at)->copy()->addDays(2)->toDateString(),
                    'total' => (float) $order->total,
                ];
            })
            ->values();

        $availableStock = (int) $products->sum('stock') + (int) $variations->sum('stock');
        $usedStock = (int) $ordersInRange->flatMap->sales->sum('quantity');
        $totalStockScope = max($availableStock + $usedStock, 1);
        $availablePercentage = round(($availableStock / $totalStockScope) * 100, 1);

        $lowStock = $this->lowStockItems($products, $variations);
        $topItems = $this->topItems($ordersInRange);
        $routeAnalytics = $this->routeAnalytics($shipmentsInRange);
        $regions = $this->regionAnalytics($ordersInRange);

        return response()->json([
            'range' => [
                'label' => $label,
                'from' => $start->toDateString(),
                'to' => $end->toDateString(),
                'granularity' => $granularity,
            ],
            'cards' => [
                'revenue' => [
                    'value' => round($currentRevenue, 2),
                    'rate' => $this->percentageDelta($currentRevenue, $previousRevenue),
                    'text' => "Compared to previous {$label}",
                    'trend' => $this->bucketOrderRevenue($ordersInRange, $trendBuckets),
                ],
                'cost' => [
                    'value' => round($currentDiscountCost, 2),
                    'rate' => $this->percentageDelta($currentDiscountCost, $previousDiscountCost),
                    'text' => "Estimated discount cost vs previous {$label}",
                    'trend' => $this->bucketDiscountCost($ordersInRange, $trendBuckets),
                ],
                'deliveries' => [
                    'value' => $currentDelivered,
                    'rate' => $this->percentageDelta($currentDelivered, $previousDelivered),
                    'text' => "Delivered shipments vs previous {$label}",
                    'trend' => $this->bucketShipmentMetric($shipmentsInRange, $trendBuckets, 'delivered'),
                ],
                'avg_delivery_time' => [
                    'value' => round($currentAvgDeliveryTime, 1),
                    'rate' => $this->percentageDelta($currentAvgDeliveryTime, $previousAvgDeliveryTime, true),
                    'text' => "Average delivered shipment age vs previous {$label}",
                    'trend' => $this->bucketShipmentAges($shipmentsInRange, $trendBuckets),
                ],
            ],
            'shipment_overview' => [
                'total' => $shipmentsInRange->count(),
                'statuses' => $shipmentStatusCounts,
                'recent' => $recentShipments,
            ],
            'orders' => [
                'statuses' => $orderStatusCounts,
                'upcoming' => $upcomingOrders,
            ],
            'inventory' => [
                'available_items' => $availableStock,
                'used_items' => $usedStock,
                'available_percentage' => $availablePercentage,
                'low_stock' => $lowStock,
            ],
            'top_items' => $topItems,
            'routes' => $routeAnalytics,
            'regions' => $regions,
        ]);
    }

    private function resolveRange(Request $request): array
    {
        $now = Carbon::now();

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $start = Carbon::parse($request->date_from)->startOfDay();
            $end = Carbon::parse($request->date_to)->endOfDay();
            $label = 'custom range';
        } else {
            $time = strtoupper((string) $request->get('time', '7D'));
            $start = match ($time) {
                'TODAY' => $now->copy()->startOfDay(),
                'YESTERDAY' => $now->copy()->subDay()->startOfDay(),
                '30D' => $now->copy()->subDays(29)->startOfDay(),
                '3M' => $now->copy()->subMonthsNoOverflow(3)->startOfDay(),
                '6M' => $now->copy()->subMonthsNoOverflow(6)->startOfDay(),
                '12M' => $now->copy()->subMonthsNoOverflow(12)->startOfDay(),
                default => $now->copy()->subDays(6)->startOfDay(),
            };

            $end = match ($time) {
                'YESTERDAY' => $now->copy()->subDay()->endOfDay(),
                default => $now->copy()->endOfDay(),
            };

            $label = strtolower($time);
        }

        $days = max($start->diffInDays($end) + 1, 1);
        $previousEnd = $start->copy()->subDay()->endOfDay();
        $previousStart = $previousEnd->copy()->subDays($days - 1)->startOfDay();

        $granularity = $days <= 31 ? 'day' : ($days <= 180 ? 'week' : 'month');

        return [$start, $end, $previousStart, $previousEnd, $granularity, $label];
    }

    private function generateBuckets(Carbon $start, Carbon $end, string $granularity): Collection
    {
        $cursor = $start->copy();
        $buckets = collect();

        while ($cursor <= $end) {
            $bucketStart = $cursor->copy();

            $bucketEnd = match ($granularity) {
                'week' => $cursor->copy()->addDays(6)->endOfDay(),
                'month' => $cursor->copy()->endOfMonth()->endOfDay(),
                default => $cursor->copy()->endOfDay(),
            };

            if ($bucketEnd > $end) {
                $bucketEnd = $end->copy();
            }

            $label = match ($granularity) {
                'week' => $bucketStart->format('M j'),
                'month' => $bucketStart->format('M Y'),
                default => $bucketStart->format('M j'),
            };

            $buckets->push([
                'start' => $bucketStart,
                'end' => $bucketEnd,
                'label' => $label,
            ]);

            $cursor = $bucketEnd->copy()->addSecond()->startOfDay();
        }

        return $buckets;
    }

    private function discountCostForOrders(Collection $orders): float
    {
        return $orders->flatMap->sales->sum(function ($sale) {
            $basePrice = $sale->productVariation?->price ?? $sale->product?->price ?? $sale->price;
            $discountPerUnit = max((float) $basePrice - (float) $sale->price, 0);

            return $discountPerUnit * (int) $sale->quantity;
        });
    }

    private function averageDeliveryAgeDays(Collection $shipments): float
    {
        $delivered = $shipments->where('status', 'delivered');

        if ($delivered->isEmpty()) {
            return 0;
        }

        return $delivered->avg(function ($shipment) {
            return max((float) $shipment->created_at?->diffInHours(now()) / 24, 0);
        }) ?? 0;
    }

    private function bucketOrderRevenue(Collection $orders, Collection $buckets): array
    {
        return $buckets->map(function ($bucket) use ($orders) {
            return round($orders
                ->filter(fn ($order) => $order->created_at >= $bucket['start'] && $order->created_at <= $bucket['end'] && $order->payment_status === 'success')
                ->sum('total'), 2);
        })->all();
    }

    private function bucketDiscountCost(Collection $orders, Collection $buckets): array
    {
        return $buckets->map(function ($bucket) use ($orders) {
            $bucketOrders = $orders->filter(fn ($order) => $order->created_at >= $bucket['start'] && $order->created_at <= $bucket['end']);

            return round($this->discountCostForOrders($bucketOrders), 2);
        })->all();
    }

    private function bucketShipmentMetric(Collection $shipments, Collection $buckets, string $status): array
    {
        return $buckets->map(function ($bucket) use ($shipments, $status) {
            return $shipments
                ->filter(fn ($shipment) => $shipment->created_at >= $bucket['start'] && $shipment->created_at <= $bucket['end'])
                ->where('status', $status)
                ->count();
        })->all();
    }

    private function bucketShipmentAges(Collection $shipments, Collection $buckets): array
    {
        return $buckets->map(function ($bucket) use ($shipments) {
            $delivered = $shipments
                ->filter(fn ($shipment) => $shipment->created_at >= $bucket['start'] && $shipment->created_at <= $bucket['end'])
                ->where('status', 'delivered');

            if ($delivered->isEmpty()) {
                return 0;
            }

            return round($delivered->avg(fn ($shipment) => $shipment->created_at?->diffInHours(now()) / 24) ?? 0, 2);
        })->all();
    }

    private function lowStockItems(Collection $products, Collection $variations): array
    {
        $productItems = $products
            ->filter(fn ($product) => (int) $product->stock <= self::LOW_STOCK_THRESHOLD)
            ->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'stock' => (int) $product->stock,
                'type' => 'product',
            ]);

        $variationItems = $variations
            ->filter(fn ($variation) => (int) $variation->stock <= self::LOW_STOCK_THRESHOLD)
            ->map(fn ($variation) => [
                'id' => $variation->id,
                'name' => trim(($variation->product?->name ?? 'Variation') . ' ' . ($variation->attribute_value ?? $variation->sku ?? '')),
                'stock' => (int) $variation->stock,
                'type' => 'variation',
            ]);

        return $productItems
            ->concat($variationItems)
            ->sortBy('stock')
            ->take(5)
            ->values()
            ->all();
    }

    private function topItems(Collection $orders): array
    {
        return $orders
            ->where('payment_status', 'success')
            ->flatMap->sales
            ->groupBy(function ($sale) {
                return $sale->product_id . ':' . ($sale->product_variation_id ?? 'base');
            })
            ->map(function ($group) {
                $first = $group->first();
                $label = $first->product?->name ?? 'Product';

                if ($first->productVariation?->attribute_value) {
                    $label .= ' - ' . $first->productVariation->attribute_value;
                }

                return [
                    'label' => $label,
                    'value' => round($group->sum('total'), 2),
                    'quantity' => (int) $group->sum('quantity'),
                ];
            })
            ->sortByDesc('value')
            ->take(5)
            ->values()
            ->all();
    }

    private function routeAnalytics(Collection $shipments): array
    {
        return $shipments
            ->map(function ($shipment) {
                $orders = $shipment->orders->filter(fn ($order) => $order->latitude && $order->longitude);
                $distance = $orders->sum(function ($order) {
                    return $this->haversine(
                        self::HUB_LATITUDE,
                        self::HUB_LONGITUDE,
                        (float) $order->latitude,
                        (float) $order->longitude
                    );
                });

                return [
                    'label' => 'Shipment #' . $shipment->id,
                    'distance_km' => round($distance, 2),
                    'avg_cost' => round($shipment->orders->avg('total') ?? 0, 2),
                ];
            })
            ->sortByDesc('distance_km')
            ->take(5)
            ->values()
            ->all();
    }

    private function regionAnalytics(Collection $orders): array
    {
        return $orders
            ->where('payment_status', 'success')
            ->groupBy(function ($order) {
                return $this->extractRegion($order->orderDetail?->address);
            })
            ->map(function ($group, $region) {
                return [
                    'label' => $region,
                    'value' => round($group->sum('total'), 2),
                    'orders' => $group->count(),
                ];
            })
            ->sortByDesc('value')
            ->take(8)
            ->values()
            ->all();
    }

    private function extractRegion(?string $address): string
    {
        if (!$address) {
            return 'Unspecified';
        }

        $parts = collect(preg_split('/,/', $address))
            ->map(fn ($part) => trim((string) $part))
            ->filter();

        return $parts->first() ?: 'Unspecified';
    }

    private function percentageDelta(float|int $current, float|int $previous, bool $inverse = false): float
    {
        $current = (float) $current;
        $previous = (float) $previous;

        if ($previous == 0.0) {
            if ($current == 0.0) {
                return 0;
            }

            return $inverse ? -100 : 100;
        }

        $delta = (($current - $previous) / abs($previous)) * 100;

        return round($inverse ? $delta * -1 : $delta, 1);
    }

    private function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}