<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PDFController extends Controller
{
    public function downloadInvoice($slug)
    {
        $order = Order::where('slug', $slug)->with(['orderDetail', 'sales.product', 'sales.productVariation'])->firstOrFail();

        $pdf = Pdf::loadView('pdfs.invoice', ['order' => $order])
                  ->setPaper('a4', 'portrait');

        return $pdf->download('Invoice-' . $order->slug . '.pdf');
    }
}
