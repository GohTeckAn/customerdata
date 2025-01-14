<?php
require_once dirname(__DIR__) . "/config/session.php";

function validatePassword($password)
{
    // Password requirements:
    // - Minimum 8 characters
    // - At least one uppercase letter
    // - At least one lowercase letter
    // - At least one number
    // - At least one special character

    if (strlen($password) < 8) {
        return "Password must be at least 8 characters long.";
    }

    if (!preg_match("/[A-Z]/", $password)) {
        return "Password must contain at least one uppercase letter.";
    }

    if (!preg_match("/[a-z]/", $password)) {
        return "Password must contain at least one lowercase letter.";
    }

    if (!preg_match("/[0-9]/", $password)) {
        return "Password must contain at least one number.";
    }

    if (!preg_match("/[!@#$%^&*(),.?\":{}|<>]/", $password)) {
        return "Password must contain at least one special character.";
    }

    return true;
}

function validatePhoneNumber($phone)
{
    // Phone number must start with +60 and followed by 9-10 digits
    return preg_match("/^\+60[0-9]{9,10}$/", $phone);
}

function validateIC($ic)
{
    // IC must be exactly 12 digits
    return preg_match("/^[0-9]{12}$/", $ic);
}

function createAuditLog($conn, $user_id, $action_type, $record_id, $changes)
{
    $sql = "INSERT INTO audit_logs (user_id, action_type, record_id, changes) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "isis", $user_id, $action_type, $record_id, $changes);
    mysqli_stmt_execute($stmt);
}

// Simple login check
function checkLogin()
{
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
        header("location: /customerdata/auth/login.php");
        exit;
    }
}

// Simple admin check
function checkAdmin()
{
    if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
        header("location: /customerdata/index.php");
        exit;
    }
}

function generateSecureOTP($length = 6)
{
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
    $otp = '';
    $max = strlen($characters) - 1;

    // Ensure at least one of each type
    $otp .= $characters[rand(0, 25)]; // uppercase
    $otp .= $characters[rand(26, 51)]; // lowercase
    $otp .= $characters[rand(52, 61)]; // number
    $otp .= $characters[rand(62, $max)]; // special char

    // Fill the rest randomly
    for ($i = 0; $i < ($length - 4); $i++) {
        $otp .= $characters[rand(0, $max)];
    }

    // Shuffle the OTP to make it random
    return str_shuffle($otp);
}
?>