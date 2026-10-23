/* DevLib · PhoneShell */
(function (Devlib) {
  'use strict';
  const {$, $$} = Devlib.h;

  class PhoneShell extends Devlib.Component {
    static selector = "[data-dl-phone]";
    init() {
      this.sheet = $(this.el, '[data-dl-phone-sheet]');
      this.btn = $(this.el, '[data-dl-phone-menu]');
      this.btn.addEventListener('click', () => this.toggle());
      $$(this.el, '[data-dl-phone-close]').forEach(x => x.addEventListener('click', () => this.toggle(false)));
      this.el.addEventListener('keydown', e => { if (e.key === 'Escape') this.toggle(false); });
    }

    // Lembar Menu dari bawah; tab Menu tetap terlihat dan menutupnya lagi.
    toggle(open = this.sheet.hidden) {
      this.sheet.hidden = !open;
      this.btn.setAttribute('aria-expanded', String(open));
      this.btn.classList.toggle('is-open', open);
    }
  }
  Devlib.define("PhoneShell", PhoneShell);
})(window.Devlib);
