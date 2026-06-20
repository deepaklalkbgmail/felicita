<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    jsonOut(['success' => false, 'message' => 'Method not allowed.']);
}

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$isAgent = !empty($_SESSION['agent_id']);
$isAdm   = isAdmin();

if (!$isAgent && !$isAdm) {
    http_response_code(401);
    jsonOut(['success' => false, 'message' => 'Unauthorised.']);
}

$house   = trim($_POST['house_name']      ?? '');
$owner   = trim($_POST['owner_name']      ?? '');
$contact = trim($_POST['contact_number']  ?? '');
$hc      = (int)($_POST['headcount']      ?? 0);
$notes   = trim($_POST['notes']           ?? '');
$type    = $isAdm && !$isAgent ? 'adhoc' : 'agent';

if (!$house || !$owner || !$contact || $hc < 1 || $hc > 200) {
    jsonOut(['success' => false, 'message' => 'Please fill all required fields correctly.']);
}

if (!preg_match('/^[0-9+\-\s]{7,15}$/', $contact)) {
    jsonOut(['success' => false, 'message' => 'Invalid contact number.']);
}

try {
    $result = createBooking([
        'house_name'     => $house,
        'owner_name'     => $owner,
        'contact_number' => $contact,
        'headcount'      => $hc,
        'notes'          => $notes,
        'booking_type'   => $type,
        'agent_id'       => $isAgent ? $_SESSION['agent_id'] : null,
    ]);
    jsonOut(['success' => true] + $result);
} catch (Exception $e) {
    http_response_code(500);
    jsonOut(['success' => false, 'message' => 'Could not create booking. Please try again.']);
}
