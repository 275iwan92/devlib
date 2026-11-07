/* DevLib · Slideshow */
(function (Devlib) {
  'use strict';
  const {$, $$} = Devlib.h;

  // Slideshow modal (x-dl::slideshow). Dibuka oleh data-dl-open (modal library menambah .is-on); komponen ini mengamati kelas itu,
  // mulai dari slide 1 setiap dibuka, dan memuat gambar hanya saat slide tampil. Panah kiri/kanan = slide lain (hanya saat terbuka).
  class Slideshow extends Devlib.Component {
    static selector = "[data-dl-slideshow]";
    init() {
      this.items = JSON.parse(this.el.dataset.items || '[]');
      this.i = 0;
      this.img = $(this.el, '[data-sl-img]'); this.cap = $(this.el, '[data-sl-cap]'); this.num = $(this.el, '[data-sl-n]');
      this.prev = $(this.el, '[data-sl-prev]'); this.next = $(this.el, '[data-sl-next]');
      this.dots = $(this.el, '[data-sl-dots]');
      this.items.forEach((_, k) => { const d = document.createElement('button'); d.type = 'button'; d.setAttribute('aria-label', String(k + 1)); d.addEventListener('click', () => this.go(k)); this.dots.appendChild(d); });
      this.prev.addEventListener('click', () => this.go(this.i - 1));
      this.next.addEventListener('click', () => this.go(this.i + 1));
      document.addEventListener('keydown', e => {
        if (!this.el.classList.contains('is-on')) return;
        if (e.key === 'ArrowLeft') this.go(this.i - 1); else if (e.key === 'ArrowRight') this.go(this.i + 1);
      });
      new MutationObserver(() => { if (this.el.classList.contains('is-on')) this.go(0); }).observe(this.el, {attributes: true, attributeFilter: ['class']});
      if (this.el.classList.contains('is-on')) this.go(0);
    }

    go(k) {
      const n = this.items.length; if (!n) return;
      this.i = Math.max(0, Math.min(n - 1, k));
      const it = this.items[this.i];
      this.img.src = it.src; this.img.alt = it.alt || it.caption || '';
      this.cap.textContent = it.caption || '';
      this.num.textContent = (this.i + 1) + ' / ' + n;
      this.prev.disabled = this.i === 0; this.next.disabled = this.i === n - 1;
      $$(this.dots, 'button').forEach((d, j) => d.classList.toggle('is-on', j === this.i));
    }
  }
  Devlib.define("Slideshow", Slideshow);
})(window.Devlib);
