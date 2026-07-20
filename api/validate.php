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

// Lookup by Wing + Door (unit) selection …
$wing = (int)($_GET['wing'] ?? $_POST['wing'] ?? 0);
$unit = trim($_GET['unit'] ?? $_POST['unit'] ?? '');

if ($wing && $unit !== '') {
    $found = findBookingByWingUnit($wing, $unit);
    if (!$found) {
        jsonOut(['success' => false, 'message' => 'No booking found for WING ' . $wing . ' - ' . $unit . '.']);
    }
    // Re-fetch via the shared lookup so history/remaining are consistent
    $booking = lookupBooking($found['secret_code']);
    if (!$booking) {
        jsonOut(['success' => false, 'message' => 'No booking found for this unit.']);
    }
    jsonOut(['success' => true, 'booking' => $booking]);
}

// … or by secret code / order id
$code = strtoupper(trim($_GET['code'] ?? $_POST['code'] ?? ''));
if (!$code) {
    jsonOut(['success' => false, 'message' => 'Code is required.']);
}

$booking = lookupBooking($code);
if (!$booking) {
    jsonOut(['success' => false, 'message' => 'Invalid code. No booking found.']);
}

jsonOut(['success' => true, 'booking' => $booking]);
