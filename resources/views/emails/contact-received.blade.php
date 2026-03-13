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
                        <td style="background: linear-gradient(135deg, #6D31ED 0%, #15ABFF 100%); padding: 32px 40px; text-align: center;">
                            <img src="{{ config('app.url') }}/logo.png" alt="NG Windsong Kenya" width="120" style="display: block; margin: 0 auto 12px;">
                            <h1 style="color: #ffffff; font-size: 22px; font-weight: 600; margin: 0;">New Contact Message</h1>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 36px 40px 20px;">
                            <p style="color: #64748b; font-size: 14px; margin: 0 0 24px; line-height: 1.6;">
                                You have received a new message from the contact form.
                            </p>

                            <!-- Info Card -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8f5ff; border-radius: 12px; border-left: 4px solid #6D31ED;">
                                <tr>
                                    <td style="padding: 24px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding-bottom: 16px;">
                                                    <span style="color: #6D31ED; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Name</span><br>
                                                    <span style="color: #1e293b; font-size: 15px; font-weight: 500;">{{ $contactMessage->first_name }} {{ $contactMessage->last_name }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding-bottom: 16px;">
                                                    <span style="color: #6D31ED; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Email</span><br>
                                                    <a href="mailto:{{ $contactMessage->email }}" style="color: #15ABFF; font-size: 15px; font-weight: 500; text-decoration: none;">{{ $contactMessage->email }}</a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <span style="color: #6D31ED; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Message</span><br>
                                                    <p style="color: #334155; font-size: 14px; line-height: 1.7; margin: 6px 0 0; white-space: pre-wrap;">{{ $contactMessage->message }}</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Reply Button -->
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 28px auto 0;">
                                <tr>
                                    <td style="border-radius: 50px; background: linear-gradient(135deg, #6D31ED 0%, #15ABFF 100%);">
                                        <a href="mailto:{{ $contactMessage->email }}" style="display: inline-block; padding: 14px 36px; color: #ffffff; font-size: 14px; font-weight: 600; text-decoration: none;">Reply to {{ $contactMessage->first_name }}</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 24px 40px 32px; text-align: center; border-top: 1px solid #f1e8ff;">
                            <p style="color: #94a3b8; font-size: 12px; margin: 0;">
                                Sent on {{ $contactMessage->created_at->format('M d, Y \a\t h:i A') }}
                            </p>
                            <p style="color: #c4b5fd; font-size: 11px; margin: 8px 0 0;">
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
