<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recommended picks</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">
    <p>Hi {{ $order->customer_name ?: 'there' }},</p>
    <p>Thanks for your purchase (Order {{ $order->order_number }}). We picked a few products that pair well with your order:</p>

    <ul style="padding-left: 18px;">
        @foreach($products as $item)
            <li style="margin-bottom: 10px;">
                <a href="{{ route('products.show', ['slug' => $item->slug]) }}" style="color: #0297FE; text-decoration: none;">
                    {{ $item->name }}
                </a>
                - {{ format_price_usd((float) ($item->price ?? $item->template?->base_price ?? 0)) }}
            </li>
        @endforeach
    </ul>

    <p>See more products on our store anytime.</p>
</body>
</html>
