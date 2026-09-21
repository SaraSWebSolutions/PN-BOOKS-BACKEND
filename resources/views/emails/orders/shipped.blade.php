<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; color:#333;">
    <h2>Your order is on the way!</h2>
    <p>Good news — your order <strong>{{ $order->order_number }}</strong> has been shipped.</p>

    <table style="margin-top:16px; border-collapse: collapse;">
        <tr>
            <td style="padding:6px 12px 6px 0; color:#666;">Tracking Number:</td>
            <td style="padding:6px 0; font-weight:bold;">{{ $order->tracking_number }}</td>
        </tr>
        @if($order->shipping_carrier)
        <tr>
            <td style="padding:6px 12px 6px 0; color:#666;">Carrier:</td>
            <td style="padding:6px 0; font-weight:bold;">{{ $order->shipping_carrier }}</td>
        </tr>
        @endif
    </table>

    <p style="margin-top:20px;">You can use the tracking number above with your carrier's website to follow your delivery.</p>
    <p>Thanks for shopping with Pustaka Nasional!</p>
</body>
</html>