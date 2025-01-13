<?php
session_start();
require_once "../config/database.php";
require_once "../includes/functions.php";

checkAdmin();

// Check if user id is provided
if(!isset($_GET["id"])) {
    header("location: manage_users.php");
    exit();
}

$user_id = $_GET["id"];

// Get user details
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_array($result);

// Check if user exists and is not the admin account
if(!$user || $user["username"] === "admin") {
    header("location: manage_users.php");
    exit();
}

$errors = [];
$success_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $new_password = trim($_POST["new_password"]);
    
    // Validate email
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    
    // Validate phone (optional)
    if(!empty($phone) && !validatePhoneNumber($phone)) {
        $errors[] = "Phone number must start with +60 followed by 9-10 digits.";
    }
    
    // Validate password if provided
    if(!empty($new_password)) {
        $password_validation = validatePassword($new_password);
        if($password_validation !== true) {
            $errors[] = $password_validation;
        }
    }
    
    if(empty($errors)) {
        if(!empty($new_password)) {
            // Update with new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET email=?, phone=?, password=? WHERE id=?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssi", $email, $phone, $hashed_password, $user_id);
        } else {
            // Update without changing password
            $sql = "UPDATE users SET email=?, phone=? WHERE id=?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssi", $email, $phone, $user_id);
        }
        
        if(mysqli_stmt_execute($stmt)) {
            $success_msg = "User details updated successfully.";
            
            // Refresh user data
            $sql = "SELECT * FROM users WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_array($result);
        } else {
            $errors[] = "Something went wrong. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User - TM Customer Data System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        .wrapper {
            width: 600px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Edit User: <?php echo htmlspecialchars($user["username"]); ?></h2>
            <a href="manage_users.php" class="btn btn-secondary">Back to Users</a>
        </div>
        
        <?php if(!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach($errors as $error): ?>
                    <p class="mb-0"><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if(!empty($success_msg)): ?>
            <div class="alert alert-success">
                <?php echo $success_msg; ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $user_id); ?>" method="post">
            <div class="form-group">
                <label>Username</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user["username"]); ?>" disabled>
                <small class="form-text text-muted">Username cannot be changed</small>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user["email"]); ?>" required>
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user["phone"]); ?>">
                <small class="form-text text-muted">Format: +60xxxxxxxxx (Optional)</small>
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" class="form-control">
                <small class="form-text text-muted">Leave blank to keep current password</small>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Update User">
            </div>
        </form>
    </div>
</body>
</html>
