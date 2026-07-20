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
        <div class="tab"       data-tab="manual">⌨️ Manual</div>
        <div class="tab"       data-tab="wing">🏢 Wing / Door</div>
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
              placeholder="e.g. 6107482" maxlength="20"
              autocomplete="off" autocapitalize="characters"
              style="font-size:1.5rem;letter-spacing:.15em;text-align:center;text-transform:uppercase;">
          </div>
          <button type="submit" class="btn btn-primary btn-block">🔍 Look Up</button>
        </form>
      </div>

      <!-- Wing / Door -->
      <div class="tab-panel" id="panel-wing">
        <form id="wing-form" onsubmit="lookupWing(event)">
          <div class="form-row">
            <div class="form-group">
              <label>Wing Number</label>
              <select id="wing-select" class="form-control" required>
                <option value="">— Select Wing —</option>
                <?php foreach (getWingNumbers() as $w): ?>
                  <option value="<?= $w ?>">WING <?= $w ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Door Number</label>
              <select id="wing-unit-select" class="form-control" required disabled>
                <option value="">— Select Wing first —</option>
              </select>
            </div>
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
let recentLog   = [];
let _currentCode = null;   // the secret code / order id currently shown
let _currentWing = null;   // { wing, unit } when looked up by wing/door

const RELATION_OPTIONS = ['Self','Spouse','Son','Daughter','Father','Mother',
  'Brother','Sister','Guest','Neighbour','Relative','Other'];

const _wingUnitCache = {};

// ── Tab switching ──────────────────────────────────────────────────────
document.querySelectorAll('#input-tabs .tab').forEach(tab => {
  tab.addEventListener('click', function(){
    document.querySelectorAll('#input-tabs .tab, #panel-scan, #panel-manual, #panel-wing').forEach(el => el.classList.remove('active'));
    this.classList.add('active');
    document.getElementById('panel-' + this.dataset.tab).classList.add('active');
    if(this.dataset.tab !== 'scan') stopScanner();
  });
});

// ── Wing / Door cascade + lookup ───────────────────────────────────────
document.getElementById('wing-select').addEventListener('change', async function(){
  const unitSel = document.getElementById('wing-unit-select');
  if(!this.value){ unitSel.disabled=true; unitSel.innerHTML='<option value="">— Select Wing first —</option>'; return; }
  unitSel.disabled = true; unitSel.innerHTML = '<option value="">Loading…</option>';
  let units = _wingUnitCache[this.value];
  if(!units){
    try {
      const res = await fetch(`${APP_URL}/api/residents.php?wing=${this.value}`);
      const data = await res.json();
      units = data.success ? data.units : [];
      _wingUnitCache[this.value] = units;
    } catch(e){ units = []; }
  }
  let html = '<option value="">— Select Door —</option>';
  units.forEach(u => {
    const label = u.unit + (u.name ? ' — ' + u.name : '') + (u.booked ? ' ✓' : '');
    html += `<option value="${escHtml(u.unit)}">${escHtml(label)}</option>`;
  });
  unitSel.innerHTML = html;
  unitSel.disabled = false;
});

function lookupWing(e){
  e.preventDefault();
  const wing = document.getElementById('wing-select').value;
  const unit = document.getElementById('wing-unit-select').value;
  if(!wing || !unit){ showFlash('Select Wing and Door number.','warning'); return; }
  fetchByWingUnit(wing, unit);
}

async function fetchByWingUnit(wing, unit){
  _currentCode = null;
  _currentWing = { wing, unit };
  showResult('<div class="card"><div style="text-align:center;padding:24px;">⏳ Looking up…</div></div>');
  try {
    const res  = await fetch(`${APP_URL}/api/validate.php?wing=${wing}&unit=${encodeURIComponent(unit)}`);
    const data = await res.json();
    if(!data.success){ showResult(renderError(data.message || 'No booking found.')); return; }
    _currentCode = data.booking.secret_code;   // reuse for post-serve refresh
    showResult(renderBooking(data.booking));
  } catch(err){ showResult(renderError('Network error. Please try again.')); }
}

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
  _currentCode = code;
  showResult('<div class="card"><div style="text-align:center;padding:24px;">⏳ Looking up…</div></div>');
  try {
    const res  = await fetch(`${APP_URL}/api/validate.php?code=${encodeURIComponent(code)}`);
    const data = await res.json();
    if(!data.success){ showResult(renderError(data.message || 'Invalid code. No booking found.')); return; }
    showResult(renderBooking(data.booking));
  } catch(err){
    showResult(renderError('Network error. Please try again.'));
  }
}

// ── Build a single relation row ─────────────────────────────────────────
function relationRowHtml(idx){
  const opts = RELATION_OPTIONS.map(o => `<option value="${o}">${o}</option>`).join('');
  return `<div class="relation-row" data-idx="${idx}" style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
    <span style="font-weight:700;color:var(--kasavu-deep);min-width:22px;">${idx+1}.</span>
    <select class="form-control relation-select" style="flex:1;" onchange="onRelationChange(this)">
      <option value="">— Select Relation —</option>
      ${opts}
    </select>
    <input type="text" class="form-control relation-other" placeholder="Specify" style="flex:1;display:none;">
    <button type="button" class="btn btn-sm btn-secondary remove-row" onclick="removeRelationRow(this)" title="Remove">✕</button>
  </div>`;
}

function onRelationChange(sel){
  const other = sel.parentElement.querySelector('.relation-other');
  other.style.display = (sel.value === 'Other') ? 'block' : 'none';
}

function addRelationRow(){
  const list = document.getElementById('relation-list');
  if(!list) return;
  const remaining = parseInt(list.dataset.remaining);
  const current   = list.querySelectorAll('.relation-row').length;
  if(current >= remaining){
    showFlash(`Only ${remaining} plate(s) remaining for this booking.`, 'warning');
    return;
  }
  list.insertAdjacentHTML('beforeend', relationRowHtml(current));
  renumberRows();
}

function removeRelationRow(btn){
  const list = document.getElementById('relation-list');
  if(list.querySelectorAll('.relation-row').length <= 1){
    showFlash('At least one person is required.', 'warning');
    return;
  }
  btn.closest('.relation-row').remove();
  renumberRows();
}

function renumberRows(){
  document.querySelectorAll('#relation-list .relation-row').forEach((row,i)=>{
    row.dataset.idx = i;
    row.querySelector('span').textContent = (i+1) + '.';
  });
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
    historyHtml = '<div style="margin-top:12px;font-size:.82rem;color:var(--text-mid);"><strong>Already served:</strong>'
      + b.history.map((h,i) => `<div style="padding:4px 0;border-bottom:1px dashed rgba(200,150,12,.15);">
          <strong>#${i+1}</strong> ${escHtml(h.relation)} — ${fmtTime(h.served_at)}
        </div>`).join('') + '</div>';
  }

  const limitWarning = remaining === 0 ? `
    <div class="limit-alert">
      ⛔ <strong>LIMIT REACHED</strong> — All ${total} plate(s) have been served.<br>
      ${b.history ? b.history.map(h => `&bull; ${escHtml(h.relation)} at ${fmtTime(h.served_at)}`).join('<br>') : ''}
    </div>` : '';

  // Multi-plate consume form
  const consumeForm = remaining > 0 ? `
    <div style="margin-top:16px;padding-top:16px;border-top:2px dashed rgba(200,150,12,.2);">
      <h4>🍛 Serve Plate(s)</h4>
      <p style="font-size:.82rem;color:var(--text-mid);margin:6px 0 12px;">
        Add one row per person being served. Up to <strong>${remaining}</strong> can be served now.
      </p>
      <div id="relation-list" data-remaining="${remaining}" data-bid="${b.id}">
        ${relationRowHtml(0)}
      </div>
      <button type="button" class="btn btn-outline btn-sm" style="margin-top:4px;" onclick="addRelationRow()">
        ➕ Add another person
      </button>
      <button type="button" class="btn btn-success btn-block" style="margin-top:14px;" onclick="servePlates(${b.id})">
        ✅ Confirm & Serve
      </button>
    </div>` : '';

  const paid = parseFloat(b.paid_amount||0);
  const totalDue = parseFloat(b.total_amount||0);
  const due  = parseFloat(b.remaining_due != null ? b.remaining_due : (totalDue - paid));
  const platesLine = `${b.plates_adults||0} adult(s), ${b.plates_kids||0} kid(s)`;
  const dueBadge = due > 0
    ? `<span class="badge badge-danger">Balance ₹${due.toLocaleString('en-IN')}</span>`
    : `<span class="badge badge-success">Fully Paid</span>`;

  const payHtml = `
    <div style="margin-top:10px;padding:8px 10px;background:rgba(200,150,12,.06);border-radius:8px;font-size:.82rem;">
      <div style="display:flex;justify-content:space-between;">
        <span>Plates</span><strong>${platesLine}</strong></div>
      <div style="display:flex;justify-content:space-between;margin-top:2px;">
        <span>Amount</span><strong>₹${totalDue.toLocaleString('en-IN')}</strong></div>
      <div style="display:flex;justify-content:space-between;margin-top:2px;">
        <span>Paid</span><strong>₹${paid.toLocaleString('en-IN')}</strong></div>
      <div style="display:flex;justify-content:space-between;margin-top:4px;align-items:center;">
        <span>Payment</span>${dueBadge}</div>
    </div>`;

  return `<div class="card validation-result ${remaining===0?'danger':'success'}">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;">
      <div>
        <h4>🏢 ${escHtml(b.house_name)}</h4>
        <p style="font-size:.88rem;margin-top:2px;">${escHtml(b.owner_name)} &bull; ${escHtml(b.contact_number)}</p>
        <p style="font-size:.78rem;color:var(--text-mid);">Order: ${escHtml(b.order_id)} &bull; Code: ${escHtml(b.secret_code)}</p>
      </div>
      <div style="text-align:right;">
        <div style="font-size:2rem;font-weight:700;color:var(--kasavu-deep);">${remaining}</div>
        <div style="font-size:.72rem;color:var(--text-mid);text-transform:uppercase;">Remaining</div>
      </div>
    </div>
    ${payHtml}

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

function showResult(html){
  const area = document.getElementById('result-area');
  area.innerHTML = html;
  area.style.display = 'block';
  area.scrollIntoView({behavior:'smooth', block:'start'});
}

// ── Serve one or more plates ────────────────────────────────────────────
async function servePlates(bid){
  const rows = document.querySelectorAll('#relation-list .relation-row');
  const relations = [];
  for(const row of rows){
    const sel = row.querySelector('.relation-select');
    let val = sel.value;
    if(val === 'Other'){
      val = row.querySelector('.relation-other').value.trim();
    }
    if(!val){ showFlash('Please select a relation for every person.', 'warning'); return; }
    relations.push(val);
  }
  if(relations.length === 0){ showFlash('Add at least one person.', 'warning'); return; }

  const fd = new FormData();
  fd.append('booking_id', bid);
  relations.forEach(r => fd.append('relations[]', r));

  try {
    const res  = await fetch(`${APP_URL}/api/consume.php`, { method:'POST', body:fd });
    const data = await res.json();

    if(data.success){
      showFlash(`✅ ${data.served} plate(s) served: ${relations.join(', ')}`, 'success');
      relations.forEach(r => addToLog(bid, r));
      // Re-fetch using the ORIGINAL code (fixes the "Invalid" bug)
      if(_currentCode){ fetchBooking(_currentCode); }
    } else if(data.message === 'limit_reached'){
      showResult(renderLimitReached(data));
    } else {
      showFlash(data.message || 'Error serving plate(s).', 'danger');
    }
  } catch(err){
    showFlash('Network error.', 'danger');
  }
}

function renderLimitReached(data){
  const histList = (data.history||[]).map((h,i)=>
    `<li><strong>#${i+1}</strong> ${escHtml(h.relation)} — ${fmtTime(h.served_at)}</li>`
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
  _currentCode = null;
  document.getElementById('result-area').style.display = 'none';
  document.getElementById('result-area').innerHTML = '';
  document.getElementById('code-input').value = '';
}

function addToLog(bid, relation){
  recentLog.unshift({ bid, relation, time: new Date().toLocaleTimeString() });
  if(recentLog.length > 30) recentLog.pop();
  renderLog();
}

function renderLog(){
  document.getElementById('today-count').textContent = recentLog.length;
  if(recentLog.length === 0) return;
  document.getElementById('recent-list').innerHTML = recentLog.map(l =>
    `<div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px dashed rgba(200,150,12,.12);font-size:.85rem;">
      <span>${escHtml(l.relation)}</span>
      <span style="color:var(--text-mid);">${l.time}</span>
    </div>`
  ).join('');
}

function fmtTime(ts){
  try { return new Date(String(ts).replace(' ','T')).toLocaleTimeString(); }
  catch(e){ return ts; }
}

function escHtml(s){
  if(s === null || s === undefined) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
