<?php
/**
 * Aaravam 2026 — Demo / Go-Live Reset Utility
 *
 * Clears TRANSACTIONAL data so you can start a demo (or go live) from a clean slate.
 * Run in the browser:  /aaravam2026/config/reset_demo.php?confirm=YES
 *
 * ⚠ DELETE THIS FILE before the app goes live to the public.
 *
 * Two modes (choose with the &mode= parameter):
 *   mode=bookings  (default) → deletes only bookings + consumption (check-in) records.
 *                              Keeps your agents, validators, settings and admin.
 *   mode=all                 → deletes bookings, consumption, ALL agents and ALL
 *                              validators too. Settings + admin login are kept.
 */
require_once __DIR__ . '/database.php';

$confirm = ($_GET['confirm'] ?? '') === 'YES';
$mode    = ($_GET['mode'] ?? 'bookings') === 'all' ? 'all' : 'bookings';
$done    = false;
$errors  = [];
$counts  = [];

if ($confirm) {
    $pdo = getDB();
    try {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

        // Always clear transactional data
        $counts['consumption'] = $pdo->query('SELECT COUNT(*) FROM consumption')->fetchColumn();
        $counts['bookings']    = $pdo->query('SELECT COUNT(*) FROM bookings')->fetchColumn();
        $pdo->exec('TRUNCATE TABLE consumption');
        $pdo->exec('TRUNCATE TABLE bookings');

        if ($mode === 'all') {
            $counts['validators'] = $pdo->query('SELECT COUNT(*) FROM validators')->fetchColumn();
            $counts['agents']     = $pdo->query('SELECT COUNT(*) FROM agents')->fetchColumn();
            $pdo->exec('TRUNCATE TABLE validators');
            $pdo->exec('TRUNCATE TABLE agents');
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        $done = true;
    } catch (PDOException $e) {
        $errors[] = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Aaravam 2026 — Reset</title>
<style>
  body{font-family:sans-serif;max-width:620px;margin:50px auto;padding:20px;color:#2C1810;line-height:1.6}
  h2{color:#9A6F08}
  .ok{color:#155724;background:#d4edda;padding:14px;border-radius:8px}
  .err{color:#721c24;background:#f8d7da;padding:14px;border-radius:8px;margin-top:8px}
  .warn{color:#7B241C;background:#f8d7da;padding:14px;border-radius:8px}
  .card{border:1px solid #E8C84A;border-radius:10px;padding:18px 20px;margin:14px 0;background:#FFFDF5}
  .btn{display:inline-block;background:#C0392B;color:#fff;text-decoration:none;padding:10px 18px;border-radius:6px;font-weight:700;margin-top:8px}
  .btn.safe{background:#9A6F08}
  code{background:#eee;padding:2px 6px;border-radius:4px}
  ul{margin:6px 0}
</style>
</head>
<body>
<h2>🧹 Aaravam 2026 — Demo / Go-Live Reset</h2>

<?php if ($done): ?>
  <p class="ok">✅ Reset complete (mode: <strong><?= htmlspecialchars($mode) ?></strong>).<br>
    Cleared:
    <?php foreach ($counts as $t => $c): ?>
      <br>• <?= htmlspecialchars($t) ?>: <?= (int)$c ?> record(s) removed
    <?php endforeach; ?>
  </p>
  <p class="warn">⚠ Now <strong>DELETE this file</strong> (config/reset_demo.php) before going live.</p>
  <p><a href="../login.php">Go to Login →</a></p>

<?php elseif ($errors): ?>
  <p class="err">Errors:<br><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></p>

<?php else: ?>
  <div class="card">
    <p><strong>Choose what to clear.</strong> Settings (event name, price) and your admin
       login are <em>always kept</em>.</p>

    <p><strong>Option A — Clear bookings only</strong> (recommended between demo and go-live if
       you already added the real agents & validators):</p>
    <ul>
      <li>Deletes all bookings and all check-in / plate-served records.</li>
      <li>Keeps agents, validators, settings, admin.</li>
    </ul>
    <a class="btn safe" href="?confirm=YES&mode=bookings">Clear bookings only</a>

    <p style="margin-top:22px;"><strong>Option B — Full clean slate</strong> (wipe demo agents &
       validators too, so you can re-add the real team):</p>
    <ul>
      <li>Deletes bookings, check-ins, <strong>all agents</strong> and <strong>all validators</strong>.</li>
      <li>Keeps settings + admin login only.</li>
    </ul>
    <a class="btn" href="?confirm=YES&mode=all">Wipe everything (bookings + agents + validators)</a>
  </div>
  <p style="font-size:.85rem;color:#8D6E63;">Nothing has been deleted yet — pick an option above to proceed.</p>
<?php endif; ?>
</body>
</html>
