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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    jsonOut(['success' => false, 'message' => 'Method not allowed.']);
}

$bookingId   = (int)($_POST['booking_id'] ?? 0);
$validatorId = isAdmin() ? null : (int)($_SESSION['validator_id'] ?? null);

// Accept either a single relation or an array of relations (multi-plate)
$relations = [];
if (isset($_POST['relations']) && is_array($_POST['relations'])) {
    $relations = $_POST['relations'];
} elseif (!empty($_POST['relation'])) {
    $relations = [$_POST['relation']];
}

// Clean up
$relations = array_values(array_filter(array_map(function ($r) {
    return substr(trim((string)$r), 0, 120);
}, $relations), fn($r) => $r !== ''));

if (!$bookingId || count($relations) === 0) {
    jsonOut(['success' => false, 'message' => 'Booking ID and at least one relation are required.']);
}

$db = getDB();
$st = $db->prepare("
    SELECT b.id, b.house_name, b.headcount,
           (SELECT COUNT(*) FROM consumption c WHERE c.booking_id = b.id) AS consumed
    FROM   bookings b WHERE b.id = ? LIMIT 1");
$st->execute([$bookingId]);
$row = $st->fetch();

if (!$row) {
    jsonOut(['success' => false, 'message' => 'Booking not found.']);
}

$headcount = (int)$row['headcount'];
$consumed  = (int)$row['consumed'];
$remaining = $headcount - $consumed;

// If nothing left at all → limit reached
if ($remaining <= 0) {
    $st2 = $db->prepare("SELECT relation, served_at FROM consumption WHERE booking_id=? ORDER BY served_at ASC");
    $st2->execute([$bookingId]);
    jsonOut([
        'success'    => false,
        'message'    => 'limit_reached',
        'house_name' => $row['house_name'],
        'headcount'  => $headcount,
        'history'    => $st2->fetchAll(),
    ]);
}

// If more requested than remaining → reject the whole batch (validator must re-check)
if (count($relations) > $remaining) {
    jsonOut([
        'success'   => false,
        'message'   => "Only $remaining plate(s) remaining, but you tried to serve " . count($relations) . ".",
        'remaining' => $remaining,
    ]);
}

// Insert all in a transaction
$db->beginTransaction();
try {
    $ins = $db->prepare("INSERT INTO consumption (booking_id, relation, validator_id) VALUES (?,?,?)");
    foreach ($relations as $rel) {
        $ins->execute([$bookingId, $rel, $validatorId]);
    }
    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    jsonOut(['success' => false, 'message' => 'Could not record plates. Please try again.']);
}

$newConsumed = $consumed + count($relations);
jsonOut([
    'success'   => true,
    'served'    => count($relations),
    'relations' => $relations,
    'consumed'  => $newConsumed,
    'remaining' => $headcount - $newConsumed,
]);
