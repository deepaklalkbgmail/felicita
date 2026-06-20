<?php
/**
 * Migration: Add validators table + validator_id to consumption.
 * Run ONCE on existing installations via browser, then DELETE this file.
 * New installations can skip this — install.php already includes these.
 */
require_once __DIR__ . '/database.php';
$pdo = getDB();

$statements = [
    "CREATE TABLE IF NOT EXISTS validators (
        id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name       VARCHAR(120) NOT NULL,
        pin        CHAR(6)      NOT NULL UNIQUE,
        is_active  TINYINT(1)   NOT NULL DEFAULT 1,
        created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "ALTER TABLE consumption
        ADD COLUMN IF NOT EXISTS validator_id INT UNSIGNED NULL AFTER relation,
        ADD CONSTRAINT fk_consumption_validator
            FOREIGN KEY (validator_id) REFERENCES validators(id) ON DELETE SET NULL;",
];

$errors = [];
foreach ($statements as $sql) {
    try { $pdo->exec($sql); }
    catch (PDOException $e) { $errors[] = $e->getMessage(); }
}
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Migration</title>
<style>body{font-family:sans-serif;max-width:600px;margin:60px auto;padding:20px}
.ok{color:#155724;background:#d4edda;padding:12px;border-radius:6px}
.err{color:#721c24;background:#f8d7da;padding:12px;border-radius:6px;margin-top:8px}</style>
</head>
<body>
<h2>Aaravam 2026 — Validators Migration</h2>
<?php if (empty($errors)): ?>
<p class="ok">✅ Migration complete! Validators table created and consumption table updated.<br>
<strong>⚠ Delete this file immediately.</strong></p>
<?php else: ?>
<?php foreach ($errors as $e): ?>
  <p class="err"><?= htmlspecialchars($e) ?></p>
<?php endforeach; ?>
<p class="ok" style="margin-top:10px;">ℹ️ FK constraint errors are normal if this migration already ran. Check the table exists in phpMyAdmin.</p>
<?php endif; ?>
<p><a href="../admin/validators.php">Go to Validator Management →</a></p>
</body></html>
