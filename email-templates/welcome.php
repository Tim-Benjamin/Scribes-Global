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
                            <h1 style="color: #ffffff; margin: 0; font-size: 32px;">🎉</h1>
                            <h2 style="color: #ffffff; margin: 10px 0 0 0; font-size: 28px;">Welcome to Scribes Global!</h2>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="color: #666666; line-height: 1.6; margin: 0 0 20px 0; font-size: 16px;">
                                Hi <?= htmlspecialchars($first_name) ?>,
                            </p>
                            
                            <p style="color: #666666; line-height: 1.6; margin: 0 0 20px 0; font-size: 16px;">
                                Your account has been successfully verified! We're thrilled to have you as part of the Scribes Global family. 
                                You're now ready to start your creative journey with us.
                            </p>
                            
                            <h3 style="color: #1a1a2e; margin: 30px 0 15px 0; font-size: 20px;">
                                Here's what you can do now:
                            </h3>
                            
                            <ul style="color: #666666; line-height: 1.8; font-size: 15px; margin: 0 0 30px 20px;">
                                <li>Complete your profile and add your creative role</li>
                                <li>Browse and register for upcoming events</li>
                                <li>Share your poetry, music, and testimonies</li>
                                <li>Join a ministry team and connect with others</li>
                                <li>Submit prayer requests and pray for others</li>
                            </ul>
                            
                            <!-- Button -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 30px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="<?= SITE_URL ?>/pages/dashboard" style="display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%); color: #ffffff; text-decoration: none; border-radius: 50px; font-weight: bold; font-size: 16px;">
                                            Go to Dashboard
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <div style="background-color: #f0f9ff; border-left: 4px solid #2D9CDB; padding: 15px; margin: 30px 0 0 0; border-radius: 4px;">
                                <p style="margin: 0; color: #1e40af; font-size: 14px;">
                                    <strong>💡 Pro Tip:</strong> Complete your profile to unlock all features and connect better with the community!
                                </p>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9f9f9; padding: 30px; text-align: center; border-top: 1px solid #eeeeee;">
                            <p style="margin: 0 0 10px 0; color: #666666; font-size: 14px;">
                                Follow us on social media to stay updated!
                            </p>
                            <p style="margin: 0 0 20px 0;">
                                <a href="#" style="margin: 0 10px; color: #6B46C1; font-size: 24px; text-decoration: none;">📘</a>
                                <a href="#" style="margin: 0 10px; color: #6B46C1; font-size: 24px; text-decoration: none;">📸</a>
                                <a href="#" style="margin: 0 10px; color: #6B46C1; font-size: 24px; text-decoration: none;">🐦</a>
                                <a href="#" style="margin: 0 10px; color: #6B46C1; font-size: 24px; text-decoration: none;">📺</a>
                            </p>
                            <p style="margin: 0 0 10px 0; color: #666666; font-size: 14px;">
                                Need help? Contact us at <a href="mailto:<?= SITE_EMAIL ?>" style="color: #6B46C1; text-decoration: none;"><?= SITE_EMAIL ?></a>
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