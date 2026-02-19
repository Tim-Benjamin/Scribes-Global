<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 32px;">✅</h1>
                            <h2 style="color: #ffffff; margin: 10px 0 0 0; font-size: 24px;">Event Registration Confirmed!</h2>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="color: #666666; line-height: 1.6; margin: 0 0 20px 0; font-size: 16px;">
                                Hi <?= htmlspecialchars($first_name) ?>,
                            </p>
                            
                            <p style="color: #666666; line-height: 1.6; margin: 0 0 30px 0; font-size: 16px;">
                                Great news! You're successfully registered for:
                            </p>
                            
                            <!-- Event Details Box -->
                            <div style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-radius: 10px; padding: 25px; margin: 0 0 30px 0;">
                                <h3 style="color: #1a1a2e; margin: 0 0 20px 0; font-size: 22px;">
                                    <?= htmlspecialchars($event_title) ?>
                                </h3>
                                
                                <table width="100%" cellpadding="8" cellspacing="0">
                                    <tr>
                                        <td style="color: #666666; font-size: 15px;">
                                            <strong>📅 Date:</strong>
                                        </td>
                                        <td style="color: #1a1a2e; font-size: 15px;">
                                            <?= $event_date ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="color: #666666; font-size: 15px;">
                                            <strong>🕐 Time:</strong>
                                        </td>
                                        <td style="color: #1a1a2e; font-size: 15px;">
                                            <?= $event_time ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="color: #666666; font-size: 15px;">
                                            <strong>📍 Location:</strong>
                                        </td>
                                        <td style="color: #1a1a2e; font-size: 15px;">
                                            <?= htmlspecialchars($event_location) ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            
                            <!-- Button -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 30px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="<?= $event_url ?>" style="display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%); color: #ffffff; text-decoration: none; border-radius: 50px; font-weight: bold; font-size: 16px;">
                                            View Event Details
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <div style="background-color: #fef3c7; border-left: 4px solid #D4AF37; padding: 15px; margin: 30px 0 0 0; border-radius: 4px;">
                                <p style="margin: 0; color: #92400e; font-size: 14px;">
                                    <strong>📧 Save This Email:</strong><br>
                                    You'll receive a reminder 24 hours before the event. We can't wait to see you there!
                                </p>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9f9f9; padding: 30px; text-align: center; border-top: 1px solid #eeeeee;">
                            <p style="margin: 0 0 10px 0; color: #666666; font-size: 14px;">
                                Questions? Contact us at <a href="mailto:<?= SITE_EMAIL ?>" style="color: #6B46C1; text-decoration: none;"><?= SITE_EMAIL ?></a>
                            </p>
                            <p style="margin: 0; color: #999999; font-size: 12px;">
                                © <?= date('Y') ?> Scribes Global. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>