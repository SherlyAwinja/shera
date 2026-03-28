<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Verify your new email</title>
</head>
<body style="margin:0; padding:20px; background:#f9f9f9; font-family: Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" role="presentation" style="background:#ffffff; border-radius:8px; overflow:hidden;">
                    <tr style="background:#12323b; color:#ffffff;">
                        <td style="padding:20px; text-align:center;">
                            <h1 style="margin:0; font-size:24px;">Confirm your new email address</h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:30px;">
                            <p style="font-size:16px; color:#333; margin-top:0;">
                                Hello {{ $user->name ?? 'there' }},
                            </p>

                            <p style="font-size:16px; color:#333;">
                                We received a request to change the email address on your {{ config('app.name') }} account.
                            </p>

                            <p style="font-size:16px; color:#333;">
                                Your current login email remains active until you verify this new address.
                            </p>

                            <p style="margin:30px 0;">
                                <a href="{{ $verificationUrl }}"
                                   style="background:#0f5f73; color:#ffffff; padding:12px 20px; border-radius:5px; text-decoration:none; font-size:14px; display:inline-block;">
                                    Verify New Email
                                </a>
                            </p>

                            <p style="font-size:14px; color:#666;">
                                If the button does not work, copy and paste this link into your browser:
                            </p>

                            <p style="font-size:13px; color:#0f5f73; word-break:break-all;">
                                {{ $verificationUrl }}
                            </p>

                            <p style="color:#999; font-size:13px; margin-top:30px;">
                                If you did not request this change, you can ignore this email and keep using your current address.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
