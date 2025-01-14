<?php
session_start();
require_once "../config/database.php";
require_once "../includes/functions.php";

// Check if user is in OTP verification phase
if(!isset($_SESSION["pending_user_id"]) || !isset($_SESSION["otp_secret"]) || !isset($_SESSION["otp_time"])) {
    header("location: login.php");
    exit;
}

// Check if OTP has expired (5 minutes)
if (time() - $_SESSION["otp_time"] > 10) {
    // Clear OTP session variables
    unset($_SESSION["pending_user_id"]);
    unset($_SESSION["otp_secret"]);
    unset($_SESSION["otp_time"]);
    
    header("location: login.php?error=otp_expired");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify OTP - TM Customer Data System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .wrapper {
            width: 360px;
            padding: 20px;
            margin: 100px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2 class="text-center mb-4">Verify OTP</h2>
        <p class="text-center">Enter the OTP code sent to your email to complete login</p>
        
        <?php if(isset($_GET["error"]) && $_GET["error"] == "otp_expired"): ?>
        <div class="alert alert-danger">
            OTP has expired. Please login again to receive a new OTP.
        </div>
        <?php endif; ?>

        <form action="login.php" method="post">
            <div class="form-group">
                <label>Enter OTP</label>
                <input type="text" name="otp" class="form-control" required>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary btn-block" value="Verify OTP">
            </div>
        </form>
    </div>
</body>
</html>
