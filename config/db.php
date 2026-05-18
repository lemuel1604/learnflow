<?php
/**
 * LearnFlow LMS — Database Connection
 * MariaDB/MySQL via mysqli
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'learnflow_db');
define('DB_PORT', 3306);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($conn->connect_error) {
    error_log('[LearnFlow] DB connect failed: ' . $conn->connect_error);
    http_response_code(503);
    die(json_encode([
        'success' => false,
        'message' => 'Database connection failed. Please try again later.'
    ]));
}

$conn->set_charset('utf8mb4');