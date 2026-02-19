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
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px;">Scribes Global</h1>
                            <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0 0; font-size: 16px;">Creative Arts Ministry</p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #1a1a2e; margin: 0 0 20px 0; font-size: 24px;">
                                Reset Your Password
                            </h2>
                            
                            <p style="color: #666666; line-height: 1.6; margin: 0 0 20px 0; font-size: 16px;">
                                Hi <?= htmlspecialchars($first_name) ?>,
                            </p>
                            
                            <p style="color: #666666; line-height: 1.6; margin: 0 0 20px 0; font-size: 16px;">
                                We received a request to reset your password for your Scribes Global account. 
                                Click the button below to create a new password.
                            </p>
                            
                            <!-- Button -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 30px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="<?= $reset_url ?>" style="display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%); color: #ffffff; text-decoration: none; border-radius: 50px; font-weight: bold; font-size: 16px;">
                                            Reset Password
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="color: #666666; line-height: 1.6; margin: 20px 0 0 0; font-size: 14px;">
                                Or copy and paste this link into your browser:
                            </p>
                            <p style="color: #2D9CDB; line-height: 1.6; margin: 10px 0 0 0; font-size: 14px; word-break: break-all;">
                                <?= $reset_url ?>
                            </p>
                            
                            <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 30px 0 0 0; border-radius: 4px;">
                                <p style="margin: 0; color: #856404; font-size: 14px;">
                                    <strong>⚠️ Security Notice:</strong><br>
                                    This link will expire in 1 hour. If you didn't request a password reset, please ignore this email or contact us if you have concerns about your account security.
                                </p>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9f9f9; padding: 30px; text-align: center; border-top: 1px solid #eeeeee;">
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