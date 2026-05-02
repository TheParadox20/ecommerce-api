<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #f4f0fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f0fa; padding: 40px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(109, 49, 237, 0.08);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #6D31ED 0%, #15ABFF 100%); padding: 32px 40px; text-align: center;">
                            <img src="{{ config('app.url') }}/logo.png" alt="NG Windsong Kenya" width="120" style="display: block; margin: 0 auto 12px;">
                            <h1 style="color: #ffffff; font-size: 22px; font-weight: 600; margin: 0;">New Order Received!</h1>
                            <p style="color: rgba(255,255,255,0.85); font-size: 13px; margin: 8px 0 0;">Order #{{ substr($order->slug, 0, 8) }}</p>
                        </td>
                    </tr>

                    <!-- Order Total -->
                    <tr>
                        <td style="padding: 32px 40px 0; text-align: center;">
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 0 auto; background: linear-gradient(135deg, #6D31ED 0%, #15ABFF 100%); border-radius: 12px;">
                                <tr>
                                    <td style="padding: 20px 40px;">
                                        <span style="color: rgba(255,255,255,0.8); font-size: 12px; text-transform: uppercase; letter-spacing: 1px; font-weight: 700;">Total Amount</span><br>
                                        <span style="color: #ffffff; font-size: 32px; font-weight: 700;">KES {{ number_format($order->total) }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Customer Details -->
                    <tr>
                        <td style="padding: 28px 40px 0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8f5ff; border-radius: 12px; border-left: 4px solid #6D31ED;">
                                <tr>
                                    <td style="padding: 24px;">
                                        <p style="color: #6D31ED; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 16px;">Customer Details</p>
                                        @if($order->orderDetail)
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            @if($order->orderDetail->full_name)
                                            <tr>
                                                <td style="padding-bottom: 10px; width: 100px; color: #94a3b8; font-size: 13px; vertical-align: top;">Name</td>
                                                <td style="padding-bottom: 10px; color: #1e293b; font-size: 14px; font-weight: 500;">{{ $order->orderDetail->full_name }}</td>
                                            </tr>
                                            @endif
                                            <tr>
                                                <td style="padding-bottom: 10px; width: 100px; color: #94a3b8; font-size: 13px; vertical-align: top;">Phone</td>
                                                <td style="padding-bottom: 10px; color: #1e293b; font-size: 14px; font-weight: 500;">
                                                    <a href="tel:{{ $order->orderDetail->phone }}" style="color: #15ABFF; text-decoration: none;">{{ $order->orderDetail->phone }}</a>
                                                </td>
                                            </tr>
                                            @if($order->orderDetail->address)
                                            <tr>
                                                <td style="padding-bottom: 10px; width: 100px; color: #94a3b8; font-size: 13px; vertical-align: top;">Address</td>
                                                <td style="padding-bottom: 10px; color: #1e293b; font-size: 14px; font-weight: 500;">{{ $order->orderDetail->address }}</td>
                                            </tr>
                                            @endif
                                            @if($order->orderDetail->notes)
                                            <tr>
                                                <td style="width: 100px; color: #94a3b8; font-size: 13px; vertical-align: top;">Notes</td>
                                                <td style="color: #475569; font-size: 14px; font-style: italic;">{{ $order->orderDetail->notes }}</td>
                                            </tr>
                                            @endif
                                        </table>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Order Items -->
                    <tr>
                        <td style="padding: 20px 40px 0;">
                            <p style="color: #6D31ED; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 12px;">Order Items</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-radius: 12px; overflow: hidden; border: 1px solid #f1e8ff;">
                                <!-- Table Header -->
                                <tr>
                                    <td style="background-color: #f8f5ff; padding: 10px 16px; color: #6D31ED; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Product</td>
                                    <td style="background-color: #f8f5ff; padding: 10px 16px; color: #6D31ED; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; text-align: center;">Qty</td>
                                    <td style="background-color: #f8f5ff; padding: 10px 16px; color: #6D31ED; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; text-align: right;">Price</td>
                                    <td style="background-color: #f8f5ff; padding: 10px 16px; color: #6D31ED; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; text-align: right;">Total</td>
                                </tr>
                                <!-- Items -->
                                @foreach($order->sales as $sale)
                                <tr>
                                    <td style="padding: 12px 16px; color: #334155; font-size: 13px; border-top: 1px solid #f1e8ff;">
                                        {{ $sale->product->name ?? 'Product #'.$sale->product_id }}
                                        @if($sale->productVariation)
                                            <br><span style="color: #94a3b8; font-size: 12px;">{{ $sale->productVariation->attribute_name }}: {{ $sale->productVariation->attribute_value }}</span>
                                        @else
                                            <br><span style="color: #94a3b8; font-size: 12px;">Standard Size</span>
                                        @endif
                                    </td>
                                    <td style="padding: 12px 16px; color: #334155; font-size: 13px; text-align: center; border-top: 1px solid #f1e8ff;">{{ $sale->quantity }}</td>
                                    <td style="padding: 12px 16px; color: #334155; font-size: 13px; text-align: right; border-top: 1px solid #f1e8ff;">KES {{ number_format($sale->price) }}</td>
                                    <td style="padding: 12px 16px; color: #1e293b; font-size: 13px; font-weight: 600; text-align: right; border-top: 1px solid #f1e8ff;">KES {{ number_format($sale->total) }}</td>
                                </tr>
                                @endforeach
                                <!-- Total Row -->
                                <tr>
                                    <td colspan="3" style="padding: 14px 16px; font-size: 13px; font-weight: 600; color: #1e293b; text-align: right; border-top: 2px solid #6D31ED;">Subtotal</td>
                                    <td style="padding: 14px 16px; font-size: 14px; font-weight: 600; color: #1e293b; text-align: right; border-top: 2px solid #6D31ED;">KES {{ number_format($order->total - ($order->shipping ?? 0)) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="3" style="padding: 8px 16px 14px; font-size: 13px; font-weight: 600; color: #1e293b; text-align: right;">Shipping</td>
                                    <td style="padding: 8px 16px 14px; font-size: 14px; font-weight: 600; color: #1e293b; text-align: right;">KES {{ number_format($order->shipping ?? 0) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="3" style="padding: 14px 16px; font-size: 14px; font-weight: 700; color: #1e293b; text-align: right; border-top: 2px solid #6D31ED;">Grand Total</td>
                                    <td style="padding: 14px 16px; font-size: 16px; font-weight: 700; color: #6D31ED; text-align: right; border-top: 2px solid #6D31ED;">KES {{ number_format($order->total) }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Payment Info -->
                    <tr>
                        <td style="padding: 20px 40px 0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding: 12px 16px; background-color: #f0fdf4; border-radius: 8px;">
                                        <span style="color: #288747; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Payment Method</span><br>
                                        <span style="color: #1e293b; font-size: 14px; font-weight: 500;">{{ $order->payment_method ?? 'Not specified' }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 28px 40px 32px; text-align: center; border-top: 1px solid #f1e8ff; margin-top: 20px;">
                            <p style="color: #94a3b8; font-size: 12px; margin: 0;">
                                {{ $order->created_at->format('M d, Y \a\t h:i A') }}
                            </p>
                            <p style="color: #c4b5fd; font-size: 11px; margin: 8px 0 0;">
                                NG Windsong Kenya Ltd &bull; Bazaar Plaza, Moi Avenue, Nairobi
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
