<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px; }
        .header { background-color: #6D31ED; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 20px; }
        .footer { font-size: 12px; color: #777; text-align: center; margin-top: 20px; }
        .btn { display: inline-block; padding: 12px 25px; background-color: #6D31ED; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Order Confirmed!</h2>
        </div>
        <div class="content">
            <p>Hello,</p>
            <p>Thank you for your order with <strong>NGWINDSONGK</strong>. We have successfully received your payment via M-PESA.</p>
            <p><strong>Order ID:</strong> #{{ $order->slug }}</p>
            <p><strong>Fulfillment Method:</strong> {{ ucfirst(str_replace('_', ' ', $order->delivery_method)) }}</p>
            <p><strong>Estimated Shipping Date:</strong> {{ \Carbon\Carbon::parse($order->expected_shipping_date)->format('l, M d, Y') }}</p>
            <p>We have attached your official invoice as a PDF to this email for your records.</p>
            <p>Our team is now processing your order. You will receive an SMS notification as soon as it has been dispatched.</p>
            <p>Best regards,<br>The NGWINDSONGK Team</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} NGWINDSONGK. All rights reserved.
        </div>
    </div>
</body>
</html>
