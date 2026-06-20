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
    $db          = getDB();
    $orderId     = generateOrderId();
    $secretCode  = generateSecretCode();
    $pricePerPlate = (float) getSetting('price_per_plate', '200');
    $headcount   = (int)   $data['headcount'];
    $totalAmount = $pricePerPlate * $headcount;
    $agentId     = !empty($data['agent_id']) ? (int)$data['agent_id'] : null;
    $type        = $data['booking_type'] ?? 'agent';

    $db->prepare("INSERT INTO bookings
        (order_id, house_name, owner_name, contact_number, headcount,
         price_per_plate, total_amount, secret_code, booking_type, agent_id, notes)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)")
       ->execute([
           $orderId,
           trim($data['house_name']),
           trim($data['owner_name']),
           trim($data['contact_number']),
           $headcount,
           $pricePerPlate,
           $totalAmount,
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
        'total_amount'   => $totalAmount,
        'price_per_plate'=> $pricePerPlate,
        'house_name'     => $data['house_name'],
        'owner_name'     => $data['owner_name'],
    ];
}

/* ═══════════════════════════════════════════════════════════════════════
   VALIDATION
═══════════════════════════════════════════════════════════════════════ */
function lookupBooking(string $code): ?array {
    $db   = getDB();
    $code = strtoupper(trim($code));

    // Accept secret code or order_id
    $st = $db->prepare("
        SELECT b.*,
               a.name AS agent_name,
               (SELECT COUNT(*) FROM consumption c WHERE c.booking_id = b.id) AS consumed
        FROM   bookings b
        LEFT JOIN agents a ON a.id = b.agent_id
        WHERE  b.secret_code = ? OR b.order_id = ?
        LIMIT  1");
    $st->execute([$code, $code]);
    $row = $st->fetch();
    if (!$row) return null;

    $row['remaining'] = $row['headcount'] - $row['consumed'];

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
