<?php
// includes/auth.php
require_once __DIR__ . '/../config/database.php';

session_start();

function login($username, $password) {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    return false;
}

function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

function logout() {
    session_destroy();
    header("Location: login.php");
    exit();
}

function validateApiToken() {
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
    } else {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
    }

    if (!isset($headers['Authorization'])) {
        return null;
    }

    $authHeader = $headers['Authorization'];
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
        $db = getDbConnection();
        $stmt = $db->prepare("
            SELECT u.* FROM users u
            JOIN api_tokens t ON u.id = t.user_id
            WHERE t.token = ? AND (t.expires_at IS NULL OR t.expires_at > CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }
    return null;
}

function generateApiToken($userId) {
    $token = bin2hex(random_bytes(32));
    $db = getDbConnection();
    $stmt = $db->prepare("INSERT INTO api_tokens (user_id, token) VALUES (?, ?)");
    $stmt->execute([$userId, $token]);
    return $token;
}

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
