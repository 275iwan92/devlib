/* DevLib · PhoneShell */
(function (Devlib) {
  'use strict';
  const {$, $$} = Devlib.h;

  class PhoneShell extends Devlib.Component {
    static selector = "[data-dl-phone]";
    init() {
      this.sheet = $(this.el, '[data-dl-phone-sheet]');
      this.btns = $$(this.el, '[data-dl-phone-menu]');   // tab Menu dan hamburger membuka lembar yang sama
      this.panel = $(this.el, '[data-dl-phone-panel]');
      this.btns.forEach(b => b.addEventListener('click', () => this.toggle()));
      $$(this.el, '[data-dl-phone-close]').forEach(x => x.addEventListener('click', () => this.toggle(false)));
      this.el.addEventListener('keydown', e => { if (e.key === 'Escape') this.toggle(false); });
    }

    // Lembar Menu dari bawah; tab Menu tetap terlihat dan menutupnya lagi.
    toggle(open = this.sheet.hidden) {
      if (open) this.opener = document.activeElement;
      this.sheet.hidden = !open;
      this.btns.forEach(b => { b.setAttribute('aria-expanded', String(open)); b.classList.toggle('is-open', open && b.classList.contains('dl-phone__tab')); });
      if (open) this.panel.focus({preventScroll: true}); else if (this.opener) this.opener.focus({preventScroll: true});
    }
  }
  Devlib.define("PhoneShell", PhoneShell);
})(window.Devlib);
