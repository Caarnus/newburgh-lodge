<!doctype html>
<html>
<body>
    <h2>Merchandise Pre-order Interest</h2>

    <p><strong>Order #:</strong> {{ $order->id }}</p>
    <p><strong>Status:</strong> {{ \App\Enums\MerchandiseOrderStatus::label($order->status) }}</p>
    <p><strong>Submitted:</strong> {{ $order->submitted_at?->toDateTimeString() }}</p>
    <p><strong>Name:</strong> {{ $order->customer_name ?: '(not provided)' }}</p>
    <p><strong>Email:</strong> {{ $order->customer_email }}</p>
    <p><strong>Payment Info:</strong> Not collected online.</p>

    <hr>

    <h3>Requested Pre-order Items</h3>
    <ul>
        @foreach($order->items as $item)
            <li>
                <strong>{{ $item->item_name }}</strong> -
                Qty: {{ $item->quantity }}
                - Displayed Price: ${{ number_format($item->unit_price_cents / 100, 2) }}
            </li>
        @endforeach
    </ul>

    @if(!empty($order->notes))
        <p><strong>Additional Notes:</strong></p>
        <p>{!! nl2br(e($order->notes)) !!}</p>
    @endif
</body>
</html>
