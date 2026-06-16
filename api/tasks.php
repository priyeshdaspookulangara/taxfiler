<?php
// api/tasks.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Security Check: Validate API Token
$user = validateApiToken();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get all tasks or a specific task
        if (isset($_GET['id'])) {
            $task = getTaskById($_GET['id']);
            if ($task) {
                $task['comments'] = getCommentsForTask($task['id']);
                echo json_encode($task);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Task not found']);
            }
        } else {
            $tasks = getAllTasks();
            echo json_encode($tasks);
        }
        break;

    case 'POST':
        // Update task status or flag doubt
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['task_id']) || !isset($input['action'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit();
        }

        $taskId = (int)$input['task_id'];
        $action = $input['action'];
        $comment = isset($input['comment']) ? $input['comment'] : null;

        if ($action === 'update_status') {
            if (!isset($input['status'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing status']);
                exit();
            }
            $status = $input['status'];
            $result = updateTaskStatus($taskId, $status, $user['id'], $comment);
        } elseif ($action === 'flag_doubt') {
            if (!$comment) {
                http_response_code(400);
                echo json_encode(['error' => 'Comment required for flagging doubt']);
                exit();
            }
            $result = flagDoubt($taskId, $user['id'], $comment);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            exit();
        }

        if ($result['success']) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => $result['message']]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?>
