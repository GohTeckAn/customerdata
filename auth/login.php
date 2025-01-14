<?php
session_start();
require_once "../config/database.php";
require_once "../includes/functions.php";
require_once "../includes/mail.php";
require_once "../config/recaptcha.php";

// Constants for login security
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 15 * 60); // 15 minutes in seconds

$login_err = "";

function clearExpiredAttempts($user_id, $conn) {
    $sql = "DELETE FROM login_attempts 
            WHERE id = ? 
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
            if($otp === $_SESSION["otp_secret"]) {
                // Get user details
                $sql = "SELECT * FROM users WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $_SESSION["pending_user_id"]);
                mysqli_stmt_execute($stmt);
                $user = mysqli_fetch_array(mysqli_stmt_get_result($stmt));
                
                if($user) {
                    // Record successful login
                    $sql = "INSERT INTO login_attempts (user_id, ip_address, success) VALUES (?, ?, 1)";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "is", $user["id"], $_SERVER['REMOTE_ADDR']);
                    mysqli_stmt_execute($stmt);
                    
                    // OTP verified, log the user in
                    $_SESSION["loggedin"] = true;
                    $_SESSION["id"] = $user["id"];
                    $_SESSION["username"] = $user["username"];
                    $_SESSION["role"] = $user["role"];
                    
                    // Clear OTP session variables
                    unset($_SESSION["pending_user_id"]);
                    unset($_SESSION["otp_secret"]);
                    unset($_SESSION["otp_time"]);
                    
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
        
        // Verify reCAPTCHA v3
        $recaptcha_response = $_POST['recaptcha_response'];
        $verify_url = "https://www.google.com/recaptcha/api/siteverify";
        $data = [
            'secret' => RECAPTCHA_SECRET_KEY,
            'response' => $recaptcha_response
        ];
        
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];
        
        $context = stream_context_create($options);
        $verify_response = file_get_contents($verify_url, false, $context);
        $response_data = json_decode($verify_response);
        
        if (!$response_data->success || $response_data->score < RECAPTCHA_SCORE_THRESHOLD) {
            $login_err = "Security verification failed. Details: " . 
                        "Success: " . ($response_data->success ? 'true' : 'false') . 
                        ", Score: " . ($response_data->score ?? 'N/A') .
                        ", Error Codes: " . (isset($response_data->{'error-codes'}) ? implode(', ', $response_data->{'error-codes'}) : 'none');
            
            // Log suspicious activity
            error_log("reCAPTCHA verification details - " .
                     "Success: " . ($response_data->success ? 'true' : 'false') . 
                     ", Score: " . ($response_data->score ?? 'N/A') . 
                     ", IP: " . $_SERVER['REMOTE_ADDR'] .
                     ", Error Codes: " . (isset($response_data->{'error-codes'}) ? implode(', ', $response_data->{'error-codes'}) : 'none'));
        } else {
            $sql = "SELECT * FROM users WHERE username = ?";
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "s", $username);
                
                if(mysqli_stmt_execute($stmt)){
                    $result = mysqli_stmt_get_result($stmt);
                    
                    if(mysqli_num_rows($result) == 1){
                        $user = mysqli_fetch_array($result);
                        
                        // Clear any expired attempts before checking lockout
                        clearExpiredAttempts($user['id'], $conn);
            
                        // Check if user is locked out
                        if (isUserLockedOut($username, $conn)) {
                            $login_err = "Account is temporarily locked due to too many failed attempts. Please try again in 15 minutes.";
                        } else {
                            if(password_verify($password, $user["password"])){
                                // Record successful initial login
                                $sql = "INSERT INTO login_attempts (user_id, ip_address, success) VALUES (?, ?, 1)";
                                $stmt = mysqli_prepare($conn, $sql);
                                mysqli_stmt_bind_param($stmt, "is", $user["id"], $_SERVER['REMOTE_ADDR']);
                                mysqli_stmt_execute($stmt);
                        
                                // Generate secure OTP
                                $otp_secret = generateSecureOTP();
                        
                                // Store OTP and timestamp in session
                                $_SESSION["pending_user_id"] = $user["id"];
                                $_SESSION["otp_secret"] = $otp_secret;
                                $_SESSION["otp_time"] = time();
                        
                                // Send OTP via email
                                if(sendOTPEmail($user["email"], $otp_secret)) {
                                header("location: verify_otp.php");
                                exit;
                                } else {
                                $login_err = "Failed to send OTP email. Please try again.";
                                }
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
    <script src="https://www.google.com/recaptcha/api.js?render=<?php echo RECAPTCHA_SITE_KEY; ?>"></script>
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

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="login-form">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>    
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <input type="hidden" name="recaptcha_response" id="recaptcha_response">
            <div class="form-group">
                <input type="submit" class="btn btn-primary btn-block" value="Login">
            </div>
        </form>
    </div>

    <script>
        grecaptcha.ready(function() {
            // Add submit event listener to the form
            document.getElementById('login-form').addEventListener('submit', function(e) {
                e.preventDefault();
                grecaptcha.execute('<?php echo RECAPTCHA_SITE_KEY; ?>', {action: 'login'})
                    .then(function(token) {
                        document.getElementById('recaptcha_response').value = token;
                        document.getElementById('login-form').submit();
                    });
            });
        });
    </script>
</body>
</html>
