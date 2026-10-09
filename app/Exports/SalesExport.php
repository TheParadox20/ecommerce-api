<?php

namespace App\Exports;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SalesExport implements WithMultipleSheets
{
    protected $dateFrom;
    protected $dateTo;
    protected $status;
    protected $orderType;
    protected $paymentStatus;
    protected $search;
    protected $preset;

    public function __construct($dateFrom = null, $dateTo = null, $status = null, $orderType = null, $paymentStatus = null, $search = null, $preset = null)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->status = $status;
        $this->orderType = $orderType;
        $this->paymentStatus = $paymentStatus;
        $this->search = $search;
        $this->preset = $preset;
    }

    public function getFilteredOrders()
    {
        $query = Order::query()->with([
            'orderDetail',
            'shipment',
            'sales.product',
            'sales.productVariation',
        ]);

        // Default baseline filter to match Sales dashboard:
        if (!$this->paymentStatus) {
            $query->where(function ($q) {
                $q->where('payment_status', 'success')
                  ->orWhere(function ($sub) {
                      $sub->where('payment_status', 'pending')
                          ->whereNotNull('payment_reference');
                  });
            });
        } elseif ($this->paymentStatus === 'pending') {
            $query->where('payment_status', 'pending')
                  ->whereNotNull('payment_reference');
        } else {
            $query->where('payment_status', $this->paymentStatus);
        }

        if ($this->dateFrom) {
            $query->where('created_at', '>=', Carbon::parse($this->dateFrom)->startOfDay());
        }

        if ($this->dateTo) {
            $query->where('created_at', '<=', Carbon::parse($this->dateTo)->endOfDay());
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->orderType) {
            $query->where('order_type', $this->orderType);
        }

        if ($this->search) {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('slug', 'like', "%{$search}%")
                  ->orWhere('id', $search)
                  ->orWhere('payment_reference', 'like', "%{$search}%")
                  ->orWhereHas('orderDetail', function ($dq) use ($search) {
                      $dq->where('full_name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%")
                         ->orWhere('address', 'like', "%{$search}%");
                  })
                  ->orWhereHas('sales.product', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function sheets(): array
    {
        $orders = $this->getFilteredOrders();

        return [
            new SalesSummarySheet(
                $orders,
                $this->dateFrom,
                $this->dateTo,
                $this->status,
                $this->orderType,
                $this->paymentStatus,
                $this->search,
                $this->preset
            ),
            new SalesOrdersSheet($orders),
            new SalesItemsSheet($orders),
        ];
    }
}

/**
 * Sheet 1: Executive KPI Summary & Preselection Filters
 */
class SalesSummarySheet implements FromCollection, ShouldAutoSize, WithStyles, WithTitle
{
    protected $orders;
    protected $dateFrom;
    protected $dateTo;
    protected $status;
    protected $orderType;
    protected $paymentStatus;
    protected $search;
    protected $preset;

    public function __construct($orders, $dateFrom, $dateTo, $status, $orderType, $paymentStatus, $search, $preset)
    {
        $this->orders = $orders;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->status = $status;
        $this->orderType = $orderType;
        $this->paymentStatus = $paymentStatus;
        $this->search = $search;
        $this->preset = $preset;
    }

    public function title(): string
    {
        return 'Executive Summary';
    }

    public function collection(): Collection
    {
        $totalOrders = $this->orders->count();
        $netPaidRevenue = (float) $this->orders->where('payment_status', 'success')->sum('total');
        $grossTotalValue = (float) $this->orders->sum('total');
        $totalItems = (int) $this->orders->sum(fn($o) => $o->sales->sum('quantity'));
        $avgOrderValue = $totalOrders > 0 ? round($grossTotalValue / $totalOrders, 2) : 0;

        $paidCount = $this->orders->where('payment_status', 'success')->count();
        $underpaymentCount = $this->orders->where('payment_status', 'pending_verification')->count();
        $pendingCount = $this->orders->where('status', 'pending')->count();
        $processingCount = $this->orders->where('status', 'processing')->count();
        $completedCount = $this->orders->where('status', 'completed')->count();
        $cancelledCount = $this->orders->where('status', 'cancelled')->count();

        $rows = [
            ['SALES INTELLIGENCE EXECUTIVE REPORT', ''],
            ['Report Generated (EAT)', Carbon::now('Africa/Nairobi')->format('d M Y, H:i:s')],
            ['Preselection Period', $this->preset ?: 'Custom Period'],
            ['Commencement Date', $this->dateFrom ? Carbon::parse($this->dateFrom)->format('d M Y') : 'Beginning of Time'],
            ['Termination Date', $this->dateTo ? Carbon::parse($this->dateTo)->format('d M Y') : 'Present'],
            ['Transaction Status Filter', $this->status ? ucfirst($this->status) : 'All Statuses'],
            ['Order Type Filter', $this->orderType ? strtoupper($this->orderType) : 'All (B2C & B2B)'],
            ['Payment Status Filter', $this->paymentStatus ? ucfirst($this->paymentStatus) : 'Paid & Verified Only'],
            ['Search Filter', $this->search ?: 'None'],
            ['', ''],
            ['COMPILED PRESELECTION TOTALS', ''],
            ['Metric Description', 'Compiled Amount / Count'],
            ['Total Orders Preselected', $totalOrders],
            ['Net Paid Revenue (KES)', number_format($netPaidRevenue, 2)],
            ['Gross Total Order Value (KES)', number_format($grossTotalValue, 2)],
            ['Total Product Units Sold', $totalItems],
            ['Average Order Value (KES)', number_format($avgOrderValue, 2)],
            ['', ''],
            ['FULFILLMENT & ORDER STATUS BREAKDOWN', ''],
            ['Status Category', 'Count'],
            ['Paid & Verified Orders', $paidCount],
            ['Underpayment (Pending Review)', $underpaymentCount],
            ['Pending Approval / Fulfillment', $pendingCount],
            ['Processing & Packing', $processingCount],
            ['Completed & Successfully Shipped', $completedCount],
            ['Cancelled Transactions', $cancelledCount],
        ];

        return new Collection($rows);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
            ],
            11 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '1E293B']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
            ],
            12 => [
                'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '334155']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'CBD5E1']],
            ],
            19 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '1E293B']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
            ],
            20 => [
                'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '334155']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'CBD5E1']],
            ],
        ];
    }
}

/**
 * Sheet 2: Detailed Orders Register with Compiled Totals Footer
 */
class SalesOrdersSheet implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    protected $orders;
    protected $totalRowsCount = 0;

    public function __construct($orders)
    {
        $this->orders = $orders;
    }

    public function title(): string
    {
        return 'Orders Register';
    }

    public function headings(): array
    {
        return [
            'Order ID',
            'Order Code',
            'Date Placed',
            'Customer Name',
            'Phone',
            'Delivery Location / Station',
            'Logistics Method',
            'Order Type',
            'Items Count',
            'Subtotal (KES)',
            'Shipping (KES)',
            'Total Order (KES)',
            'Payment Status',
            'Payment Receipt / Ref',
            'Payment Method',
            'Fulfillment Status',
            'Products Summary',
        ];
    }

    public function collection(): Collection
    {
        $rows = [];
        $totalVolume = 0;
        $totalSubtotal = 0;
        $totalShipping = 0;
        $grandTotal = 0;

        foreach ($this->orders as $order) {
            $itemQty = (int) $order->sales->sum('quantity');
            $shipping = (float) ($order->shipping ?? 0);
            $total = (float) $order->total;
            $subtotal = $total - $shipping;

            $totalVolume += $itemQty;
            $totalSubtotal += $subtotal;
            $totalShipping += $shipping;
            $grandTotal += $total;

            $productsSummary = $order->sales->map(function ($s) {
                $pName = $s->product?->name ?? ('Item #' . ($s->product_id ?? $s->id));
                $var = $s->productVariation?->sku ? " [{$s->productVariation->sku}]" : "";
                return "{$pName}{$var} (x{$s->quantity})";
            })->implode('; ');

            $rows[] = [
                '#' . $order->id,
                $order->slug,
                $order->created_at->format('Y-m-d H:i'),
                $order->orderDetail?->full_name ?? 'N/A',
                $order->orderDetail?->phone ?? 'N/A',
                $order->orderDetail?->address ?? ($order->pickup_station ?? 'N/A'),
                $order->delivery_method ? ucfirst($order->delivery_method) : 'N/A',
                strtoupper($order->order_type ?? 'B2C'),
                $itemQty,
                $subtotal,
                $shipping,
                $total,
                ucfirst(str_replace('_', ' ', $order->payment_status ?? 'pending')),
                $order->payment_reference ?? 'N/A',
                ucfirst($order->payment_method ?? 'N/A'),
                ucfirst(str_replace('_', ' ', $order->status ?? 'pending')),
                $productsSummary,
            ];
        }

        // Empty separator row
        $rows[] = array_fill(0, 17, '');

        // Grand Totals row compiled
        $rows[] = [
            'GRAND TOTALS',
            '',
            '',
            '',
            '',
            '',
            '',
            $this->orders->count() . ' orders',
            $totalVolume,
            $totalSubtotal,
            $totalShipping,
            $grandTotal,
            '',
            '',
            '',
            '',
            '',
        ];

        $this->totalRowsCount = count($rows);

        return new Collection($rows);
    }

    public function styles(Worksheet $sheet)
    {
        $lastRowIndex = $this->totalRowsCount + 1; // +1 for heading row

        return [
            1 => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4338CA']], // Indigo-700
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ],
            $lastRowIndex => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '1E293B']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']], // Amber-100 highlight
                'borders' => [
                    'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D97706']],
                    'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => 'D97706']],
                ],
            ],
        ];
    }
}

/**
 * Sheet 3: Itemized Product Line Sales Breakdown
 */
class SalesItemsSheet implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    protected $orders;
    protected $totalRowsCount = 0;

    public function __construct($orders)
    {
        $this->orders = $orders;
    }

    public function title(): string
    {
        return 'Product Sales Breakdown';
    }

    public function headings(): array
    {
        return [
            'Order ID',
            'Order Code',
            'Date',
            'Product Name',
            'Variation / SKU',
            'Unit Price (KES)',
            'Quantity Sold',
            'Line Total (KES)',
            'Customer Name',
            'Payment Status',
        ];
    }

    public function collection(): Collection
    {
        $rows = [];
        $totalUnits = 0;
        $totalSalesVal = 0;

        foreach ($this->orders as $order) {
            foreach ($order->sales as $sale) {
                $pName = $sale->product?->name ?? ('Product #' . ($sale->product_id ?? $sale->id));
                $sku = $sale->productVariation?->sku ?? 'Standard';
                $price = (float) $sale->price;
                $qty = (int) $sale->quantity;
                $lineTotal = (float) ($sale->total ?: ($price * $qty));

                $totalUnits += $qty;
                $totalSalesVal += $lineTotal;

                $rows[] = [
                    '#' . $order->id,
                    $order->slug,
                    $order->created_at->format('Y-m-d H:i'),
                    $pName,
                    $sku,
                    $price,
                    $qty,
                    $lineTotal,
                    $order->orderDetail?->full_name ?? 'N/A',
                    ucfirst(str_replace('_', ' ', $order->payment_status ?? 'pending')),
                ];
            }
        }

        // Empty separator
        $rows[] = array_fill(0, 10, '');

        // Totals row
        $rows[] = [
            'TOTALS',
            '',
            '',
            '',
            '',
            '',
            $totalUnits,
            $totalSalesVal,
            '',
            '',
        ];

        $this->totalRowsCount = count($rows);

        return new Collection($rows);
    }

    public function styles(Worksheet $sheet)
    {
        $lastRowIndex = $this->totalRowsCount + 1;

        return [
            1 => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F766E']], // Teal-700
            ],
            $lastRowIndex => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '134E4A']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'CCFBF1']],
                'borders' => [
                    'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '0F766E']],
                    'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => '0F766E']],
                ],
            ],
        ];
    }
}

