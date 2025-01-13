<?php
// SMTP Configuration for Gmail

/*
IMPORTANT: To use Gmail SMTP, follow these steps:
1. Go to your Google Account settings (https://myaccount.google.com)
2. Enable 2-Step Verification if not already enabled
3. Generate an App Password:
   - Go to Security settings
   - Under "2-Step Verification", click on "App passwords"
   - Select "Mail" and your device
   - Copy the generated 16-character password
4. Replace the SMTP_PASSWORD below with your generated App Password
*/

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'ai220262@student.uthm.edu.my');
define('SMTP_PASSWORD', 'wgjdlgsgwmdhmqgn');
define('SMTP_FROM_NAME', 'TM Customer Data System');
?>
