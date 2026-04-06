<h1>Order Confirmed!</h1>
<p>Hello {{ $order->user->name }},</p>
<p>Thank you for your order <strong>#{{ $order->id }}</strong>.</p>
<p>Total Amount: ${{ number_format($order->total_price, 2) }}</p>
<p>We will notify you when it ships!</p>