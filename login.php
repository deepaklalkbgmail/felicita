<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$role  = $_GET['role'] ?? 'admin';  // admin | validator | agent
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? 'admin';

    if ($role === 'admin') {
        if (adminLogin(trim($_POST['username'] ?? ''), $_POST['password'] ?? '')) {
            header('Location: ' . APP_URL . '/admin/index.php');
            exit;
        }
        $error = 'Invalid username or password.';

    } elseif ($role === 'validator') {
        if (validatorLogin(trim($_POST['pin'] ?? ''))) {
            header('Location: ' . APP_URL . '/validator/index.php');
            exit;
        }
        $error = 'Incorrect validator PIN.';

    } elseif ($role === 'agent') {
        $agent = verifyAgent(trim($_POST['pin'] ?? ''));
        if ($agent) {
            $_SESSION['agent_id']   = $agent['id'];
            $_SESSION['agent_name'] = $agent['name'];
            header('Location: ' . APP_URL . '/agent/index.php');
            exit;
        }
        $error = 'Invalid agent PIN.';
    }
}

$showNav  = false;
$pageTitle = 'Login';
include __DIR__ . '/includes/header.php';
?>

<div class="login-wrap">
  <div class="login-card">
    <div class="logo">
      <!-- Aaravam logo with Maveli -->
      <img
        src="<?= APP_URL ?>/assets/img/aaravam-logo.png"
        alt="Aaravam 2026"
        class="login-aaravam-logo"
        onerror="this.style.display='none';document.getElementById('login-logo-fallback').style.display='block'">
      <div id="login-logo-fallback" style="display:none;">
        <div class="lotus">🪷</div>
        <h2><?= APP_NAME ?></h2>
      </div>
      <p style="margin-top:6px;">Onam Sadhya Ticketing System</p>
      <!-- Sponsor badge -->
      <div style="display:inline-flex;align-items:center;gap:6px;margin-top:10px;
                  background:rgba(44,62,107,.07);border:1px solid rgba(44,62,107,.15);
                  border-radius:20px;padding:4px 12px 4px 6px;">
        <img
          src="<?= APP_URL ?>/assets/img/mmg-logo.png"
          alt="MMG"
          style="height:20px;width:auto;object-fit:contain;"
          onerror="this.style.display='none'">
        <span style="font-size:.72rem;color:#4a6080;font-weight:600;letter-spacing:.03em;">Powered by Meta Mates Group</span>
      </div>
    </div>

    <div class="tabs" id="role-tabs">
      <div class="tab <?= $role==='admin'?'active':'' ?>"     data-role="admin">Admin</div>
      <div class="tab <?= $role==='validator'?'active':'' ?>" data-role="validator">Validator</div>
      <div class="tab <?= $role==='agent'?'active':'' ?>"     data-role="agent">Agent</div>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger">⚠ <?= h($error) ?></div>
    <?php endif; ?>

    <!-- Admin login -->
    <div id="form-admin" class="tab-panel <?= $role==='admin'?'active':'' ?>">
      <form method="POST">
        <input type="hidden" name="role" value="admin">
        <div class="form-group">
          <label>Username</label>
          <input type="text" name="username" class="form-control" placeholder="admin" required autocomplete="username">
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Login as Admin</button>
      </form>
    </div>

    <!-- Validator login -->
    <div id="form-validator" class="tab-panel <?= $role==='validator'?'active':'' ?>">
      <form method="POST">
        <input type="hidden" name="role" value="validator">
        <div class="form-group">
          <label>Validator PIN</label>
          <input type="password" name="pin" class="form-control pin-input" placeholder="••••••" maxlength="10" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Enter Validator Mode</button>
      </form>
    </div>

    <!-- Agent login -->
    <div id="form-agent" class="tab-panel <?= $role==='agent'?'active':'' ?>">
      <form method="POST">
        <input type="hidden" name="role" value="agent">
        <div class="form-group">
          <label>Agent PIN</label>
          <input type="password" name="pin" class="form-control pin-input" placeholder="••••••" maxlength="6" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Start Booking</button>
      </form>
    </div>

    <div class="floral-divider" style="margin:16px 0 0;">✿ ❀ ✿</div>
  </div>
</div>

<script>
document.querySelectorAll('.tab').forEach(tab => {
  tab.addEventListener('click', function(){
    document.querySelectorAll('.tab, .tab-panel').forEach(el => el.classList.remove('active'));
    this.classList.add('active');
    document.getElementById('form-' + this.dataset.role).classList.add('active');
  });
});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
