<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireAdmin(): void {
    if (empty($_SESSION['admin_id'])) {
        header('Location: ' . APP_URL . '/login.php?role=admin');
        exit;
    }
}

function requireValidator(): void {
    if (empty($_SESSION['validator'])) {
        header('Location: ' . APP_URL . '/login.php?role=validator');
        exit;
    }
}

function isAdmin(): bool   { return !empty($_SESSION['admin_id']); }
function isValidator(): bool { return !empty($_SESSION['validator']); }

function adminLogin(string $username, string $password): bool {
    require_once __DIR__ . '/../config/database.php';
    $db = getDB();
    $st = $db->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
    $st->execute([$username]);
    $row = $st->fetch();
    if ($row && password_verify($password, $row['password_hash'])) {
        $_SESSION['admin_id']  = $row['id'];
        $_SESSION['admin_user'] = $row['username'];
        return true;
    }
    return false;
}

function validatorLogin(string $code): bool {
    // Validators use a fixed PIN stored in settings
    require_once __DIR__ . '/../config/database.php';
    $db = getDB();
    $st = $db->prepare("SELECT setting_value FROM settings WHERE setting_key='validator_pin' LIMIT 1");
    $st->execute();
    $row = $st->fetch();
    $pin = $row ? $row['setting_value'] : '999999';
    if ($code === $pin) {
        $_SESSION['validator'] = true;
        return true;
    }
    return false;
}
