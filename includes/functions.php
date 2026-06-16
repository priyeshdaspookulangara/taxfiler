<?php
// includes/functions.php
require_once __DIR__ . '/../config/database.php';

function getAllTasks() {
    $db = getDbConnection();
    $stmt = $db->query("SELECT * FROM tasks ORDER BY updated_at DESC");
    return $stmt->fetchAll();
}

function getTaskById($id) {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function updateTaskStatus($taskId, $status, $userId, $comment = null) {
    $db = getDbConnection();
    $validStatuses = ['NEW', 'PROCESSING', 'ON_HOLD', 'VERIFYING', 'COMPLETED'];

    if (!in_array($status, $validStatuses)) {
        return ['success' => false, 'message' => 'Invalid status'];
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

function flagDoubt($taskId, $userId, $comment) {
    // When a staff member flags a doubt, the system must trigger an ON_HOLD status and log a comment.
    return updateTaskStatus($taskId, 'ON_HOLD', $userId, "DOUBT FLAGGED: " . $comment);
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
