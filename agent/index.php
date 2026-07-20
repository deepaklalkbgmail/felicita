<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Allow agent session or admin
if (empty($_SESSION['agent_id']) && !isAdmin()) {
    header('Location: ' . APP_URL . '/login.php?role=agent');
    exit;
}

$agentName  = $_SESSION['agent_name'] ?? 'Admin';
$prices     = getPrices();
$eventName  = getSetting('event_name', 'Onam Sadhya');
$eventDate  = getSetting('event_date', '');
$wings      = getWingNumbers();

$pageTitle = 'Book Sadhya';
$activeNav = '';
$showNav   = isAdmin();
include __DIR__ . '/../includes/header.php';
?>

<div class="page-wrap">
  <div class="page-hero">
    <div class="hero-emoji">🌸</div>
    <h2>Sadhya Booking</h2>
    <p><?= h($eventName) ?><?= $eventDate ? ' &bull; ' . date('d M Y', strtotime($eventDate)) : '' ?></p>
    <p style="margin-top:4px;">Agent: <strong><?= h($agentName) ?></strong>
      &bull; Adults <strong>₹<?= number_format($prices['adults'], 0) ?></strong>
      &bull; Kids <strong>₹<?= number_format($prices['kids'], 0) ?></strong></p>
    <?php if (!empty($_SESSION['agent_id'])): ?>
    <div style="margin-top:12px;">
      <a href="<?= APP_URL ?>/logout.php" class="btn btn-danger btn-sm">🚪 Logout</a>
    </div>
    <?php endif; ?>
  </div>

  <div style="max-width:580px;margin:0 auto;">

    <div class="tabs" id="mode-tabs">
      <div class="tab active" data-mode="new">🏠 New Booking</div>
      <div class="tab"        data-mode="edit">✏️ Edit Booking</div>
    </div>

    <!-- ══ NEW BOOKING ══════════════════════════════════════════════════ -->
    <div class="tab-panel active" id="mode-new">
    <div class="card">
      <div class="card-header">
        <span class="card-icon">🏠</span>
        <h3>New Booking</h3>
      </div>

      <form id="booking-form">
        <div class="form-row">
          <div class="form-group">
            <label>Block Number <span class="req">*</span></label>
            <select name="wing_no" id="wing_no" class="form-control" required>
              <option value="">— Select Wing —</option>
              <?php foreach ($wings as $w): ?>
                <option value="<?= $w ?>">WING <?= $w ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Unit <span class="req">*</span></label>
            <select name="unit" id="unit" class="form-control" required disabled>
              <option value="">— Select Wing first —</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label>Name <span class="req">*</span></label>
          <input type="text" name="owner_name" id="owner_name" class="form-control" placeholder="Select a unit to auto-fill" required>
        </div>

        <div class="form-group">
          <label>Contact Number <span class="req">*</span></label>
          <input type="tel" name="contact_number" id="contact_number" class="form-control" placeholder="9876543210" maxlength="15" required pattern="[0-9+\-\s]{7,15}">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>No. of Plates — Adults</label>
            <input type="number" name="plates_adults" id="plates_adults" class="form-control plate-input" min="0" max="100" value="1">
          </div>
          <div class="form-group">
            <label>No. of Plates — Kids</label>
            <input type="number" name="plates_kids" id="plates_kids" class="form-control plate-input" min="0" max="100" value="0">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Total Amount</label>
            <input type="text" id="total_display" class="form-control" readonly>
          </div>
          <div class="form-group">
            <label>Amount Paid (₹)</label>
            <input type="number" name="paid_amount" id="paid_amount" class="form-control" min="0" step="0.01" value="0">
          </div>
        </div>
        <div class="form-group">
          <label>Balance Due</label>
          <input type="text" id="due_display" class="form-control" readonly>
        </div>

        <div class="form-group">
          <label>Notes (optional)</label>
          <input type="text" name="notes" id="notes" class="form-control" placeholder="Any special note…">
        </div>

        <button type="submit" class="btn btn-primary btn-block" id="submit-btn">
          <span>✅</span> Confirm & Generate Ticket
        </button>
      </form>
    </div>

    <!-- Ticket output -->
    <div id="ticket-output" style="display:none;">
      <div class="card" style="border:2px solid var(--pookalam-green);">
        <div style="text-align:center;margin-bottom:12px;">
          <span style="font-size:2rem;">🎉</span>
          <h3 style="color:var(--pookalam-green);margin-top:4px;">Booking Confirmed!</h3>
        </div>

        <div class="ticket" id="print-ticket">
          <div class="ticket-header">
            <div style="font-size:1.8rem;">🪷</div>
            <h3><?= h($eventName) ?></h3>
            <small id="t-orderid"></small>
          </div>
          <div class="ticket-body">
            <div class="ticket-row"><span class="lbl">Block / Unit</span><span class="val" id="t-house"></span></div>
            <div class="ticket-row"><span class="lbl">Name</span><span class="val" id="t-name"></span></div>
            <div class="ticket-row"><span class="lbl">Plates</span><span class="val" id="t-plates"></span></div>
            <div class="ticket-row"><span class="lbl">Amount</span><span class="val" id="t-amount"></span></div>
            <div class="ticket-row"><span class="lbl">Paid</span><span class="val" id="t-paid"></span></div>
            <div class="ticket-row"><span class="lbl">Balance</span><span class="val" id="t-due"></span></div>
            <div class="ticket-row"><span class="lbl">Agent</span><span class="val"><?= h($agentName) ?></span></div>
          </div>
          <div class="secret-code-box" style="margin:0;border-radius:0;">
            <div class="code-label">🔑 Secret Code</div>
            <div class="code-value" id="t-code"></div>
            <div class="code-chars" id="t-code-chars"></div>
          </div>
          <div style="padding:14px;background:#fff;text-align:center;">
            <div class="qr-wrap" style="margin:0;padding:10px;">
              <div id="qrcode"></div>
              <p>Scan at dining hall</p>
            </div>
          </div>
          <div class="ticket-footer">
            Present this ticket at the Sadhya venue &bull; <?= h($eventName) ?>
          </div>
        </div>

        <div style="display:flex;gap:10px;margin-top:16px;flex-wrap:wrap;">
          <button class="btn btn-outline" onclick="window.print()">🖨 Print</button>
          <button class="btn btn-secondary" onclick="shareTicket()">📤 Share</button>
          <button class="btn btn-primary" onclick="resetForm()">➕ New Booking</button>
        </div>
      </div>
    </div>
    </div><!-- /mode-new -->

    <!-- ══ EDIT BOOKING ═════════════════════════════════════════════════ -->
    <div class="tab-panel" id="mode-edit">
    <div class="card">
      <div class="card-header"><span class="card-icon">✏️</span><h3>Find Booking to Edit</h3></div>
      <div class="form-row">
        <div class="form-group">
          <label>Block Number</label>
          <select id="edit_wing" class="form-control">
            <option value="">— Select Wing —</option>
            <?php foreach ($wings as $w): ?>
              <option value="<?= $w ?>">WING <?= $w ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Unit</label>
          <select id="edit_unit" class="form-control" disabled>
            <option value="">— Select Wing first —</option>
          </select>
        </div>
      </div>
      <button type="button" class="btn btn-primary btn-block" onclick="loadForEdit()">🔍 Load Booking</button>
    </div>

    <div class="card" id="edit-form-card" style="display:none;">
      <div class="card-header"><span class="card-icon">📝</span><h3 id="edit-title">Edit Booking</h3></div>
      <form id="edit-form">
        <input type="hidden" id="edit_booking_id">
        <div class="form-group">
          <label>Name</label>
          <input type="text" id="edit_owner" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Contact Number</label>
          <input type="tel" id="edit_contact" class="form-control" maxlength="15" required pattern="[0-9+\-\s]{7,15}">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Plates — Adults</label>
            <input type="number" id="edit_adults" class="form-control edit-plate" min="0" max="100" value="0">
          </div>
          <div class="form-group">
            <label>Plates — Kids</label>
            <input type="number" id="edit_kids" class="form-control edit-plate" min="0" max="100" value="0">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Total Amount</label>
            <input type="text" id="edit_total" class="form-control" readonly>
          </div>
          <div class="form-group">
            <label>Amount Paid (₹)</label>
            <input type="number" id="edit_paid" class="form-control" min="0" step="0.01" value="0">
          </div>
        </div>
        <div class="form-group">
          <label>Balance Due</label>
          <input type="text" id="edit_due" class="form-control" readonly>
        </div>
        <p style="font-size:.78rem;color:var(--text-mid);">All edits are logged for audit (visible to admin).</p>
        <button type="submit" class="btn btn-success btn-block">💾 Save Changes</button>
      </form>
    </div>
    </div><!-- /mode-edit -->

  </div>
</div>

<!-- QR library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
const APP_URL      = '<?= APP_URL ?>';
const PRICE_ADULTS = <?= $prices['adults'] ?>;
const PRICE_KIDS   = <?= $prices['kids'] ?>;
let qrInstance = null;
const _unitCache = {};   // wing -> units[]

function money(n){ return '₹' + (Number(n)||0).toLocaleString('en-IN',{minimumFractionDigits:2}); }

// ── Mode tabs ───────────────────────────────────────────────────────────
document.querySelectorAll('#mode-tabs .tab').forEach(tab => {
  tab.addEventListener('click', function(){
    document.querySelectorAll('#mode-tabs .tab, #mode-new, #mode-edit').forEach(el=>el.classList.remove('active'));
    this.classList.add('active');
    document.getElementById('mode-' + this.dataset.mode).classList.add('active');
  });
});

// ── Load units for a wing into a <select> ───────────────────────────────
async function loadUnits(wing, selectEl, {markBooked=false}={}){
  selectEl.disabled = true;
  selectEl.innerHTML = '<option value="">Loading…</option>';
  let units = _unitCache[wing];
  if(!units){
    try {
      const res = await fetch(`${APP_URL}/api/residents.php?wing=${wing}`);
      const data = await res.json();
      units = data.success ? data.units : [];
      _unitCache[wing] = units;
    } catch(e){ units = []; }
  }
  let html = '<option value="">— Select Unit —</option>';
  units.forEach(u => {
    const label = u.unit + (u.name ? ' — ' + u.name : '') + (markBooked && u.booked ? ' ✓ booked' : '');
    html += `<option value="${escAttr(u.unit)}" data-name="${escAttr(u.name||'')}">${escHtml(label)}</option>`;
  });
  selectEl.innerHTML = html;
  selectEl.disabled = false;
}

// ── NEW BOOKING cascade ─────────────────────────────────────────────────
document.getElementById('wing_no').addEventListener('change', function(){
  const unitSel = document.getElementById('unit');
  document.getElementById('owner_name').value = '';
  if(!this.value){ unitSel.disabled = true; unitSel.innerHTML='<option value="">— Select Wing first —</option>'; return; }
  loadUnits(this.value, unitSel, {markBooked:true});
});

document.getElementById('unit').addEventListener('change', function(){
  const opt = this.options[this.selectedIndex];
  const name = opt ? opt.getAttribute('data-name') : '';
  if(name) document.getElementById('owner_name').value = name;
});

// ── Totals (new booking) ────────────────────────────────────────────────
function recalcNew(){
  const a = parseInt(document.getElementById('plates_adults').value)||0;
  const k = parseInt(document.getElementById('plates_kids').value)||0;
  const total = a*PRICE_ADULTS + k*PRICE_KIDS;
  const paid  = parseFloat(document.getElementById('paid_amount').value)||0;
  document.getElementById('total_display').value = money(total);
  document.getElementById('due_display').value   = money(Math.max(0,total-paid));
}
document.querySelectorAll('.plate-input, #paid_amount').forEach(el=>el.addEventListener('input', recalcNew));
recalcNew();

// ── Submit new booking ──────────────────────────────────────────────────
document.getElementById('booking-form').addEventListener('submit', async function(e){
  e.preventDefault();
  const a = parseInt(document.getElementById('plates_adults').value)||0;
  const k = parseInt(document.getElementById('plates_kids').value)||0;
  if(a+k < 1){ showFlash('Enter at least one plate (adults or kids).','warning'); return; }

  const btn = document.getElementById('submit-btn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Processing…';

  const fd = new FormData(this);
  try {
    const res  = await fetch(`${APP_URL}/api/booking.php`, { method:'POST', body: fd });
    const data = await res.json();
    if (data.success) { renderTicket(data); }
    else { showFlash(data.message || 'Booking failed.', 'danger'); }
  } catch(err) { showFlash('Network error. Please try again.', 'danger'); }

  btn.disabled = false;
  btn.innerHTML = '<span>✅</span> Confirm & Generate Ticket';
});

function renderTicket(data){
  const plates = `${data.plates_adults} adult(s), ${data.plates_kids} kid(s)`;
  document.getElementById('t-orderid').textContent = 'Order: ' + data.order_id;
  document.getElementById('t-house').textContent   = data.house_name;
  document.getElementById('t-name').textContent    = data.owner_name;
  document.getElementById('t-plates').textContent  = plates;
  document.getElementById('t-amount').textContent  = money(data.total_amount);
  document.getElementById('t-paid').textContent    = money(data.paid_amount);
  document.getElementById('t-due').textContent     = money(data.remaining_due);
  document.getElementById('t-code').textContent    = data.secret_code;

  const charsEl = document.getElementById('t-code-chars');
  charsEl.innerHTML = '';
  data.secret_code.split('').forEach(ch => {
    const span = document.createElement('span');
    span.className = 'code-char';
    span.textContent = ch;
    charsEl.appendChild(span);
  });

  const qrEl = document.getElementById('qrcode');
  qrEl.innerHTML = '';
  qrInstance = new QRCode(qrEl, {
    text: data.secret_code, width: 180, height: 180,
    colorDark: '#2C1810', colorLight: '#FFFFFF', correctLevel: QRCode.CorrectLevel.H
  });

  document.getElementById('booking-form').closest('.card').style.display = 'none';
  document.getElementById('ticket-output').style.display = 'block';
  window.scrollTo({top:0, behavior:'smooth'});
  showFlash('Ticket generated successfully! 🎉', 'success');
  window._lastBooking = data;
}

function resetForm(){
  document.getElementById('booking-form').reset();
  recalcNew();
  document.getElementById('unit').disabled = true;
  document.getElementById('unit').innerHTML = '<option value="">— Select Wing first —</option>';
  document.getElementById('qrcode').innerHTML = '';
  document.getElementById('ticket-output').style.display = 'none';
  document.getElementById('booking-form').closest('.card').style.display = 'block';
}

function shareTicket(){
  const b = window._lastBooking;
  if (!b) return;
  const text = `🪷 ${<?= json_encode($eventName) ?>}\n` +
    `Order: ${b.order_id}\nUnit: ${b.house_name}\n` +
    `Plates: ${b.plates_adults} adult(s), ${b.plates_kids} kid(s)\n` +
    `Amount: ${money(b.total_amount)} • Paid: ${money(b.paid_amount)} • Balance: ${money(b.remaining_due)}\n` +
    `🔑 Secret Code: ${b.secret_code}\n\nPresent this at the Sadhya venue.`;
  if (navigator.share) { navigator.share({ title: 'Sadhya Ticket', text }); }
  else { navigator.clipboard.writeText(text).then(()=>showFlash('Ticket details copied!','success')); }
}

// ── EDIT BOOKING ────────────────────────────────────────────────────────
document.getElementById('edit_wing').addEventListener('change', function(){
  const unitSel = document.getElementById('edit_unit');
  if(!this.value){ unitSel.disabled=true; unitSel.innerHTML='<option value="">— Select Wing first —</option>'; return; }
  loadUnits(this.value, unitSel, {markBooked:true});
});

async function loadForEdit(){
  const wing = document.getElementById('edit_wing').value;
  const unit = document.getElementById('edit_unit').value;
  if(!wing || !unit){ showFlash('Select Block Number and Unit.','warning'); return; }
  try {
    const res = await fetch(`${APP_URL}/api/booking_edit.php?wing=${wing}&unit=${encodeURIComponent(unit)}`);
    const data = await res.json();
    if(!data.success){ showFlash(data.message || 'No booking found.','danger'); document.getElementById('edit-form-card').style.display='none'; return; }
    const b = data.booking;
    document.getElementById('edit_booking_id').value = b.id;
    document.getElementById('edit-title').textContent = 'Edit — ' + b.house_name;
    document.getElementById('edit_owner').value   = b.owner_name;
    document.getElementById('edit_contact').value = b.contact_number;
    document.getElementById('edit_adults').value  = b.plates_adults;
    document.getElementById('edit_kids').value    = b.plates_kids;
    document.getElementById('edit_paid').value    = b.paid_amount;
    recalcEdit();
    document.getElementById('edit-form-card').style.display = 'block';
    document.getElementById('edit-form-card').scrollIntoView({behavior:'smooth'});
  } catch(e){ showFlash('Network error.','danger'); }
}

function recalcEdit(){
  const a = parseInt(document.getElementById('edit_adults').value)||0;
  const k = parseInt(document.getElementById('edit_kids').value)||0;
  const total = a*PRICE_ADULTS + k*PRICE_KIDS;
  const paid  = parseFloat(document.getElementById('edit_paid').value)||0;
  document.getElementById('edit_total').value = money(total);
  document.getElementById('edit_due').value   = money(Math.max(0,total-paid));
}
document.querySelectorAll('.edit-plate, #edit_paid').forEach(el=>el.addEventListener('input', recalcEdit));

document.getElementById('edit-form').addEventListener('submit', async function(e){
  e.preventDefault();
  const fd = new FormData();
  fd.append('booking_id',     document.getElementById('edit_booking_id').value);
  fd.append('owner_name',     document.getElementById('edit_owner').value);
  fd.append('contact_number', document.getElementById('edit_contact').value);
  fd.append('plates_adults',  document.getElementById('edit_adults').value);
  fd.append('plates_kids',    document.getElementById('edit_kids').value);
  fd.append('paid_amount',    document.getElementById('edit_paid').value);
  try {
    const res = await fetch(`${APP_URL}/api/booking_edit.php`, { method:'POST', body: fd });
    const data = await res.json();
    if(data.success){
      showFlash(data.changed===false ? 'No changes made.' : '✅ Booking updated.', 'success');
    } else { showFlash(data.message || 'Update failed.','danger'); }
  } catch(e){ showFlash('Network error.','danger'); }
});

// ── helpers ─────────────────────────────────────────────────────────────
function escHtml(s){ return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function escAttr(s){ return String(s??'').replace(/"/g,'&quot;').replace(/</g,'&lt;'); }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
