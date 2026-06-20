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

$agentName = $_SESSION['agent_name'] ?? 'Admin';
$pricePerPlate = (float) getSetting('price_per_plate', '200');
$eventName     = getSetting('event_name', 'Onam Sadhya');
$eventDate     = getSetting('event_date', '');

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
    <p style="margin-top:4px;">Agent: <strong><?= h($agentName) ?></strong> &bull; Price: <strong>₹<?= number_format($pricePerPlate, 0) ?>/plate</strong></p>
    <?php if (!empty($_SESSION['agent_id'])): ?>
    <div style="margin-top:12px;">
      <a href="<?= APP_URL ?>/logout.php" class="btn btn-danger btn-sm">🚪 Logout</a>
    </div>
    <?php endif; ?>
  </div>

  <div style="max-width:560px;margin:0 auto;">
    <div class="card">
      <div class="card-header">
        <span class="card-icon">🏠</span>
        <h3>New Booking</h3>
      </div>

      <form id="booking-form">
        <div class="form-row">
          <div class="form-group">
            <label>House Name <span class="req">*</span></label>
            <input type="text" name="house_name" id="house_name" class="form-control" placeholder="e.g. Kaveri Nivas" required>
          </div>
          <div class="form-group">
            <label>Owner Name <span class="req">*</span></label>
            <input type="text" name="owner_name" id="owner_name" class="form-control" placeholder="e.g. Rajan K P" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Contact Number <span class="req">*</span></label>
            <input type="tel" name="contact_number" id="contact_number" class="form-control" placeholder="9876543210" maxlength="15" required pattern="[0-9+\-\s]{7,15}">
          </div>
          <div class="form-group">
            <label>No. of Plates <span class="req">*</span></label>
            <input type="number" name="headcount" id="headcount" class="form-control" min="1" max="50" value="1" required>
          </div>
        </div>

        <div class="form-group">
          <label>Total Amount</label>
          <input type="text" id="total_display" class="form-control" readonly value="₹<?= number_format($pricePerPlate, 2) ?>">
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
            <div class="ticket-row"><span class="lbl">House</span><span class="val" id="t-house"></span></div>
            <div class="ticket-row"><span class="lbl">Name</span><span class="val" id="t-name"></span></div>
            <div class="ticket-row"><span class="lbl">Plates</span><span class="val" id="t-plates"></span></div>
            <div class="ticket-row"><span class="lbl">Amount</span><span class="val" id="t-amount"></span></div>
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

  </div>
</div>

<!-- QR library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
const PRICE = <?= $pricePerPlate ?>;
let qrInstance = null;

document.getElementById('headcount').addEventListener('input', function(){
  const n = parseInt(this.value) || 0;
  document.getElementById('total_display').value = '₹' + (n * PRICE).toLocaleString('en-IN', {minimumFractionDigits:2});
});

document.getElementById('booking-form').addEventListener('submit', async function(e){
  e.preventDefault();
  const btn = document.getElementById('submit-btn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Processing…';

  const fd = new FormData(this);
  try {
    const res  = await fetch('<?= APP_URL ?>/api/booking.php', { method:'POST', body: fd });
    const data = await res.json();

    if (data.success) {
      renderTicket(data);
    } else {
      showFlash(data.message || 'Booking failed.', 'danger');
    }
  } catch(err) {
    showFlash('Network error. Please try again.', 'danger');
  }

  btn.disabled = false;
  btn.innerHTML = '<span>✅</span> Confirm & Generate Ticket';
});

function renderTicket(data){
  document.getElementById('t-orderid').textContent  = 'Order: ' + data.order_id;
  document.getElementById('t-house').textContent    = data.house_name;
  document.getElementById('t-name').textContent     = data.owner_name;
  document.getElementById('t-plates').textContent   = data.headcount + ' plate(s)';
  document.getElementById('t-amount').textContent   = '₹' + parseFloat(data.total_amount).toLocaleString('en-IN',{minimumFractionDigits:2});
  document.getElementById('t-code').textContent     = data.secret_code;

  // Individual char boxes
  const charsEl = document.getElementById('t-code-chars');
  charsEl.innerHTML = '';
  data.secret_code.split('').forEach(ch => {
    const span = document.createElement('span');
    span.className = 'code-char';
    span.textContent = ch;
    charsEl.appendChild(span);
  });

  // QR code
  const qrEl = document.getElementById('qrcode');
  qrEl.innerHTML = '';
  qrInstance = new QRCode(qrEl, {
    text: data.secret_code,
    width: 180, height: 180,
    colorDark: '#2C1810', colorLight: '#FFFFFF',
    correctLevel: QRCode.CorrectLevel.H
  });

  document.getElementById('booking-form').closest('.card').style.display = 'none';
  document.getElementById('ticket-output').style.display = 'block';
  window.scrollTo({top:0, behavior:'smooth'});
  showFlash('Ticket generated successfully! 🎉', 'success');
  window._lastBooking = data;
}

function resetForm(){
  document.getElementById('booking-form').reset();
  document.getElementById('total_display').value = '₹<?= number_format($pricePerPlate,2) ?>';
  document.getElementById('qrcode').innerHTML = '';
  document.getElementById('ticket-output').style.display = 'none';
  document.getElementById('booking-form').closest('.card').style.display = 'block';
}

function shareTicket(){
  const b = window._lastBooking;
  if (!b) return;
  const text = `🪷 ${<?= json_encode($eventName) ?>}\n` +
    `Order: ${b.order_id}\nHouse: ${b.house_name}\nPlates: ${b.headcount}\n` +
    `Amount: ₹${parseFloat(b.total_amount).toLocaleString('en-IN',{minimumFractionDigits:2})}\n` +
    `🔑 Secret Code: ${b.secret_code}\n\nPresent this at the Sadhya venue.`;
  if (navigator.share) {
    navigator.share({ title: 'Sadhya Ticket', text });
  } else {
    navigator.clipboard.writeText(text).then(()=>showFlash('Ticket details copied!','success'));
  }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
