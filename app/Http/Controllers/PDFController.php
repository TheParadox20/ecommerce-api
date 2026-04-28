<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PDFController extends Controller
{
    public function downloadInvoice($slug)
    {
        $user = auth()->user();
        $order = Order::where('slug', $slug)->with(['orderDetail', 'sales.product', 'sales.productVariation'])->firstOrFail();

        // Allow access if user is super_admin, admin, or the owner of the order
        if (!$user->hasAnyRole(['super_admin', 'admin']) && $user->id !== $order->user_id) {
            abort(403, 'You do not have permission to download this invoice.');
        }

        $pdf = Pdf::loadView('pdfs.invoice', ['order' => $order])
                  ->setPaper('a4', 'portrait');

        return $pdf->download('Invoice-' . $order->slug . '.pdf');
    }
}
