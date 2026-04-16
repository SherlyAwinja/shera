<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <title>Order Status Updated - {{ config('app.name') }}</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <style>
            body {
                font-family: Arial, Helvetica, sans-serif;
                margin: 0;
                padding: 0;
                background: #f7fafc;
                color: #222;
            }

            .wrapper {
                width: 100%;
                padding: 24px 0;
                background: #f7fafc;
            }

            .container {
                max-width: 680px;
                margin: 0 auto;
                background: #fff;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 4px 18px rgba(0,0,0,0.06);
            }

            .header {
                background: #0ea5a3;
                color: #fff;
                padding: 18px;
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

            .info {
                background: #f3f9ff;
                padding: 12px;
                border-radius: 6px;
                margin-top: 10px;
            }

            .btn {
                display: inline-block;
                margin-top: 12px;
                padding: 10px 16px;
                background: #0ea5a3;
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

            .small {
                font-size: 13px;
                color: #6b7280;
            }
        </style>
    </head>

    <body>
        <div class="wrapper">
            <div class="container">

                <div class="header">
                    <h1>Order Status Updated</h1>
                </div>

                <div class="body">

                    <h2>Hello {{ $order->user->name ?? 'Customer' }},</h2>

                    <p>
                        The status of your order <strong>#{{ $order->id }}</strong>
                        has been updated to
                        <strong>{{ $log->status->name ?? $order->status ?? 'Updated' }}</strong>.
                    </p>

                    @if(!empty($log->remarks))
                        <div class="info">
                            <strong>Remarks:</strong>
                            <div style="margin-top:6px; white-space:pre-wrap;">
                                {!! nl2br(e($log->remarks)) !!}
                            </div>
                        </div>
                    @endif

                    @php
                        $shippingPartner = $log->tracking_link
                            ? ($log->shipping_partner ?? $order->shipping_partner)
                            : ($log->shipping_partner ?? $order->shipping_partner ?? null);

                        $trackingNumber = $log->tracking_number ?? $order->tracking_number ?? null;
                        $trackingLink   = $log->tracking_link ?? $order->tracking_link ?? null;
                    @endphp

                    @if(strtolower($log->status->name ?? $order->status ?? '') === 'shipped' || $trackingNumber || $trackingLink)

                        <h3 style="margin-top:10px;">Shipping Details</h3>

                        <p class="small">
                            <strong>Shipping Partner:</strong> {{ $shippingPartner ?? 'N/A' }}<br>
                            <strong>Tracking Number:</strong> {{ $trackingNumber ?? 'N/A' }}
                        </p>

                        @if($trackingLink)
                            <p>
                                <a href="{{ $trackingLink }}" class="btn" target="_blank">
                                    Track Shipment
                                </a>
                            </p>

                        @elseif($trackingNumber)
                            @php
                                $searchQuery = rawurlencode(($shippingPartner ?? '') . ' ' . $trackingNumber);
                                $trackUrl = 'https://www.google.com/search?q=' . $searchQuery;
                            @endphp

                            <p>
                                <a href="{{ $trackUrl }}" class="btn" target="_blank">
                                    Track Shipment
                                </a>
                            </p>
                        @endif

                    @endif

                    <div style="margin-top:12px;">
                        <a href="{{ url('/orders/' . $order->id) }}" class="btn">
                            View Order
                        </a>
                    </div>

                    <p class="muted" style="margin-top:14px;">
                        If you didn't request this change or need help, please contact support.
                    </p>

                </div>

                <div class="footer">
                    &copy; {{ date('Y') }} {{ config('app.name') }} - Order updates from our team.
                </div>

            </div>
        </div>
    </body>
</html>
