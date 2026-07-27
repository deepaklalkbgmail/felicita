<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$db = getDB();

// ── Filters ──────────────────────────────────────────────────────────────
$fType     = trim($_GET['type']       ?? '');          // agent|adhoc
$fAgent    = (int)($_GET['agent_id']  ?? 0);
$fStatus   = trim($_GET['status']     ?? '');          // consumed|unused|partial
$fDateFrom = trim($_GET['date_from']  ?? '');
$fDateTo   = trim($_GET['date_to']    ?? '');
$fSearch   = trim($_GET['search']     ?? '');
$fPaidTo   = trim($_GET['paid_to']    ?? '');
$export    = trim($_GET['export']     ?? '');          // csv

// ── Build query ───────────────────────────────────────────────────────────
$where  = ['1=1'];
$params = [];

if ($fType)   { $where[] = "b.booking_type = ?";  $params[] = $fType; }
if ($fAgent)  { $where[] = "b.agent_id = ?";       $params[] = $fAgent; }
if ($fPaidTo) { $where[] = "b.paid_to = ?";        $params[] = $fPaidTo; }
if ($fDateFrom){ $where[] = "DATE(b.created_at) >= ?"; $params[] = $fDateFrom; }
if ($fDateTo)  { $where[] = "DATE(b.created_at) <= ?"; $params[] = $fDateTo; }
if ($fSearch) {
    $like = "%$fSearch%";
    $where[]  = "(b.order_id LIKE ? OR b.house_name LIKE ? OR b.owner_name LIKE ? OR b.contact_number LIKE ?)";
    $params   = array_merge($params, [$like,$like,$like,$like]);
}

// Status filter applied after join
$whereStr = implode(' AND ', $where);

$sql = "
    SELECT b.*,
           a.name  AS agent_name,
           (SELECT COUNT(*) FROM consumption c WHERE c.booking_id = b.id) AS consumed
    FROM   bookings b
    LEFT JOIN agents a ON a.id = b.agent_id
    WHERE  $whereStr
    ORDER  BY b.created_at DESC
";

$st = $db->prepare($sql);
$st->execute($params);
$allRows = $st->fetchAll();

// Post-filter by consumption status
if ($fStatus) {
    $allRows = array_filter($allRows, function($r) use ($fStatus) {
        $c = (int)$r['consumed'];
        $h = (int)$r['headcount'];
        return match($fStatus) {
            'unused'   => $c === 0,
            'partial'  => $c > 0 && $c < $h,
            'consumed' => $c >= $h,
            default    => true,
        };
    });
    $allRows = array_values($allRows);
}

// ── Summary totals ────────────────────────────────────────────────────────
$totalBookings = count($allRows);
$totalPlates   = array_sum(array_column($allRows,'headcount'));
$totalRevenue  = array_sum(array_column($allRows,'total_amount'));
$totalPaid     = array_sum(array_column($allRows,'paid_amount'));
$totalDue      = $totalRevenue - $totalPaid;
$totalServed   = array_sum(array_column($allRows,'consumed'));
$totalUnused   = $totalPlates - $totalServed;

// Collections grouped by "Paid to" account (respects current filters)
$collByAccount = [];
foreach ($allRows as $r) {
    $acct = ($r['paid_to'] ?? '') !== '' ? $r['paid_to'] : '— Unassigned —';
    if (!isset($collByAccount[$acct])) $collByAccount[$acct] = ['count' => 0, 'collected' => 0.0];
    $collByAccount[$acct]['count']++;
    $collByAccount[$acct]['collected'] += (float)$r['paid_amount'];
}
uasort($collByAccount, fn($a, $b) => $b['collected'] <=> $a['collected']);

// ── CSV Export ────────────────────────────────────────────────────────────
if ($export === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="aaravam2026_report_' . date('Ymd_His') . '.csv"');
    header('Pragma: no-cache');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel
    fputcsv($out, ['Order ID','Block/Unit','Owner Name','Contact','Adults','Kids','Total Plates','Served','Remaining','Amount','Paid','Balance','Paid To','Agent','Type','Secret Code','Date']);
    foreach ($allRows as $r) {
        fputcsv($out, [
            $r['order_id'], $r['house_name'], $r['owner_name'], $r['contact_number'],
            $r['plates_adults'], $r['plates_kids'], $r['headcount'],
            $r['consumed'], $r['headcount']-$r['consumed'],
            $r['total_amount'], $r['paid_amount'], $r['total_amount']-$r['paid_amount'],
            $r['paid_to'] ?? '',
            $r['agent_name']??'Admin',
            $r['booking_type'], $r['secret_code'], $r['created_at'],
        ]);
    }
    fclose($out);
    exit;
}

// ── Agent summary ─────────────────────────────────────────────────────────
$agentSummary = $db->query("
    SELECT a.name, COUNT(b.id) AS bookings, SUM(b.headcount) AS plates, SUM(b.total_amount) AS revenue,
           (SELECT SUM(1) FROM consumption c JOIN bookings bb ON bb.id=c.booking_id WHERE bb.agent_id=a.id) AS served
    FROM agents a
    LEFT JOIN bookings b ON b.agent_id=a.id
    GROUP BY a.id
    ORDER BY revenue DESC
")->fetchAll();

// ── Check-in timeline (last 7 days) ───────────────────────────────────────
$timeline = $db->query("
    SELECT DATE(served_at) AS day, COUNT(*) AS cnt
    FROM consumption
    WHERE served_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY day ORDER BY day
")->fetchAll();

$agents = $db->query("SELECT id,name FROM agents ORDER BY name")->fetchAll();
$paidToOpts = getPaidToOptions();

$pageTitle = 'Reports';
$activeNav = 'reports';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-wrap">
  <div class="page-hero">
    <div class="hero-emoji">📋</div>
    <h2>Reports & Analytics</h2>
    <p>Filter, analyse, and export Sadhya booking data.</p>
  </div>

  <!-- Summary stats row -->
  <div class="stats-grid">
    <div class="stat-card gold">
      <div class="stat-icon">🎟</div>
      <div class="stat-val"><?= $totalBookings ?></div>
      <div class="stat-lbl">Bookings</div>
    </div>
    <div class="stat-card green">
      <div class="stat-icon">🍽</div>
      <div class="stat-val"><?= $totalPlates ?></div>
      <div class="stat-lbl">Plates Sold</div>
    </div>
    <div class="stat-card orange">
      <div class="stat-icon">✅</div>
      <div class="stat-val"><?= $totalServed ?></div>
      <div class="stat-lbl">Served</div>
    </div>
    <div class="stat-card red">
      <div class="stat-icon">🔵</div>
      <div class="stat-val"><?= $totalUnused ?></div>
      <div class="stat-lbl">Unused</div>
    </div>
    <div class="stat-card gold">
      <div class="stat-icon">💰</div>
      <div class="stat-val">₹<?= number_format($totalRevenue,0) ?></div>
      <div class="stat-lbl">Billed</div>
    </div>
    <div class="stat-card green">
      <div class="stat-icon">✅</div>
      <div class="stat-val">₹<?= number_format($totalPaid,0) ?></div>
      <div class="stat-lbl">Collected</div>
    </div>
    <div class="stat-card red">
      <div class="stat-icon">⏳</div>
      <div class="stat-val">₹<?= number_format($totalDue,0) ?></div>
      <div class="stat-lbl">Pending</div>
    </div>
  </div>

  <!-- Filter bar -->
  <div class="card">
    <form method="GET" class="filter-bar">
      <div class="form-group">
        <label>Search</label>
        <input type="text" name="search" class="form-control" placeholder="Order / Name / Code" value="<?= h($fSearch) ?>">
      </div>
      <div class="form-group">
        <label>Agent</label>
        <select name="agent_id" class="form-control">
          <option value="">All Agents</option>
          <?php foreach ($agents as $ag): ?>
            <option value="<?= $ag['id'] ?>" <?= $fAgent==$ag['id']?'selected':'' ?>><?= h($ag['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Booking Type</label>
        <select name="type" class="form-control">
          <option value="">All</option>
          <option value="agent" <?= $fType==='agent'?'selected':'' ?>>Agent</option>
          <option value="adhoc" <?= $fType==='adhoc'?'selected':'' ?>>Ad-hoc/VIP</option>
        </select>
      </div>
      <div class="form-group">
        <label>Status</label>
        <select name="status" class="form-control">
          <option value="">All</option>
          <option value="unused"   <?= $fStatus==='unused'  ?'selected':'' ?>>Unused</option>
          <option value="partial"  <?= $fStatus==='partial' ?'selected':'' ?>>Partial</option>
          <option value="consumed" <?= $fStatus==='consumed'?'selected':'' ?>>Fully Consumed</option>
        </select>
      </div>
      <div class="form-group">
        <label>Paid To</label>
        <select name="paid_to" class="form-control">
          <option value="">All Accounts</option>
          <?php foreach ($paidToOpts as $opt): ?>
            <option value="<?= h($opt) ?>" <?= $fPaidTo===$opt?'selected':'' ?>><?= h($opt) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Date From</label>
        <input type="date" name="date_from" class="form-control" value="<?= h($fDateFrom) ?>">
      </div>
      <div class="form-group">
        <label>Date To</label>
        <input type="date" name="date_to" class="form-control" value="<?= h($fDateTo) ?>">
      </div>
      <div class="form-group" style="align-self:flex-end;display:flex;gap:6px;flex-wrap:wrap;">
        <button type="submit" class="btn btn-primary">🔍 Filter</button>
        <a href="<?= APP_URL ?>/admin/reports.php" class="btn btn-secondary">Clear</a>
        <a href="?<?= h(http_build_query(array_filter(['search'=>$fSearch,'agent_id'=>$fAgent,'type'=>$fType,'status'=>$fStatus,'paid_to'=>$fPaidTo,'date_from'=>$fDateFrom,'date_to'=>$fDateTo]))) ?>&export=csv"
           class="btn btn-success">📥 CSV</a>
      </div>
    </form>
  </div>

  <!-- Collections by account ("Paid to") -->
  <div class="card">
    <div class="card-header"><span class="card-icon">🏦</span><h3>Collections by Account (Paid To)</h3></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Account</th><th>Bookings</th><th>Amount Collected</th></tr></thead>
        <tbody>
          <?php foreach ($collByAccount as $acct => $info): ?>
          <tr>
            <td><strong><?= h($acct) ?></strong></td>
            <td><?= (int)$info['count'] ?></td>
            <td>₹<?= number_format($info['collected'],0) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($collByAccount)): ?>
          <tr><td colspan="3" style="text-align:center;padding:20px;">No collections yet.</td></tr>
          <?php endif; ?>
        </tbody>
        <?php if(!empty($collByAccount)): ?>
        <tfoot>
          <tr style="background:rgba(200,150,12,.08);font-weight:700;">
            <td style="padding:10px 14px;text-align:right;color:var(--kasavu-deep);">Total Collected:</td>
            <td></td>
            <td style="padding:10px 14px;">₹<?= number_format($totalPaid,0) ?></td>
          </tr>
        </tfoot>
        <?php endif; ?>
      </table>
    </div>
  </div>

  <!-- Agent-wise summary -->
  <div class="card">
    <div class="card-header"><span class="card-icon">👥</span><h3>Agent-wise Summary</h3></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Agent</th><th>Bookings</th><th>Plates Sold</th><th>Plates Served</th><th>Revenue Collected</th></tr></thead>
        <tbody>
          <?php foreach ($agentSummary as $ag): ?>
          <tr>
            <td><strong><?= h($ag['name']) ?></strong></td>
            <td><?= (int)$ag['bookings'] ?></td>
            <td><?= (int)$ag['plates'] ?></td>
            <td><?= (int)$ag['served'] ?></td>
            <td>₹<?= number_format((float)$ag['revenue'],0) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($agentSummary)): ?>
          <tr><td colspan="5" style="text-align:center;padding:20px;">No agent data.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Detailed table -->
  <div class="card">
    <div class="card-header">
      <span class="card-icon">📄</span>
      <h3>Detailed Bookings</h3>
      <span class="badge badge-gold" style="margin-left:auto;"><?= $totalBookings ?> record(s)</span>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th><th>Order ID</th><th>Block/Unit</th><th>Owner</th><th>Contact</th>
            <th>Plates (A/K)</th><th>Served</th><th>Remaining</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Paid To</th>
            <th>Agent</th><th>Type</th><th>Secret Code</th><th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($allRows as $i => $r):
            $c  = (int)$r['consumed'];
            $h2 = (int)$r['headcount'];
            $rem = $h2 - $c;
            $cls = $c >= $h2 ? 'badge-danger' : ($c > 0 ? 'badge-warning' : 'badge-success');
            $due = (float)$r['total_amount'] - (float)$r['paid_amount'];
          ?>
          <tr>
            <td style="color:var(--text-mid);"><?= $i+1 ?></td>
            <td><strong><?= h($r['order_id']) ?></strong></td>
            <td><?= h($r['house_name']) ?></td>
            <td><?= h($r['owner_name']) ?></td>
            <td><?= h($r['contact_number']) ?></td>
            <td><?= (int)$r['plates_adults'] ?>/<?= (int)$r['plates_kids'] ?></td>
            <td><?= $c ?></td>
            <td><span class="badge <?= $cls ?>"><?= $rem ?></span></td>
            <td>₹<?= number_format((float)$r['total_amount'],0) ?></td>
            <td>₹<?= number_format((float)$r['paid_amount'],0) ?></td>
            <td><span class="badge <?= $due > 0 ? 'badge-danger':'badge-success' ?>">₹<?= number_format($due,0) ?></span></td>
            <td><?= $r['paid_to'] ? h($r['paid_to']) : '<span style="color:var(--text-mid);">—</span>' ?></td>
            <td><?= h($r['agent_name']??'Admin') ?></td>
            <td><span class="badge <?= $r['booking_type']==='adhoc'?'badge-gold':'badge-info' ?>"><?= h($r['booking_type']) ?></span></td>
            <td><code style="font-weight:700;letter-spacing:.1em;font-size:.82rem;"><?= h($r['secret_code']) ?></code></td>
            <td style="white-space:nowrap;"><?= date('d M, H:i', strtotime($r['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($allRows)): ?>
          <tr><td colspan="16" style="text-align:center;padding:24px;">No records match the selected filters.</td></tr>
          <?php endif; ?>
        </tbody>
        <?php if($totalBookings > 0): ?>
        <tfoot>
          <tr style="background:rgba(200,150,12,.08);font-weight:700;">
            <td colspan="6" style="padding:10px 14px;text-align:right;color:var(--kasavu-deep);">Totals:</td>
            <td style="padding:10px 14px;"><?= $totalServed ?></td>
            <td style="padding:10px 14px;"><?= $totalUnused ?></td>
            <td style="padding:10px 14px;">₹<?= number_format($totalRevenue,0) ?></td>
            <td style="padding:10px 14px;">₹<?= number_format($totalPaid,0) ?></td>
            <td style="padding:10px 14px;">₹<?= number_format($totalDue,0) ?></td>
            <td colspan="5"></td>
          </tr>
        </tfoot>
        <?php endif; ?>
      </table>
    </div>
  </div>

  <!-- Consumption detail (check-in log) -->
  <div class="card">
    <div class="card-header"><span class="card-icon">📅</span><h3>Check-in Log (Recent 100)</h3></div>
    <?php
      $checkIns = $db->query("
          SELECT c.relation, c.served_at, b.order_id, b.house_name, b.owner_name,
                 v.name AS validator_name
          FROM consumption c
          JOIN bookings b ON b.id = c.booking_id
          LEFT JOIN validators v ON v.id = c.validator_id
          ORDER BY c.served_at DESC
          LIMIT 100
      ")->fetchAll();
    ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Time</th><th>Order ID</th><th>Block/Unit</th><th>Relation</th><th>Served By</th></tr></thead>
        <tbody>
          <?php foreach ($checkIns as $ci): ?>
          <tr>
            <td style="white-space:nowrap;"><?= date('d M, H:i:s', strtotime($ci['served_at'])) ?></td>
            <td><strong><?= h($ci['order_id']) ?></strong></td>
            <td><?= h($ci['house_name']) ?></td>
            <td><span class="badge badge-info"><?= h($ci['relation']) ?></span></td>
            <td><?= h($ci['validator_name'] ?? 'Admin') ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($checkIns)): ?>
          <tr><td colspan="5" style="text-align:center;padding:20px;">No check-ins yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
