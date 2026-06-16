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
        updateTaskStatus($taskId, $status, $user_id, $comment, $_SESSION['role']);
    } elseif ($action === 'flag_doubt') {
        flagDoubt($taskId, $user_id, $comment, $_SESSION['role']);
    } elseif ($action === 'assign_task' && $_SESSION['role'] === 'admin') {
        $staffId = $_POST['staff_id'] ?? 0;
        assignTask($taskId, $staffId, $user_id);
    }
    header("Location: index.php");
    exit();
}

$role = $_SESSION['role'];
$tasks = getAllTasks($user_id, $role);
$staffUsers = ($role === 'admin') ? getStaffUsers() : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Task Manager</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Task Management</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($username); ?></span>
                <a href="logout.php" class="btn-sm">Logout</a>
            </div>
        </div>
    </header>

    <main class="container">
        <section class="task-list">
            <h2>Active Tasks</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Customer & Title</th>
                            <th>Status</th>
                            <th>Assigned To</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                            <tr>
                                <td>
                                    <div class="customer-info">
                                        <strong><?php echo htmlspecialchars($task['customer_name'] ?? 'N/A'); ?></strong>
                                        <span class="desc">(<?php echo htmlspecialchars($task['customer_email'] ?? 'N/A'); ?>)</span>
                                    </div>
                                    <strong><?php echo htmlspecialchars($task['title']); ?></strong>
                                    <p class="desc"><?php echo htmlspecialchars($task['description'] ?? ''); ?></p>
                                </td>
                                <td><span class="status-badge <?php echo strtolower($task['status']); ?>"><?php echo htmlspecialchars($task['status']); ?></span></td>
                                <td><?php echo htmlspecialchars($task['assigned_staff'] ?? 'Unassigned'); ?></td>
                                <td><?php echo htmlspecialchars($task['updated_at']); ?></td>
                                <td>
                                    <button class="btn-sm" onclick="showActionModal(<?php echo $task['id']; ?>, '<?php echo $task['status']; ?>')">Update</button>
                                    <?php if ($role === 'admin'): ?>
                                        <button class="btn-sm" onclick="showAssignModal(<?php echo $task['id']; ?>)">Assign</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Simple Modal for Actions -->
    <div id="actionModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3 id="modalTitle">Update Task</h3>
            <form id="actionForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="task_id" id="modalTaskId">
                <input type="hidden" name="action" id="modalAction" value="update_status">

                <div class="form-group" id="statusGroup">
                    <label for="status">New Status</label>
                    <select name="status" id="modalStatus">
                        <option value="NEW">NEW</option>
                        <option value="PROCESSING">PROCESSING</option>
                        <option value="ON_HOLD">ON_HOLD</option>
                        <option value="VERIFYING">VERIFYING</option>
                        <option value="COMPLETED">COMPLETED</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="comment">Comment / Doubt Details</label>
                    <textarea name="comment" id="modalComment" rows="3"></textarea>
                </div>

                <div class="modal-buttons">
                    <button type="submit" class="btn">Save Changes</button>
                    <button type="button" class="btn btn-danger" onclick="setFlagDoubt()">Flag Doubt</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Assignment Modal -->
    <div id="assignModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAssignModal()">&times;</span>
            <h3>Assign Staff</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="task_id" id="assignTaskId">
                <input type="hidden" name="action" value="assign_task">

                <div class="form-group">
                    <label for="staff_id">Select Staff</label>
                    <select name="staff_id" id="staff_id" required>
                        <option value="">-- Choose Staff --</option>
                        <?php foreach ($staffUsers as $staff): ?>
                            <option value="<?php echo $staff['id']; ?>"><?php echo htmlspecialchars($staff['username']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="modal-buttons">
                    <button type="submit" class="btn">Assign Task</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAssignModal(id) {
            document.getElementById('assignTaskId').value = id;
            document.getElementById('assignModal').style.display = "block";
        }

        function closeAssignModal() {
            document.getElementById('assignModal').style.display = "none";
        }

        function showActionModal(id, status) {
            document.getElementById('modalTaskId').value = id;
            document.getElementById('modalStatus').value = status;
            document.getElementById('modalAction').value = 'update_status';
            document.getElementById('actionModal').style.display = "block";
        }

        function closeModal() {
            document.getElementById('actionModal').style.display = "none";
        }

        function setFlagDoubt() {
            document.getElementById('modalAction').value = 'flag_doubt';
            if (document.getElementById('modalComment').value.trim() === '') {
                alert('Please provide a comment for the doubt.');
                return;
            }
            document.getElementById('actionForm').submit();
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('actionModal')) {
                closeModal();
            }
            if (event.target == document.getElementById('assignModal')) {
                closeAssignModal();
            }
        }
    </script>
</body>
</html>
