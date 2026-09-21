<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; color:#333;">
    <h2>Thank you for your order!</h2>
    <p>Your order <strong>{{ $order->order_number }}</strong> has been placed successfully.</p>

    <table style="width:100%; border-collapse: collapse; margin-top:16px;">
        <thead>
            <tr style="background:#f2f2f2;">
                <th style="text-align:left; padding:8px;">Book</th>
                <th style="text-align:center; padding:8px;">Qty</th>
                <th style="text-align:right; padding:8px;">Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
            <tr>
                <td style="padding:8px;">{{ $item->title }}</td>
                <td style="padding:8px; text-align:center;">{{ $item->quantity }}</td>
                <td style="padding:8px; text-align:right;">{{ number_format($item->total_price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin-top:16px;">
        <strong>Total: {{ $order->currency->code ?? 'SGD' }} {{ number_format($order->total_amount, 2) }}</strong>
    </p>

    <p>Thanks for shopping with Pustaka Nasional!</p>
</body>
</html>