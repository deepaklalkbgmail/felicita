<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$db = getDB();

// Summary stats
$stats = $db->query("
  SELECT
    (SELECT COUNT(*) FROM bookings)                                         AS total_bookings,
    (SELECT SUM(headcount) FROM bookings)                                   AS total_plates,
    (SELECT SUM(total_amount) FROM bookings)                                AS total_revenue,
    (SELECT SUM(paid_amount) FROM bookings)                                 AS total_paid,
    (SELECT COUNT(*) FROM consumption)                                      AS plates_served,
    (SELECT COUNT(*) FROM bookings WHERE booking_type='adhoc')              AS adhoc_bookings,
    (SELECT COUNT(*) FROM agents WHERE is_active=1)                         AS active_agents
")->fetch();

$unused     = (int)$stats['total_plates'] - (int)$stats['plates_served'];
$totalDue   = (float)$stats['total_revenue'] - (float)$stats['total_paid'];

// Recent bookings
$recent = $db->query("
  SELECT b.order_id, b.house_name, b.owner_name, b.headcount,
         b.plates_kids, b.plates_adults, b.total_amount, b.paid_amount,
         b.secret_code, b.created_at, a.name AS agent_name,
         (SELECT COUNT(*) FROM consumption c WHERE c.booking_id=b.id) AS consumed
  FROM   bookings b
  LEFT JOIN agents a ON a.id = b.agent_id
  ORDER BY b.created_at DESC
  LIMIT 10
")->fetchAll();

$pageTitle = 'Admin Dashboard';
$activeNav = 'dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-wrap">
  <div class="page-hero">
    <div class="hero-emoji">🪷</div>
    <h2>Admin Dashboard</h2>
    <p><?= h(getSetting('event_name','Aaravam 2026')) ?> &bull; <?= h(getSetting('event_date','')) ?></p>
  </div>

  <!-- Stats -->
  <div class="stats-grid">
    <div class="stat-card gold">
      <div class="stat-icon">🎟</div>
      <div class="stat-val"><?= number_format((int)$stats['total_bookings']) ?></div>
      <div class="stat-lbl">Total Bookings</div>
    </div>
    <div class="stat-card green">
      <div class="stat-icon">🍽</div>
      <div class="stat-val"><?= number_format((int)$stats['total_plates']) ?></div>
      <div class="stat-lbl">Total Plates Sold</div>
    </div>
    <div class="stat-card orange">
      <div class="stat-icon">✅</div>
      <div class="stat-val"><?= number_format((int)$stats['plates_served']) ?></div>
      <div class="stat-lbl">Plates Served</div>
    </div>
    <div class="stat-card red">
      <div class="stat-icon">🔵</div>
      <div class="stat-val"><?= number_format($unused) ?></div>
      <div class="stat-lbl">Unused Plates</div>
    </div>
    <div class="stat-card gold">
      <div class="stat-icon">💰</div>
      <div class="stat-val">₹<?= number_format((float)$stats['total_revenue'], 0) ?></div>
      <div class="stat-lbl">Total Billed</div>
    </div>
    <div class="stat-card green">
      <div class="stat-icon">✅</div>
      <div class="stat-val">₹<?= number_format((float)$stats['total_paid'], 0) ?></div>
      <div class="stat-lbl">Amount Collected</div>
    </div>
    <div class="stat-card red">
      <div class="stat-icon">⏳</div>
      <div class="stat-val">₹<?= number_format($totalDue, 0) ?></div>
      <div class="stat-lbl">Payment Pending</div>
    </div>
    <div class="stat-card blue">
      <div class="stat-icon">👤</div>
      <div class="stat-val"><?= number_format((int)$stats['active_agents']) ?></div>
      <div class="stat-lbl">Active Agents</div>
    </div>
  </div>

  <!-- Progress bar: consumption -->
  <?php
    $totalPlates  = max(1, (int)$stats['total_plates']);
    $servedPlates = (int)$stats['plates_served'];
    $pct          = round(($servedPlates / $totalPlates) * 100);
  ?>
  <div class="card">
    <div class="card-header"><span class="card-icon">📊</span><h3>Consumption Progress</h3></div>
    <div style="display:flex;justify-content:space-between;font-size:.85rem;color:var(--text-mid);margin-bottom:6px;">
      <span><?= $servedPlates ?> served</span>
      <span><?= $unused ?> remaining (<?= 100 - $pct ?>%)</span>
    </div>
    <div class="progress" style="height:18px;">
      <div class="progress-bar" style="width:<?= $pct ?>%"></div>
    </div>
    <p style="margin-top:8px;font-size:.82rem;text-align:center;"><?= $pct ?>% of all sold plates have been served</p>
  </div>

  <!-- Quick links -->
  <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:24px;">
    <a href="<?= APP_URL ?>/agent/index.php"   class="btn btn-outline"><span>🏠</span> New Booking</a>
    <a href="<?= APP_URL ?>/admin/tickets.php" class="btn btn-outline"><span>⭐</span> VIP / Ad-hoc Ticket</a>
    <a href="<?= APP_URL ?>/admin/reports.php" class="btn btn-outline"><span>📋</span> Full Report</a>
    <a href="<?= APP_URL ?>/validator/index.php" class="btn btn-outline"><span>🍛</span> Validator View</a>
  </div>

  <!-- Recent bookings -->
  <div class="card">
    <div class="card-header">
      <span class="card-icon">🕐</span>
      <h3>Recent Bookings</h3>
      <a href="<?= APP_URL ?>/admin/tickets.php" class="btn btn-sm btn-outline" style="margin-left:auto;">View All</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Block / Unit</th>
            <th>Owner</th>
            <th>Plates (A/K)</th>
            <th>Served</th>
            <th>Amount</th>
            <th>Paid</th>
            <th>Balance</th>
            <th>Agent</th>
            <th>Secret Code</th>
            <th>Time</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recent as $r):
            $due = (float)$r['total_amount'] - (float)$r['paid_amount'];
          ?>
          <tr>
            <td><strong><?= h($r['order_id']) ?></strong></td>
            <td><?= h($r['house_name']) ?></td>
            <td><?= h($r['owner_name']) ?></td>
            <td><?= (int)$r['plates_adults'] ?>/<?= (int)$r['plates_kids'] ?></td>
            <td>
              <?php
                $c = (int)$r['consumed'];
                $h = (int)$r['headcount'];
                $cls = $c >= $h ? 'badge-danger' : ($c > 0 ? 'badge-warning' : 'badge-success');
              ?>
              <span class="badge <?= $cls ?>"><?= $c ?>/<?= $h ?></span>
            </td>
            <td>₹<?= number_format((float)$r['total_amount'], 0) ?></td>
            <td>₹<?= number_format((float)$r['paid_amount'], 0) ?></td>
            <td><span class="badge <?= $due > 0 ? 'badge-danger' : 'badge-success' ?>">₹<?= number_format($due, 0) ?></span></td>
            <td><?= h($r['agent_name'] ?? 'Admin') ?></td>
            <td><code style="font-size:.85rem;font-weight:600;letter-spacing:.1em;"><?= h($r['secret_code']) ?></code></td>
            <td style="white-space:nowrap;"><?= date('d M, H:i', strtotime($r['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($recent)): ?>
          <tr><td colspan="11" style="text-align:center;padding:24px;color:var(--text-mid);">No bookings yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
