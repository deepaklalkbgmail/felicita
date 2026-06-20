/* Aaravam 2026 — Global JS utilities */

// Auto-uppercase secret code inputs
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('input[autocapitalize="characters"]').forEach(el => {
    el.addEventListener('input', function(){ this.value = this.value.toUpperCase(); });
  });

  // Smooth tab switching (generic)
  document.querySelectorAll('[data-tab-target]').forEach(btn => {
    btn.addEventListener('click', function(){
      const group = this.closest('[data-tab-group]');
      if(!group) return;
      group.querySelectorAll('[data-tab-target],[data-tab-panel]').forEach(el => el.classList.remove('active'));
      this.classList.add('active');
      group.querySelector(`[data-tab-panel="${this.dataset.tabTarget}"]`)?.classList.add('active');
    });
  });
});
