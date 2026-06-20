<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isAdmin() && empty($_SESSION['validator'])) {
    http_response_code(401);
    jsonOut(['success' => false, 'message' => 'Unauthorised.']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    jsonOut(['success' => false, 'message' => 'Method not allowed.']);
}

$bookingId = (int)($_POST['booking_id'] ?? 0);
$relation  = trim($_POST['relation']    ?? '');

if (!$bookingId || !$relation) {
    jsonOut(['success' => false, 'message' => 'Booking ID and relation are required.']);
}

if (strlen($relation) > 120) {
    jsonOut(['success' => false, 'message' => 'Relation text too long.']);
}

// Re-fetch full booking for limit-reached details
$db = getDB();
$st = $db->prepare("SELECT id, house_name, headcount FROM bookings WHERE id=? LIMIT 1");
$st->execute([$bookingId]);
$row = $st->fetch();
if (!$row) {
    jsonOut(['success' => false, 'message' => 'Booking not found.']);
}

$result = consumePlate($bookingId, $relation);

if (!$result['success'] && $result['message'] === 'limit_reached') {
    // Get full history for display
    $st2 = $db->prepare("SELECT relation, served_at FROM consumption WHERE booking_id=? ORDER BY served_at ASC");
    $st2->execute([$bookingId]);
    $history = $st2->fetchAll();
    jsonOut([
        'success'    => false,
        'message'    => 'limit_reached',
        'house_name' => $row['house_name'],
        'headcount'  => $row['headcount'],
        'history'    => $history,
    ]);
}

jsonOut($result);
