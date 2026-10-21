/* DevLib · Rich */
(function (Devlib) {
  'use strict';
  const {$, $$, val} = Devlib.h;

  class Rich extends Devlib.Component {
    static selector = "[data-dl-rich]";
    init() {
      this.area = $(this.el, '.dl-rich__area');
      this.val = $(this.el, '[data-dl-rich-value]');
      $$(this.el, '[data-cmd]').forEach(b => b.addEventListener('mousedown', e => { e.preventDefault(); document.execCommand(b.dataset.cmd); this.sync(); }));
      this.area.addEventListener('input', this.sync);
      // Devlib.fill / Undo menulis textarea lalu menembak input: area ikut. Tulisan sync() sendiri sudah sama, jadi tidak berputar.
      this.val.addEventListener('input', () => { if (this.val.value !== this.area.innerHTML) this.area.innerHTML = this.val.value; });
    }
    // Textarea bernama itu yang melapor: dirty-guard dan penghapus pesan Required membacanya (bukan div tanpa nama).
    sync() { this.val.value = this.area.innerHTML; this.val.dispatchEvent(new Event('input', {bubbles: true})); }
  }
  Devlib.define("Rich", Rich);
})(window.Devlib);
