<?php
session_start();
require_once "config/database.php";
require_once "includes/functions.php";
require_once "includes/encryption.php";

// Check if user is logged in
checkLogin();

// Get all customers or only those created by the current user
$sql = "SELECT c.*, u.username as created_by_username 
        FROM customers c 
        JOIN users u ON c.created_by = u.id";

if($_SESSION["role"] !== "admin") {
    $sql .= " WHERE c.created_by = ?";
}

$sql .= " ORDER BY c.created_at DESC";
$stmt = mysqli_prepare($conn, $sql);

if($_SESSION["role"] !== "admin") {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Fetch all customers and decrypt their data
$customers = [];
while($row = mysqli_fetch_array($result)) {
    $customers[] = decryptCustomerData($row);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - TM Customer Data System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css">
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
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
        <a class="navbar-brand" href="#">TM Customer Data System</a>
        <div class="navbar-nav ml-auto">
            <?php if($_SESSION["role"] === "admin"): ?>
                <a class="nav-item nav-link" href="admin/manage_users.php">Manage Users</a>
                <a class="nav-item nav-link" href="admin/audit_logs.php">Audit Logs</a>
            <?php endif; ?>
            <a class="nav-item nav-link" href="auth/logout.php">Logout</a>
        </div>
    </nav>

    <div class="wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Customer Records</h2>
            <a href="customers/add.php" class="btn btn-success">Add New Customer</a>
        </div>

        <table id="customersTable" class="table table-striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>IC Number</th>
                    <th>Subscription Plan</th>
                    <th>Created By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($customers as $customer): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($customer['name']); ?></td>
                        <td><?php echo htmlspecialchars($customer['email']); ?></td>
                        <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                        <td><?php echo htmlspecialchars($customer['ic_number']); ?></td>
                        <td><?php echo htmlspecialchars($customer['subscription_plan']); ?></td>
                        <td><?php echo htmlspecialchars($customer['created_by_username']); ?></td>
                        <td>
                            <a href="customers/view.php?id=<?php echo $customer['id']; ?>" class="btn btn-info btn-sm">View</a>
                            <?php if($_SESSION["role"] === "admin" || $_SESSION["id"] === $customer['created_by']): ?>
                                <a href="customers/edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#customersTable').DataTable({
                "order": [[0, "asc"]]
            });
        });
    </script>
</body>
</html>
