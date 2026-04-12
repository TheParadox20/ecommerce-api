<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesExport implements FromQuery, WithMapping, WithHeadings, ShouldAutoSize, WithStyles
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
            $query->where('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->where('created_at', '<=', $this->dateTo);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function map($order): array
    {
        return [
            $order->slug,
            $order->created_at->format('Y-m-d H:i'),
            $order->orderDetail->full_name ?? 'N/A',
            $order->orderDetail->phone ?? 'N/A',
            $order->total,
            $order->payment_status,
            $order->payment_reference,
            $order->delivery_method,
            $order->sales->map(fn($s) => $s->product->name . " (x" . $s->quantity . ")")->implode(', '),
        ];
    }

    public function headings(): array
    {
        return [
            'Order ID',
            'Date',
            'Customer Name',
            'Phone',
            'Total Amount',
            'Payment Status',
            'Receipt/Ref',
            'Method',
            'Products',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
