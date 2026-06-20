<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $pin  = trim($_POST['pin']  ?? '');
        if ($name && preg_match('/^\d{4,6}$/', $pin)) {
            $result = createValidator($name, $pin);
            $msg = $result['success'] ? 'ok:Validator added successfully.' : 'err:' . $result['message'];
        } else {
            $msg = 'err:Name and a 4–6 digit PIN are required.';
        }
    }

    if ($action === 'toggle') {
        toggleValidator((int)$_POST['id'], (int)$_POST['active']);
        $msg = 'ok:Validator status updated.';
    }

    if ($action === 'enter_validator') {
        // Admin acts as validator — set a session flag and redirect
        adminEnterValidatorMode();
        header('Location: ' . APP_URL . '/validator/index.php');
        exit;
    }
}

$validators = getAllValidators();

$pageTitle = 'Validators';
$activeNav = 'validators';
include __DIR__ . '/../includes/header.php';
[$msgType, $msgText] = $msg ? explode(':', $msg, 2) : ['',''];
?>

<div class="page-wrap">
  <div class="page-hero">
    <div class="hero-emoji">🍛</div>
    <h2>Validator Management</h2>
    <p>Create dining hall validators and perform check-in actions as admin.</p>
  </div>

  <?php if ($msgText): ?>
  <div class="alert alert-<?= $msgType==='ok'?'success':'danger' ?>">
    <?= $msgType==='ok' ? '✅' : '⚠' ?> <?= h($msgText) ?>
  </div>
  <?php endif; ?>

  <!-- Admin enter validator mode -->
  <div class="card" style="border:2px solid var(--kasavu-gold);max-width:900px;margin:0 auto 24px;">
    <div class="card-header"><span class="card-icon">🎭</span><h3>Act as Validator (Admin Mode)</h3></div>
    <p>Enter the dining hall validator screen directly as admin — scan QR codes, check bookings, and serve plates without needing a separate validator account.</p>
    <form method="POST" style="margin-top:14px;">
      <input type="hidden" name="action" value="enter_validator">
      <button type="submit" class="btn btn-primary">
        🍛 Enter Validator Mode
      </button>
    </form>
  </div>

  <div style="max-width:900px;margin:0 auto;">
    <div class="tabs">
      <div class="tab active" data-tab="list">🍛 All Validators</div>
      <div class="tab"       data-tab="add">➕ Add Validator</div>
    </div>

    <!-- Validators list -->
    <div class="tab-panel active" id="tab-list">
      <div class="card">
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Name</th><th>PIN</th><th>Plates Served</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
              <?php foreach ($validators as $v): ?>
              <tr>
                <td><strong><?= h($v['name']) ?></strong></td>
                <td><code style="font-weight:600;letter-spacing:.1em;"><?= h($v['pin']) ?></code></td>
                <td><?= (int)$v['serves'] ?></td>
                <td>
                  <span class="badge <?= $v['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                    <?= $v['is_active'] ? 'Active' : 'Inactive' ?>
                  </span>
                </td>
                <td>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id"     value="<?= $v['id'] ?>">
                    <input type="hidden" name="active" value="<?= $v['is_active'] ?>">
                    <button type="submit" class="btn btn-sm <?= $v['is_active'] ? 'btn-danger' : 'btn-success' ?>">
                      <?= $v['is_active'] ? 'Deactivate' : 'Activate' ?>
                    </button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($validators)): ?>
              <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--text-mid);">
                No validators yet. Add one below.
              </td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Add validator -->
    <div class="tab-panel" id="tab-add">
      <div class="card" style="max-width:420px;margin:0 auto;">
        <div class="card-header"><span class="card-icon">➕</span><h3>Add New Validator</h3></div>
        <form method="POST">
          <input type="hidden" name="action" value="add">
          <div class="form-group">
            <label>Validator Name <span class="req">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Priya Nair" required>
          </div>
          <div class="form-group">
            <label>4–6 Digit PIN <span class="req">*</span></label>
            <input type="text" name="pin" class="form-control pin-input" placeholder="e.g. 4521" maxlength="6" pattern="\d{4,6}" required>
            <small style="color:var(--text-mid);font-size:.78rem;">Validator will use this PIN to log in to the dining hall screen.</small>
          </div>
          <button type="submit" class="btn btn-primary btn-block">Add Validator</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.tabs .tab').forEach(tab => {
  tab.addEventListener('click', function(){
    document.querySelectorAll('.tabs .tab,.tab-panel').forEach(el => el.classList.remove('active'));
    this.classList.add('active');
    document.getElementById('tab-' + this.dataset.tab).classList.add('active');
  });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
