<h1>Order Confirmed!</h1>
<p>Hello {{ $order->user->name ?? 'Customer' }},</p>
<p>Thank you for your order <strong>#{{ $order->id }}</strong>.</p>
<p>Total Amount: ₹{{ number_format($order->total_amount, 2) }}</p>
<p>We will notify you when it ships!</p>