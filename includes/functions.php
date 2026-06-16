<?php
// includes/functions.php
require_once __DIR__ . '/../config/database.php';

function getAllTasks($userId = null, $role = 'admin') {
    $db = getDbConnection();
    if ($role === 'admin') {
        $stmt = $db->query("
            SELECT t.*, u.username as assigned_staff
            FROM tasks t
            LEFT JOIN users u ON t.assigned_to = u.id
            ORDER BY t.updated_at DESC
        ");
        return $stmt->fetchAll();
    } else {
        $stmt = $db->prepare("
            SELECT t.*, u.username as assigned_staff
            FROM tasks t
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE t.assigned_to = ?
            ORDER BY t.updated_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}

function getTaskById($id) {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function updateTaskStatus($taskId, $status, $userId, $comment = null, $role = 'staff') {
    $db = getDbConnection();
    $validStatuses = ['NEW', 'PROCESSING', 'ON_HOLD', 'VERIFYING', 'COMPLETED'];

    if (!in_array($status, $validStatuses)) {
        return ['success' => false, 'message' => 'Invalid status'];
    }

    // Security Check: Staff can only update their own assigned tasks
    if ($role === 'staff') {
        $stmt = $db->prepare("SELECT assigned_to FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();
        if (!$task || $task['assigned_to'] != $userId) {
            return ['success' => false, 'message' => 'Unauthorized: Task not assigned to you'];
        }
    }

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("UPDATE tasks SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$status, $taskId]);

        if ($comment) {
            $stmt = $db->prepare("INSERT INTO comments (task_id, user_id, comment) VALUES (?, ?, ?)");
            $stmt->execute([$taskId, $userId, $comment]);
        }

        $db->commit();
        return ['success' => true];
    } catch (Exception $e) {
        $db->rollBack();
        // Log error in production
        error_log("Update task status failed: " . $e->getMessage());
        return ['success' => false, 'message' => 'An internal error occurred'];
    }
}

function flagDoubt($taskId, $userId, $comment, $role = 'staff') {
    // When a staff member flags a doubt, the system must trigger an ON_HOLD status and log a comment.
    return updateTaskStatus($taskId, 'ON_HOLD', $userId, "DOUBT FLAGGED: " . $comment, $role);
}

function assignTask($taskId, $staffId, $adminId) {
    $db = getDbConnection();
    try {
        $stmt = $db->prepare("UPDATE tasks SET assigned_to = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$staffId, $taskId]);

        $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$staffId]);
        $staff = $stmt->fetch();

        $comment = "Task assigned to " . ($staff['username'] ?? 'Unknown');
        $stmt = $db->prepare("INSERT INTO comments (task_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->execute([$taskId, $adminId, $comment]);

        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function getStaffUsers() {
    $db = getDbConnection();
    $stmt = $db->query("SELECT id, username FROM users WHERE role = 'staff'");
    return $stmt->fetchAll();
}

function sanitizeInput($data) {
    // Basic trimming and tag stripping, but NOT htmlspecialchars here
    // HTML escaping should happen on output to prevent double encoding or API corruption
    return strip_tags(trim($data));
}

function getCommentsForTask($taskId) {
    $db = getDbConnection();
    $stmt = $db->prepare("
        SELECT c.*, u.username
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.task_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$taskId]);
    return $stmt->fetchAll();
}
?>
