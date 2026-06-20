<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isValidator() && !isAdmin()) {
    header('Location: ' . APP_URL . '/login.php?role=validator');
    exit;
}

$eventName     = getSetting('event_name', 'Onam Sadhya');
$validatorName = $_SESSION['validator_name'] ?? ($_SESSION['admin_user'] ?? 'Admin');
$pageTitle     = 'Dining Hall Validator';
$activeNav     = 'validator';
$showNav       = isAdmin();
include __DIR__ . '/../includes/header.php';
?>

<div class="page-wrap">
  <div class="page-hero">
    <div class="hero-emoji">🍛</div>
    <h2>Dining Hall Validator</h2>
    <p><?= h($eventName) ?></p>
    <p style="margin-top:4px;">Validator: <strong><?= h($validatorName) ?></strong></p>
    <?php if (isValidator() && !isAdmin()): ?>
    <div style="margin-top:12px;">
      <a href="<?= APP_URL ?>/logout.php" class="btn btn-danger btn-sm">🚪 Logout</a>
    </div>
    <?php elseif (isAdmin()): ?>
    <div style="margin-top:12px;">
      <a href="<?= APP_URL ?>/admin/index.php" class="btn btn-outline btn-sm">← Back to Admin</a>
    </div>
    <?php endif; ?>
  </div>

  <div style="max-width:520px;margin:0 auto;">

    <!-- Scanner / Code input -->
    <div class="card" id="scan-card">
      <div class="card-header">
        <span class="card-icon">📷</span>
        <h3>Scan or Enter Code</h3>
      </div>

      <div class="tabs" id="input-tabs">
        <div class="tab active" data-tab="scan">📷 Scan QR</div>
        <div class="tab"       data-tab="manual">⌨️ Manual Entry</div>
      </div>

      <!-- QR Scanner -->
      <div class="tab-panel active" id="panel-scan">
        <div id="qr-reader"></div>
        <button class="btn btn-outline btn-block" style="margin-top:12px;" id="start-scan-btn" onclick="startScanner()">▶ Start Camera</button>
        <button class="btn btn-secondary btn-block" style="margin-top:8px;display:none;" id="stop-scan-btn" onclick="stopScanner()">⏹ Stop Camera</button>
      </div>

      <!-- Manual -->
      <div class="tab-panel" id="panel-manual">
        <form id="manual-form" onsubmit="lookupCode(event)">
          <div class="form-group">
            <label>Secret Code or Order ID</label>
            <input type="text" id="code-input" class="form-control"
              placeholder="e.g. AB3XY7" maxlength="20"
              autocomplete="off" autocapitalize="characters"
              style="font-size:1.5rem;letter-spacing:.2em;text-align:center;text-transform:uppercase;">
          </div>
          <button type="submit" class="btn btn-primary btn-block">🔍 Look Up</button>
        </form>
      </div>
    </div>

    <!-- Booking result -->
    <div id="result-area" style="display:none;"></div>

    <!-- Recent validations -->
    <div class="card" id="recent-card">
      <div class="card-header">
        <span class="card-icon">📜</span>
        <h3>Today's Log</h3>
        <span id="today-count" class="badge badge-gold" style="margin-left:auto;">0</span>
      </div>
      <div id="recent-list"><p style="color:var(--text-mid);font-size:.88rem;">No validations yet.</p></div>
    </div>

  </div>
</div>

<!-- html5-qrcode library -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
const APP_URL = '<?= APP_URL ?>';
let html5QrcodeScanner = null;
let recentLog = [];

// ── Tab switching ──────────────────────────────────────────────────────
document.querySelectorAll('#input-tabs .tab').forEach(tab => {
  tab.addEventListener('click', function(){
    document.querySelectorAll('#input-tabs .tab, #panel-scan, #panel-manual').forEach(el => el.classList.remove('active'));
    this.classList.add('active');
    document.getElementById('panel-' + this.dataset.tab).classList.add('active');
    if(this.dataset.tab !== 'scan') stopScanner();
  });
});

// ── QR Scanner ─────────────────────────────────────────────────────────
function startScanner(){
  document.getElementById('start-scan-btn').style.display = 'none';
  document.getElementById('stop-scan-btn').style.display  = 'block';

  html5QrcodeScanner = new Html5Qrcode("qr-reader");
  html5QrcodeScanner.start(
    { facingMode: "environment" },
    { fps: 12, qrbox: { width:220, height:220 } },
    code => {
      stopScanner();
      fetchBooking(code.trim().toUpperCase());
    },
    err => {}
  ).catch(err => showFlash('Camera error: ' + err, 'danger'));
}

function stopScanner(){
  if(html5QrcodeScanner && html5QrcodeScanner.isScanning){
    html5QrcodeScanner.stop().catch(()=>{});
  }
  document.getElementById('start-scan-btn').style.display = 'block';
  document.getElementById('stop-scan-btn').style.display  = 'none';
}

// ── Manual lookup ──────────────────────────────────────────────────────
function lookupCode(e){
  e.preventDefault();
  const code = document.getElementById('code-input').value.trim().toUpperCase();
  if(!code) return;
  fetchBooking(code);
}

// ── Fetch booking ──────────────────────────────────────────────────────
async function fetchBooking(code){
  showResult('<div style="text-align:center;padding:24px;">⏳ Looking up…</div>');
  try {
    const res  = await fetch(`${APP_URL}/api/validate.php?code=${encodeURIComponent(code)}`);
    const data = await res.json();
    if(!data.success){ showResult(renderError(data.message || 'Not found.')); return; }
    showResult(renderBooking(data.booking));
  } catch(err){
    showResult(renderError('Network error. Please try again.'));
  }
}

// ── Render booking card ────────────────────────────────────────────────
function renderBooking(b){
  const remaining = parseInt(b.remaining);
  const consumed  = parseInt(b.consumed);
  const total     = parseInt(b.headcount);
  const pct       = total > 0 ? Math.round((consumed/total)*100) : 0;
  const barClass  = remaining === 0 ? 'danger' : '';

  let dots = '';
  for(let i=0;i<total;i++){
    dots += `<div class="plate-dot ${i<consumed?'used':'free'}" title="${i<consumed?'Served':'Available'}">
      ${i<consumed?'🍽':'🟢'}
    </div>`;
  }

  let historyHtml = '';
  if(b.history && b.history.length > 0){
    historyHtml = '<div style="margin-top:12px;font-size:.82rem;color:var(--text-mid);">'
      + b.history.map((h,i) => `<div style="padding:4px 0;border-bottom:1px dashed rgba(200,150,12,.15);">
          <strong>#${i+1}</strong> ${escHtml(h.relation)} — ${new Date(h.served_at.replace(' ','T')).toLocaleTimeString()}
        </div>`).join('') + '</div>';
  }

  const limitWarning = remaining === 0 ? `
    <div class="limit-alert">
      ⛔ <strong>LIMIT REACHED</strong> — All ${total} plate(s) have been served.<br>
      ${b.history ? b.history.map(h => `&bull; ${escHtml(h.relation)} at ${new Date(h.served_at.replace(' ','T')).toLocaleTimeString()}`).join('<br>') : ''}
    </div>` : '';

  const consumeForm = remaining > 0 ? `
    <div style="margin-top:16px;padding-top:16px;border-top:2px dashed rgba(200,150,12,.2);">
      <h4>🍛 Mark Plate as Served</h4>
      <div class="form-group" style="margin-top:12px;">
        <label>Relation to House Owner <span class="req">*</span></label>
        <select id="relation-input" class="form-control">
          <option value="">— Select Relation —</option>
          <option>Self</option>
          <option>Spouse</option>
          <option>Son</option>
          <option>Daughter</option>
          <option>Father</option>
          <option>Mother</option>
          <option>Brother</option>
          <option>Sister</option>
          <option>Guest</option>
          <option>Neighbour</option>
          <option>Relative</option>
          <option>Other</option>
        </select>
      </div>
      <div id="other-relation-wrap" style="display:none;" class="form-group">
        <label>Specify Relation</label>
        <input type="text" id="other-relation" class="form-control" placeholder="e.g. Uncle">
      </div>
      <button class="btn btn-success btn-block" onclick="servePlate(${b.id})">
        ✅ Confirm & Serve Plate
      </button>
    </div>` : '';

  return `<div class="card validation-result ${remaining===0?'danger':'success'}">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;">
      <div>
        <h4>🏠 ${escHtml(b.house_name)}</h4>
        <p style="font-size:.88rem;margin-top:2px;">${escHtml(b.owner_name)} &bull; ${escHtml(b.contact_number)}</p>
        <p style="font-size:.78rem;color:var(--text-mid);">Order: ${escHtml(b.order_id)}</p>
      </div>
      <div style="text-align:right;">
        <div style="font-size:2rem;font-weight:700;color:var(--kasavu-deep);">${remaining}</div>
        <div style="font-size:.72rem;color:var(--text-mid);text-transform:uppercase;">Remaining</div>
      </div>
    </div>

    <div style="margin:12px 0;">
      <div style="display:flex;justify-content:space-between;font-size:.82rem;color:var(--text-mid);margin-bottom:4px;">
        <span>${consumed} served</span><span>${remaining} left of ${total}</span>
      </div>
      <div class="progress"><div class="progress-bar ${barClass}" style="width:${pct}%"></div></div>
    </div>

    <div class="plate-counter">${dots}</div>

    ${limitWarning}
    ${historyHtml}
    ${consumeForm}

    <button class="btn btn-secondary btn-block" style="margin-top:12px;" onclick="clearResult()">🔄 Scan Next</button>
  </div>`;
}

function renderError(msg){
  return `<div class="card validation-result danger">
    <h4>❌ ${escHtml(msg)}</h4>
    <button class="btn btn-secondary btn-block" style="margin-top:12px;" onclick="clearResult()">🔄 Try Again</button>
  </div>`;
}

// ── Serve plate ────────────────────────────────────────────────────────
let _currentBookingId = null;

function showResult(html){
  const area = document.getElementById('result-area');
  area.innerHTML = html;
  area.style.display = 'block';
  area.scrollIntoView({behavior:'smooth', block:'start'});

  // Cache booking id from hidden data attr
  const el = area.querySelector('[data-bid]');
  if(el) _currentBookingId = parseInt(el.dataset.bid);
}

// Override to capture bid
function renderBookingCapture(b){
  _currentBookingId = b.id;
  const html = renderBooking(b);
  return html;
}

async function servePlate(bid){
  const sel = document.getElementById('relation-input');
  let relation = sel ? sel.value : '';
  if(relation === 'Other'){
    relation = document.getElementById('other-relation').value.trim();
  }
  if(!relation){ showFlash('Please select a relation.','warning'); return; }

  const fd = new FormData();
  fd.append('booking_id', bid);
  fd.append('relation',   relation);

  try {
    const res  = await fetch(`${APP_URL}/api/consume.php`, { method:'POST', body:fd });
    const data = await res.json();

    if(data.success){
      showFlash(`✅ Plate served for: ${relation}`, 'success');
      addToLog(bid, relation);
      // Re-lookup to refresh UI
      const codeEl = document.getElementById('code-input');
      // Re-fetch by order from result
      const orderEl = document.querySelector('.validation-result p');
      fetchBooking(bid.toString()); // use booking ID — validate.php handles it
    } else if(data.message === 'limit_reached'){
      showResult(renderLimitReached(data));
    } else {
      showFlash(data.message || 'Error serving plate.', 'danger');
    }
  } catch(err){
    showFlash('Network error.', 'danger');
  }
}

function renderLimitReached(data){
  const histList = (data.history||[]).map((h,i)=>
    `<li><strong>#${i+1}</strong> ${escHtml(h.relation)} — ${new Date(h.served_at.replace(' ','T')).toLocaleTimeString()}</li>`
  ).join('');
  return `<div class="card validation-result danger">
    <div class="limit-alert" style="font-size:1rem;">
      ⛔ <strong>LIMIT REACHED</strong><br>
      All ${data.headcount} plate(s) for <strong>${escHtml(data.house_name)}</strong> have been served.
    </div>
    <div style="margin-top:12px;">
      <strong>Previous attendees:</strong>
      <ul style="margin-top:6px;padding-left:18px;font-size:.88rem;">${histList}</ul>
    </div>
    <button class="btn btn-secondary btn-block" style="margin-top:14px;" onclick="clearResult()">🔄 Scan Next</button>
  </div>`;
}

function clearResult(){
  document.getElementById('result-area').style.display = 'none';
  document.getElementById('result-area').innerHTML = '';
  document.getElementById('code-input').value = '';
}

function addToLog(bid, relation){
  const now = new Date().toLocaleTimeString();
  recentLog.unshift({ bid, relation, time: now });
  if(recentLog.length > 20) recentLog.pop();
  renderLog();
}

function renderLog(){
  document.getElementById('today-count').textContent = recentLog.length;
  if(recentLog.length === 0) return;
  document.getElementById('recent-list').innerHTML = recentLog.map(l =>
    `<div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px dashed rgba(200,150,12,.12);font-size:.85rem;">
      <span>Booking #${l.bid} — ${escHtml(l.relation)}</span>
      <span style="color:var(--text-mid);">${l.time}</span>
    </div>`
  ).join('');
}

// Show "Other" text field
document.addEventListener('change', function(e){
  if(e.target.id === 'relation-input'){
    document.getElementById('other-relation-wrap').style.display =
      e.target.value === 'Other' ? 'block' : 'none';
  }
});

function escHtml(s){
  if(!s) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Intercept showResult to capture bookingId
const _origShow = showResult;
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
