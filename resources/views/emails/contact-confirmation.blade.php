<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #f4f0fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f0fa; padding: 40px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(109, 49, 237, 0.08);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #6D31ED 0%, #15ABFF 100%); padding: 40px 40px 32px; text-align: center;">
                            <img src="{{ asset('logo.png') }}" alt="NG Windsong Kenya" width="120" style="display: block; margin: 0 auto 16px;">
                            <h1 style="color: #ffffff; font-size: 24px; font-weight: 600; margin: 0;">Thank You, {{ $contactMessage->first_name }}!</h1>
                            <p style="color: rgba(255,255,255,0.85); font-size: 14px; margin: 8px 0 0;">We've received your message</p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 36px 40px 20px;">
                            <!-- Check Icon -->
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 0 auto 24px;">
                                <tr>
                                    <td style="width: 56px; height: 56px; border-radius: 50%; background-color: #f0fdf4; text-align: center; line-height: 56px;">
                                        <span style="color: #288747; font-size: 28px;">&#10003;</span>
                                    </td>
                                </tr>
                            </table>

                            <p style="color: #334155; font-size: 15px; line-height: 1.7; text-align: center; margin: 0 0 24px;">
                                We appreciate you reaching out to us. Our team will review your message and get back to you as soon as possible.
                            </p>

                            <!-- Message Summary -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8f5ff; border-radius: 12px;">
                                <tr>
                                    <td style="padding: 24px;">
                                        <p style="color: #6D31ED; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 12px;">Your Message</p>
                                        <p style="color: #475569; font-size: 14px; line-height: 1.7; margin: 0; white-space: pre-wrap;">{{ $contactMessage->message }}</p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Divider -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 28px 0;">
                                <tr>
                                    <td style="border-top: 1px solid #f1e8ff;"></td>
                                </tr>
                            </table>

                            <!-- Contact Info -->
                            <p style="color: #64748b; font-size: 13px; text-align: center; line-height: 1.6; margin: 0;">
                                In the meantime, feel free to reach us at<br>
                                <a href="mailto:sales@ngwindsongk.com" style="color: #6D31ED; text-decoration: none; font-weight: 600;">sales@ngwindsongk.com</a>
                                &nbsp;or&nbsp;
                                <a href="tel:+254113748906" style="color: #6D31ED; text-decoration: none; font-weight: 600;">+254 113 748 906</a>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 24px 40px 32px; text-align: center; border-top: 1px solid #f1e8ff;">
                            <p style="color: #c4b5fd; font-size: 11px; margin: 0;">
                                NG Windsong Kenya Ltd &bull; Bazaar Plaza, Moi Avenue, Nairobi
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
