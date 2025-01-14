<?php
session_start();

// Session timeout check (30 minutes)
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    session_unset();
    session_destroy();
    header("location: login.php?timeout=1");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

// Session ID regeneration every 5 minutes
if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = time();
} else if (time() - $_SESSION['CREATED'] > 300) {
    session_regenerate_id(true);
    $_SESSION['CREATED'] = time();
}

require_once "../config/database.php";
require_once "../includes/functions.php";
require_once "../includes/mail.php";
require_once "../config/recaptcha.php";

// Constants for login security
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 15 * 60); // 15 minutes in seconds
define('OTP_EXPIRY_TIME', 300); // 5 minutes in seconds

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

function getRemainingAttempts($username, $conn) {
    $sql = "SELECT COUNT(*) as failed_attempts 
            FROM login_attempts 
            WHERE user_id = (SELECT id FROM users WHERE username = ?)
            AND success = 0 
            AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $attempts = mysqli_fetch_array($result);
    
    return MAX_LOGIN_ATTEMPTS - ($attempts['failed_attempts'] ?? 0);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if this is an OTP verification
    if(isset($_POST["otp"])) {
        $otp = trim($_POST["otp"]);
        if(isset($_SESSION["pending_user_id"]) && isset($_SESSION["otp_secret"]) && isset($_SESSION["otp_time"])) {
            $time_elapsed = time() - $_SESSION["otp_time"];
            
            if($time_elapsed <= OTP_EXPIRY_TIME) {
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
                    $remaining_time = OTP_EXPIRY_TIME - $time_elapsed;
                    $login_err = "Invalid OTP code. Time remaining: " . floor($remaining_time/60) . "m " . ($remaining_time%60) . "s";
                }
            } else {
                $login_err = "OTP has expired. Please login again.";
                unset($_SESSION["pending_user_id"]);
                unset($_SESSION["otp_secret"]);
                unset($_SESSION["otp_time"]);
                header("location: login.php?expired=1");
                exit;
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
            
                        // Check if account is locked
                    if ($user["locked_until"] !== null) {
                        $current_time = new DateTime();
                        $lock_time = new DateTime($user["locked_until"]);
                        
                        if ($current_time < $lock_time) {
                            $time_remaining = $current_time->diff($lock_time);
                            $login_err = "Account is locked. Please try again after " . 
                                       $time_remaining->format('%i minutes and %s seconds');
                            mysqli_stmt_close($stmt);
                            goto display_page;
                        } else {
                            // Lock period has expired, reset the lock and attempts
                            $reset_sql = "UPDATE users SET locked_until = NULL, login_attempts = 0 WHERE id = ?";
                            $reset_stmt = mysqli_prepare($conn, $reset_sql);
                            mysqli_stmt_bind_param($reset_stmt, "i", $user["id"]);
                            mysqli_stmt_execute($reset_stmt);
                        }
                    }

                            if(password_verify($password, $user["password"])){
                                // Successful login - reset login attempts
                        $reset_sql = "UPDATE users SET login_attempts = 0 WHERE id = ?";
                        $reset_stmt = mysqli_prepare($conn, $reset_sql);
                        mysqli_stmt_bind_param($reset_stmt, "i", $user["id"]);
                        mysqli_stmt_execute($reset_stmt);
                        
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
                            // Failed login attempt - increment counter
                        $attempts = $user["login_attempts"] + 1;
                        
                        if ($attempts >= 5) {
                            // Lock the account for 15 minutes
                            $locked_until = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                            $update_sql = "UPDATE users SET login_attempts = ?, locked_until = ? WHERE id = ?";
                            $update_stmt = mysqli_prepare($conn, $update_sql);
                            mysqli_stmt_bind_param($update_stmt, "isi", $attempts, $locked_until, $user["id"]);
                            mysqli_stmt_execute($update_stmt);
                            
                            $login_err = "Account has been locked for 5 minutes due to multiple failed attempts.";
                        } else {
                            // Update attempt counter
                            $update_sql = "UPDATE users SET login_attempts = ? WHERE id = ?";
                            $update_stmt = mysqli_prepare($conn, $update_sql);
                            mysqli_stmt_bind_param($update_stmt, "ii", $attempts, $user["id"]);
                            mysqli_stmt_execute($update_stmt);
                            
                            $remaining_attempts = 5 - $attempts;
                            $login_err = "Invalid username or password. {$remaining_attempts} attempts remaining before account lockout.";
                        }
                    }
                    } else {
                        $login_err = "Invalid username or password.";
                    }
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
}
display_page:
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - TM Customer Data System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://www.google.com/recaptcha/api.js?render=<?php echo RECAPTCHA_SITE_KEY; ?>"></script>
    <style>
        body { background-color: #f8f9fa; }
        .wrapper {
            width: 360px;
            padding: 20px;
            margin: 100px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        #otp-timer {
            color: #dc3545;
            font-size: 0.9em;
            margin-top: 5px;
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
        if(isset($_GET["expired"])){
            echo '<div class="alert alert-warning">OTP has expired. Please try logging in again.</div>';
        }
        if(isset($_GET["timeout"])){
            echo '<div class="alert alert-warning">Your session has expired. Please log in again.</div>';
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="login-form">
            <?php if(isset($_SESSION["otp_time"])): ?>
            <div class="form-group">
                <label>Enter OTP</label>
                <input type="text" name="otp" class="form-control" required>
                <div id="otp-timer"></div>
            </div>
            <?php else: ?>
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>    
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <input type="hidden" name="recaptcha_response" id="recaptcha_response">
            <?php endif; ?>
            <div class="form-group">
                <input type="submit" class="btn btn-primary btn-block" value="<?php echo isset($_SESSION["otp_time"]) ? 'Verify OTP' : 'Login'; ?>">
            </div>
        </form>
    </div>

    <script>
        <?php if(isset($_SESSION["otp_time"])): ?>
        // OTP Timer
        function updateOTPTimer() {
            const startTime = <?php echo $_SESSION["otp_time"]; ?>;
            const currentTime = Math.floor(Date.now() / 1000);
            const timeElapsed = currentTime - startTime;
            const timeRemaining = <?php echo OTP_EXPIRY_TIME; ?> - timeElapsed;
            
            if(timeRemaining <= 0) {
                document.getElementById('otp-timer').innerHTML = 'OTP has expired. Please refresh to try again.';
                return;
            }
            
            const minutes = Math.floor(timeRemaining / 60);
            const seconds = timeRemaining % 60;
            document.getElementById('otp-timer').innerHTML = `Time remaining: ${minutes}m ${seconds}s`;
        }
        
        updateOTPTimer();
        setInterval(updateOTPTimer, 1000);
        <?php else: ?>
        grecaptcha.ready(function() {
            document.getElementById('login-form').addEventListener('submit', function(e) {
                e.preventDefault();
                grecaptcha.execute('<?php echo RECAPTCHA_SITE_KEY; ?>', {action: 'login'})
                    .then(function(token) {
                        document.getElementById('recaptcha_response').value = token;
                        document.getElementById('login-form').submit();
                    });
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>
