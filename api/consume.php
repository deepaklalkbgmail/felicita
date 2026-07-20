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

// Each person served is either an 'adult' or a 'kid'
$rawTypes = [];
if (isset($_POST['types']) && is_array($_POST['types'])) {
    $rawTypes = $_POST['types'];
} elseif (!empty($_POST['type'])) {
    $rawTypes = [$_POST['type']];
}

// Normalise to 'adult' / 'kid'
$types = [];
foreach ($rawTypes as $t) {
    $t = strtolower(trim((string)$t));
    if ($t === 'adult' || $t === 'adults') $types[] = 'adult';
    elseif ($t === 'kid' || $t === 'kids') $types[] = 'kid';
}

if (!$bookingId || count($types) === 0) {
    jsonOut(['success' => false, 'message' => 'Booking ID and at least one Adult/Kid must be selected.']);
}

$reqAdults = count(array_filter($types, fn($t) => $t === 'adult'));
$reqKids   = count(array_filter($types, fn($t) => $t === 'kid'));

$db = getDB();
$st = $db->prepare("
    SELECT b.id, b.house_name, b.headcount, b.plates_adults, b.plates_kids,
           (SELECT COUNT(*) FROM consumption c WHERE c.booking_id=b.id) AS consumed,
           (SELECT COUNT(*) FROM consumption c WHERE c.booking_id=b.id AND c.person_type='adult') AS served_adults,
           (SELECT COUNT(*) FROM consumption c WHERE c.booking_id=b.id AND c.person_type='kid')   AS served_kids
    FROM   bookings b WHERE b.id = ? LIMIT 1");
$st->execute([$bookingId]);
$row = $st->fetch();

if (!$row) {
    jsonOut(['success' => false, 'message' => 'Booking not found.']);
}

$plateAdults   = (int)$row['plates_adults'];
$plateKids     = (int)$row['plates_kids'];
$servedAdults  = (int)$row['served_adults'];
$servedKids    = (int)$row['served_kids'];
$remAdults     = $plateAdults - $servedAdults;
$remKids       = $plateKids   - $servedKids;

// Everything already served?
if ($remAdults <= 0 && $remKids <= 0) {
    $st2 = $db->prepare("SELECT relation, person_type, served_at FROM consumption WHERE booking_id=? ORDER BY served_at ASC");
    $st2->execute([$bookingId]);
    jsonOut([
        'success'    => false,
        'message'    => 'limit_reached',
        'house_name' => $row['house_name'],
        'headcount'  => (int)$row['headcount'],
        'history'    => $st2->fetchAll(),
    ]);
}

// Per-type limit checks — reject the whole batch if either exceeds
if ($reqAdults > $remAdults) {
    jsonOut(['success' => false,
        'message' => "Only $remAdults adult plate(s) remaining, but you tried to serve $reqAdults.",
        'remaining_adults' => $remAdults, 'remaining_kids' => $remKids]);
}
if ($reqKids > $remKids) {
    jsonOut(['success' => false,
        'message' => "Only $remKids kid plate(s) remaining, but you tried to serve $reqKids.",
        'remaining_adults' => $remAdults, 'remaining_kids' => $remKids]);
}

// Insert all in a transaction
$db->beginTransaction();
try {
    $ins = $db->prepare("INSERT INTO consumption (booking_id, relation, person_type, validator_id) VALUES (?,?,?,?)");
    foreach ($types as $t) {
        $label = $t === 'adult' ? 'Adult' : 'Kid';
        $ins->execute([$bookingId, $label, $t, $validatorId]);
    }
    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    jsonOut(['success' => false, 'message' => 'Could not record plates. Please try again.']);
}

$newServedAdults = $servedAdults + $reqAdults;
$newServedKids   = $servedKids + $reqKids;

jsonOut([
    'success'          => true,
    'served'           => count($types),
    'served_adults'    => $reqAdults,
    'served_kids'      => $reqKids,
    'consumed'         => (int)$row['consumed'] + count($types),
    'remaining'        => (int)$row['headcount'] - ((int)$row['consumed'] + count($types)),
    'remaining_adults' => $plateAdults - $newServedAdults,
    'remaining_kids'   => $plateKids   - $newServedKids,
]);
