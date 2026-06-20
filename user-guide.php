<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$eventName  = getSetting('event_name',  'Aaravam 2026 Onam Sadhya');
$eventDate  = getSetting('event_date',  '');
$eventVenue = getSetting('event_venue', '');
$price      = getSetting('price_per_plate', '200');

$displayDate  = $eventDate  ? date('d F Y', strtotime($eventDate))  : '';
$displayPrice = '₹' . number_format((float)$price, 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($eventName) ?> — User Guide</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
/* ── Variables ─────────────────────────────────────────────────────── */
:root {
  --gold     : #C8960C;
  --deep     : #9A6F08;
  --cream    : #FFF8E7;
  --border   : #E8C84A;
  --green    : #1A7A40;
  --red      : #C0392B;
  --blue     : #1A4A8A;
  --mmg-blue : #4A6FA5;
  --text     : #2C1810;
  --mid      : #5D4037;
  --light    : #8D6E63;
  --bg       : #FFFDF5;
  --white    : #FFFFFF;
}

/* ── Base ──────────────────────────────────────────────────────────── */
* { box-sizing: border-box; margin: 0; padding: 0; }
html { font-size: 15px; }
body {
  font-family: 'Inter', sans-serif;
  background: var(--bg);
  color: var(--text);
  line-height: 1.7;
  max-width: 860px;
  margin: 0 auto;
  padding: 0 0 60px;
}

/* ── COVER PAGE ────────────────────────────────────────────────────── */
.cover {
  background: linear-gradient(160deg, #1a2a4a 0%, #2c3e6b 45%, #1a3a5c 100%);
  color: #fff;
  padding: 60px 50px 50px;
  position: relative;
  overflow: hidden;
  min-height: 320px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}
.cover::before {
  content: '';
  position: absolute;
  bottom: 0; left: 0; right: 0;
  height: 6px;
  background: linear-gradient(90deg,#C0392B,#E67E22,#C8960C,#1A7A40,#1A4A8A,#C0392B);
}
.cover-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 30px;
}
.cover-mmg {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 6px;
}
.cover-mmg img {
  width: 90px;
  height: 90px;
  object-fit: contain;
  filter: brightness(0) invert(1) opacity(.9);
}
.cover-mmg-label {
  font-size: .7rem;
  color: rgba(255,255,255,.55);
  letter-spacing: .08em;
  text-transform: uppercase;
  text-align: right;
}
.cover-mmg-name {
  font-size: .82rem;
  font-weight: 600;
  color: rgba(255,255,255,.8);
  text-align: right;
}
.cover-aaravam img {
  width: 200px;
  height: auto;
  max-height: 200px;
  object-fit: contain;
  filter: drop-shadow(0 4px 20px rgba(0,0,0,.4));
}
.cover-bottom { margin-top: 30px; }
.cover-title {
  font-family: 'Playfair Display', serif;
  font-size: 2.2rem;
  font-weight: 900;
  line-height: 1.2;
  text-shadow: 1px 1px 6px rgba(0,0,0,.4);
}
.cover-subtitle {
  font-size: 1rem;
  color: rgba(255,255,255,.7);
  margin-top: 6px;
  letter-spacing: .04em;
}
.cover-meta {
  margin-top: 20px;
  display: flex;
  gap: 24px;
  flex-wrap: wrap;
}
.cover-meta-item {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.cover-meta-label {
  font-size: .68rem;
  color: rgba(255,255,255,.45);
  text-transform: uppercase;
  letter-spacing: .08em;
}
.cover-meta-value {
  font-size: .9rem;
  font-weight: 600;
  color: rgba(255,255,255,.9);
}

/* ── ROLE BADGE STRIP ──────────────────────────────────────────────── */
.role-strip {
  display: flex;
  background: var(--cream);
  border-bottom: 2px solid var(--border);
}
.role-badge {
  flex: 1;
  text-align: center;
  padding: 14px 10px;
  border-right: 1px solid rgba(200,150,12,.25);
  font-size: .82rem;
  font-weight: 600;
  color: var(--deep);
  text-transform: uppercase;
  letter-spacing: .05em;
}
.role-badge:last-child { border-right: none; }
.role-badge .role-icon { font-size: 1.5rem; display: block; margin-bottom: 4px; }

/* ── TABLE OF CONTENTS ─────────────────────────────────────────────── */
.toc {
  background: var(--white);
  border: 1.5px solid rgba(200,150,12,.25);
  border-radius: 12px;
  margin: 30px 30px 10px;
  padding: 24px 28px;
}
.toc h3 {
  font-family: 'Playfair Display', serif;
  color: var(--deep);
  font-size: 1.1rem;
  margin-bottom: 14px;
  padding-bottom: 10px;
  border-bottom: 1px solid rgba(200,150,12,.2);
}
.toc-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 6px 30px;
}
.toc-item {
  display: flex;
  align-items: baseline;
  gap: 8px;
  font-size: .84rem;
  color: var(--mid);
  text-decoration: none;
}
.toc-item .toc-num {
  font-size: .72rem;
  font-weight: 700;
  color: var(--gold);
  min-width: 20px;
}
.toc-item:hover { color: var(--deep); }

/* ── SECTIONS ──────────────────────────────────────────────────────── */
.guide-body { padding: 10px 30px; }

.section {
  margin-bottom: 36px;
  page-break-inside: avoid;
}
.section-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  padding-bottom: 10px;
  border-bottom: 2px solid rgba(200,150,12,.25);
}
.section-num {
  background: linear-gradient(135deg, var(--deep), var(--gold));
  color: #fff;
  font-size: .75rem;
  font-weight: 700;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.section h2 {
  font-family: 'Playfair Display', serif;
  font-size: 1.25rem;
  color: var(--deep);
}
.section-icon { font-size: 1.4rem; }

/* ── Role section headings ─────────────────────────────────────────── */
.role-section-admin    { border-left: 4px solid var(--gold);   padding-left: 14px; margin-bottom: 18px; }
.role-section-agent    { border-left: 4px solid var(--green);  padding-left: 14px; margin-bottom: 18px; }
.role-section-validator{ border-left: 4px solid var(--blue);   padding-left: 14px; margin-bottom: 18px; }

.role-section-admin h3,
.role-section-agent h3,
.role-section-validator h3 {
  font-size: 1rem;
  font-weight: 700;
  margin-bottom: 6px;
}
.role-section-admin h3    { color: var(--deep); }
.role-section-agent h3    { color: var(--green); }
.role-section-validator h3{ color: var(--blue); }

/* ── Prose ─────────────────────────────────────────────────────────── */
p { color: var(--mid); font-size: .9rem; margin-bottom: 10px; }
ul, ol { padding-left: 20px; color: var(--mid); font-size: .9rem; }
ul li, ol li { margin-bottom: 5px; }
strong { color: var(--text); }

/* ── Cards / callouts ──────────────────────────────────────────────── */
.callout {
  border-radius: 8px;
  padding: 14px 16px;
  margin: 12px 0;
  font-size: .88rem;
  display: flex;
  gap: 10px;
  align-items: flex-start;
}
.callout-tip     { background: rgba(200,150,12,.1);  border-left: 4px solid var(--gold);  color: var(--deep); }
.callout-warning { background: rgba(192,57,43,.08);  border-left: 4px solid var(--red);   color: #7B241C; }
.callout-info    { background: rgba(26,74,138,.08);  border-left: 4px solid var(--blue);  color: var(--blue); }
.callout-success { background: rgba(26,122,64,.08);  border-left: 4px solid var(--green); color: var(--green); }
.callout-icon    { font-size: 1.1rem; flex-shrink: 0; line-height: 1.4; }

/* ── Tables ────────────────────────────────────────────────────────── */
.guide-table {
  width: 100%;
  border-collapse: collapse;
  font-size: .85rem;
  margin: 14px 0;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid rgba(200,150,12,.2);
}
.guide-table thead tr {
  background: linear-gradient(90deg, var(--deep), var(--gold));
  color: #fff;
}
.guide-table th {
  padding: 10px 14px;
  text-align: left;
  font-size: .78rem;
  text-transform: uppercase;
  letter-spacing: .04em;
  font-weight: 600;
}
.guide-table td {
  padding: 9px 14px;
  border-bottom: 1px solid rgba(200,150,12,.1);
  color: var(--mid);
}
.guide-table tr:last-child td { border-bottom: none; }
.guide-table tr:nth-child(even) td { background: rgba(200,150,12,.04); }

/* ── Steps ─────────────────────────────────────────────────────────── */
.steps { counter-reset: step; list-style: none; padding: 0; }
.steps li {
  counter-increment: step;
  display: flex;
  gap: 12px;
  margin-bottom: 14px;
  align-items: flex-start;
  font-size: .9rem;
  color: var(--mid);
}
.steps li::before {
  content: counter(step);
  background: linear-gradient(135deg, var(--deep), var(--gold));
  color: #fff;
  font-size: .75rem;
  font-weight: 700;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  margin-top: 2px;
}

/* ── URL pill ──────────────────────────────────────────────────────── */
.url-pill {
  display: inline-block;
  background: rgba(26,74,138,.08);
  border: 1px solid rgba(26,74,138,.2);
  border-radius: 6px;
  padding: 4px 10px;
  font-family: monospace;
  font-size: .82rem;
  color: var(--blue);
  word-break: break-all;
}

/* ── Flow diagram ──────────────────────────────────────────────────── */
.flow {
  display: flex;
  align-items: center;
  gap: 0;
  flex-wrap: wrap;
  margin: 16px 0;
  justify-content: center;
}
.flow-step {
  background: var(--white);
  border: 1.5px solid rgba(200,150,12,.3);
  border-radius: 10px;
  padding: 10px 14px;
  text-align: center;
  font-size: .8rem;
  font-weight: 600;
  color: var(--deep);
  min-width: 110px;
}
.flow-step .flow-icon { font-size: 1.3rem; display: block; margin-bottom: 4px; }
.flow-arrow {
  font-size: 1.2rem;
  color: var(--gold);
  padding: 0 6px;
  flex-shrink: 0;
}

/* ── Alert sim ─────────────────────────────────────────────────────── */
.alert-sim {
  background: var(--red);
  color: #fff;
  border-radius: 8px;
  padding: 14px 16px;
  margin: 12px 0;
  font-size: .88rem;
}
.alert-sim strong { color: #fff; font-size: 1rem; }

/* ── Checklist ─────────────────────────────────────────────────────── */
.checklist { list-style: none; padding: 0; }
.checklist li {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  padding: 5px 0;
  font-size: .88rem;
  color: var(--mid);
  border-bottom: 1px dashed rgba(200,150,12,.15);
}
.checklist li:last-child { border-bottom: none; }
.checklist li::before {
  content: '☐';
  font-size: 1rem;
  color: var(--gold);
  flex-shrink: 0;
  line-height: 1.4;
}

/* ── Secret code display ───────────────────────────────────────────── */
.code-demo {
  display: flex;
  gap: 6px;
  justify-content: center;
  margin: 12px 0;
}
.code-char-demo {
  width: 40px;
  height: 46px;
  background: linear-gradient(135deg, var(--deep), var(--gold));
  color: #fff;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
  font-weight: 700;
  font-family: monospace;
  box-shadow: 0 2px 8px rgba(200,150,12,.35);
}

/* ── Page divider ──────────────────────────────────────────────────── */
.divider {
  text-align: center;
  font-size: 1.1rem;
  color: var(--gold);
  letter-spacing: .3em;
  margin: 28px 0 20px;
  opacity: .6;
}

/* ── FOOTER ────────────────────────────────────────────────────────── */
.guide-footer {
  margin-top: 40px;
  background: linear-gradient(135deg, #1a2a4a, #2c3e6b);
  color: rgba(255,255,255,.75);
  padding: 28px 30px;
  border-radius: 0 0 12px 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  flex-wrap: wrap;
}
.guide-footer img {
  height: 50px;
  width: auto;
  object-fit: contain;
  filter: brightness(0) invert(1) opacity(.7);
}
.guide-footer-text { font-size: .8rem; }
.guide-footer-text strong { color: #fff; }

/* ── Print button ──────────────────────────────────────────────────── */
.print-bar {
  position: sticky;
  top: 0;
  z-index: 100;
  background: var(--deep);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 20px;
  font-size: .85rem;
  gap: 10px;
  flex-wrap: wrap;
}
.print-bar strong { font-size: .95rem; }
.btn-print {
  background: var(--gold);
  color: var(--text);
  border: none;
  border-radius: 6px;
  padding: 8px 20px;
  font-size: .85rem;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  text-decoration: none;
  white-space: nowrap;
}
.btn-print:hover { background: var(--border); }

/* ── PRINT STYLES ──────────────────────────────────────────────────── */
@media print {
  .print-bar { display: none !important; }
  body {
    background: #fff;
    font-size: 11pt;
    max-width: 100%;
    padding: 0;
  }
  .cover { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .guide-footer { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .guide-table thead tr { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .section { page-break-inside: avoid; }
  .callout { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .toc { page-break-after: always; }
  h2, h3 { page-break-after: avoid; }
  .role-strip { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}

/* ── Responsive ────────────────────────────────────────────────────── */
@media (max-width: 600px) {
  .cover { padding: 30px 20px; }
  .cover-top { flex-direction: column-reverse; align-items: center; }
  .cover-mmg { align-items: center; }
  .cover-aaravam img { width: 160px; }
  .cover-title { font-size: 1.6rem; }
  .toc { margin: 20px 16px 0; padding: 18px; }
  .toc-grid { grid-template-columns: 1fr; }
  .guide-body { padding: 10px 16px; }
  .guide-footer { flex-direction: column; align-items: center; text-align: center; }
  .flow { flex-direction: column; }
  .flow-arrow { transform: rotate(90deg); }
}
</style>
</head>
<body>

<!-- Print bar -->
<div class="print-bar">
  <div>
    <strong><?= htmlspecialchars($eventName) ?></strong> — Complete User Guide
  </div>
  <button class="btn-print" onclick="window.print()">🖨 Save as PDF / Print</button>
</div>

<!-- ══ COVER ═══════════════════════════════════════════════════════════ -->
<div class="cover">
  <div class="cover-top">
    <div class="cover-aaravam">
      <img src="<?= APP_URL ?>/assets/img/aaravam-logo.png" alt="Aaravam 2026"
           style="width:200px;max-width:55vw;height:auto;object-fit:contain;"
           onerror="this.style.display='none'">
    </div>
    <div class="cover-mmg">
      <img src="<?= APP_URL ?>/assets/img/mmg-logo.png" alt="Meta Mates Group"
           style="width:90px;height:90px;max-width:25vw;object-fit:contain;"
           onerror="this.style.display='none'">
      <div class="cover-mmg-label">Official Sponsor</div>
      <div class="cover-mmg-name">Meta Mates Group</div>
    </div>
  </div>
  <div class="cover-bottom">
    <div class="cover-title">Sadhya Ticketing System<br>User Guide</div>
    <div class="cover-subtitle">All-in-one guide for Admins, Agents & Validators</div>
    <div class="cover-meta">
      <?php if ($displayDate): ?>
      <div class="cover-meta-item">
        <span class="cover-meta-label">Event Date</span>
        <span class="cover-meta-value"><?= htmlspecialchars($displayDate) ?></span>
      </div>
      <?php endif; ?>
      <?php if ($eventVenue): ?>
      <div class="cover-meta-item">
        <span class="cover-meta-label">Venue</span>
        <span class="cover-meta-value"><?= htmlspecialchars($eventVenue) ?></span>
      </div>
      <?php endif; ?>
      <div class="cover-meta-item">
        <span class="cover-meta-label">Price Per Plate</span>
        <span class="cover-meta-value"><?= htmlspecialchars($displayPrice) ?></span>
      </div>
    </div>
  </div>
</div>

<!-- Role strip -->
<div class="role-strip">
  <div class="role-badge"><span class="role-icon">🛡</span>Admin</div>
  <div class="role-badge"><span class="role-icon">🏠</span>Agent</div>
  <div class="role-badge"><span class="role-icon">🍛</span>Validator</div>
</div>

<!-- ══ TABLE OF CONTENTS ════════════════════════════════════════════════ -->
<div class="toc">
  <h3>📑 Contents</h3>
  <div class="toc-grid">
    <a class="toc-item" href="#overview">      <span class="toc-num">01</span> Overview & User Roles</a>
    <a class="toc-item" href="#setup">         <span class="toc-num">02</span> Installation & Setup</a>
    <a class="toc-item" href="#login">         <span class="toc-num">03</span> Logging In</a>
    <a class="toc-item" href="#admin">         <span class="toc-num">04</span> Admin — Dashboard</a>
    <a class="toc-item" href="#agents-mgmt">   <span class="toc-num">05</span> Admin — Managing Agents</a>
    <a class="toc-item" href="#validators-mgmt"><span class="toc-num">06</span> Admin — Managing Validators</a>
    <a class="toc-item" href="#tickets">       <span class="toc-num">07</span> Admin — Tickets & VIP</a>
    <a class="toc-item" href="#reports">       <span class="toc-num">08</span> Admin — Reports & Export</a>
    <a class="toc-item" href="#settings">      <span class="toc-num">09</span> Admin — Settings</a>
    <a class="toc-item" href="#agent">         <span class="toc-num">10</span> Agent — Booking a Family</a>
    <a class="toc-item" href="#validator">     <span class="toc-num">11</span> Validator — Check-in</a>
    <a class="toc-item" href="#codes">         <span class="toc-num">12</span> Secret Code & QR Code</a>
    <a class="toc-item" href="#fraud">         <span class="toc-num">13</span> Fraud / Limit Reached</a>
    <a class="toc-item" href="#checklist">     <span class="toc-num">14</span> Event Day Checklist</a>
    <a class="toc-item" href="#trouble">       <span class="toc-num">15</span> Troubleshooting</a>
  </div>
</div>

<!-- ══ BODY ═════════════════════════════════════════════════════════════ -->
<div class="guide-body">

<!-- 01 Overview -->
<div class="section" id="overview">
  <div class="section-header">
    <div class="section-num">01</div>
    <span class="section-icon">🪷</span>
    <h2>Overview & User Roles</h2>
  </div>
  <p>The <strong><?= htmlspecialchars($eventName) ?> Sadhya Ticketing System</strong> is a mobile-first web application for managing feast ticketing and dining hall validation. It supports three distinct roles:</p>

  <table class="guide-table">
    <thead><tr><th>Role</th><th>Who</th><th>What They Do</th><th>How to Login</th></tr></thead>
    <tbody>
      <tr>
        <td><strong>🛡 Admin</strong></td>
        <td>Event organisers</td>
        <td>Dashboard, reports, agents, validators, settings, VIP tickets</td>
        <td>Username + Password at Login page</td>
      </tr>
      <tr>
        <td><strong>🏠 Agent</strong></td>
        <td>Volunteers on field</td>
        <td>Book families door-to-door, generate tickets</td>
        <td>Personal 4–6 digit PIN at Login page</td>
      </tr>
      <tr>
        <td><strong>🍛 Validator</strong></td>
        <td>Venue check-in staff</td>
        <td>Scan QR / enter code, mark plates served</td>
        <td>Personal Validator PIN at Login page</td>
      </tr>
    </tbody>
  </table>

  <p><strong>End-to-end flow:</strong></p>
  <div class="flow">
    <div class="flow-step"><span class="flow-icon">🏠</span>Agent books family</div>
    <div class="flow-arrow">→</div>
    <div class="flow-step"><span class="flow-icon">🎟</span>Ticket + QR generated</div>
    <div class="flow-arrow">→</div>
    <div class="flow-step"><span class="flow-icon">📲</span>Family arrives at venue</div>
    <div class="flow-arrow">→</div>
    <div class="flow-step"><span class="flow-icon">✅</span>Validator scans & serves</div>
  </div>
</div>

<div class="divider">✿ ❀ ✿</div>

<!-- 02 Setup -->
<div class="section" id="setup">
  <div class="section-header">
    <div class="section-num">02</div>
    <span class="section-icon">🖥</span>
    <h2>Installation & Setup</h2>
  </div>

  <div class="callout callout-info">
    <span class="callout-icon">ℹ️</span>
    <div>These steps are for the system administrator setting up the app on a cPanel hosting server for the first time.</div>
  </div>

  <ol class="steps">
    <li><strong>Upload files:</strong> Upload the entire application folder to <span class="url-pill">public_html/aaravam2026/</span> using cPanel File Manager or FTP.</li>
    <li><strong>Create database:</strong> In cPanel → MySQL Databases, create a new database and a database user, then grant the user All Privileges on that database.</li>
    <li><strong>Configure:</strong> Open <span class="url-pill">config/database.php</span> and fill in DB_HOST, DB_USER, DB_PASS, DB_NAME, and APP_URL (your full domain URL, no trailing slash).</li>
    <li><strong>Run installer:</strong> Open a browser and visit <span class="url-pill">/aaravam2026/config/install.php</span> — you will see a green success message confirming tables were created.</li>
    <li><strong>Delete installer:</strong> <strong>Immediately</strong> delete <span class="url-pill">config/install.php</span> from the server after installation.</li>
    <li><strong>Set event details:</strong> Log in as admin → Settings → set the event name, date, venue, and price per plate.</li>
    <li><strong>Add agents:</strong> Admin → Agents → Add each volunteer with their name and a unique PIN.</li>
    <li><strong>Add validators:</strong> Admin → Validators → Add each check-in staff member with their name and a unique PIN. Share each person's PIN privately with them.</li>
  </ol>

  <div class="callout callout-warning">
    <span class="callout-icon">⚠️</span>
    <div><strong>Security:</strong> Change the default admin password immediately after first login (Admin → Settings → Change Password). Never share admin credentials.</div>
  </div>
</div>

<div class="divider">✿ ❀ ✿</div>

<!-- 03 Login -->
<div class="section" id="login">
  <div class="section-header">
    <div class="section-num">03</div>
    <span class="section-icon">🔐</span>
    <h2>Logging In</h2>
  </div>

  <p>All roles log in at the same page — <span class="url-pill">/aaravam2026/login.php</span> — using the appropriate tab:</p>

  <table class="guide-table">
    <thead><tr><th>Tab</th><th>Credentials Required</th><th>Redirects to</th></tr></thead>
    <tbody>
      <tr><td><strong>Admin</strong></td><td>Username and Password (set by system administrator)</td><td>Admin Dashboard</td></tr>
      <tr><td><strong>Validator</strong></td><td>Personal Validator PIN (assigned by admin via Admin → Validators)</td><td>Dining Hall Validator screen</td></tr>
      <tr><td><strong>Agent</strong></td><td>Personal 4–6 digit PIN (assigned by admin via Admin → Agents)</td><td>Booking page</td></tr>
    </tbody>
  </table>

  <div class="callout callout-tip">
    <span class="callout-icon">💡</span>
    <div>Bookmark the login page on your phone's home screen for quick access during the event.</div>
  </div>
</div>

<div class="divider">✿ ❀ ✿</div>

<!-- 04 Admin Dashboard -->
<div class="section" id="admin">
  <div class="section-header">
    <div class="section-num">04</div>
    <span class="section-icon">📊</span>
    <h2>Admin — Dashboard</h2>
  </div>

  <p>The dashboard is the first screen after admin login and gives a live overview of the event.</p>

  <table class="guide-table">
    <thead><tr><th>Card</th><th>What It Shows</th></tr></thead>
    <tbody>
      <tr><td>🎟 Total Bookings</td><td>Number of booking records created</td></tr>
      <tr><td>🍽 Total Plates Sold</td><td>Sum of all headcounts across all bookings</td></tr>
      <tr><td>✅ Plates Served</td><td>Plates that have been consumed at the venue</td></tr>
      <tr><td>🔵 Unused Plates</td><td>Plates sold but not yet served</td></tr>
      <tr><td>💰 Total Revenue</td><td>Sum of all booking amounts collected</td></tr>
      <tr><td>👤 Active Agents</td><td>Number of active volunteers</td></tr>
    </tbody>
  </table>

  <p>A <strong>consumption progress bar</strong> shows what percentage of sold plates have been served. The bottom of the dashboard shows the 10 most recent bookings including each booking's secret code.</p>

  <p>The admin navigation bar contains quick links to: <strong>Dashboard · Agents · Validators · Tickets · Reports · Settings</strong>.</p>
</div>

<div class="divider">✿ ❀ ✿</div>

<!-- 05 Agents -->
<div class="section" id="agents-mgmt">
  <div class="section-header">
    <div class="section-num">05</div>
    <span class="section-icon">👥</span>
    <h2>Admin — Managing Agents</h2>
  </div>

  <div class="role-section-admin">
    <h3>Adding an Agent</h3>
    <ol class="steps">
      <li>Go to <strong>Admin → Agents → Add Agent</strong> tab.</li>
      <li>Enter the volunteer's full name.</li>
      <li>Assign a unique <strong>4–6 digit numeric PIN</strong> (each agent must have a different PIN).</li>
      <li>Click <strong>Add Agent</strong>. Share the PIN privately with the volunteer.</li>
    </ol>
  </div>

  <div class="role-section-admin">
    <h3>Agent Summary Table</h3>
    <p>The <em>All Agents</em> tab shows each agent's PIN, number of bookings, total amount collected, and active/inactive status.</p>
  </div>

  <div class="role-section-admin">
    <h3>Activating / Deactivating</h3>
    <p>Click <strong>Deactivate</strong> to block an agent's login (e.g. if they are no longer volunteering). Their booking history is preserved. Click <strong>Activate</strong> to restore access.</p>
  </div>

  <div class="callout callout-tip">
    <span class="callout-icon">💡</span>
    <div>Each agent's PIN is visible only to the admin — use the Agents table to look up a forgotten PIN and share it again privately.</div>
  </div>
</div>

<div class="divider">✿ ❀ ✿</div>

<!-- 06 Validators Management -->
<div class="section" id="validators-mgmt">
  <div class="section-header">
    <div class="section-num">06</div>
    <span class="section-icon">🍛</span>
    <h2>Admin — Managing Validators</h2>
  </div>

  <p>Each check-in staff member gets their own individual validator account with a personal PIN. Go to <strong>Admin → Validators</strong> to manage them.</p>

  <div class="role-section-admin">
    <h3>Adding a Validator</h3>
    <ol class="steps">
      <li>Go to <strong>Admin → Validators → Add Validator</strong>.</li>
      <li>Enter the staff member's <strong>name</strong>.</li>
      <li>Assign a unique <strong>4–6 digit PIN</strong>. Each validator must have their own PIN — do not reuse PINs between validators.</li>
      <li>Click <strong>Add Validator</strong>. Share the PIN privately with that staff member.</li>
    </ol>
  </div>

  <div class="role-section-admin">
    <h3>Validator Summary Table</h3>
    <p>The validators list shows each validator's name, PIN, number of plates they have served, and their active/inactive status. This lets you track who served which plates (also visible in Admin → Reports → Check-in Log).</p>
  </div>

  <div class="role-section-admin">
    <h3>Activating / Deactivating</h3>
    <p>Click <strong>Deactivate</strong> to revoke a validator's login. Their serving history is preserved. Click <strong>Activate</strong> to restore access.</p>
  </div>

  <div class="role-section-admin">
    <h3>Admin Entering Validator Mode</h3>
    <p>The admin can operate as a validator directly — no separate PIN needed. On the Validators page, click the <strong>Enter Validator Mode</strong> button. This opens the full validator check-in screen under the admin's session. To return to the admin panel, use the <strong>Back to Admin</strong> link at the top of the validator screen.</p>
  </div>

  <div class="callout callout-info">
    <span class="callout-icon">ℹ️</span>
    <div>Individual validator accounts mean every plate served is attributed to a specific staff member — useful for accountability and post-event auditing.</div>
  </div>
</div>

<div class="divider">✿ ❀ ✿</div>

<!-- 07 Tickets -->
<div class="section" id="tickets">
  <div class="section-header">
    <div class="section-num">07</div>
    <span class="section-icon">🎟</span>
    <h2>Admin — Tickets & VIP Bookings</h2>
  </div>

  <div class="role-section-admin">
    <h3>Viewing All Bookings</h3>
    <p>The <strong>All Bookings</strong> tab lists every booking. Use the filter bar to narrow by search text, agent, or booking type (Agent / Ad-hoc).</p>
  </div>

  <div class="role-section-admin">
    <h3>Generating a VIP / Ad-hoc Ticket</h3>
    <ol class="steps">
      <li>Go to <strong>Admin → Tickets → New VIP / Ad-hoc</strong> tab.</li>
      <li>Fill in guest name, contact number, and plate count.</li>
      <li>Click <strong>Generate Ad-hoc Ticket</strong>.</li>
      <li>A ticket with a QR code and secret code appears — print or photograph it.</li>
    </ol>
    <p>Ad-hoc tickets are recorded with type <em>adhoc</em> and attributed to Admin rather than a field agent.</p>
  </div>

  <div class="callout callout-info">
    <span class="callout-icon">ℹ️</span>
    <div>If a family has lost their secret code, search their house name in the Tickets table — every secret code is visible to the admin here.</div>
  </div>
</div>

<div class="divider">✿ ❀ ✿</div>

<!-- 08 Reports -->
<div class="section" id="reports">
  <div class="section-header">
    <div class="section-num">08</div>
    <span class="section-icon">📋</span>
    <h2>Admin — Reports & Export</h2>
  </div>

  <p>Go to <strong>Admin → Reports</strong> for the full reporting engine.</p>

  <table class="guide-table">
    <thead><tr><th>Filter</th><th>Options</th></tr></thead>
    <tbody>
      <tr><td>Search</td><td>Order ID, house name, owner name, contact number, secret code</td></tr>
      <tr><td>Agent</td><td>Show bookings by a specific volunteer</td></tr>
      <tr><td>Type</td><td>Agent bookings vs Ad-hoc / VIP</td></tr>
      <tr><td>Status</td><td>Unused (0 served) · Partial (some served) · Fully Consumed</td></tr>
      <tr><td>Date From / To</td><td>Filter by booking creation date range</td></tr>
    </tbody>
  </table>

  <p>The page also shows an <strong>Agent-wise Summary</strong> (bookings, plates, revenue per agent) and a <strong>Check-in Log</strong> of the 100 most recent plate servings with timestamps and the name of the validator who served each plate.</p>

  <div class="callout callout-success">
    <span class="callout-icon">📥</span>
    <div>Click <strong>CSV</strong> in the filter bar to download the current filtered results as an Excel-compatible spreadsheet, including all secret codes and amounts.</div>
  </div>
</div>

<div class="divider">✿ ❀ ✿</div>

<!-- 09 Settings -->
<div class="section" id="settings">
  <div class="section-header">
    <div class="section-num">09</div>
    <span class="section-icon">⚙️</span>
    <h2>Admin — Settings</h2>
  </div>

  <table class="guide-table">
    <thead><tr><th>Setting</th><th>Description</th></tr></thead>
    <tbody>
      <tr><td>Event Name</td><td>Shown on tickets, headers, and all printed materials</td></tr>
      <tr><td>Event Date</td><td>Displayed on the booking and login pages</td></tr>
      <tr><td>Event Venue</td><td>Informational — appears in the user guide and footer</td></tr>
      <tr><td>Price Per Plate (₹)</td><td>Auto-calculates totals for <em>new</em> bookings only</td></tr>
    </tbody>
  </table>

  <div class="callout callout-warning">
    <span class="callout-icon">⚠️</span>
    <div>Changing the price per plate only affects bookings created <strong>after</strong> the change. Existing bookings keep their original price.</div>
  </div>

  <p>Use <strong>Change Admin Password</strong> (at the bottom of Settings) to update the admin login password at any time.</p>
</div>

<div class="divider">✿ ❀ ✿</div>

<!-- 10 Agent -->
<div class="section" id="agent">
  <div class="section-header">
    <div class="section-num">10</div>
    <span class="section-icon">🏠</span>
    <h2>Agent — Booking a Family Door-to-Door</h2>
  </div>

  <div class="callout callout-info">
    <span class="callout-icon">📱</span>
    <div>The booking page is designed for mobile. Open it on your phone browser, bookmark it, and keep it ready as you go house-to-house.</div>
  </div>

  <div class="role-section-agent">
    <h3>How to Book</h3>
    <ol class="steps">
      <li>Go to the login page, tap the <strong>Agent</strong> tab, enter your PIN, and tap <strong>Start Booking</strong>.</li>
      <li>Fill in the family's details: House Name, Owner Name, Contact Number.</li>
      <li>Enter the number of <strong>Sadhya plates</strong> they want. The total amount calculates automatically.</li>
      <li>Add any optional notes (e.g. "paying later", "2 children included").</li>
      <li>Tap <strong>Confirm & Generate Ticket</strong>.</li>
    </ol>
  </div>

  <div class="role-section-agent">
    <h3>The Generated Ticket</h3>
    <p>Once confirmed, the system generates:</p>
    <ul>
      <li>A unique <strong>Order ID</strong> (e.g. <span class="url-pill">ARV3F4A2B1</span>)</li>
      <li>A unique <strong>6-character Secret Code</strong> the family can use at the venue</li>
      <li>A scannable <strong>QR Code</strong> that encodes the secret code</li>
    </ul>
  </div>

  <div class="role-section-agent">
    <h3>Sharing the Ticket</h3>
    <table class="guide-table">
      <thead><tr><th>Button</th><th>Action</th></tr></thead>
      <tbody>
        <tr><td>🖨 Print</td><td>Opens browser print dialog for a physical copy</td></tr>
        <tr><td>📤 Share</td><td>Opens native phone share sheet — send via WhatsApp, SMS, etc. On desktop, copies text to clipboard.</td></tr>
        <tr><td>➕ New Booking</td><td>Clears the form for the next household</td></tr>
        <tr><td>🚪 Logout</td><td>Ends your session (button is in the page header)</td></tr>
      </tbody>
    </table>
    <div class="callout callout-tip">
      <span class="callout-icon">💡</span>
      <div>Ask families to <strong>screenshot the ticket</strong> or note the 6-character secret code. They will need either the QR code or the secret code when they arrive at the venue.</div>
    </div>
  </div>
</div>

<div class="divider">✿ ❀ ✿</div>

<!-- 11 Validator -->
<div class="section" id="validator">
  <div class="section-header">
    <div class="section-num">11</div>
    <span class="section-icon">🍛</span>
    <h2>Validator — Dining Hall Check-in</h2>
  </div>

  <p>Validators are stationed at the venue entrance. They verify each family's ticket and mark plates as served. Each validator logs in with their own personal PIN assigned by the admin.</p>

  <div class="role-section-validator">
    <h3>Logging In</h3>
    <ol class="steps">
      <li>Go to the login page, tap the <strong>Validator</strong> tab.</li>
      <li>Enter your personal Validator PIN and tap <strong>Enter Validator Mode</strong>.</li>
      <li>Your name appears at the top of the check-in screen.</li>
      <li>To logout when you are done, tap the <strong>🚪 Logout</strong> button at the top right of the screen.</li>
    </ol>
  </div>

  <div class="role-section-validator">
    <h3>Scanning a QR Code</h3>
    <ol class="steps">
      <li>Ensure the <strong>📷 Scan QR</strong> tab is selected.</li>
      <li>Tap <strong>▶ Start Camera</strong> and allow camera access when prompted.</li>
      <li>Point the camera at the family's QR code — the ticket details appear automatically.</li>
    </ol>
  </div>

  <div class="role-section-validator">
    <h3>Manual Code Entry</h3>
    <ol class="steps">
      <li>Tap the <strong>⌨️ Manual Entry</strong> tab.</li>
      <li>Type the family's 6-character secret code (the field auto-capitalises).</li>
      <li>Tap <strong>🔍 Look Up</strong>.</li>
    </ol>
  </div>

  <div class="role-section-validator">
    <h3>Serving Plates — Single or Multiple at Once</h3>
    <p>The booking card shows how many plates have been served and how many remain. A visual dot row shows each plate's status (🟢 available / 🍽 served).</p>
    <p>If two or more family members arrive together and scan once, you can serve all of them in a single step:</p>
    <ol class="steps">
      <li>In the <strong>Mark Plates as Served</strong> section, the first row is already shown. Choose the first attendee's <strong>Relation to House Owner</strong> from the dropdown (Self, Spouse, Son, Daughter, Guest, Neighbour, Other, etc.).</li>
      <li>To add another person, tap <strong>+ Add Another Person</strong>. A new row appears. Fill in their relation.</li>
      <li>Repeat for each additional person arriving together. You can add as many rows as remaining plates allow.</li>
      <li>To remove an extra row, tap the <strong>✕</strong> button beside that row.</li>
      <li>Tap <strong>✅ Confirm & Serve</strong> to record all plates in one go.</li>
      <li>The counter updates immediately, showing the new remaining count.</li>
    </ol>
    <div class="callout callout-tip">
      <span class="callout-icon">💡</span>
      <div>You cannot add more rows than the number of remaining plates — the system prevents over-serving automatically.</div>
    </div>
    <div class="callout callout-info">
      <span class="callout-icon">📜</span>
      <div>Every plate serving is permanently recorded with an exact timestamp, the relation, and your validator name — creating an accurate audit trail.</div>
    </div>
  </div>
</div>

<div class="divider">✿ ❀ ✿</div>

<!-- 12 Codes -->
<div class="section" id="codes">
  <div class="section-header">
    <div class="section-num">12</div>
    <span class="section-icon">🔑</span>
    <h2>Secret Code & QR Code Explained</h2>
  </div>

  <p>Every booking gets a unique 6-character secret code displayed like this:</p>
  <div class="code-demo">
    <div class="code-char-demo">A</div>
    <div class="code-char-demo">7</div>
    <div class="code-char-demo">R</div>
    <div class="code-char-demo">3</div>
    <div class="code-char-demo">Z</div>
    <div class="code-char-demo">K</div>
  </div>

  <p><strong>Characters used:</strong> digits 2–9 and uppercase letters A–Z <em>excluding O</em>.</p>

  <div class="callout callout-success">
    <span class="callout-icon">✅</span>
    <div><strong>Deliberately excluded</strong> characters that look similar to each other: <strong>0</strong> (zero), <strong>1</strong> (one), <strong>O</strong> (capital O), <strong>l</strong> (lowercase L), <strong>o</strong> (lowercase O). This means families can safely read the code aloud or write it down without confusion.</div>
  </div>

  <table class="guide-table">
    <thead><tr><th>Code Type</th><th>Format</th><th>How to Use</th></tr></thead>
    <tbody>
      <tr><td>Secret Code</td><td>6 uppercase characters (e.g. A7R3ZK)</td><td>Type in Manual Entry at the validator screen</td></tr>
      <tr><td>QR Code</td><td>Scannable image encoding the secret code</td><td>Point camera at it — same as typing the code</td></tr>
      <tr><td>Order ID</td><td>Starts with ARV (e.g. ARV3F4A2B1)</td><td>Can also be entered in Manual Entry</td></tr>
    </tbody>
  </table>

  <div class="callout callout-info">
    <span class="callout-icon">ℹ️</span>
    <div>If a family loses their code, the admin can find it by searching the house name or owner name in <strong>Admin → Tickets</strong>.</div>
  </div>
</div>

<div class="divider">✿ ❀ ✿</div>

<!-- 13 Fraud -->
<div class="section" id="fraud">
  <div class="section-header">
    <div class="section-num">13</div>
    <span class="section-icon">🚨</span>
    <h2>Fraud & Limit Reached Handling</h2>
  </div>

  <p>When a code is scanned but all plates for that booking have already been served, the validator sees a high-visibility red alert:</p>

  <div class="alert-sim">
    <strong>⛔ LIMIT REACHED</strong><br>
    All 4 plate(s) for "Kaveri Nivas" have been served.<br><br>
    • Self — 11:32:04 AM<br>
    • Spouse — 11:32:51 AM<br>
    • Son — 11:45:10 AM<br>
    • Daughter — 11:45:38 AM
  </div>

  <p>The validator must <strong>not</strong> serve an additional plate. Direct the family to the event coordinator.</p>

  <table class="guide-table">
    <thead><tr><th>Scenario</th><th>System Response</th><th>Action</th></tr></thead>
    <tbody>
      <tr><td>Valid code, plates remaining</td><td>Shows booking card with green status</td><td>Serve plate(s) normally</td></tr>
      <tr><td>Valid code, partially consumed</td><td>Shows booking card, yellow partial status</td><td>Serve remaining plates</td></tr>
      <tr><td>Valid code, all plates used</td><td>Red LIMIT REACHED alert with full history</td><td>Do not serve; refer to coordinator</td></tr>
      <tr><td>Tried to serve more than remaining</td><td>Error: "Only N plate(s) remaining, but you tried to serve M"</td><td>Remove extra rows until count matches remaining</td></tr>
      <tr><td>Invalid / unknown code</td><td>"Invalid code. No booking found."</td><td>Ask family to double-check their code</td></tr>
    </tbody>
  </table>

  <div class="callout callout-info">
    <span class="callout-icon">📜</span>
    <div>Every plate serving is permanently stored with an exact timestamp and relation. The full history is visible in <strong>Admin → Reports → Check-in Log</strong> for audit purposes.</div>
  </div>
</div>

<div class="divider">✿ ❀ ✿</div>

<!-- 14 Checklist -->
<div class="section" id="checklist">
  <div class="section-header">
    <div class="section-num">14</div>
    <span class="section-icon">✅</span>
    <h2>Event Day Checklist</h2>
  </div>

  <p><strong>Before the event (Admin):</strong></p>
  <ul class="checklist">
    <li>Price per plate confirmed in Settings</li>
    <li>Event name, date, and venue are set correctly</li>
    <li>All agents have been added with their PINs and confirmed active</li>
    <li>All validators have been added with individual PINs (Admin → Validators) and confirmed active</li>
    <li>Each validator has been given their personal PIN privately</li>
    <li>Each agent has confirmed their PIN works on the login page</li>
    <li>At least one test booking created and verified end-to-end</li>
    <li>Validator staff have tested camera QR scanning on their device</li>
    <li>Backup manual entry confirmed working (in case of camera issues)</li>
    <li>Multi-plate serving tested: two or more people served in a single scan</li>
  </ul>

  <p style="margin-top:16px;"><strong>On the day (Validators):</strong></p>
  <ul class="checklist">
    <li>Phone charged and browser open to Validator login page</li>
    <li>Logged in with your personal Validator PIN</li>
    <li>Camera permission granted in the browser</li>
    <li>Bright lighting at the scanning point (helps QR read faster)</li>
    <li>Know how to switch to Manual Entry if QR scan fails</li>
    <li>Know how to add multiple relation rows for families arriving together</li>
    <li>Know how to reach the admin if a family reports a missing booking</li>
    <li>Logout when your shift ends (🚪 Logout button on validator screen)</li>
  </ul>

  <p style="margin-top:16px;"><strong>On the day (Agents, if still collecting):</strong></p>
  <ul class="checklist">
    <li>Phone charged with booking page bookmarked</li>
    <li>Each ticket shared via WhatsApp/SMS immediately after booking</li>
    <li>Remind families to screenshot their ticket or note the secret code</li>
  </ul>
</div>

<div class="divider">✿ ❀ ✿</div>

<!-- 15 Troubleshooting -->
<div class="section" id="trouble">
  <div class="section-header">
    <div class="section-num">15</div>
    <span class="section-icon">🔧</span>
    <h2>Troubleshooting</h2>
  </div>

  <table class="guide-table">
    <thead><tr><th>Problem</th><th>Likely Cause</th><th>Fix</th></tr></thead>
    <tbody>
      <tr>
        <td>Camera won't open on validator page</td>
        <td>Camera permission denied, or HTTP instead of HTTPS</td>
        <td>Allow camera in browser settings; ensure site runs on HTTPS (SSL certificate required for camera access on iOS/Android)</td>
      </tr>
      <tr>
        <td>QR code not scanning</td>
        <td>Poor lighting or screen glare</td>
        <td>Switch to Manual Entry; increase screen brightness on the family's phone; move to better light</td>
      </tr>
      <tr>
        <td>Agent PIN not working</td>
        <td>Wrong PIN, or agent marked Inactive</td>
        <td>Admin → Agents — verify the PIN and check the agent is Active</td>
      </tr>
      <tr>
        <td>Validator PIN not working</td>
        <td>Wrong PIN, or validator marked Inactive, or PIN not yet created</td>
        <td>Admin → Validators — verify the PIN and check the validator is Active. Each validator must have their own individual account created by the admin.</td>
      </tr>
      <tr>
        <td>"Invalid code" when scanning</td>
        <td>Wrong code entered, or booking in different system</td>
        <td>Ask family for Order ID (starts with ARV) as alternative; admin can search by name</td>
      </tr>
      <tr>
        <td>"Only N plate(s) remaining" error</td>
        <td>Too many relation rows added</td>
        <td>Remove extra rows using the ✕ button until the number matches the remaining plate count</td>
      </tr>
      <tr>
        <td>Booking form shows "Network error"</td>
        <td>APP_URL misconfigured, or api/ folder missing</td>
        <td>Check APP_URL in config/database.php matches the actual domain exactly</td>
      </tr>
      <tr>
        <td>CSV opens garbled in Excel</td>
        <td>Encoding issue</td>
        <td>Open Excel → Data → From Text/CSV → select UTF-8 encoding manually</td>
      </tr>
      <tr>
        <td>Family lost their ticket</td>
        <td>Did not screenshot or save ticket</td>
        <td>Admin → Tickets → search house name/owner — secret code is shown in the table</td>
      </tr>
      <tr>
        <td>Price changed but old bookings show old price</td>
        <td>By design</td>
        <td>Each booking locks in the price at time of booking. Only new bookings use the updated price.</td>
      </tr>
    </tbody>
  </table>

  <div class="callout callout-tip">
    <span class="callout-icon">💡</span>
    <div>For any issue not covered here, the Admin can see all data in Reports and Tickets, and can look up any booking by any detail (name, phone, order ID, or secret code).</div>
  </div>
</div>

</div><!-- /guide-body -->

<!-- ══ FOOTER ════════════════════════════════════════════════════════════ -->
<div class="guide-footer">
  <div>
    <img src="<?= APP_URL ?>/assets/img/mmg-logo.png" alt="Meta Mates Group"
         style="height:50px;width:auto;max-width:90px;object-fit:contain;"
         onerror="this.style.display='none'">
  </div>
  <div class="guide-footer-text">
    <div><strong>Meta Mates Group</strong> &mdash; Official Sponsor</div>
    <div style="margin-top:4px;"><?= htmlspecialchars($eventName) ?> &mdash; Sadhya Ticketing System</div>
    <?php if ($displayDate): ?>
    <div style="margin-top:2px;opacity:.7;"><?= htmlspecialchars($displayDate) ?><?= $eventVenue ? ' &bull; ' . htmlspecialchars($eventVenue) : '' ?></div>
    <?php endif; ?>
  </div>
  <div>
    <img src="<?= APP_URL ?>/assets/img/aaravam-logo.png" alt="Aaravam 2026"
         style="height:60px;width:auto;max-width:120px;object-fit:contain;"
         onerror="this.style.display='none'">
  </div>
</div>

</body>
</html>
