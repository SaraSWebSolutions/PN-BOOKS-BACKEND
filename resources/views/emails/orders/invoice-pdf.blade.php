<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; color: #333; font-size: 13px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header img { width: 100px; height: auto; margin-bottom: 8px; }
        .header h1 { margin: 0; font-size: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th { background: #f2f2f2; text-align: left; padding: 8px; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row td { font-weight: bold; border-top: 2px solid #333; border-bottom: none; }
        .meta { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('assets/images/PN_Books_logo_png.png') }}" alt="Pustaka Nasional">
        <h1>Pustaka Nasional</h1>
        <p>Invoice</p>
    </div>

    <div class="meta">
        <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
        <p><strong>Order Date:</strong> {{ $order->placed_at?->format('d M Y, h:i A') }}</p>
        <p><strong>Customer:</strong> {{ $order->user->name ?? 'N/A' }} ({{ $order->user->email ?? 'N/A' }})</p>
        <p><strong>Payment Method:</strong> {{ strtoupper($order->payment_method) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Book</th>
                <th class="text-center">Format</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
            <tr>
                <td>{{ $item->title }}</td>
                <td class="text-center">{{ $item->format_name }}</td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                <td class="text-right">{{ number_format($item->total_price, 2) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4" class="text-right">Total</td>
                <td class="text-right">{{ $order->currency->code ?? 'SGD' }} {{ number_format($order->total_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <p style="margin-top: 30px;">Thank you for shopping with Pustaka Nasional!</p>
</body>
</html>