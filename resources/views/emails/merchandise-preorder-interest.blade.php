<!doctype html>
<html>
<body>
    <h2>Merchandise Pre-order Interest</h2>

    @php
        $lineItem = $order->items->first();
    @endphp

    <p><strong>Order #:</strong> {{ $order->id }}</p>
    <p><strong>Status:</strong> {{ \App\Enums\MerchandiseOrderStatus::label($order->status) }}</p>
    <p><strong>Submitted:</strong> {{ $order->submitted_at?->toDateTimeString() }}</p>
    <p><strong>Name:</strong> {{ $order->customer_name ?: '(not provided)' }}</p>
    <p><strong>Email:</strong> {{ $order->customer_email }}</p>
    <p><strong>Payment Info:</strong> Not collected online.</p>

    <hr>

    <p><strong>Item:</strong> {{ $lineItem?->item_name ?: '(unknown)' }}</p>
    <p><strong>Interested Quantity:</strong> {{ $lineItem?->quantity ?: 0 }}</p>
    <p><strong>Displayed Price:</strong> ${{ number_format(($lineItem?->unit_price_cents ?: 0) / 100, 2) }}</p>

    @if(!empty($order->notes))
        <p><strong>Additional Notes:</strong></p>
        <p>{!! nl2br(e($order->notes)) !!}</p>
    @endif
</body>
</html>
