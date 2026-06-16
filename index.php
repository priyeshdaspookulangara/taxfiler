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
        updateTaskStatus($taskId, $status, $user_id, $comment);
    } elseif ($action === 'flag_doubt') {
        flagDoubt($taskId, $user_id, $comment);
    }
    header("Location: index.php");
    exit();
}

$tasks = getAllTasks();
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
                            <th>Title</th>
                            <th>Status</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($task['title']); ?></strong>
                                    <p class="desc"><?php echo htmlspecialchars($task['description'] ?? ''); ?></p>
                                </td>
                                <td><span class="status-badge <?php echo strtolower($task['status']); ?>"><?php echo htmlspecialchars($task['status']); ?></span></td>
                                <td><?php echo htmlspecialchars($task['updated_at']); ?></td>
                                <td>
                                    <button class="btn-sm" onclick="showActionModal(<?php echo $task['id']; ?>, '<?php echo $task['status']; ?>')">Update</button>
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

    <script>
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
        }
    </script>
</body>
</html>
