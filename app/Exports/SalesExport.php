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
    protected $status;
    protected $orderType;
    protected $paymentStatus;
    protected $search;

    public function __construct($dateFrom = null, $dateTo = null, $status = null, $orderType = null, $paymentStatus = null, $search = null)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->status = $status;
        $this->orderType = $orderType;
        $this->paymentStatus = $paymentStatus;
        $this->search = $search;
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

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->orderType) {
            $query->where('order_type', $this->orderType);
        }

        if ($this->paymentStatus) {
            $query->where('payment_status', $this->paymentStatus);
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->where('slug', 'like', "%{$this->search}%")
                  ->orWhereHas('orderDetail', function($q) {
                      $q->where('full_name', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%");
                  });
            });
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
