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

$wingNo  = (int)($_POST['wing_no'] ?? 0);
$unit    = trim($_POST['unit']            ?? '');
$owner   = trim($_POST['owner_name']      ?? '');
$contact = trim($_POST['contact_number']  ?? '');
$kids    = (int)($_POST['plates_kids']    ?? 0);
$adults  = (int)($_POST['plates_adults']  ?? 0);
$paid    = (float)($_POST['paid_amount']  ?? 0);
$paidTo  = trim($_POST['paid_to']         ?? '');
$notes   = trim($_POST['notes']           ?? '');

// Validate paid_to against the configured account list
if ($paidTo !== '' && !in_array($paidTo, getPaidToOptions(), true)) {
    jsonOut(['success' => false, 'message' => 'Invalid "Paid to" account.']);
}
// "Paid to" is required only when an amount has actually been paid
if ($paid > 0 && $paidTo === '') {
    jsonOut(['success' => false, 'message' => 'Please select "Paid to" account when an amount is paid.']);
}
$type    = $isAdm && !$isAgent ? 'adhoc' : 'agent';

$total = $kids + $adults;

if (!$wingNo || !$unit) {
    jsonOut(['success' => false, 'message' => 'Please select Block Number and Unit.']);
}
if (!$owner) {
    jsonOut(['success' => false, 'message' => 'Owner name is required.']);
}
if (!$contact) {
    jsonOut(['success' => false, 'message' => 'Contact number is required.']);
}
if ($total < 1 || $total > 200) {
    jsonOut(['success' => false, 'message' => 'Enter at least one plate (kids or adults).']);
}
if (!preg_match('/^[0-9+\-\s]{7,15}$/', $contact)) {
    jsonOut(['success' => false, 'message' => 'Invalid contact number.']);
}

// Prevent duplicate booking for the same wing + unit
$existing = findBookingByWingUnit($wingNo, $unit);
if ($existing) {
    jsonOut([
        'success' => false,
        'message' => 'A booking already exists for WING ' . $wingNo . ' - ' . $unit .
                     '. Use "Edit Booking" to update it.',
        'duplicate' => true,
    ]);
}

try {
    $result = createBooking([
        'wing_no'        => $wingNo,
        'unit'           => $unit,
        'owner_name'     => $owner,
        'contact_number' => $contact,
        'plates_kids'    => $kids,
        'plates_adults'  => $adults,
        'paid_amount'    => $paid,
        'paid_to'        => $paidTo,
        'notes'          => $notes,
        'booking_type'   => $type,
        'agent_id'       => $isAgent ? $_SESSION['agent_id'] : null,
    ]);
    jsonOut(['success' => true] + $result);
} catch (Exception $e) {
    http_response_code(500);
    jsonOut(['success' => false, 'message' => 'Could not create booking. Please try again.']);
}
