/* DevLib · Slider */
(function (Devlib) {
  'use strict';
  const {$, $$, idNum, fmt, Tf} = Devlib.h;
  // Slider: angka / tanggal, satu gagang / rentang, horizontal / vertikal.
  class Slider extends Devlib.Component {
    static selector = "[data-dl-slider]";
    init() {
      [this.ia, this.ib] = $$(this.el, '.dl-slider__input');
      this.ha = $(this.el, '[data-dl-slider-a]');
      this.hb = $(this.el, '[data-dl-slider-b]');
      this.out = $(this.el, '[data-dl-slider-value]');
      this.min = +this.ia.min;
      this.max = +this.ia.max;
      this.date = this.el.dataset.kind === 'date';
      this.s0 = this.date ? Date.parse(this.el.dataset.start + 'T00:00:00Z') : 0;
      // Kotak range tidak bernama: hidden yang melapor ke dirty-guard; Devlib.fill / Undo menulis hidden lalu menembak input.
      this.hid = [this.ha, this.hb].filter(Boolean);
      [this.ia, this.ib].filter(Boolean).forEach(i => i.addEventListener('input', e => {
        this.u(e); this.own = true;
        this.hid.forEach(h => h.dispatchEvent(new Event('input', {bubbles: true})));
        this.own = false;
      }));
      this.hid.forEach(h => h.addEventListener('input', () => { if (!this.own) this.sync(); }));
      this.u();
    }

    // hidden -> gagang (tanggal: hari sejak awal); nilai tak terbaca dilewati.
    sync() {
      const set = (h, i) => { const v = this.date ? Math.round((Date.parse(h.value + 'T00:00:00Z') - this.s0) / 86400000) : parseFloat(h.value); if (!isNaN(v)) i.value = v; };
      set(this.ha, this.ia); if (this.ib) set(this.hb, this.ib);
      this.draw();
    }

    iso(n) {
      return new Date(this.s0 + n * 86400000).toISOString().slice(0, 10);
    }

    show(n) {
      return this.date ? fmt.date(this.iso(n) + 'T12:00:00+07:00') : this.el.dataset.format.replace('{v}', idNum(n));
    }

    pct(v) {
      return this.max === this.min ? 0 : (v - this.min) / (this.max - this.min) * 100;
    }

    u(e) {
      if (this.ib && +this.ia.value > +this.ib.value) { if (e && e.target === this.ib) this.ib.value = this.ia.value; else this.ia.value = this.ib.value; }
      this.ha.value = this.date ? this.iso(+this.ia.value) : +this.ia.value; if (this.hb) this.hb.value = this.date ? this.iso(+this.ib.value) : +this.ib.value;
      this.draw();
    }

    draw() {
      const a = +this.ia.value, b = this.ib ? +this.ib.value : a;
      this.el.style.setProperty('--a', this.ib ? this.pct(a) : 0); this.el.style.setProperty('--b', this.pct(b));
      this.ia.style.zIndex = this.ib && a >= this.max - (this.max - this.min) * 0.02 ? 3 : 1;
      this.out.textContent = this.ib ? `${this.show(a)} – ${this.show(b)}` + (this.date ? ' · ' + Tf('sl_days', {n: b - a + 1}) : '') : this.show(a);
    }
  }
  Devlib.define("Slider", Slider);
})(window.Devlib);
