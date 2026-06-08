<?php

namespace App\Http\Controllers;

use App\Exports\SalesExport;
use App\Exports\DeliveriesExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function sales(Request $request)
    {
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $status = $request->query('status');
        $orderType = $request->query('order_type');
        $paymentStatus = $request->query('payment_status');
        $search = $request->query('search');
        
        $filename = 'sales_report_' . now()->format('Y-m-d') . '.xlsx';
        
        return Excel::download(new SalesExport($dateFrom, $dateTo, $status, $orderType, $paymentStatus, $search), $filename);
    }

    public function deliveries(Request $request)
    {
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        
        $filename = 'deliveries_report_' . now()->format('Y-m-d') . '.xlsx';
        
        return Excel::download(new DeliveriesExport($dateFrom, $dateTo), $filename);
    }
}
