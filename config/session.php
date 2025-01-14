<?php
// First check if session is already active
if (session_status() === PHP_SESSION_NONE) {
    // Session configuration - must be set before session_start()
    if (strpos($_SERVER['PHP_SELF'], 'verify_otp.php') === false) {
        // Only apply short timeout for regular pages, not OTP verification
        ini_set('session.gc_maxlifetime', 60);    // 20 seconds timeout
        ini_set('session.cookie_lifetime', 60);   
    } else {
        // Use longer timeout for OTP verification
        ini_set('session.gc_maxlifetime', 300);    // 5 minutes for OTP
        ini_set('session.cookie_lifetime', 300);   
    }

// Session security settings
ini_set('session.cookie_httponly', 1);       // Prevent JavaScript access to session cookie
ini_set('session.cookie_secure', 0);         // Allow cookies over HTTP for localhost
ini_set('session.use_strict_mode', 1);       // Only use cookies created by the server
ini_set('session.use_only_cookies', 1);      // Only use cookies for session handling
ini_set('session.cookie_samesite', 'Lax');   // Protect against CSRF attacks

// Session name (avoid default 'PHPSESSID')
session_name('TM_SECURE_SESSION');

session_start();
}

// Custom session handling
function sessionTimeoutCheck() {
    $max_lifetime = 20; // 30 minutes in seconds
    
    if (isset($_SESSION['LAST_ACTIVITY'])) {
        $inactive_time = time() - $_SESSION['LAST_ACTIVITY'];
        
        if ($inactive_time >= $max_lifetime) {
            // Session has expired
            session_unset();     // Remove all session variables
            session_destroy();    // Destroy the session
            header("location: /customerdata/auth/login.php?timeout=1");
            exit;
        }
    }
    
    // Update last activity timestamp
    $_SESSION['LAST_ACTIVITY'] = time();
}

// Function to regenerate session ID periodically
function regenerateSessionId() {
    $regenerate_time = 300; // 5 minutes in seconds
    
    if (!isset($_SESSION['CREATED'])) {
        $_SESSION['CREATED'] = time();
    } else if (time() - $_SESSION['CREATED'] > $regenerate_time) {
        // Regenerate session ID every 5 minutes
        session_regenerate_id(true);
        $_SESSION['CREATED'] = time();
    }
}

// Run security checks
sessionTimeoutCheck();
regenerateSessionId();

// Add CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
