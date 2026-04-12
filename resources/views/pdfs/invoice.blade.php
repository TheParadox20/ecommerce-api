<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $order->slug }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #333; margin: 0; padding: 0; line-height: 1.5; }
        .invoice-container { padding: 40px; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #6D31ED; padding-bottom: 20px; }
        .logo { font-size: 28px; font-weight: 900; color: #6D31ED; text-transform: uppercase; letter-spacing: 2px; }
        .invoice-title { text-align: right; }
        .invoice-title h1 { margin: 0; font-size: 40px; color: #000; text-transform: uppercase; }
        .details-section { margin-top: 40px; display: table; width: 100%; }
        .details-col { display: table-cell; width: 50%; vertical-align: top; }
        .label { font-size: 10px; font-weight: bold; color: #999; text-transform: uppercase; margin-bottom: 5px; }
        .value { font-size: 14px; font-weight: bold; margin-bottom: 20px; }
        .items-table { width: 100%; border-collapse: collapse; margin-top: 40px; }
        .items-table th { background-color: #f9f9f9; text-align: left; padding: 12px; font-size: 10px; text-transform: uppercase; border-bottom: 1px solid #eee; }
        .items-table td { padding: 15px 12px; border-bottom: 1px solid #eee; font-size: 13px; }
        .summary-section { margin-top: 40px; text-align: right; }
        .summary-row { margin-bottom: 10px; }
        .summary-label { font-size: 13px; color: #666; display: inline-block; width: 120px; }
        .summary-value { font-size: 13px; font-weight: bold; display: inline-block; width: 100px; }
        .total-row { border-top: 2px solid #000; padding-top: 15px; margin-top: 20px; }
        .total-label { font-size: 18px; font-weight: 900; color: #000; display: inline-block; width: 120px; }
        .total-value { font-size: 18px; font-weight: 900; color: #6D31ED; display: inline-block; width: 100px; }
        .footer { margin-top: 60px; font-size: 10px; color: #999; text-align: center; border-top: 1px solid #eee; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="invoice-container">
        <table style="width: 100%;">
            <tr>
                <td>
                    <img src="{{ public_path('logo.png') }}" style="width: 180px;" alt="NGWINDSONGK">
                </td>
                <td class="invoice-title">
                    <h1>Invoice</h1>
                    <div class="value">#{{ $order->id }}/{{ $order->created_at->format('m/Y') }}</div>
                    <div style="margin-top: 5px;">
                        @if($order->payment_status === 'success')
                            <span style="background-color: #22c55e; color: white; padding: 5px 15px; border-radius: 5px; font-size: 12px; font-weight: bold; text-transform: uppercase;">Paid</span>
                        @else
                            <span style="background-color: #ef4444; color: white; padding: 5px 15px; border-radius: 5px; font-size: 12px; font-weight: bold; text-transform: uppercase;">Not Paid</span>
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <div class="details-section">
            <table style="width: 100%;">
                <tr>
                    <td class="details-col">
                        <div class="label">Billed To</div>
                        <div class="value">{{ $order->orderDetail->full_name }}</div>
                        <div class="value" style="font-weight: normal; font-size: 12px; color: #666;">
                            {{ $order->orderDetail->phone }}<br>
                            {{ $order->orderDetail->email }}<br>
                            {{ $order->orderDetail->address }}
                        </div>
                    </td>
                    <td class="details-col" style="padding-left: 40px;">
                        <div class="label">Order Details</div>
                        <div class="value">Date: {{ $order->created_at->format('M d, Y') }}</div>
                        <div class="label">Fulfillment Details</div>
                        @if($order->delivery_method === 'pickup')
                            <div class="value" style="color: #6D31ED;">
                                Pickup Station: {{ $order->pickup_station }}<br>
                                Collection Date: {{ \Carbon\Carbon::parse($order->expected_shipping_date)->format('l, M d, Y') }}
                            </div>
                        @else
                            <div class="value">
                                Method: Standard Delivery<br>
                                Est. Shipment: {{ \Carbon\Carbon::parse($order->expected_shipping_date)->format('M d, Y') }}
                            </div>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Item Description</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Unit Price</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->sales as $sale)
                <tr>
                    <td>
                        <div style="font-weight: bold;">{{ $sale->product->name }}</div>
                        <div style="font-size: 11px; color: #888;">{{ $sale->productVariation->name ?? 'Standard Size' }}</div>
                    </td>
                    <td style="text-align: center;">{{ $sale->quantity }}</td>
                    <td style="text-align: right;">{{ number_format($sale->price, 2) }}</td>
                    <td style="text-align: right; font-weight: bold;">{{ number_format($sale->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary-section">
            <div class="summary-row">
                <span class="summary-label">Subtotal:</span>
                <span class="summary-value">{{ number_format($order->total - ($order->shipping ?? 0), 2) }} KES</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Shipping:</span>
                <span class="summary-value">{{ number_format($order->shipping ?? 0, 2) }} KES</span>
            </div>
            <div class="total-row">
                <span class="total-label">Total:</span>
                <span class="total-value">{{ number_format($order->total, 2) }} KES</span>
            </div>
        </div>

        <div class="footer">
            Thank you for your business. For any queries, please contact us at sales@ngwindsongk.com<br>
            Expected Shipment Date is {{ \Carbon\Carbon::parse($order->expected_shipping_date)->format('l, M d, Y') }}.
        </div>
    </div>
</body>
</html>
