<div class="floral-divider">✿ ❀ ✿ ❀ ✿</div>
<footer style="text-align:center;padding:16px;font-size:.78rem;color:#9a7030;background:rgba(200,150,12,.06);border-top:1px solid rgba(200,150,12,.15);">
  <?= APP_NAME ?> &mdash; Onam Sadhya Ticketing System &copy; <?= date('Y') ?>
</footer>

<script>
// Flash message utility
function showFlash(msg, type='info'){
  const w = document.getElementById('flash-wrap');
  if(!w) return;
  const d = document.createElement('div');
  d.className = `alert alert-${type}`;
  d.style.cssText='position:fixed;top:70px;left:50%;transform:translateX(-50%);z-index:9999;min-width:280px;max-width:90vw;box-shadow:0 4px 20px rgba(0,0,0,.2)';
  d.innerHTML = `<span>${msg}</span>`;
  w.appendChild(d);
  setTimeout(()=>{ d.style.opacity='0'; d.style.transition='opacity .4s'; setTimeout(()=>d.remove(),400); }, 3500);
}

// Number formatter
function formatMoney(n){ return '₹' + parseFloat(n).toLocaleString('en-IN', {minimumFractionDigits:2}); }
</script>
</body>
</html>
