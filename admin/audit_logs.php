<?php
session_start();
require_once "../config/database.php";
require_once "../includes/functions.php";

// Check if user is admin
checkAdmin();

// Fetch all audit logs with user and customer information
$sql = "SELECT al.*, u.username, c.name as customer_name 
        FROM audit_logs al 
        JOIN users u ON al.user_id = u.id 
        LEFT JOIN customers c ON al.record_id = c.id 
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
        .modal-lg {
            max-width: 800px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Audit Logs</h2>
            <a href="../index.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        <table id="auditTable" class="table table-striped">
            <thead>
                <tr>
                    <th>Date</th>
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
                        <td>
                            <?php 
                            $changes = json_decode($row['changes'], true);
                            if($changes) {
                                if(isset($changes['action']) && $changes['action'] === 'create') {
                                    echo '<button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#changesModal" '
                                        . 'data-changes="Created with values:<br>';
                                    foreach($changes['fields'] as $field => $value) {
                                        echo htmlspecialchars($field) . ': ' . htmlspecialchars($value) . '<br>';
                                    }
                                    echo '">View Changes</button>';
                                } else {
                                    $changesText = '';
                                    foreach($changes as $field => $change) {
                                        $changesText .= htmlspecialchars($field) . ': ' 
                                                   . htmlspecialchars($change['old']) . ' → ' 
                                                   . htmlspecialchars($change['new']) . '<br>';
                                    }
                                    echo '<button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#changesModal" '
                                        . 'data-changes="' . $changesText . '">View Changes</button>';
                                }
                            }
                            ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Changes Modal -->
    <div class="modal fade" id="changesModal" tabindex="-1" role="dialog" aria-labelledby="changesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changesModalLabel">Changes Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="changesModalBody">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#auditTable').DataTable({
                "order": [[0, "desc"]]
            });

            $('#changesModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var changes = button.data('changes');
                var modal = $(this);
                modal.find('.modal-body').html(changes);
            });
        });
    </script>
</body>
</html>
