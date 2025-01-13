<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'tm_customer_data');

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

// Check connection
if($conn === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if(mysqli_query($conn, $sql)){
    mysqli_select_db($conn, DB_NAME);
} else {
    die("ERROR: Could not create database. " . mysqli_error($conn));
}

// Create necessary tables
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    otp_secret VARCHAR(32) DEFAULT NULL,
    otp_valid_until TIMESTAMP NULL DEFAULT NULL
)";
mysqli_query($conn, $sql);

$sql = "CREATE TABLE IF NOT EXISTS customers (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    birthday DATE NOT NULL,
    ic_number VARCHAR(12) NOT NULL,
    payment_method ENUM('credit_card', 'online_banking', 'cash') NOT NULL,
    subscription_plan VARCHAR(50) NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
)";
mysqli_query($conn, $sql);

$sql = "CREATE TABLE IF NOT EXISTS audit_logs (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    record_id INT NOT NULL,
    changes TEXT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";
mysqli_query($conn, $sql);

// Create default admin account if not exists
$default_admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$sql = "INSERT IGNORE INTO users (username, password, role, email) 
        VALUES ('admin', '$default_admin_password', 'admin', 'teckangoh@gmail.com')";
mysqli_query($conn, $sql);
?>
