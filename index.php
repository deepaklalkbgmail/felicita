<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (isAdmin()) {
    header('Location: ' . APP_URL . '/admin/index.php');
} elseif (isValidator()) {
    header('Location: ' . APP_URL . '/validator/index.php');
} elseif (!empty($_SESSION['agent_id'])) {
    header('Location: ' . APP_URL . '/agent/index.php');
} else {
    header('Location: ' . APP_URL . '/login.php');
}
exit;
