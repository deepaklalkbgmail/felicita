<?php
// All dates/times across the app are shown in India Standard Time (IST)
date_default_timezone_set('Asia/Kolkata');

define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'aaravam2026');

define('APP_NAME', 'Aaravam 2026');
define('APP_URL', 'http://yourdomain.com/aaravam2026');

$_pdo = null;

function getDB(): PDO {
    global $_pdo;
    if ($_pdo === null) {
        try {
            $_pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
            // Make MySQL return/store TIMESTAMP values in IST for this session
            $_pdo->exec("SET time_zone = '+05:30'");
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Database connection failed.']));
        }
    }
    return $_pdo;
}
