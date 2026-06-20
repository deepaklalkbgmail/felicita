<div class="footer-sponsor">
  <span>Powered by</span>
  <img src="<?= APP_URL ?>/assets/img/mmg-logo.png" alt="Meta Mates Group" onerror="this.style.display='none'">
  <strong>Meta Mates Group</strong>
</div>

<div class="floral-divider">✿ ❀ ✿ ❀ ✿</div>
<footer style="text-align:center;padding:16px;font-size:.78rem;color:#9a7030;background:rgba(200,150,12,.06);border-top:1px solid rgba(200,150,12,.15);">
  <?= APP_NAME ?> &mdash; Onam Sadhya Ticketing System &copy; <?= date('Y') ?>
</footer>

<!-- Page Loader (shown on every page, fades out after load) -->
<div id="page-loader">
  <div class="loader-logo-wrap">
    <img
      src="<?= APP_URL ?>/assets/img/mmg-logo.png"
      alt="Meta Mates Group"
      class="loader-mmg-img"
      onerror="this.style.display='none';document.getElementById('loader-fallback').style.display='block'">
    <div id="loader-fallback" style="display:none;font-size:3rem;">⬡</div>
    <div class="loader-brand-name">Meta Mates Group</div>
    <div class="loader-tagline">presents</div>
    <div class="loader-dots"><span></span><span></span><span></span></div>
  </div>
  <div class="loader-sponsor"><?= APP_NAME ?> — Onam Sadhya Manager</div>
</div>

<script>
// ── Hide loader after page is fully loaded ──────────────────────────────
window.addEventListener('load', function () {
  var loader = document.getElementById('page-loader');
  if (loader) {
    // Small delay so the animation is visible even on fast connections
    setTimeout(function () { loader.classList.add('hidden'); }, 700);
  }
});

// Flash message utility
function showFlash(msg, type='info'){
  const w = document.getElementById('flash-wrap');
  if(!w) return;
  const d = document.createElement('div');
  d.className = `alert alert-${type}`;
  d.style.cssText='position:fixed;top:70px;left:50%;transform:translateX(-50%);z-index:9998;min-width:280px;max-width:90vw;box-shadow:0 4px 20px rgba(0,0,0,.2)';
  d.innerHTML = `<span>${msg}</span>`;
  w.appendChild(d);
  setTimeout(()=>{ d.style.opacity='0'; d.style.transition='opacity .4s'; setTimeout(()=>d.remove(),400); }, 3500);
}

function formatMoney(n){ return '₹' + parseFloat(n).toLocaleString('en-IN', {minimumFractionDigits:2}); }
</script>
</body>
</html>
