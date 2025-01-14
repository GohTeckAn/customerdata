<?php
session_start();
require_once "../config/database.php";
require_once "../includes/functions.php";
require_once "../includes/encryption.php";

checkLogin();

// Check if customer id is provided
if(!isset($_GET["id"])) {
    header("location: ../index.php");
    exit();
}

$customer_id = $_GET["id"];

// Get customer details
$sql = "SELECT c.*, u.username as created_by_username 
        FROM customers c 
        JOIN users u ON c.created_by = u.id 
        WHERE c.id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $customer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$customer = mysqli_fetch_array($result);

// Verify if user has permission to view this customer
if(!$customer || ($_SESSION["role"] !== "admin" && $customer["created_by"] !== $_SESSION["id"])) {
    header("location: ../index.php");
    exit();
}

// Decrypt sensitive data
$customer = decryptCustomerData($customer);

// Get audit logs if user is admin
$audit_logs = [];
if($_SESSION["role"] === "admin") {
    $sql = "SELECT al.*, u.username 
            FROM audit_logs al 
            JOIN users u ON al.user_id = u.id 
            WHERE al.record_id = ? 
            ORDER BY al.timestamp DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $customer_id);
    mysqli_stmt_execute($stmt);
    $audit_logs = mysqli_stmt_get_result($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Customer - TM Customer Data System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        .wrapper {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Customer Details</h2>
            <div>
                <a href="../index.php" class="btn btn-secondary">Back to Dashboard</a>
                <?php if($_SESSION["role"] === "admin" || $_SESSION["id"] === $customer["created_by"]): ?>
                    <a href="edit.php?id=<?php echo $customer_id; ?>" class="btn btn-primary">Edit</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">Name</th>
                        <td><?php echo htmlspecialchars($customer["name"]); ?></td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td><?php echo htmlspecialchars($customer["email"]); ?></td>
                    </tr>
                    <tr>
                        <th>Phone</th>
                        <td><?php echo htmlspecialchars($customer["phone"]); ?></td>
                    </tr>
                    <tr>
                        <th>Birthday</th>
                        <td><?php echo htmlspecialchars($customer["birthday"]); ?></td>
                    </tr>
                    <tr>
                        <th>IC Number</th>
                        <td><?php echo htmlspecialchars($customer["ic_number"]); ?></td>
                    </tr>
                    <tr>
                        <th>Payment Method</th>
                        <td><?php echo htmlspecialchars($customer["payment_method"]); ?></td>
                    </tr>
                    <tr>
                        <th>Subscription Plan</th>
                        <td><?php echo htmlspecialchars($customer["subscription_plan"]); ?></td>
                    </tr>
                    <tr>
                        <th>Created By</th>
                        <td><?php echo htmlspecialchars($customer["created_by_username"]); ?></td>
                    </tr>
                    <tr>
                        <th>Created At</th>
                        <td><?php echo htmlspecialchars($customer["created_at"]); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <?php if($_SESSION["role"] === "admin" && isset($audit_logs)): ?>
            <div class="mt-4">
                <h3>Audit Log</h3>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Changes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($log = mysqli_fetch_array($audit_logs)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($log["timestamp"]); ?></td>
                                <td><?php echo htmlspecialchars($log["username"]); ?></td>
                                <td><?php echo htmlspecialchars($log["action_type"]); ?></td>
                                <td>
                                    <?php 
                                    $changes = json_decode($log["changes"], true);
                                    if($changes) {
                                        if(isset($changes["action"]) && $changes["action"] === "create") {
                                            // For create action, show all initial values
                                            echo "Created with values:<br>";
                                            foreach($changes["fields"] as $field => $value) {
                                                echo htmlspecialchars($field) . ": " . 
                                                     htmlspecialchars($value) . "<br>";
                                            }
                                        } else {
                                            // For update action, show changes
                                            foreach($changes as $field => $change) {
                                                echo htmlspecialchars($field) . ": " . 
                                                     htmlspecialchars($change["old"]) . " → " . 
                                                     htmlspecialchars($change["new"]) . "<br>";
                                            }
                                        }
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
