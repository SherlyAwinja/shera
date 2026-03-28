<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Welcome to {{ config('app.name') }}</title>
</head>
<body style="margin:0; padding:20px; background:#f9f9f9; font-family: Arial, sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">

                <!-- Main Container -->
                <table width="600" cellpadding="0" cellspacing="0" role="presentation" style="background:#ffffff; border-radius:8px; overflow:hidden;">

                    <!-- Header -->
                    <tr style="background:#2c7be5; color:#ffffff;">
                        <td style="padding:20px; text-align:center;">
                            <h1 style="margin:0; font-size:24px;">
                                Welcome to {{ config('app.name') }}
                            </h1>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:30px;">

                            <h2 style="margin-top:0;">
                                Hello {{ $user->name ?? 'User' }},
                            </h2>

                            <p style="font-size:16px; color:#333;">
                                Thank you for registering with
                                <strong>{{ config('app.name') }}</strong>.
                                Your account is now ready.
                            </p>

                            <p style="font-size:16px; color:#333;">
                                You can now explore products, shop, and enjoy our services.
                            </p>

                            <!-- Button -->
                            <p style="margin:30px 0;">
                                <a href="{{ url('/') }}"
                                   style="background:#2c7be5; color:#ffffff; padding:12px 20px; border-radius:5px; text-decoration:none; font-size:14px;">
                                    Visit Our Store
                                </a>
                            </p>

                            <!-- Footer -->
                            <p style="color:#999; font-size:13px; margin-top:30px;">
                                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                            </p>

                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
