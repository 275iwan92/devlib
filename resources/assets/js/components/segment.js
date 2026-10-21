/* DevLib · Segment */
(function (Devlib) {
  'use strict';
  const {$, $$} = Devlib.h;

  class Segment extends Devlib.Component {
    static selector = "[data-dl-segment]";
    init() {
      this.hidden = $(this.el, '[data-dl-value]');
      const mark = v => $$(this.el, '.dl-segment__opt').forEach(x => x.classList.toggle('is-active', x.dataset.value === v));
      $$(this.el, '.dl-segment__opt').forEach(b => b.addEventListener('click', () => {
        mark(b.dataset.value);
        this.hidden.value = b.dataset.value; this.hidden.dispatchEvent(new Event('change', {bubbles: true}));
      }));
      // Devlib.fill / Undo menulis nilainya lalu menembak input: tombol yang menyala ikut.
      this.hidden.addEventListener('input', () => mark(this.hidden.value));
    }
  }
  Devlib.define("Segment", Segment);
})(window.Devlib);
