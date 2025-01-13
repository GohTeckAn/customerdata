<?php
session_start();
require_once "../config/database.php";
require_once "../includes/functions.php";

$login_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if this is an OTP verification
    if(isset($_POST["otp"])) {
        $otp = trim($_POST["otp"]);
        if(isset($_SESSION["pending_user_id"]) && isset($_SESSION["otp_secret"])) {
            if($otp == $_SESSION["otp_secret"]) {
                // Get user details
                $sql = "SELECT * FROM users WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $_SESSION["pending_user_id"]);
                mysqli_stmt_execute($stmt);
                $user = mysqli_fetch_array(mysqli_stmt_get_result($stmt));
                
                if($user) {
                    // OTP verified, log the user in
                    $_SESSION["loggedin"] = true;
                    $_SESSION["id"] = $user["id"];
                    $_SESSION["username"] = $user["username"];
                    $_SESSION["role"] = $user["role"];
                    
                    // Clear OTP session variables
                    unset($_SESSION["pending_user_id"]);
                    unset($_SESSION["otp_secret"]);
                    
                    header("location: ../index.php");
                    exit;
                }
            } else {
                $login_err = "Invalid OTP code.";
            }
        }
    } else {
        // Normal login attempt
        $username = trim($_POST["username"]);
        $password = $_POST["password"];
        
        $sql = "SELECT * FROM users WHERE username = ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $username);
            
            if(mysqli_stmt_execute($stmt)){
                $result = mysqli_stmt_get_result($stmt);
                
                if(mysqli_num_rows($result) == 1){
                    $user = mysqli_fetch_array($result);
                    
                    if(password_verify($password, $user["password"])){
                        // Generate OTP
                        $otp_secret = rand(100000, 999999);
                        
                        // Store OTP in session
                        $_SESSION["pending_user_id"] = $user["id"];
                        $_SESSION["otp_secret"] = $otp_secret;
                        $_SESSION["demo_otp"] = $otp_secret;
                        
                        header("location: verify_otp.php");
                        exit;
                    } else {
                        $login_err = "Invalid username or password.";
                    }
                } else {
                    $login_err = "Invalid username or password.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - TM Customer Data System</title>
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
        <h2 class="text-center mb-4">Login</h2>
        <p class="text-center">TM Customer Data System</p>

        <?php 
        if(!empty($login_err)){
            echo '<div class="alert alert-danger">' . $login_err . '</div>';
        }        
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>    
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary btn-block" value="Login">
            </div>
        </form>
    </div>
</body>
</html>
