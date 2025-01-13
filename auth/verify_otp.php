<?php
require_once "../config/session.php";
require_once "../config/database.php";
require_once "../includes/functions.php";

// Check if user is in OTP verification phase
if(!isset($_SESSION["pending_user_id"]) || !isset($_SESSION["otp_secret"])) {
    header("location: login.php");
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
        <p class="text-center">Enter the OTP code to complete login</p>
        
        <!-- For demo purposes, display OTP -->
        <div class="alert alert-info">
            <strong>Demo Mode:</strong> Your OTP is: <?php echo $_SESSION["demo_otp"]; ?>
            <br>
            <small>(In production, this would be sent to your email)</small>
        </div>

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
