<?php
/**
 * Aaravam 2026 – One-time database installer.
 * Run this ONCE via browser: http://yourdomain.com/aaravam2026/config/install.php
 * DELETE this file after successful installation.
 */
require_once __DIR__ . '/database.php';

$pdo = getDB();

$statements = [

/* ── Settings ─────────────────────────────────────────────────────────── */
"CREATE TABLE IF NOT EXISTS settings (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key   VARCHAR(100)  NOT NULL UNIQUE,
    setting_value TEXT          NOT NULL,
    updated_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

/* ── Admins ─────────────────────────────────────────────────────────────*/
"CREATE TABLE IF NOT EXISTS admins (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(60)   NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL,
    created_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

/* ── Agents / Volunteers ────────────────────────────────────────────────*/
"CREATE TABLE IF NOT EXISTS agents (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(120) NOT NULL,
    pin        CHAR(6)      NOT NULL,
    is_active  TINYINT(1)   NOT NULL DEFAULT 1,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

/* ── Residents master list (Wing / Unit / Name) ─────────────────────────*/
"CREATE TABLE IF NOT EXISTS residents (
    id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    wing_no  TINYINT UNSIGNED NOT NULL,
    unit     VARCHAR(20)  NOT NULL,
    name     VARCHAR(200) NOT NULL DEFAULT '',
    UNIQUE KEY uniq_wing_unit (wing_no, unit)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

/* ── Bookings ───────────────────────────────────────────────────────────*/
"CREATE TABLE IF NOT EXISTS bookings (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id       VARCHAR(20)    NOT NULL UNIQUE,
    house_name     VARCHAR(200)   NOT NULL,
    wing_no        TINYINT UNSIGNED NULL,
    unit           VARCHAR(20)    NULL,
    owner_name     VARCHAR(200)   NOT NULL,
    contact_number VARCHAR(20)    NOT NULL,
    headcount      SMALLINT UNSIGNED NOT NULL,
    plates_kids    INT UNSIGNED   NOT NULL DEFAULT 0,
    plates_adults  INT UNSIGNED   NOT NULL DEFAULT 0,
    price_per_plate DECIMAL(10,2) NOT NULL,
    total_amount   DECIMAL(10,2) NOT NULL,
    paid_amount    DECIMAL(10,2) NOT NULL DEFAULT 0,
    secret_code    VARCHAR(20)    NOT NULL UNIQUE,
    booking_type   ENUM('agent','adhoc') NOT NULL DEFAULT 'agent',
    agent_id       INT UNSIGNED   NULL,
    notes          TEXT           NULL,
    created_at     TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_booking_agent FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

/* ── Booking edit audit log ─────────────────────────────────────────────*/
"CREATE TABLE IF NOT EXISTS booking_edits (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id  INT UNSIGNED NOT NULL,
    agent_id    INT UNSIGNED NULL,
    editor_name VARCHAR(120) NOT NULL,
    changes     TEXT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_edit_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

/* ── Validators ─────────────────────────────────────────────────────────*/
"CREATE TABLE IF NOT EXISTS validators (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(120) NOT NULL,
    pin        CHAR(6)      NOT NULL UNIQUE,
    is_active  TINYINT(1)   NOT NULL DEFAULT 1,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

/* ── Consumption (one row per plate served) ─────────────────────────────*/
"CREATE TABLE IF NOT EXISTS consumption (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id   INT UNSIGNED NOT NULL,
    relation     VARCHAR(120) NOT NULL,
    validator_id INT UNSIGNED NULL,
    served_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_consumption_booking   FOREIGN KEY (booking_id)   REFERENCES bookings(id)   ON DELETE CASCADE,
    CONSTRAINT fk_consumption_validator FOREIGN KEY (validator_id) REFERENCES validators(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

/* ── Seed default settings ──────────────────────────────────────────────*/
"INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
    ('price_per_plate', '200'),
    ('price_adults',    '200'),
    ('price_kids',      '100'),
    ('event_name',      'Aaravam 2026 Onam Sadhya'),
    ('event_date',      '2026-09-12'),
    ('event_venue',     'Community Hall');",

/* ── Default admin (username: admin / password: Admin@1234) ─────────────*/
"INSERT IGNORE INTO admins (username, password_hash) VALUES
    ('admin', '" . password_hash('Admin@1234', PASSWORD_DEFAULT) . "');",
];

$errors = [];
foreach ($statements as $sql) {
    try {
        $pdo->exec($sql);
    } catch (PDOException $e) {
        $errors[] = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Aaravam 2026 – Install</title>
<style>body{font-family:sans-serif;max-width:600px;margin:60px auto;padding:20px}
.ok{color:#155724;background:#d4edda;padding:12px;border-radius:6px}
.err{color:#721c24;background:#f8d7da;padding:12px;border-radius:6px;margin-top:8px}</style>
</head>
<body>
<h2>Aaravam 2026 — Database Installer</h2>
<?php if (empty($errors)): ?>
<p class="ok">✅ Installation complete! Default admin: <strong>admin / Admin@1234</strong><br>
<strong>⚠ Delete this file immediately after noting your credentials.</strong></p>
<?php else: ?>
<p class="err">Errors occurred:<br><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></p>
<?php endif; ?>
<p><a href="../login.php">Go to Login →</a></p>
</body></html>
