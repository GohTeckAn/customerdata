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
            <body style="font-family: Arial, sans-serif;">
                <h2>Your One-Time Password (OTP)</h2>
                <p>Hello,</p>
                <p>Your OTP for logging into the TM Customer Data System is:</p>
                <h1 style="color: #007bff; font-size: 32px; letter-spacing: 5px;">' . $otp . '</h1>
                <p>This OTP will expire in 5 minutes.</p>
                <p>If you did not request this OTP, please ignore this email.</p>
                <br>
                <p>Best regards,<br>TM Customer Data System</p>
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
