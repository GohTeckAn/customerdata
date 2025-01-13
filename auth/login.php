<?php
session_start();
require_once "../config/database.php";
require_once "../includes/functions.php";

// Constants for login security
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 15 * 60); // 15 minutes in seconds

$login_err = "";

function clearExpiredAttempts($user_id, $conn) {
    $sql = "DELETE FROM login_attempts 
            WHERE user_id = ? 
            AND success = 0 
            AND attempted_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
}

function isUserLockedOut($username, $conn) {
    $sql = "SELECT id FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_array($result);
    
    if ($user) {
        // First clear expired attempts
        clearExpiredAttempts($user['id'], $conn);
        
        // Then check remaining failed attempts
        $sql = "SELECT COUNT(*) as failed_attempts 
                FROM login_attempts 
                WHERE user_id = ? 
                AND success = 0 
                AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user['id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $attempts = mysqli_fetch_array($result);
        
        return $attempts['failed_attempts'] >= MAX_LOGIN_ATTEMPTS;
    }
    return false;
}

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
                $result = mysqli_stmt_get_result($stmt);
                $user = mysqli_fetch_array($result);
                
                if($user) {
                    // Record successful login
                    $sql = "INSERT INTO login_attempts (user_id, ip_address, success) VALUES (?, ?, 1)";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "is", $user["id"], $_SERVER['REMOTE_ADDR']);
                    mysqli_stmt_execute($stmt);

                    // Set session variables
                    $_SESSION["loggedin"] = true;
                    $_SESSION["id"] = $user["id"];
                    $_SESSION["username"] = $user["username"];
                    $_SESSION["role"] = $user["role"];
                    
                    // Clear OTP session variables
                    unset($_SESSION["pending_user_id"]);
                    unset($_SESSION["otp_secret"]);
                    unset($_SESSION["demo_otp"]);
                    
                    // Redirect to index.php
                    header("Location: ../index.php");
                    exit();
                }
            } else {
                $login_err = "Invalid OTP code.";
            }
        }
    } else {
        // Normal login attempt
        $username = trim($_POST["username"]);
        $password = $_POST["password"];
        
        // Check if user exists first
        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_array($result);
            
            // Clear any expired attempts before checking lockout
            clearExpiredAttempts($user['id'], $conn);
            
            // Check if user is locked out
            if (isUserLockedOut($username, $conn)) {
                $login_err = "Account is temporarily locked due to too many failed attempts. Please try again in 15 minutes.";
            } else {
                if(password_verify($password, $user["password"])) {
                    // Record successful initial login
                    $sql = "INSERT INTO login_attempts (user_id, ip_address, success) VALUES (?, ?, 1)";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "is", $user["id"], $_SERVER['REMOTE_ADDR']);
                    mysqli_stmt_execute($stmt);

                    // Generate and store OTP
                    $otp_secret = rand(100000, 999999);
                    $_SESSION["pending_user_id"] = $user["id"];
                    $_SESSION["otp_secret"] = $otp_secret;
                    $_SESSION["demo_otp"] = $otp_secret;
                    
                    // Redirect to OTP verification
                    header("Location: verify_otp.php");
                    exit();
                } else {
                    // Record failed attempt
                    $sql = "INSERT INTO login_attempts (user_id, ip_address, success) VALUES (?, ?, 0)";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "is", $user["id"], $_SERVER['REMOTE_ADDR']);
                    mysqli_stmt_execute($stmt);
                    
                    $login_err = "Invalid username or password.";
                }
            }
        } else {
            $login_err = "Invalid username or password.";
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
