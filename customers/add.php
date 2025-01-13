<?php
session_start();
require_once "../config/database.php";
require_once "../includes/functions.php";

checkLogin();

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate input
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $birthday = trim($_POST["birthday"]);
    $ic_number = trim($_POST["ic_number"]);
    $payment_method = trim($_POST["payment_method"]);
    $subscription_plan = trim($_POST["subscription_plan"]);

    // Validate phone number
    if (!validatePhoneNumber($phone)) {
        $errors[] = "Phone number must start with +60 followed by 9-10 digits.";
    }

    // Validate IC
    if (!validateIC($ic_number)) {
        $errors[] = "IC number must be exactly 12 digits.";
    }

    if (empty($errors)) {
        $sql = "INSERT INTO customers (name, email, phone, birthday, ic_number, payment_method, subscription_plan, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "sssssssi", $name, $email, $phone, $birthday, $ic_number, $payment_method, $subscription_plan, $_SESSION["id"]);
            
            if(mysqli_stmt_execute($stmt)){
                // Create audit log
                $customer_id = mysqli_insert_id($conn);
                $changes = json_encode([
                    "action" => "create",
                    "fields" => [
                        "name" => $name,
                        "email" => $email,
                        "phone" => $phone,
                        "birthday" => $birthday,
                        "ic_number" => $ic_number,
                        "payment_method" => $payment_method,
                        "subscription_plan" => $subscription_plan
                    ]
                ]);
                createAuditLog($conn, $_SESSION["id"], "create", $customer_id, $changes);
                
                header("location: ../index.php");
                exit();
            } else {
                $errors[] = "Something went wrong. Please try again later.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Customer - TM Customer Data System</title>
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
        <h2>Add New Customer</h2>
        
        <?php if(!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach($errors as $error): ?>
                    <p class="mb-0"><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Phone (+60XXXXXXXXX)</label>
                <input type="text" name="phone" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Birthday</label>
                <input type="date" name="birthday" class="form-control" required>
            </div>
            <div class="form-group">
                <label>IC Number (12 digits)</label>
                <input type="text" name="ic_number" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Payment Method</label>
                <select name="payment_method" class="form-control" required>
                    <option value="credit_card">Credit Card</option>
                    <option value="online_banking">Online Banking</option>
                    <option value="cash">Cash</option>
                </select>
            </div>
            <div class="form-group">
                <label>Subscription Plan</label>
                <select name="subscription_plan" class="form-control" required>
                    <option value="basic">Basic Plan</option>
                    <option value="premium">Premium Plan</option>
                    <option value="business">Business Plan</option>
                </select>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Add Customer">
                <a href="../index.php" class="btn btn-secondary ml-2">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>
