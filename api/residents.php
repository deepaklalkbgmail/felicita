<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Agents, validators and admins may all read the resident directory
if (empty($_SESSION['agent_id']) && !isAdmin() && !isValidator()) {
    http_response_code(401);
    jsonOut(['success' => false, 'message' => 'Unauthorised.']);
}

$wing = (int)($_GET['wing'] ?? 0);
if ($wing < 1) {
    jsonOut(['success' => false, 'message' => 'Wing is required.']);
}

$units = getResidentUnits($wing);

// Attach existing-booking info so edit/validate flows know what's already booked
$db = getDB();
$bkSt = $db->prepare("SELECT unit FROM bookings WHERE wing_no = ?");
$bkSt->execute([$wing]);
$booked = array_column($bkSt->fetchAll(), 'unit');

foreach ($units as &$u) {
    $u['booked'] = in_array($u['unit'], $booked, true);
}
unset($u);

jsonOut(['success' => true, 'wing' => $wing, 'units' => $units]);
