<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$db = getDB();

$search = trim($_GET['search'] ?? '');
$where  = ['1=1'];
$params = [];
if ($search) {
    $like = "%$search%";
    $where[] = "(b.order_id LIKE ? OR b.house_name LIKE ? OR b.owner_name LIKE ? OR e.editor_name LIKE ?)";
    $params  = array_merge($params, [$like, $like, $like, $like]);
}
$whereStr = implode(' AND ', $where);

$st = $db->prepare("
    SELECT e.*, b.order_id, b.house_name, b.owner_name
    FROM booking_edits e
    JOIN bookings b ON b.id = e.booking_id
    WHERE $whereStr
    ORDER BY e.created_at DESC
    LIMIT 300
");
$st->execute($params);
$logs = $st->fetchAll();

// Friendly field labels + currency formatting
function fmtChangeVal(string $field, $val): string {
    if (in_array($field, ['paid_amount'], true)) return '₹' . number_format((float)$val, 2);
    return (string)$val;
}
function fieldLabel(string $f): string {
    return [
        'owner_name'     => 'Owner Name',
        'contact_number' => 'Contact',
        'plates_kids'    => 'Kids Plates',
        'plates_adults'  => 'Adult Plates',
        'paid_amount'    => 'Paid Amount',
    ][$f] ?? $f;
}

$pageTitle = 'Edit Logs';
$activeNav = 'edit_logs';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-wrap">
  <div class="page-hero">
    <div class="hero-emoji">🧾</div>
    <h2>Booking Edit Logs</h2>
    <p>Audit trail of every change made to a booking by agents or admin.</p>
  </div>

  <div class="card">
    <form method="GET" class="filter-bar">
      <div class="form-group">
        <label>Search</label>
        <input type="text" name="search" class="form-control" placeholder="Order / Unit / Owner / Editor" value="<?= h($search) ?>">
      </div>
      <div class="form-group" style="align-self:flex-end;">
        <button type="submit" class="btn btn-primary">🔍 Filter</button>
        <a href="<?= APP_URL ?>/admin/edit_logs.php" class="btn btn-secondary" style="margin-left:6px;">Clear</a>
      </div>
    </form>

    <p style="font-size:.85rem;color:var(--text-mid);margin-bottom:10px;"><?= count($logs) ?> edit(s) recorded.</p>

    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Time</th><th>Order ID</th><th>Block/Unit</th><th>Edited By</th><th>Changes</th></tr>
        </thead>
        <tbody>
          <?php foreach ($logs as $lg):
            $changes = json_decode($lg['changes'], true) ?: [];
          ?>
          <tr>
            <td style="white-space:nowrap;"><?= date('d M, H:i:s', strtotime($lg['created_at'])) ?></td>
            <td><strong><?= h($lg['order_id']) ?></strong></td>
            <td><?= h($lg['house_name']) ?></td>
            <td><?= h($lg['editor_name']) ?></td>
            <td>
              <?php foreach ($changes as $field => $ch): ?>
                <div style="font-size:.82rem;margin-bottom:2px;">
                  <strong><?= h(fieldLabel($field)) ?>:</strong>
                  <span style="color:#C0392B;text-decoration:line-through;"><?= h(fmtChangeVal($field, $ch['from'] ?? '')) ?></span>
                  →
                  <span style="color:#1A7A40;"><?= h(fmtChangeVal($field, $ch['to'] ?? '')) ?></span>
                </div>
              <?php endforeach; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($logs)): ?>
          <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--text-mid);">No edits recorded yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
