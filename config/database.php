<?php
// config/database.php

function getDbConnection() {
    try {
        $db_path = __DIR__ . '/../task_manager.db';
        $pdo = new PDO("sqlite:" . $db_path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        // In production, log the error and show a generic message
        die("Connection failed: " . $e->getMessage());
    }
}
?>
