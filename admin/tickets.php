<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$db = getDB();
$msg = '';
$newBooking = null;

// Handle ad-hoc ticket creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adhoc'])) {
    $house   = trim($_POST['house_name']     ?? '');
    $owner   = trim($_POST['owner_name']     ?? '');
    $contact = trim($_POST['contact_number'] ?? '');
    $kids    = (int)($_POST['plates_kids']   ?? 0);
    $adults  = (int)($_POST['plates_adults'] ?? 0);
    $paid    = (float)($_POST['paid_amount'] ?? 0);
    $notes   = trim($_POST['notes']          ?? '');

    if ($house && $owner && $contact && ($kids + $adults) >= 1) {
        $newBooking = createBooking([
            'house_name'     => $house,
            'owner_name'     => $owner,
            'contact_number' => $contact,
            'plates_kids'    => $kids,
            'plates_adults'  => $adults,
            'paid_amount'    => $paid,
            'notes'          => $notes,
            'booking_type'   => 'adhoc',
            'agent_id'       => null,
        ]);
        $msg = 'adhoc_created';
    }
}

// Filters
$search   = trim($_GET['search']    ?? '');
$agFilter = (int)($_GET['agent_id'] ?? 0);
$type     = trim($_GET['type']      ?? '');

$where  = ['1=1'];
$params = [];
if ($search) {
    $where[]  = "(b.order_id LIKE ? OR b.house_name LIKE ? OR b.owner_name LIKE ? OR b.secret_code LIKE ? OR b.unit LIKE ?)";
    $like = "%$search%";
    $params   = array_merge($params, [$like, $like, $like, $like, $like]);
}
if ($agFilter) { $where[] = "b.agent_id = ?"; $params[] = $agFilter; }
if ($type)     { $where[] = "b.booking_type = ?"; $params[] = $type; }

$whereStr = implode(' AND ', $where);
$st = $db->prepare("
    SELECT b.*, a.name AS agent_name,
           (SELECT COUNT(*) FROM consumption c WHERE c.booking_id=b.id) AS consumed
    FROM   bookings b
    LEFT JOIN agents a ON a.id=b.agent_id
    WHERE  $whereStr
    ORDER BY b.created_at DESC
");
$st->execute($params);
$bookings = $st->fetchAll();

$agents = $db->query("SELECT id, name FROM agents ORDER BY name")->fetchAll();

$prices = getPrices();

$pageTitle = 'Tickets';
$activeNav = 'tickets';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-wrap">
  <div class="page-hero">
    <div class="hero-emoji">🎟</div>
    <h2>Tickets & Bookings</h2>
    <p>Manage all bookings. Generate VIP / ad-hoc tickets.</p>
  </div>

  <?php if ($msg === 'adhoc_created' && $newBooking): ?>
  <div class="card" style="border:2px solid var(--pookalam-green);max-width:480px;margin:0 auto 24px;">
    <div style="text-align:center;"><span style="font-size:2rem;">🎉</span>
      <h3 style="color:var(--pookalam-green);">Ad-hoc Ticket Created!</h3></div>

    <div class="ticket">
      <div class="ticket-header">
        <div style="font-size:1.5rem;">🪷</div>
        <h3><?= h(getSetting('event_name','Aaravam 2026')) ?></h3>
        <small><?= h($newBooking['order_id']) ?></small>
      </div>
      <div class="ticket-body">
        <div class="ticket-row"><span class="lbl">Identifier</span><span class="val"><?= h($newBooking['house_name']) ?></span></div>
        <div class="ticket-row"><span class="lbl">Owner</span><span class="val"><?= h($newBooking['owner_name']) ?></span></div>
        <div class="ticket-row"><span class="lbl">Plates</span><span class="val"><?= (int)$newBooking['plates_adults'] ?> adult(s), <?= (int)$newBooking['plates_kids'] ?> kid(s)</span></div>
        <div class="ticket-row"><span class="lbl">Amount</span><span class="val">₹<?= number_format((float)$newBooking['total_amount'],2) ?></span></div>
        <div class="ticket-row"><span class="lbl">Paid</span><span class="val">₹<?= number_format((float)$newBooking['paid_amount'],2) ?></span></div>
        <div class="ticket-row"><span class="lbl">Balance</span><span class="val">₹<?= number_format((float)$newBooking['remaining_due'],2) ?></span></div>
      </div>
      <div class="secret-code-box" style="border-radius:0;">
        <div class="code-label">🔑 Secret Code</div>
        <div class="code-value"><?= h($newBooking['secret_code']) ?></div>
        <div class="code-chars">
          <?php foreach (str_split($newBooking['secret_code']) as $ch): ?>
            <span class="code-char"><?= h($ch) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
      <div style="padding:14px;background:#fff;text-align:center;">
        <div class="qr-wrap" style="margin:0;padding:10px;">
          <div id="adhoc-qr"></div>
        </div>
      </div>
      <div class="ticket-footer">VIP / Ad-hoc Ticket &bull; <?= h(getSetting('event_name','')) ?></div>
    </div>
    <div style="display:flex;gap:10px;margin-top:14px;flex-wrap:wrap;">
      <button class="btn btn-outline" onclick="window.print()">🖨 Print</button>
    </div>
  </div>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script>
    new QRCode(document.getElementById('adhoc-qr'), {
      text: '<?= h($newBooking['secret_code']) ?>',
      width:160, height:160, colorDark:'#2C1810', colorLight:'#fff',
      correctLevel: QRCode.CorrectLevel.H
    });
  </script>
  <?php endif; ?>

  <div class="tabs">
    <div class="tab active" data-tab="list">📋 All Bookings</div>
    <div class="tab"       data-tab="adhoc">⭐ New VIP / Ad-hoc</div>
  </div>

  <!-- Bookings list -->
  <div class="tab-panel active" id="tab-list">
    <div class="card">
      <form method="GET" class="filter-bar">
        <div class="form-group">
          <label>Search</label>
          <input type="text" name="search" class="form-control" placeholder="Order ID / Name / Code" value="<?= h($search) ?>">
        </div>
        <div class="form-group">
          <label>Agent</label>
          <select name="agent_id" class="form-control">
            <option value="">All Agents</option>
            <?php foreach ($agents as $ag): ?>
              <option value="<?= $ag['id'] ?>" <?= $agFilter==$ag['id']?'selected':'' ?>><?= h($ag['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Type</label>
          <select name="type" class="form-control">
            <option value="">All Types</option>
            <option value="agent" <?= $type==='agent'?'selected':'' ?>>Agent</option>
            <option value="adhoc" <?= $type==='adhoc'?'selected':'' ?>>Ad-hoc/VIP</option>
          </select>
        </div>
        <div class="form-group" style="align-self:flex-end;">
          <button type="submit" class="btn btn-primary">Filter</button>
          <a href="<?= APP_URL ?>/admin/tickets.php" class="btn btn-secondary" style="margin-left:6px;">Clear</a>
        </div>
      </form>

      <p style="font-size:.85rem;color:var(--text-mid);margin-bottom:10px;"><?= count($bookings) ?> booking(s) found.</p>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Order ID</th><th>Block / Unit</th><th>Owner</th><th>Contact</th>
              <th>Plates (A/K)</th><th>Served</th><th>Amount</th><th>Paid</th><th>Balance</th>
              <th>Secret Code</th><th>Agent</th><th>Type</th><th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bookings as $bk):
              $c  = (int)$bk['consumed'];
              $h  = (int)$bk['headcount'];
              $cls = $c >= $h ? 'badge-danger' : ($c > 0 ? 'badge-warning' : 'badge-success');
              $due = (float)$bk['total_amount'] - (float)$bk['paid_amount'];
            ?>
            <tr>
              <td><strong><?= h($bk['order_id']) ?></strong></td>
              <td><?= h($bk['house_name']) ?></td>
              <td><?= h($bk['owner_name']) ?></td>
              <td><?= h($bk['contact_number']) ?></td>
              <td><?= (int)$bk['plates_adults'] ?>/<?= (int)$bk['plates_kids'] ?></td>
              <td><span class="badge <?= $cls ?>"><?= $c ?>/<?= $h ?></span></td>
              <td>₹<?= number_format((float)$bk['total_amount'],0) ?></td>
              <td>₹<?= number_format((float)$bk['paid_amount'],0) ?></td>
              <td><span class="badge <?= $due > 0 ? 'badge-danger' : 'badge-success' ?>">₹<?= number_format($due,0) ?></span></td>
              <td><code style="letter-spacing:.1em;font-weight:700;"><?= h($bk['secret_code']) ?></code></td>
              <td><?= h($bk['agent_name'] ?? 'Admin') ?></td>
              <td><span class="badge <?= $bk['booking_type']==='adhoc'?'badge-gold':'badge-info' ?>"><?= h($bk['booking_type']) ?></span></td>
              <td style="white-space:nowrap;"><?= date('d M, H:i', strtotime($bk['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($bookings)): ?>
            <tr><td colspan="13" style="text-align:center;padding:24px;">No bookings found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Ad-hoc form -->
  <div class="tab-panel" id="tab-adhoc">
    <div class="card" style="max-width:520px;margin:0 auto;">
      <div class="card-header"><span class="card-icon">⭐</span><h3>Generate VIP / Ad-hoc Ticket</h3></div>
      <form method="POST">
        <input type="hidden" name="adhoc" value="1">
        <div class="form-row">
          <div class="form-group">
            <label>House / Identifier <span class="req">*</span></label>
            <input type="text" name="house_name" class="form-control" placeholder="e.g. VIP Table 1" required>
          </div>
          <div class="form-group">
            <label>Guest Name <span class="req">*</span></label>
            <input type="text" name="owner_name" class="form-control" placeholder="e.g. Smt. Radha Devi" required>
          </div>
        </div>
        <div class="form-group">
          <label>Contact <span class="req">*</span></label>
          <input type="tel" name="contact_number" class="form-control" placeholder="9876543210" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Plates — Adults</label>
            <input type="number" name="plates_adults" id="adhoc-adults" class="form-control adhoc-plate" min="0" max="100" value="1">
          </div>
          <div class="form-group">
            <label>Plates — Kids</label>
            <input type="number" name="plates_kids" id="adhoc-kids" class="form-control adhoc-plate" min="0" max="100" value="0">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Total Amount</label>
            <input type="text" id="adhoc-total" class="form-control" readonly>
          </div>
          <div class="form-group">
            <label>Amount Paid (₹)</label>
            <input type="number" name="paid_amount" id="adhoc-paid" class="form-control" min="0" step="0.01" value="0">
          </div>
        </div>
        <div class="form-group">
          <label>Notes</label>
          <input type="text" name="notes" class="form-control" placeholder="VIP, special arrangement, etc.">
        </div>
        <button type="submit" class="btn btn-primary btn-block">⭐ Generate Ad-hoc Ticket</button>
      </form>
    </div>
  </div>

</div>

<script>
document.querySelectorAll('.tabs .tab').forEach(tab => {
  tab.addEventListener('click', function(){
    document.querySelectorAll('.tabs .tab,.tab-panel').forEach(el=>el.classList.remove('active'));
    this.classList.add('active');
    document.getElementById('tab-' + this.dataset.tab).classList.add('active');
  });
});

const PRICE_ADULTS = <?= $prices['adults'] ?>;
const PRICE_KIDS   = <?= $prices['kids'] ?>;
function adhocRecalc(){
  const a = parseInt(document.getElementById('adhoc-adults').value)||0;
  const k = parseInt(document.getElementById('adhoc-kids').value)||0;
  const total = a*PRICE_ADULTS + k*PRICE_KIDS;
  const el = document.getElementById('adhoc-total');
  if(el) el.value = '₹' + total.toLocaleString('en-IN',{minimumFractionDigits:2});
}
document.querySelectorAll('.adhoc-plate').forEach(el=>el.addEventListener('input', adhocRecalc));
adhocRecalc();

<?php if ($msg === 'adhoc_created'): ?>
document.querySelector('[data-tab="list"]').click();
<?php endif; ?>
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
