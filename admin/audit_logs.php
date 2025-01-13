<?php
session_start();
require_once "../config/database.php";
require_once "../includes/functions.php";

checkLogin();
checkAdmin();

// Fetch audit logs with user and customer information
$sql = "SELECT al.*, u.username, c.name as customer_name 
        FROM audit_logs al 
        JOIN users u ON al.user_id = u.id 
        JOIN customers c ON al.record_id = c.id 
        ORDER BY al.timestamp DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Audit Logs - TM Customer Data System</title>
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
        .changes-cell {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .changes-modal pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
        <a class="navbar-brand" href="../index.php">TM Customer Data System</a>
        <div class="navbar-nav ml-auto">
            <a class="nav-item nav-link" href="manage_users.php">Manage Users</a>
            <a class="nav-item nav-link" href="../auth/logout.php">Logout</a>
        </div>
    </nav>

    <div class="wrapper">
        <h2>Audit Logs</h2>

        <table id="auditTable" class="table table-striped">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>User</th>
                    <th>Customer</th>
                    <th>Action</th>
                    <th>Changes</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_array($result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['timestamp']); ?></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['action_type']); ?></td>
                        <td class="changes-cell">
                            <a href="#" class="view-changes" data-toggle="modal" data-target="#changesModal" 
                               data-changes='<?php echo htmlspecialchars($row['changes']); ?>'>
                                View Changes
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Changes Modal -->
    <div class="modal fade changes-modal" id="changesModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Changes Details</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <pre id="changesContent"></pre>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#auditTable').DataTable({
                "order": [[0, "desc"]]
            });

            $('.view-changes').click(function() {
                let changes = JSON.parse($(this).data('changes'));
                $('#changesContent').text(JSON.stringify(changes, null, 2));
            });
        });
    </script>
</body>
</html>
