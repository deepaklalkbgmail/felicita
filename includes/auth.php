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
    if (empty($_SESSION['validator_id']) && empty($_SESSION['admin_id'])) {
        header('Location: ' . APP_URL . '/login.php?role=validator');
        exit;
    }
}

function isAdmin(): bool     { return !empty($_SESSION['admin_id']); }
function isValidator(): bool { return !empty($_SESSION['validator_id']); }

function adminLogin(string $username, string $password): bool {
    require_once __DIR__ . '/../config/database.php';
    $db = getDB();
    $st = $db->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
    $st->execute([$username]);
    $row = $st->fetch();
    if ($row && password_verify($password, $row['password_hash'])) {
        $_SESSION['admin_id']   = $row['id'];
        $_SESSION['admin_user'] = $row['username'];
        return true;
    }
    return false;
}

function validatorLogin(string $pin): bool {
    require_once __DIR__ . '/../config/database.php';
    $db = getDB();
    $st = $db->prepare("SELECT * FROM validators WHERE pin = ? AND is_active = 1 LIMIT 1");
    $st->execute([$pin]);
    $row = $st->fetch();
    if ($row) {
        $_SESSION['validator_id']   = $row['id'];
        $_SESSION['validator_name'] = $row['name'];
        return true;
    }
    return false;
}

/* Admin enters validator mode without a separate login */
function adminEnterValidatorMode(): void {
    $_SESSION['validator_id']   = 0;          // 0 = admin acting as validator
    $_SESSION['validator_name'] = $_SESSION['admin_user'] ?? 'Admin';
}
