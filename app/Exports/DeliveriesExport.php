<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DeliveriesExport implements FromQuery, WithMapping, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $dateFrom;
    protected $dateTo;

    public function __construct($dateFrom = null, $dateTo = null)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function query()
    {
        $query = Order::query()->with(['orderDetail', 'sales.product']);

        if ($this->dateFrom) {
            $query->where('expected_shipping_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->where('expected_shipping_date', '<=', $this->dateTo);
        }

        return $query->orderBy('expected_shipping_date', 'asc');
    }

    public function map($order): array
    {
        return [
            $order->slug,
            $order->expected_shipping_date,
            $order->orderDetail->full_name ?? 'N/A',
            $order->orderDetail->phone ?? 'N/A',
            $order->orderDetail->address ?? 'N/A',
            $order->delivery_method,
            $order->pickup_station ?? 'N/A',
            $order->status,
            $order->sales->map(fn($s) => $s->product->name . " (x" . $s->quantity . ")")->implode(', '),
        ];
    }

    public function headings(): array
    {
        return [
            'Order ID',
            'Scheduled Ship Date',
            'Customer Name',
            'Phone',
            'Delivery Address',
            'Method',
            'Station',
            'Fulfillment Status',
            'Items',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
