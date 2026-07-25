<?php
require_once __DIR__ . '/../config/database.php';

/* ═══════════════════════════════════════════════════════════════════════
   SETTINGS
═══════════════════════════════════════════════════════════════════════ */
function getSetting(string $key, string $default = ''): string {
    $db  = getDB();
    $st  = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
    $st->execute([$key]);
    $row = $st->fetch();
    return $row ? $row['setting_value'] : $default;
}

function setSetting(string $key, string $value): void {
    $db = getDB();
    $db->prepare("INSERT INTO settings (setting_key,setting_value) VALUES (?,?)
                  ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")
       ->execute([$key, $value]);
}

/* ═══════════════════════════════════════════════════════════════════════
   PRICING (separate kids / adults price per plate)
═══════════════════════════════════════════════════════════════════════ */
function getPrices(): array {
    $fallback = getSetting('price_per_plate', '200');
    return [
        'kids'   => (float) getSetting('price_kids',   $fallback),
        'adults' => (float) getSetting('price_adults', $fallback),
    ];
}

/* ═══════════════════════════════════════════════════════════════════════
   SECRET CODE GENERATOR
   Characters: 2-9, A-Z (except O), uppercase only — no 0,1,O,l,o
═══════════════════════════════════════════════════════════════════════ */
function generateSecretCode(): string {
    $chars = '23456789ABCDEFGHIJKLMNPQRSTUVWXYZ';
    $len   = strlen($chars);
    $db    = getDB();

    do {
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $chars[random_int(0, $len - 1)];
        }
        $st = $db->prepare("SELECT id FROM bookings WHERE secret_code = ? LIMIT 1");
        $st->execute([$code]);
    } while ($st->fetch());

    return $code;
}

/* Secret code = {Wing Number}{Door Number}{3-digit random}. Falls back to a
   random 6-char code when there is no wing/unit (e.g. VIP / ad-hoc tickets). */
function generateWingSecretCode(?int $wingNo, ?string $unit): string {
    if (!$wingNo || $unit === null || trim($unit) === '') {
        return generateSecretCode();
    }
    $db   = getDB();
    $door = strtoupper(preg_replace('/\s+/', '', $unit));   // "001 A" -> "001A"
    $base = $wingNo . $door;
    do {
        // {Wing}{Door}-{random 3 digits} e.g. 6107-482 — hyphen aids recall
        $code = $base . '-' . random_int(100, 999);
        $st = $db->prepare("SELECT id FROM bookings WHERE secret_code = ? LIMIT 1");
        $st->execute([$code]);
    } while ($st->fetch());
    return $code;
}

/* ═══════════════════════════════════════════════════════════════════════
   ORDER ID GENERATOR
═══════════════════════════════════════════════════════════════════════ */
function generateOrderId(): string {
    $db = getDB();
    do {
        $id = 'ARV' . strtoupper(substr(uniqid(), -7));
        $st = $db->prepare("SELECT id FROM bookings WHERE order_id = ? LIMIT 1");
        $st->execute([$id]);
    } while ($st->fetch());
    return $id;
}

/* ═══════════════════════════════════════════════════════════════════════
   BOOKING
═══════════════════════════════════════════════════════════════════════ */
function createBooking(array $data): array {
    $db       = getDB();
    $orderId  = generateOrderId();
    $prices   = getPrices();

    $wingNo   = !empty($data['wing_no']) ? (int)$data['wing_no'] : null;
    $unit     = isset($data['unit']) && trim((string)$data['unit']) !== '' ? trim($data['unit']) : null;

    $kids     = max(0, (int)($data['plates_kids']   ?? 0));
    $adults   = max(0, (int)($data['plates_adults'] ?? 0));
    // Back-compat: if caller passed a plain headcount, treat it as adults
    if ($kids === 0 && $adults === 0 && !empty($data['headcount'])) {
        $adults = (int)$data['headcount'];
    }
    $headcount = $kids + $adults;

    $totalAmount = $kids * $prices['kids'] + $adults * $prices['adults'];
    $paid        = max(0, (float)($data['paid_amount'] ?? 0));

    // House label: prefer explicit, else "WING N - UNIT"
    $house = trim((string)($data['house_name'] ?? ''));
    if ($house === '' && $wingNo && $unit) {
        $house = 'WING ' . $wingNo . ' - ' . $unit;
    }

    // Reference price stored on the booking (adult rate)
    $refPrice = $prices['adults'];

    $secretCode = generateWingSecretCode($wingNo, $unit);
    $agentId    = !empty($data['agent_id']) ? (int)$data['agent_id'] : null;
    $type       = $data['booking_type'] ?? 'agent';

    $db->prepare("INSERT INTO bookings
        (order_id, house_name, wing_no, unit, owner_name, contact_number,
         headcount, plates_kids, plates_adults,
         price_per_plate, total_amount, paid_amount,
         secret_code, booking_type, agent_id, notes)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
       ->execute([
           $orderId,
           $house,
           $wingNo,
           $unit,
           trim($data['owner_name']),
           trim($data['contact_number']),
           $headcount,
           $kids,
           $adults,
           $refPrice,
           $totalAmount,
           $paid,
           $secretCode,
           $type,
           $agentId,
           $data['notes'] ?? null,
       ]);

    $bookingId = (int) $db->lastInsertId();

    return [
        'id'             => $bookingId,
        'order_id'       => $orderId,
        'secret_code'    => $secretCode,
        'headcount'      => $headcount,
        'plates_kids'    => $kids,
        'plates_adults'  => $adults,
        'total_amount'   => $totalAmount,
        'paid_amount'    => $paid,
        'remaining_due'  => $totalAmount - $paid,
        'price_per_plate'=> $refPrice,
        'wing_no'        => $wingNo,
        'unit'           => $unit,
        'house_name'     => $house,
        'owner_name'     => $data['owner_name'],
    ];
}

/* ═══════════════════════════════════════════════════════════════════════
   RESIDENTS (wing / unit master list)
═══════════════════════════════════════════════════════════════════════ */
function getResidentUnits(int $wingNo): array {
    $db = getDB();
    $st = $db->prepare("SELECT unit, name FROM residents WHERE wing_no = ? ORDER BY unit");
    $st->execute([$wingNo]);
    return $st->fetchAll();
}

function getWingNumbers(): array {
    $db = getDB();
    $rows = $db->query("SELECT DISTINCT wing_no FROM residents ORDER BY wing_no")->fetchAll();
    $wings = array_map(fn($r) => (int)$r['wing_no'], $rows);
    return $wings ?: [1,2,3,4,5,6,7,8];   // sensible default before seeding
}

/* ═══════════════════════════════════════════════════════════════════════
   BOOKING EDIT + AUDIT LOG
═══════════════════════════════════════════════════════════════════════ */
function findBookingByWingUnit(int $wingNo, string $unit): ?array {
    $db = getDB();
    $st = $db->prepare("
        SELECT b.*, a.name AS agent_name,
               (SELECT COUNT(*) FROM consumption c WHERE c.booking_id = b.id) AS consumed
        FROM bookings b LEFT JOIN agents a ON a.id = b.agent_id
        WHERE b.wing_no = ? AND b.unit = ? ORDER BY b.created_at DESC LIMIT 1");
    $st->execute([$wingNo, trim($unit)]);
    return $st->fetch() ?: null;
}

/**
 * Update editable fields of a booking and record an audit entry.
 * $editable keys: owner_name, contact_number, plates_kids, plates_adults, paid_amount
 */
function updateBooking(int $bookingId, array $new, ?int $agentId, string $editorName): array {
    $db = getDB();
    $st = $db->prepare("SELECT * FROM bookings WHERE id = ? LIMIT 1");
    $st->execute([$bookingId]);
    $old = $st->fetch();
    if (!$old) return ['success' => false, 'message' => 'Booking not found.'];

    $prices = getPrices();

    $ownerName = array_key_exists('owner_name', $new)     ? trim((string)$new['owner_name'])     : $old['owner_name'];
    $contact   = array_key_exists('contact_number', $new) ? trim((string)$new['contact_number']) : $old['contact_number'];
    $kids      = array_key_exists('plates_kids', $new)    ? max(0,(int)$new['plates_kids'])       : (int)$old['plates_kids'];
    $adults    = array_key_exists('plates_adults', $new)  ? max(0,(int)$new['plates_adults'])     : (int)$old['plates_adults'];
    $paid      = array_key_exists('paid_amount', $new)    ? max(0,(float)$new['paid_amount'])     : (float)$old['paid_amount'];

    $headcount   = $kids + $adults;
    if ($headcount < 1) return ['success' => false, 'message' => 'Total plates must be at least 1.'];

    // Cannot reduce plates below the number already served
    $servedSt = $db->prepare("SELECT COUNT(*) FROM consumption WHERE booking_id = ?");
    $servedSt->execute([$bookingId]);
    $served = (int)$servedSt->fetchColumn();
    if ($headcount < $served) {
        return ['success' => false, 'message' => "Cannot set plates to $headcount — $served already served."];
    }

    $total = $kids * $prices['kids'] + $adults * $prices['adults'];

    // Build a diff for the audit log
    $fields = [
        'owner_name'     => [$old['owner_name'],            $ownerName],
        'contact_number' => [$old['contact_number'],        $contact],
        'plates_kids'    => [(int)$old['plates_kids'],      $kids],
        'plates_adults'  => [(int)$old['plates_adults'],    $adults],
        'paid_amount'    => [(float)$old['paid_amount'],    $paid],
    ];
    $changes = [];
    foreach ($fields as $k => [$o, $n]) {
        if ((string)$o !== (string)$n) $changes[$k] = ['from' => $o, 'to' => $n];
    }

    if (empty($changes)) return ['success' => true, 'message' => 'No changes made.', 'changed' => false];

    $db->prepare("UPDATE bookings
        SET owner_name=?, contact_number=?, plates_kids=?, plates_adults=?,
            headcount=?, total_amount=?, paid_amount=? WHERE id=?")
       ->execute([$ownerName, $contact, $kids, $adults, $headcount, $total, $paid, $bookingId]);

    $db->prepare("INSERT INTO booking_edits (booking_id, agent_id, editor_name, changes) VALUES (?,?,?,?)")
       ->execute([$bookingId, $agentId, $editorName, json_encode($changes, JSON_UNESCAPED_UNICODE)]);

    return [
        'success'       => true,
        'changed'       => true,
        'total_amount'  => $total,
        'paid_amount'   => $paid,
        'remaining_due' => $total - $paid,
        'headcount'     => $headcount,
        'plates_kids'   => $kids,
        'plates_adults' => $adults,
    ];
}

/* ═══════════════════════════════════════════════════════════════════════
   VALIDATION
═══════════════════════════════════════════════════════════════════════ */
function lookupBooking(string $code): ?array {
    $db   = getDB();
    $code = strtoupper(trim($code));

    // Accept secret code (with or without the hyphen) or order_id
    $codeNoDash = str_replace('-', '', $code);
    $st = $db->prepare("
        SELECT b.*,
               a.name AS agent_name,
               (SELECT COUNT(*) FROM consumption c WHERE c.booking_id = b.id) AS consumed,
               (SELECT COUNT(*) FROM consumption c WHERE c.booking_id = b.id AND c.person_type='adult') AS served_adults,
               (SELECT COUNT(*) FROM consumption c WHERE c.booking_id = b.id AND c.person_type='kid')   AS served_kids
        FROM   bookings b
        LEFT JOIN agents a ON a.id = b.agent_id
        WHERE  b.secret_code = ? OR REPLACE(b.secret_code,'-','') = ? OR b.order_id = ?
        LIMIT  1");
    $st->execute([$code, $codeNoDash, $code]);
    $row = $st->fetch();
    if (!$row) return null;

    $row['served_adults']    = (int)$row['served_adults'];
    $row['served_kids']      = (int)$row['served_kids'];
    $row['remaining']        = $row['headcount'] - $row['consumed'];
    $row['remaining_adults'] = max(0, (int)$row['plates_adults'] - $row['served_adults']);
    $row['remaining_kids']   = max(0, (int)$row['plates_kids']   - $row['served_kids']);
    $row['remaining_due']    = (float)$row['total_amount'] - (float)$row['paid_amount'];

    $st2 = $db->prepare("SELECT relation, served_at FROM consumption WHERE booking_id = ? ORDER BY served_at DESC");
    $st2->execute([$row['id']]);
    $row['history'] = $st2->fetchAll();

    return $row;
}

function consumePlate(int $bookingId, string $relation, ?int $validatorId = null): array {
    $db = getDB();

    $st = $db->prepare("
        SELECT headcount,
               (SELECT COUNT(*) FROM consumption WHERE booking_id = ?) AS consumed
        FROM   bookings WHERE id = ? LIMIT 1");
    $st->execute([$bookingId, $bookingId]);
    $row = $st->fetch();

    if (!$row) return ['success' => false, 'message' => 'Booking not found.'];

    if ((int)$row['consumed'] >= (int)$row['headcount']) {
        return ['success' => false, 'message' => 'limit_reached', 'consumed' => (int)$row['consumed']];
    }

    $db->prepare("INSERT INTO consumption (booking_id, relation, validator_id) VALUES (?,?,?)")
       ->execute([$bookingId, trim($relation), $validatorId]);

    $newConsumed = (int)$row['consumed'] + 1;
    return [
        'success'   => true,
        'consumed'  => $newConsumed,
        'remaining' => (int)$row['headcount'] - $newConsumed,
    ];
}

/* ═══════════════════════════════════════════════════════════════════════
   AGENT AUTH
═══════════════════════════════════════════════════════════════════════ */
function verifyAgent(string $pin): ?array {
    $db = getDB();
    $st = $db->prepare("SELECT * FROM agents WHERE pin = ? AND is_active = 1 LIMIT 1");
    $st->execute([$pin]);
    return $st->fetch() ?: null;
}

/* ═══════════════════════════════════════════════════════════════════════
   VALIDATOR MANAGEMENT
═══════════════════════════════════════════════════════════════════════ */
function getAllValidators(): array {
    $db = getDB();
    return $db->query("
        SELECT v.*,
               (SELECT COUNT(*) FROM consumption c WHERE c.validator_id = v.id) AS serves
        FROM validators v
        ORDER BY v.name
    ")->fetchAll();
}

function createValidator(string $name, string $pin): array {
    $db = getDB();
    $st = $db->prepare("SELECT id FROM validators WHERE pin = ? LIMIT 1");
    $st->execute([$pin]);
    if ($st->fetch()) return ['success' => false, 'message' => 'PIN already in use.'];
    $db->prepare("INSERT INTO validators (name, pin) VALUES (?,?)")->execute([$name, $pin]);
    return ['success' => true];
}

function toggleValidator(int $id, int $currentActive): void {
    getDB()->prepare("UPDATE validators SET is_active=? WHERE id=?")->execute([1 - $currentActive, $id]);
}

/* ═══════════════════════════════════════════════════════════════════════
   HELPERS
═══════════════════════════════════════════════════════════════════════ */
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function formatMoney(float $amount): string { return '₹' . number_format($amount, 2); }
function jsonOut(array $data): void {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
