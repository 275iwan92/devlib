/* DevLib · PhoneShell */
(function (Devlib) {
  'use strict';
  const {$, $$} = Devlib.h;

  class PhoneShell extends Devlib.Component {
    static selector = "[data-dl-phone]";
    init() {
      this.sheet = $(this.el, '[data-dl-phone-sheet]');
      this.btn = $(this.el, '[data-dl-phone-menu]');   // satu tombol hamburger = satu-satunya pembuka menu
      this.panel = $(this.el, '[data-dl-phone-panel]');
      this.btn.addEventListener('click', () => this.toggle());
      $$(this.el, '[data-dl-phone-close]').forEach(x => x.addEventListener('click', () => this.toggle(false)));
      this.el.addEventListener('keydown', e => { if (e.key === 'Escape') this.toggle(false); });
    }

    // Panel menu dari atas menutupi isi; ikon berganti ≡ / X, fokus masuk ke panel dan kembali ke tombol saat ditutup.
    toggle(open = this.sheet.hidden) {
      if (open === !this.sheet.hidden) return;
      this.sheet.hidden = !open;
      this.btn.setAttribute('aria-expanded', String(open));
      this.btn.firstElementChild.className = open ? 'fa-solid fa-xmark' : 'fa-solid fa-bars';
      if (open) this.panel.focus({preventScroll: true}); else this.btn.focus({preventScroll: true});
    }
  }
  Devlib.define("PhoneShell", PhoneShell);
})(window.Devlib);
