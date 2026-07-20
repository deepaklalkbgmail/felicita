<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$isAgent = !empty($_SESSION['agent_id']);
$isAdm   = isAdmin();
if (!$isAgent && !$isAdm) {
    http_response_code(401);
    jsonOut(['success' => false, 'message' => 'Unauthorised.']);
}

$method = $_SERVER['REQUEST_METHOD'];

// GET → look up an existing booking by wing + unit (to pre-fill the edit form)
if ($method === 'GET') {
    $wing = (int)($_GET['wing'] ?? 0);
    $unit = trim($_GET['unit'] ?? '');
    if (!$wing || $unit === '') {
        jsonOut(['success' => false, 'message' => 'Block Number and Unit are required.']);
    }
    $bk = findBookingByWingUnit($wing, $unit);
    if (!$bk) {
        jsonOut(['success' => false, 'message' => 'No booking found for WING ' . $wing . ' - ' . $unit . '.']);
    }
    $bk['remaining_due'] = (float)$bk['total_amount'] - (float)$bk['paid_amount'];
    jsonOut(['success' => true, 'booking' => $bk]);
}

// POST → apply the edit
if ($method !== 'POST') {
    http_response_code(405);
    jsonOut(['success' => false, 'message' => 'Method not allowed.']);
}

$bookingId = (int)($_POST['booking_id'] ?? 0);
if (!$bookingId) {
    jsonOut(['success' => false, 'message' => 'Booking ID is required.']);
}

$contact = trim($_POST['contact_number'] ?? '');
if ($contact !== '' && !preg_match('/^[0-9+\-\s]{7,15}$/', $contact)) {
    jsonOut(['success' => false, 'message' => 'Invalid contact number.']);
}

$editorName = $isAgent
    ? ($_SESSION['agent_name'] ?? 'Agent')
    : ($_SESSION['admin_user'] ?? 'Admin');
$agentId = $isAgent ? (int)$_SESSION['agent_id'] : null;

$new = [];
foreach (['owner_name', 'contact_number', 'plates_kids', 'plates_adults', 'paid_amount'] as $f) {
    if (isset($_POST[$f]) && $_POST[$f] !== '') $new[$f] = $_POST[$f];
}

$result = updateBooking($bookingId, $new, $agentId, $editorName);
jsonOut($result + ['success' => $result['success'] ?? false]);
