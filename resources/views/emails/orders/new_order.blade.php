<h2>New Sale Alert! 🚀</h2>
<p>Hello Seller,</p>
<p>You have received a new order: <strong>#{{ $order->id }}</strong>.</p>

<table style="width: 100%; border-collapse: collapse;">
    <thead>
        <tr style="background: #f4f4f4;">
            <th style="padding: 10px; border: 1px solid #ddd;">Product</th>
            <th style="padding: 10px; border: 1px solid #ddd;">Qty</th>
            <th style="padding: 10px; border: 1px solid #ddd;">Price</th>
        </tr>
    </thead>
    <tbody>
        @foreach($order->items as $item)
        <tr>
            <td style="padding: 10px; border: 1px solid #ddd;">{{ $item->product->name }}</td>
            <td style="padding: 10px; border: 1px solid #ddd;">{{ $item->quantity }}</td>
            <td style="padding: 10px; border: 1px solid #ddd;">₹{{ $item->price }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<p style="font-size: 18px;"><strong>Total Revenue: ₹{{ $order->total_amount }}</strong></p>

<hr>
<p><strong>Customer Details:</strong></p>
<ul>
    <li>Name: {{ $order->user->name }}</li>
    <li>Email: {{ $order->user->email }}</li>
</ul>

<p>Please log in to your dashboard to process this shipment.</p>