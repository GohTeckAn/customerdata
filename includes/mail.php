<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require dirname(__DIR__) . '/vendor/autoload.php';
require_once 'mail_config.php';

function sendOTPEmail($to_email, $otp) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_USERNAME, SMTP_FROM_NAME);
        $mail->addAddress($to_email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP for TM Customer Data System';
        $mail->Body = '
            <html>
        <body style="font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f9f9f9;">
            <table align="center" cellpadding="0" cellspacing="0" width="600" style="border-collapse: collapse; background-color: #ffffff; border: 1px solid #dddddd; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); overflow: hidden;">
                <tr>
                    <td align="center" style="background-color: #007bff; padding: 20px;">
                        <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 600;">TM Customer Data System</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 30px;">
                        <h2 style="color: #333333; font-size: 20px; margin-bottom: 20px;">Your One-Time Password (OTP)</h2>
                        <p style="color: #555555; font-size: 16px; line-height: 1.5;">Hello,</p>
                        <p style="color: #555555; font-size: 16px; line-height: 1.5;">Your OTP for logging into the TM Customer Data System is:</p>
                        <div style="margin: 20px 0; text-align: center;">
                            <span style="display: inline-block; padding: 15px 30px; font-size: 28px; color: #ffffff; background-color: #007bff; border-radius: 8px; letter-spacing: 5px; font-weight: bold;">' . $otp . '</span>
                        </div>
                        <p style="color: #555555; font-size: 16px; line-height: 1.5;">This OTP will expire in <strong>5 minutes</strong>.</p>
                        <p style="color: #555555; font-size: 16px; line-height: 1.5;">If you did not request this OTP, please ignore this email.</p>
                    </td>
                </tr>
                <tr>
                    <td align="center" style="background-color: #f2f2f2; padding: 20px;">
                        <p style="color: #777777; font-size: 14px; margin: 0;">Best regards,</p>
                        <p style="color: #007bff; font-size: 14px; font-weight: bold; margin: 0;">TM Customer Data System</p>
                    </td>
                </tr>
            </table>
        </body>
        </html>';

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>
