<?php
// index.php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (!isAuthenticated()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        die("Invalid CSRF token");
    }

    $action = $_POST['action'] ?? '';
    $taskId = $_POST['task_id'] ?? 0;
    $comment = isset($_POST['comment']) ? $_POST['comment'] : null;

    if ($action === 'update_status') {
        $status = $_POST['status'] ?? '';
        updateTaskStatus($taskId, $status, $user_id, $comment, $role);
    } elseif ($action === 'flag_doubt') {
        flagDoubt($taskId, $user_id, $comment, $role);
    } elseif ($action === 'assign_task' && $role === 'admin') {
        $staffId = $_POST['staff_id'] ?? 0;
        assignTask($taskId, $staffId, $user_id);
    }
    header("Location: index.php");
    exit();
}

$tasks = getAllTasks($user_id, $role);
$staffUsers = ($role === 'admin') ? getStaffUsers() : [];

// Statistics
$totalPending = 0;
$totalHold = 0;
$totalCompleted = 0;
foreach ($tasks as $task) {
    if ($task['status'] === 'NEW' || $task['status'] === 'PROCESSING') $totalPending++;
    if ($task['status'] === 'ON_HOLD') $totalHold++;
    if ($task['status'] === 'COMPLETED') $totalCompleted++;
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>CMS Admin - Task Management</title>
    <meta name="robots" content="noindex">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <style>
        :root {
            --primary-color: #007BFF;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --border-color: #e9ecef;
        }

        body {
            background-color: #f4f7f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Badge Styling */
        .badge-priority {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .priority-high { background-color: #ffebee; color: #c62828; }
        .priority-medium { background-color: #fff3e0; color: #ef6c00; }
        .priority-low { background-color: #e8f5e9; color: #2e7d32; }

        .status-pill {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }
        .status-new { background-color: #eee; color: #666; }
        .status-processing { background-color: #e3f2fd; color: #1976d2; }
        .status-on_hold { background-color: #ffebee; color: #c62828; }
        .status-verifying { background-color: #fff3e0; color: #ef6c00; }
        .status-completed { background-color: #e8f5e9; color: #2e7d32; }

        .table-controls {
            background: #fff;
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }

        .search-input-group {
            position: relative;
            flex: 1;
            min-width: 250px;
        }
        .search-input-group i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #adb5bd;
        }
        .search-input-group input {
            padding-left: 35px;
        }

        .card-stats {
            border-left: 4px solid var(--primary-color);
        }

        /* Header Fixes */
        .navbar-main {
            background-color: #212529 !important;
        }

        /* Modal fixes */
        .modal-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .customer-info small {
            display: block;
            color: #6c757d;
        }
    </style>
</head>

<body class="layout-default">

    <div id="header" class="navbar navbar-expand-sm navbar-main navbar-dark bg-dark pr-0">
        <div class="container-fluid p-0">
            <a href="index.php" class="navbar-brand ml-3">
                <i class="fas fa-microchip mr-2"></i>
                <span>CMS Admin</span>
            </a>
            <ul class="nav navbar-nav ml-auto d-none d-md-flex mr-3">
                <li class="nav-item">
                    <span class="nav-link text-white">Welcome, <?php echo htmlspecialchars($username); ?></span>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link text-white"><i class="material-icons">exit_to_app</i></a>
                </li>
            </ul>
        </div>
    </div>

    <main class="container-fluid page__container">
        <div class="page__heading d-flex align-items-center mt-3 mb-4">
            <div class="flex">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 bg-transparent p-0">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Technical Reviews</li>
                    </ol>
                </nav>
                <h1 class="m-0">Technical Review Requests</h1>
            </div>
            <a href="raise_ticket.php" class="btn btn-primary ml-3"><i class="fas fa-plus mr-1"></i> New Request</a>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-lg-4">
                <div class="card card-body flex-row align-items-center card-stats h-100">
                    <div class="flex">
                        <div class="text-muted small text-uppercase font-weight-bold">Total Pending</div>
                        <div class="h3 m-0"><?php echo $totalPending; ?></div>
                    </div>
                    <i class="fas fa-clock fa-2x text-warning opacity-50 ml-auto"></i>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card card-body flex-row align-items-center card-stats h-100" style="border-left-color: var(--danger-color);">
                    <div class="flex">
                        <div class="text-muted small text-uppercase font-weight-bold">On Hold</div>
                        <div class="h3 m-0"><?php echo $totalHold; ?></div>
                    </div>
                    <i class="fas fa-exclamation-triangle fa-2x text-danger opacity-50 ml-auto"></i>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card card-body flex-row align-items-center card-stats h-100" style="border-left-color: var(--success-color);">
                    <div class="flex">
                        <div class="text-muted small text-uppercase font-weight-bold">Completed</div>
                        <div class="h3 m-0"><?php echo $totalCompleted; ?></div>
                    </div>
                    <i class="fas fa-check-circle fa-2x text-success opacity-50 ml-auto"></i>
                </div>
            </div>
        </div>

        <!-- Main Table Card -->
        <div class="card shadow-sm">
            <div class="table-controls">
                <div class="search-input-group">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" placeholder="Search by Project ID or Title...">
                </div>
                <select class="form-control d-none d-md-block" style="width: 150px;">
                    <option>All Statuses</option>
                    <option>Pending</option>
                    <option>In Progress</option>
                    <option>Completed</option>
                </select>
            </div>

            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Request ID</th>
                            <th>Customer & Title</th>
                            <th>Assigned To</th>
                            <th>Status</th>
                            <th>Last Updated</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                            <tr>
                                <td><span class="text-muted">#TR-<?php echo sprintf('%04d', $task['id']); ?></span></td>
                                <td>
                                    <div class="customer-info">
                                        <strong><?php echo htmlspecialchars($task['customer_name'] ?? 'N/A'); ?></strong>
                                        <small><?php echo htmlspecialchars($task['customer_email'] ?? 'N/A'); ?></small>
                                    </div>
                                    <div class="mt-1">
                                        <strong><?php echo htmlspecialchars($task['title']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($task['description'] ?? ''); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-dark"><?php echo htmlspecialchars($task['assigned_staff'] ?? 'Unassigned'); ?></span>
                                </td>
                                <td>
                                    <span class="status-pill status-<?php echo strtolower($task['status']); ?>">
                                        <?php echo $task['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($task['updated_at'])); ?></td>
                                <td class="text-right">
                                    <button class="btn btn-sm btn-white border" onclick="showActionModal(<?php echo $task['id']; ?>, '<?php echo $task['status']; ?>')">Update</button>
                                    <?php if ($role === 'admin'): ?>
                                        <button class="btn btn-sm btn-primary" onclick="showAssignModal(<?php echo $task['id']; ?>)">Assign</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modals -->
    <!-- Action Modal -->
    <div class="modal fade" id="actionModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Task</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form id="actionForm" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="task_id" id="modalTaskId">
                        <input type="hidden" name="action" id="modalAction" value="update_status">

                        <div class="form-group">
                            <label>New Status</label>
                            <select name="status" id="modalStatus" class="form-control">
                                <option value="NEW">NEW</option>
                                <option value="PROCESSING">PROCESSING</option>
                                <option value="ON_HOLD">ON_HOLD</option>
                                <option value="VERIFYING">VERIFYING</option>
                                <option value="COMPLETED">COMPLETED</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Comment / Doubt Details</label>
                            <textarea name="comment" id="modalComment" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger mr-auto" onclick="setFlagDoubt()">Flag Doubt</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Assignment Modal -->
    <div class="modal fade" id="assignModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Staff</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="task_id" id="assignTaskId">
                        <input type="hidden" name="action" value="assign_task">

                        <div class="form-group">
                            <label>Select Staff</label>
                            <select name="staff_id" class="form-control" required>
                                <option value="">-- Choose Staff --</option>
                                <?php foreach ($staffUsers as $staff): ?>
                                    <option value="<?php echo $staff['id']; ?>"><?php echo htmlspecialchars($staff['username']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Assign Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        function showActionModal(id, status) {
            document.getElementById('modalTaskId').value = id;
            document.getElementById('modalStatus').value = status;
            document.getElementById('modalAction').value = 'update_status';
            $('#actionModal').modal('show');
        }

        function showAssignModal(id) {
            document.getElementById('assignTaskId').value = id;
            $('#assignModal').modal('show');
        }

        function setFlagDoubt() {
            document.getElementById('modalAction').value = 'flag_doubt';
            if (document.getElementById('modalComment').value.trim() === '') {
                alert('Please provide a comment for the doubt.');
                return;
            }
            document.getElementById('actionForm').submit();
        }
    </script>
</body>
</html>
