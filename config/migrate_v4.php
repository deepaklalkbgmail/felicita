<?php
/**
 * Aaravam 2026 — Migration v4
 * Adds bookings.paid_to (which account the payment was collected into) and
 * seeds the configurable list of accounts.
 *
 * Run ONCE in browser on an EXISTING install:  /aaravam2026/config/migrate_v4.php
 * DELETE this file after running.
 */
require_once __DIR__ . '/database.php';
$pdo = getDB();

$statements = [
    "ALTER TABLE bookings ADD COLUMN paid_to VARCHAR(120) NULL AFTER paid_amount",
    "INSERT IGNORE INTO settings (setting_key, setting_value)
        VALUES ('paid_to_options', 'Deepaklal,Naveen,Arun Vishnu')",
];

$errors = [];
foreach ($statements as $sql) {
    try { $pdo->exec($sql); }
    catch (PDOException $e) { $errors[] = $e->getMessage(); }
}
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Migration v4</title>
<style>body{font-family:sans-serif;max-width:640px;margin:50px auto;padding:20px}
.ok{color:#155724;background:#d4edda;padding:14px;border-radius:8px}
.err{color:#721c24;background:#f8d7da;padding:12px;border-radius:8px;margin-top:8px}</style>
</head><body>
<h2>Aaravam 2026 — Migration v4</h2>
<?php if (empty($errors)): ?>
<p class="ok">✅ Done — bookings now record a "Paid to" account.<br>
<strong>Delete this file (config/migrate_v4.php).</strong></p>
<?php else: ?>
<?php foreach ($errors as $e): ?>
  <p class="err"><?= htmlspecialchars($e) ?></p>
<?php endforeach; ?>
<p class="ok" style="margin-top:10px;">ℹ️ "Duplicate column" is safe if this ran before.</p>
<?php endif; ?>
<p><a href="../admin/index.php">Go to Admin →</a></p>
</body></html>
