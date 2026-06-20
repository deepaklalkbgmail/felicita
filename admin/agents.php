<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$db  = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $pin  = trim($_POST['pin']  ?? '');
        if ($name && preg_match('/^\d{4,6}$/', $pin)) {
            // Check unique pin
            $st = $db->prepare("SELECT id FROM agents WHERE pin=? LIMIT 1");
            $st->execute([$pin]);
            if ($st->fetch()) {
                $msg = 'err:PIN already in use.';
            } else {
                $db->prepare("INSERT INTO agents (name,pin) VALUES (?,?)")->execute([$name, $pin]);
                $msg = 'ok:Agent added successfully.';
            }
        } else {
            $msg = 'err:Name and 4-6 digit PIN required.';
        }
    }

    if ($action === 'toggle') {
        $id     = (int)$_POST['id'];
        $active = (int)$_POST['active'];
        $db->prepare("UPDATE agents SET is_active=? WHERE id=?")->execute([1-$active, $id]);
        $msg = 'ok:Agent status updated.';
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $db->prepare("UPDATE agents SET is_active=0 WHERE id=?")->execute([$id]);
        $msg = 'ok:Agent deactivated.';
    }
}

$agents = $db->query("
    SELECT a.*, COUNT(b.id) AS booking_count, SUM(b.total_amount) AS total_collected
    FROM agents a
    LEFT JOIN bookings b ON b.agent_id = a.id
    GROUP BY a.id
    ORDER BY a.name
")->fetchAll();

$pageTitle = 'Agents';
$activeNav = 'agents';
include __DIR__ . '/../includes/header.php';
[$msgType, $msgText] = $msg ? explode(':', $msg, 2) : ['',''];
?>

<div class="page-wrap">
  <div class="page-hero">
    <div class="hero-emoji">👥</div>
    <h2>Agent Management</h2>
    <p>Add volunteers / agents with PIN for door-to-door bookings.</p>
  </div>

  <?php if ($msgText): ?>
  <div class="alert alert-<?= $msgType==='ok'?'success':'danger' ?>">
    <?= $msgType==='ok' ? '✅' : '⚠' ?> <?= h($msgText) ?>
  </div>
  <?php endif; ?>

  <div style="max-width:900px;margin:0 auto;">
    <div class="tabs">
      <div class="tab active" data-tab="list">👥 All Agents</div>
      <div class="tab"       data-tab="add">➕ Add Agent</div>
    </div>

    <div class="tab-panel active" id="tab-list">
      <div class="card">
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Name</th><th>PIN</th><th>Bookings</th><th>Collected</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <?php foreach ($agents as $ag): ?>
              <tr>
                <td><strong><?= h($ag['name']) ?></strong></td>
                <td><code style="font-weight:600;letter-spacing:.1em;"><?= h($ag['pin']) ?></code></td>
                <td><?= (int)$ag['booking_count'] ?></td>
                <td>₹<?= number_format((float)($ag['total_collected']??0), 0) ?></td>
                <td>
                  <span class="badge <?= $ag['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                    <?= $ag['is_active'] ? 'Active' : 'Inactive' ?>
                  </span>
                </td>
                <td>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id"     value="<?= $ag['id'] ?>">
                    <input type="hidden" name="active" value="<?= $ag['is_active'] ?>">
                    <button type="submit" class="btn btn-sm <?= $ag['is_active']?'btn-danger':'btn-success' ?>">
                      <?= $ag['is_active'] ? 'Deactivate' : 'Activate' ?>
                    </button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($agents)): ?>
              <tr><td colspan="6" style="text-align:center;padding:24px;">No agents yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="tab-panel" id="tab-add">
      <div class="card" style="max-width:420px;margin:0 auto;">
        <div class="card-header"><span class="card-icon">➕</span><h3>Add New Agent</h3></div>
        <form method="POST">
          <input type="hidden" name="action" value="add">
          <div class="form-group">
            <label>Agent / Volunteer Name <span class="req">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Rajan K P" required>
          </div>
          <div class="form-group">
            <label>4-6 Digit PIN <span class="req">*</span></label>
            <input type="text" name="pin" class="form-control pin-input" placeholder="e.g. 1234" maxlength="6" pattern="\d{4,6}" required>
            <small style="color:var(--text-mid);font-size:.78rem;">Agent will use this PIN to log in to the booking page.</small>
          </div>
          <button type="submit" class="btn btn-primary btn-block">Add Agent</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.tabs .tab').forEach(tab=>{
  tab.addEventListener('click',function(){
    document.querySelectorAll('.tabs .tab,.tab-panel').forEach(el=>el.classList.remove('active'));
    this.classList.add('active');
    document.getElementById('tab-'+this.dataset.tab).classList.add('active');
  });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
