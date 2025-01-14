<?php
session_start();
require_once "../config/database.php";
require_once "../includes/functions.php";
require_once "../includes/encryption.php";

checkLogin();

if(!isset($_GET["id"])) {
    header("location: ../index.php");
    exit();
}

$customer_id = $_GET["id"];

// Get customer details
$sql = "SELECT * FROM customers WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $customer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$customer = mysqli_fetch_array($result);

// Verify if user has permission to edit this customer
if(!$customer || ($_SESSION["role"] !== "admin" && $customer["created_by"] !== $_SESSION["id"])) {
    header("location: ../index.php");
    exit();
}

// Decrypt customer data for display
$customer = decryptCustomerData($customer);

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and validate input
    $customer_data = [
        'name' => trim($_POST["name"]),
        'email' => trim($_POST["email"]),
        'phone' => trim($_POST["phone"]),
        'birthday' => trim($_POST["birthday"]),
        'ic_number' => trim($_POST["ic_number"]),
        'payment_method' => trim($_POST["payment_method"]),
        'subscription_plan' => trim($_POST["subscription_plan"])
    ];

    // Validate phone number
    if (!validatePhoneNumber($customer_data['phone'])) {
        $errors[] = "Phone number must start with +60 followed by 9-10 digits.";
    }

    // Validate IC
    if (!validateIC($customer_data['ic_number'])) {
        $errors[] = "IC number must be exactly 12 digits.";
    }

    if (empty($errors)) {
        // Track changes for audit log
        $changes = [];
        foreach($customer_data as $field => $value) {
            if($customer[$field] !== $value) {
                $changes[$field] = [
                    'old' => $customer[$field],
                    'new' => $value
                ];
            }
        }

        if(!empty($changes)) {
            // Encrypt the new data
            $encrypted_data = encryptCustomerData($customer_data);
            
            $sql = "UPDATE customers SET 
                    name=?, email=?, phone=?, birthday=?, 
                    ic_number=?, payment_method=?, subscription_plan=? 
                    WHERE id=?";
            
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssssssi", 
                $encrypted_data['name'],
                $encrypted_data['email'],
                $encrypted_data['phone'],
                $customer_data['birthday'],
                $encrypted_data['ic_number'],
                $customer_data['payment_method'],
                $customer_data['subscription_plan'],
                $customer_id
            );
            
            if(mysqli_stmt_execute($stmt)) {
                // Create audit log with unencrypted data for readability
                createAuditLog($conn, $_SESSION["id"], "update", $customer_id, json_encode($changes));
                header("location: view.php?id=" . $customer_id);
                exit();
            } else {
                $errors[] = "Something went wrong. Please try again later.";
            }
        } else {
            header("location: view.php?id=" . $customer_id);
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Customer - TM Customer Data System</title>
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
        <h2>Edit Customer</h2>
        
        <?php if(!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach($errors as $error): ?>
                    <p class="mb-0"><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $customer_id); ?>" method="post">
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($customer["name"]); ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($customer["email"]); ?>" required>
            </div>
            <div class="form-group">
                <label>Phone (+60XXXXXXXXX)</label>
                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($customer["phone"]); ?>" required>
            </div>
            <div class="form-group">
                <label>Birthday</label>
                <input type="date" name="birthday" class="form-control" value="<?php echo htmlspecialchars($customer["birthday"]); ?>" required>
            </div>
            <div class="form-group">
                <label>IC Number (12 digits)</label>
                <input type="text" name="ic_number" class="form-control" value="<?php echo htmlspecialchars($customer["ic_number"]); ?>" required>
            </div>
            <div class="form-group">
                <label>Payment Method</label>
                <select name="payment_method" class="form-control" required>
                    <option value="credit_card" <?php echo $customer["payment_method"] === "credit_card" ? "selected" : ""; ?>>Credit Card</option>
                    <option value="online_banking" <?php echo $customer["payment_method"] === "online_banking" ? "selected" : ""; ?>>Online Banking</option>
                    <option value="cash" <?php echo $customer["payment_method"] === "cash" ? "selected" : ""; ?>>Cash</option>
                </select>
            </div>
            <div class="form-group">
                <label>Subscription Plan</label>
                <select name="subscription_plan" class="form-control" required>
                    <option value="basic" <?php echo $customer["subscription_plan"] === "basic" ? "selected" : ""; ?>>Basic Plan</option>
                    <option value="premium" <?php echo $customer["subscription_plan"] === "premium" ? "selected" : ""; ?>>Premium Plan</option>
                    <option value="business" <?php echo $customer["subscription_plan"] === "business" ? "selected" : ""; ?>>Business Plan</option>
                </select>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Update Customer">
                <a href="../index.php" class="btn btn-secondary ml-2">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>
