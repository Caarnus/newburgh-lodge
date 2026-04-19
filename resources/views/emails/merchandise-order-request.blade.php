<!doctype html>
<html>
<body>
    <h2>Merchandise Order Request</h2>

    <p><strong>Order #:</strong> {{ $order->id }}</p>
    <p><strong>Status:</strong> {{ \App\Enums\MerchandiseOrderStatus::label($order->status) }}</p>
    <p><strong>Submitted:</strong> {{ $order->submitted_at?->toDateTimeString() }}</p>
    <p><strong>Name:</strong> {{ $order->customer_name ?: '(not provided)' }}</p>
    <p><strong>Email:</strong> {{ $order->customer_email }}</p>
    <p><strong>Phone:</strong> {{ $order->customer_phone ?: '(not provided)' }}</p>
    <p><strong>Payment Info:</strong> Not collected online.</p>

    <hr>

    <h3>Requested Items</h3>
    <ul>
        @foreach($order->items as $item)
            <li>
                <strong>{{ $item->item_name }}</strong> -
                Qty: {{ $item->quantity }}
                @if($item->size)
                    - Size: {{ $item->size }}
                @endif
                - ${{ number_format($item->unit_price_cents / 100, 2) }} each
                - Line Total: ${{ number_format(($item->unit_price_cents * $item->quantity) / 100, 2) }}
            </li>
        @endforeach
    </ul>

    <p>
        <strong>Estimated Total:</strong>
        ${{ number_format(($order->items->sum(fn ($item) => $item->unit_price_cents * $item->quantity)) / 100, 2) }}
    </p>

    @if(!empty($order->notes))
        <p><strong>Additional Notes:</strong></p>
        <p>{!! nl2br(e($order->notes)) !!}</p>
    @endif
</body>
</html>
