<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$pageTitle   = $pageTitle   ?? APP_NAME;
$activeNav   = $activeNav   ?? '';
$showNav     = $showNav     ?? true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <title><?= h($pageTitle) ?> — <?= APP_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css?v=<?= @filemtime(__DIR__ . '/../assets/css/style.css') ?: time() ?>">
  <meta name="theme-color" content="#C8960C">
  <?= $extraHead ?? '' ?>
</head>
<body>

<?php if ($showNav): ?>
<header class="app-header">
  <div class="header-inner">
    <a href="<?= APP_URL ?>/admin/index.php" class="brand">
      <img
        src="<?= APP_URL ?>/assets/img/aaravam-logo.png"
        alt="Aaravam 2026"
        class="header-aaravam-logo"
        style="height:40px;width:auto;max-width:160px;object-fit:contain;"
        onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
      <div class="brand-text" style="display:none;">
        <h1><?= APP_NAME ?></h1>
        <small>Onam Sadhya Manager</small>
      </div>
    </a>
    <nav>
      <?php if (isAdmin()): ?>
        <a href="<?= APP_URL ?>/admin/index.php"    class="<?= $activeNav==='dashboard' ?'active':'' ?>"><span>📊</span> Dashboard</a>
        <a href="<?= APP_URL ?>/admin/tickets.php"  class="<?= $activeNav==='tickets'   ?'active':'' ?>"><span>🎟</span> Tickets</a>
        <a href="<?= APP_URL ?>/admin/agents.php"   class="<?= $activeNav==='agents'    ?'active':'' ?>"><span>👥</span> Agents</a>
        <a href="<?= APP_URL ?>/admin/reports.php"  class="<?= $activeNav==='reports'   ?'active':'' ?>"><span>📋</span> Reports</a>
        <a href="<?= APP_URL ?>/admin/settings.php" class="<?= $activeNav==='settings'  ?'active':'' ?>"><span>⚙️</span> Settings</a>
        <a href="<?= APP_URL ?>/logout.php" class="btn-logout">Logout</a>
      <?php elseif (isValidator()): ?>
        <a href="<?= APP_URL ?>/validator/index.php">🍛 Validator</a>
        <a href="<?= APP_URL ?>/logout.php" class="btn-logout">Logout</a>
      <?php endif; ?>
    </nav>
  </div>
  <div class="pookalam-strip"></div>
  <div class="sponsor-bar">
    <img src="<?= APP_URL ?>/assets/img/mmg-logo.png" alt="MMG"
         style="height:22px;width:auto;max-width:44px;object-fit:contain;"
         onerror="this.style.display='none'">
    <span>Powered by <strong>Meta Mates Group</strong></span>
  </div>
</header>
<?php endif; ?>

<div id="flash-wrap"></div>
