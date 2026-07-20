<?php
/**
 * Aaravam 2026 — Migration v2
 * Adds: kids/adults pricing + plates, payment tracking, wing/unit on bookings,
 *       residents master table, and a booking edit audit log.
 *
 * Run ONCE in browser on an EXISTING install:  /aaravam2026/config/migrate_v2.php
 * Then run  config/seed_residents.php  to load the wing/unit resident list.
 * DELETE both files after running.
 *
 * Fresh installs: config/install.php already contains all of this.
 */
require_once __DIR__ . '/database.php';
$pdo = getDB();

$statements = [

    // Residents master list (Wing / Unit / Name)
    "CREATE TABLE IF NOT EXISTS residents (
        id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        wing_no  TINYINT UNSIGNED NOT NULL,
        unit     VARCHAR(20) NOT NULL,
        name     VARCHAR(200) NOT NULL DEFAULT '',
        UNIQUE KEY uniq_wing_unit (wing_no, unit)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // Booking edit audit log
    "CREATE TABLE IF NOT EXISTS booking_edits (
        id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        booking_id  INT UNSIGNED NOT NULL,
        agent_id    INT UNSIGNED NULL,
        editor_name VARCHAR(120) NOT NULL,
        changes     TEXT NOT NULL,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_edit_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // Widen secret_code (wing codes are longer than 6 chars)
    "ALTER TABLE bookings MODIFY COLUMN secret_code VARCHAR(20) NOT NULL",

    // New booking columns (one per statement so a re-run/partial is harmless)
    "ALTER TABLE bookings ADD COLUMN wing_no       TINYINT UNSIGNED NULL AFTER house_name",
    "ALTER TABLE bookings ADD COLUMN unit          VARCHAR(20) NULL AFTER wing_no",
    "ALTER TABLE bookings ADD COLUMN plates_kids   INT UNSIGNED NOT NULL DEFAULT 0 AFTER headcount",
    "ALTER TABLE bookings ADD COLUMN plates_adults INT UNSIGNED NOT NULL DEFAULT 0 AFTER plates_kids",
    "ALTER TABLE bookings ADD COLUMN paid_amount   DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER total_amount",

    // New pricing settings (kept alongside legacy price_per_plate as fallback)
    "INSERT IGNORE INTO settings (setting_key, setting_value)
        SELECT 'price_adults', COALESCE((SELECT setting_value FROM settings s2 WHERE s2.setting_key='price_per_plate'), '200')",
    "INSERT IGNORE INTO settings (setting_key, setting_value)
        SELECT 'price_kids', COALESCE((SELECT setting_value FROM settings s2 WHERE s2.setting_key='price_per_plate'), '100')",
];

$errors = [];
foreach ($statements as $sql) {
    try { $pdo->exec($sql); }
    catch (PDOException $e) { $errors[] = $e->getMessage(); }
}
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Migration v2</title>
<style>body{font-family:sans-serif;max-width:640px;margin:50px auto;padding:20px}
.ok{color:#155724;background:#d4edda;padding:14px;border-radius:8px}
.err{color:#721c24;background:#f8d7da;padding:12px;border-radius:8px;margin-top:8px}</style>
</head><body>
<h2>Aaravam 2026 — Migration v2</h2>
<?php if (empty($errors)): ?>
<p class="ok">✅ Migration complete — pricing, payments, wing/unit and edit-log are ready.<br>
Next: run <strong>config/seed_residents.php</strong> to load the resident list,<br>
then <strong>delete both files</strong>.</p>
<?php else: ?>
<?php foreach ($errors as $e): ?>
  <p class="err"><?= htmlspecialchars($e) ?></p>
<?php endforeach; ?>
<p class="ok" style="margin-top:10px;">ℹ️ "Duplicate column"/"exists" errors are safe if this ran before.</p>
<?php endif; ?>
<p><a href="seed_residents.php">Run resident seed →</a> &nbsp;|&nbsp; <a href="../admin/index.php">Admin →</a></p>
</body></html>
