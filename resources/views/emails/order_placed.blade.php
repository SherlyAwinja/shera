<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>Order Received - {{ config('app.name') }}</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <style>
            body {
                font-family: Arial, Helvetica, sans-serif;
                margin: 0;
                padding: 0;
                background: #f7fafc;
                color: #333;
            }

            .wrapper {
                width: 100%;
                padding: 24px 0;
                background: #f7fafc;
            }

            .container {
                max-width: 680px;
                margin: 0 auto;
                background: #ffffff;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 4px 18px rgba(0,0,0,0.06);
            }

            .header {
                background: #2c7be5;
                color: #fff;
                padding: 22px;
                text-align: center;
            }

            .header h1 {
                margin: 0;
                font-size: 20px;
            }

            .body {
                padding: 22px;
                font-size: 15px;
                line-height: 1.6;
            }

            .muted {
                color: #6b7280;
                font-size: 13px;
            }

            .small {
                font-size: 13px;
                color: #6b7280;
            }

            .order-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 12px;
            }

            .order-table th,
            .order-table td {
                text-align: left;
                padding: 8px 6px;
                border-bottom: 1px solid #eee;
            }

            .summary {
                margin-top: 14px;
                padding: 12px;
                background: #fbfbfe;
                border-radius: 6px;
                font-size: 14px;
            }

            .btn {
                display: inline-block;
                margin-top: 14px;
                padding: 10px 16px;
                background: #2c7be5;
                color: #fff !important;
                text-decoration: none;
                border-radius: 6px;
                font-weight: 600;
            }

            .footer {
                text-align: center;
                padding: 16px;
                background: #f1f5f9;
                font-size: 13px;
                color: #6b7280;
            }
        </style>
    </head>
    <body>
        <div class="wrapper">
            <div class="container">

                <div class="header">
                    <h1>Order Received</h1>
                </div>

                <div class="body">
                    <h2>
                        Hello {{ $order->user->name ?? ($order->user->email ?? 'Customer') }},
                    </h2>

                    <p class="small">
                        Thank you for your order. We have received your order and it's now pending confirmation.
                    </p>

                    <p>
                        <strong>Order ID:</strong> #{{ $order->id }}<br>
                        <strong>Payment Method:</strong> {{ strtoupper($order->payment_method ?? 'N/A') }}<br>
                        <strong>Order Total:</strong> {{ number_format($order->total ?? 0, 2) }}
                    </p>

                    <h3>Items</h3>

                    <table class="order-table">
                        <thead>
                            <tr>
                                <th style="width:60%;">Product</th>
                                <th style="width:15%;">Qty</th>
                                <th style="width:25%;">Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                <tr>
                                    <td>
                                        {{ $item->product->name ?? $item->product_name ?? 'Product' }}
                                        @if(!empty($item->product) && !empty($item->product->sku))
                                            <div class="muted">SKU: {{ $item->product->sku }}</div>
                                        @endif
                                    </td>

                                    <td>
                                        {{ $item->quantity ?? $item->qty ?? 1 }}
                                    </td>

                                    <td>
                                        {{ number_format($item->price ?? $item->unit_price ?? 0, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="summary">
                        <div><strong>Subtotal:</strong> {{ number_format($order->subtotal ?? 0, 2) }}</div>
                        <div><strong>Shipping:</strong> {{ number_format($order->shipping ?? 0, 2) }}</div>
                        <div><strong>Discount:</strong> {{ number_format($order->discount ?? 0, 2) }}</div>
                        <div style="margin-top:6px;">
                            <strong>Total:</strong> {{ number_format($order->total ?? 0, 2) }}
                        </div>
                    </div>

                    <h3 style="margin-top:12px;">Shipping Address</h3>

                    @if($order->address)
                        <p class="small">
                            {{ $order->address->first_name ?? '' }} {{ $order->address->last_name ?? '' }}<br>
                            {{ $order->address->address_line1 ?? '' }}<br>

                            @if(!empty($order->address->address_line2))
                                {{ $order->address->address_line2 }}<br>
                            @endif

                            {{ $order->address->city ?? '' }},
                            {{ $order->address->state ?? '' }} -
                            {{ $order->address->postcode ?? '' }}<br>

                            {{ $order->address->country ?? '' }}<br>

                            Mobile: {{ $order->address->mobile ?? $order->user->mobile ?? 'N/A' }}
                        </p>
                    @else
                        <p class="small">No shipping address available.</p>
                    @endif

                    <a href="{{ url('/orders/' . $order->id) }}" class="btn" target="_blank">
                        View Order
                    </a>

                    <p class="small" style="margin-top:16px;">
                        If you have any questions, reply to this email or visit our support page.
                    </p>
                </div>

                <div class="footer">
                    &copy; {{ date('Y') }} {{ config('app.name') }} - Thanks for shopping with us.
                </div>

            </div>
        </div>
    </body>
</html>
