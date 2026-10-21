/* DevLib · NumberWords */
(function (Devlib) {
  'use strict';
  const {$, $$, words, fmt, NUM, fillState} = Devlib.h;

  class NumberWords extends Devlib.Component {
    static selector = "[data-dl-words]";
    init() {
      this.C = JSON.parse(this.el.dataset.currencies);
      this.inp = $(this.el, '[data-dl-words-input]');
      this.hid = $(this.el, '[data-dl-words-value]');
      this.out = $(this.el, '[data-dl-words-text]');
      this.cc = $(this.el, '[data-dl-prefix-value]');
      // Kotak ketik tidak bernama: hidden yang melapor ke dirty-guard; Devlib.fill / Undo menulis hidden lalu menembak input.
      this.inp.addEventListener('input', () => {
        this.upd(); this.own = true;
        this.hid.dispatchEvent(new Event('input', {bubbles: true}));
        this.own = false;
      });
      this.hid.addEventListener('input', () => { if (!this.own) this.sync(); });
      $$(this.el, '.dl-dd__item').forEach(it => it.addEventListener('click', () => { this.upd(); if (!fillState.on) this.hid.dispatchEvent(new Event('change', {bubbles: true})); }));
      this.sync();
    }

    // hidden (titik desimal kanonik) -> kotak ketik dengan pemisah desimal bahasa tampilan.
    sync() {
      this.inp.value = String(this.hid.value).replace('.', NUM.dec);
      this.upd();
    }

    places() {
      return this.cc ? this.C[this.cc.value][5] : +this.el.dataset.decimals;
    }

    upd() {
      const p = this.places(), neg = this.inp.value.trim().startsWith('-');
      // Pemisah desimal ikut bahasa tampilan (id koma, en titik); nilai kanonik dikirim lewat input tersembunyi.
      const D = NUM.dec;
      let [a, b] = this.inp.value.replace(D === '.' ? /[^\d.]/g : /[^\d,]/g, '').split(D);
      a = (a || '').replace(/^0+(?=\d)/, '').slice(0, 15);
      const hasComma = p > 0 && this.inp.value.includes(D);
      b = p > 0 ? (b || '').slice(0, p) : '';
      this.inp.value = (neg ? '-' : '') + (a ? fmt.number(+a) : (hasComma ? '0' : '')) + (hasComma ? D + b : '');
      this.hid.value = a || b ? (neg ? '-' : '') + (a || '0') + (b ? '.' + b : '') : '';
      this.out.textContent = this.hid.value ? words(this.hid.value, this.cc ? this.C[this.cc.value] : null) : '';
      this.out.classList.toggle('is-empty', !this.hid.value);
    }
  }
  Devlib.define("NumberWords", NumberWords);
})(window.Devlib);
