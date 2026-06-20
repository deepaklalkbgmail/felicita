<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isAdmin() && !isValidator()) {
    http_response_code(401);
    jsonOut(['success' => false, 'message' => 'Unauthorised.']);
}

$code = strtoupper(trim($_GET['code'] ?? $_POST['code'] ?? ''));
if (!$code) {
    jsonOut(['success' => false, 'message' => 'Code is required.']);
}

$booking = lookupBooking($code);
if (!$booking) {
    jsonOut(['success' => false, 'message' => 'Invalid code. No booking found.']);
}

jsonOut(['success' => true, 'booking' => $booking]);
