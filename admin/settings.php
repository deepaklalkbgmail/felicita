<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_settings') {
        $priceAdults = (float)($_POST['price_adults'] ?? 0);
        $priceKids   = (float)($_POST['price_kids']   ?? 0);
        if ($priceAdults > 0 && $priceKids >= 0) {
            setSetting('price_adults',    (string)$priceAdults);
            setSetting('price_kids',      (string)$priceKids);
            setSetting('price_per_plate', (string)$priceAdults);  // keep legacy key in sync
            setSetting('event_name',   trim($_POST['event_name']  ?? ''));
            setSetting('event_date',   trim($_POST['event_date']  ?? ''));
            setSetting('event_venue',  trim($_POST['event_venue'] ?? ''));
            $msg = 'ok:Settings saved successfully.';
        } else {
            $msg = 'err:Adult price must be greater than 0 and kids price cannot be negative.';
        }
    }

    if ($action === 'change_password') {
        $db       = getDB();
        $current  = $_POST['current_password']  ?? '';
        $new      = $_POST['new_password']       ?? '';
        $confirm  = $_POST['confirm_password']   ?? '';

        if ($new !== $confirm) {
            $msg = 'err:New passwords do not match.';
        } elseif (strlen($new) < 8) {
            $msg = 'err:Password must be at least 8 characters.';
        } else {
            $st = $db->prepare("SELECT password_hash FROM admins WHERE id=?");
            $st->execute([$_SESSION['admin_id']]);
            $row = $st->fetch();
            if ($row && password_verify($current, $row['password_hash'])) {
                $db->prepare("UPDATE admins SET password_hash=? WHERE id=?")
                   ->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['admin_id']]);
                $msg = 'ok:Password changed successfully.';
            } else {
                $msg = 'err:Current password is incorrect.';
            }
        }
    }
}

$fallbackPrice = getSetting('price_per_plate', '200');
$settings = [
    'price_adults'    => getSetting('price_adults', $fallbackPrice),
    'price_kids'      => getSetting('price_kids',   $fallbackPrice),
    'event_name'      => getSetting('event_name',  'Aaravam 2026 Onam Sadhya'),
    'event_date'      => getSetting('event_date',  ''),
    'event_venue'     => getSetting('event_venue', ''),
];

$pageTitle = 'Settings';
$activeNav = 'settings';
include __DIR__ . '/../includes/header.php';
[$msgType, $msgText] = $msg ? explode(':', $msg, 2) : ['',''];
?>

<div class="page-wrap">
  <div class="page-hero">
    <div class="hero-emoji">⚙️</div>
    <h2>Settings</h2>
    <p>Configure pricing, event details, and access PINs.</p>
  </div>

  <?php if ($msgText): ?>
  <div class="alert alert-<?= $msgType==='ok'?'success':'danger' ?>">
    <?= $msgType==='ok' ? '✅' : '⚠' ?> <?= h($msgText) ?>
  </div>
  <?php endif; ?>

  <div style="max-width:680px;margin:0 auto;">

    <!-- General settings -->
    <div class="card">
      <div class="card-header"><span class="card-icon">🎪</span><h3>Event & Pricing</h3></div>
      <form method="POST">
        <input type="hidden" name="action" value="save_settings">
        <div class="form-group">
          <label>Event Name <span class="req">*</span></label>
          <input type="text" name="event_name" class="form-control" value="<?= h($settings['event_name']) ?>" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Event Date</label>
            <input type="date" name="event_date" class="form-control" value="<?= h($settings['event_date']) ?>">
          </div>
          <div class="form-group">
            <label>Event Venue</label>
            <input type="text" name="event_venue" class="form-control" value="<?= h($settings['event_venue']) ?>" placeholder="Community Hall">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Price Per Plate — Adults (₹) <span class="req">*</span></label>
            <input type="number" name="price_adults" class="form-control" value="<?= h($settings['price_adults']) ?>" min="1" step="0.01" required>
          </div>
          <div class="form-group">
            <label>Price Per Plate — Kids (₹) <span class="req">*</span></label>
            <input type="number" name="price_kids" class="form-control" value="<?= h($settings['price_kids']) ?>" min="0" step="0.01" required>
          </div>
        </div>
        <small style="color:var(--text-mid);font-size:.78rem;">⚠ Changing prices only affects new bookings and re-calculations, not the paid amount already recorded.</small>
        <div style="margin-top:14px;"><button type="submit" class="btn btn-primary">💾 Save Settings</button></div>
      </form>
    </div>

    <!-- Change password -->
    <div class="card">
      <div class="card-header"><span class="card-icon">🔐</span><h3>Change Admin Password</h3></div>
      <form method="POST">
        <input type="hidden" name="action" value="change_password">
        <div class="form-group">
          <label>Current Password</label>
          <input type="password" name="current_password" class="form-control" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" class="form-control" minlength="8" required>
          </div>
          <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" required>
          </div>
        </div>
        <button type="submit" class="btn btn-danger">🔑 Change Password</button>
      </form>
    </div>

    <!-- Info box -->
    <div class="card" style="background:var(--kasavu-cream);border-color:var(--kasavu-border);">
      <div class="card-header"><span class="card-icon">ℹ️</span><h3>Access Information</h3></div>
      <table style="width:100%;font-size:.88rem;">
        <tr style="border-bottom:1px dashed rgba(200,150,12,.2);">
          <td style="padding:8px;color:var(--text-mid);">Agent Booking URL</td>
          <td style="padding:8px;"><code><?= APP_URL ?>/agent/index.php</code></td>
        </tr>
        <tr style="border-bottom:1px dashed rgba(200,150,12,.2);">
          <td style="padding:8px;color:var(--text-mid);">Validator URL</td>
          <td style="padding:8px;"><code><?= APP_URL ?>/validator/index.php</code></td>
        </tr>
        <tr>
          <td style="padding:8px;color:var(--text-mid);">Admin URL</td>
          <td style="padding:8px;"><code><?= APP_URL ?>/admin/index.php</code></td>
        </tr>
      </table>
    </div>

  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
