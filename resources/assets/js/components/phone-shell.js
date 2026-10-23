/* DevLib · PhoneShell */
(function (Devlib) {
  'use strict';
  const {$, $$} = Devlib.h;
  const HIDE_MS = 6000;

  // Satu ikon ≡ MELAYANG (tanpa bilah atas permanen) berputar tiga keadaan:
  //   A = ikon saja; B = deret AKSI layar (form: .dl-form__acts, grid: .dl-grid__toolbar; hanya bila ada); C = lima menu utama (+ 'Menu' = daftar semua modul).
  //   Layar tanpa aksi: A -> C -> A. Awal setiap layar = A. B dan C tertutup sendiri (menggulir, ketuk di luar, memilih menu, diam 6 detik), kecuali B saat form kotor.
  class PhoneShell extends Devlib.Component {
    static selector = "[data-dl-phone]";
    static ICON = {A: 'fa-bars', B: 'fa-floppy-disk', C: 'fa-table-cells-large'};

    init() {
      this.btn = $(this.el, '[data-dl-phone-menu]');
      this.drop = $(this.el, '[data-dl-phone-drop]');
      this.list = $(this.el, '[data-dl-phone-list]');
      this.more = $(this.el, '[data-dl-phone-more]');
      this.live = $(this.el, '[data-dl-phone-live]');
      this.frame = $(this.el, '.dl-phone__frame');
      this.main = $(this.el, '.dl-phone__main');
      this.L = {A: this.el.dataset.lA, B: this.el.dataset.lB, C: this.el.dataset.lC, dirty: this.el.dataset.lDirty, leave: this.el.dataset.lLeave};
      this.state = 'A';
      this.btn.addEventListener('click', () => this.set(this.next(), true));
      this.more.addEventListener('click', () => { this.list.hidden = !this.list.hidden; this.more.setAttribute('aria-expanded', String(!this.list.hidden)); this.arm(); });
      $$(this.el, '[data-dl-phone-close]').forEach(x => x.addEventListener('click', () => this.set('A', true)));
      this.el.addEventListener('keydown', e => { if (e.key === 'Escape' && this.state !== 'A') this.set('A', true); this.arm(); });
      this.el.addEventListener('pointerdown', () => this.arm());
      // Pindah layar dari menu: bila form kotor, tanya dulu (Simpan tidak boleh hilang tanpa disadari).
      $$(this.el, '.dl-phone__quick a, .dl-phone__list a').forEach(a => a.addEventListener('click', e => { if (this.dirty() && !window.confirm(this.L.leave)) e.preventDefault(); }));
      this.main.addEventListener('scroll', () => this.scrolled(), {passive: true});
      if (this.frame) this.frame.addEventListener('load', () => this.attach()); else this.attach();
    }

    // Dokumen yang memuat aksi layar: iframe (halaman desktop di dalam kerangka) atau isi kerangka sendiri.
    doc() {
      if (!this.frame) return this.el;
      try { return this.frame.contentDocument; } catch (e) { return null; }
    }

    hasActions() { return !!this.doc()?.querySelector('.dl-form__acts, .dl-grid__toolbar'); }

    dirty() { return !!this.doc()?.querySelector('form.is-dirty'); }

    // Layar baru (atau iframe selesai dimuat): kembali ke A, tandai dokumen ada di dalam kerangka, pantau form kotor, tutup saat ketuk di luar / menggulir.
    attach() {
      const d = this.doc();
      this.obs?.disconnect();
      if (d && this.frame) {
        d.body.classList.add('dl-phone-shelled');
        d.addEventListener('scroll', () => this.scrolled(), true);
        d.addEventListener('pointerdown', e => { if (this.state === 'B' && !e.target.closest('.dl-form__acts, .dl-grid__toolbar, .dl-pop2__panel, .dl-grid__expmenu') && !this.dirty()) this.set('A'); }, true);
      }
      if (d) {
        this.obs = new MutationObserver(() => this.mark());
        this.obs.observe(this.frame ? d.documentElement : this.el, {attributes: true, subtree: true, attributeFilter: ['class']});
      }
      this.set('A');
    }

    mark() {
      const dirty = this.dirty();
      this.btn.classList.toggle('has-dirty', dirty);
      this.btn.title = dirty ? this.L.dirty : '';
    }

    next() { return {A: this.hasActions() ? 'B' : 'C', B: 'C', C: 'A'}[this.state]; }

    set(s, user) {
      this.state = s;
      this.at = Date.now();
      this.el.dataset.state = s;
      this.doc()?.body?.classList?.toggle('dl-phone-acts', s === 'B');
      this.drop.hidden = s !== 'C';
      this.list.hidden = true;
      this.more.setAttribute('aria-expanded', 'false');
      this.btn.setAttribute('aria-expanded', String(s !== 'A'));
      this.btn.setAttribute('aria-label', this.L[s]);
      this.btn.firstElementChild.className = 'fa-solid ' + PhoneShell.ICON[s];
      this.live.textContent = this.L[s];
      this.mark();
      this.arm();
      if (!user) return;
      if (s === 'A') this.btn.focus({preventScroll: true});
      else if (s === 'B') this.doc()?.querySelector('.dl-form__acts .dl-btn:not([disabled]), .dl-grid__toolbar .dl-btn')?.focus?.({preventScroll: true});
      else $(this.el, '.dl-phone__quick a')?.focus({preventScroll: true});
    }

    // Fokus sedang di dalam deret (mengetik cari, memilih saringan): jangan ditutup.
    busy() {
      const d = this.doc(), a = d?.activeElement;
      return !!a && (this.state === 'B' ? !!a.closest?.('.dl-form__acts, .dl-grid__toolbar') : !!a.closest?.('.dl-phone__drop'));
    }

    // Tutup sendiri setelah diam; deret aksi form TIDAK tertutup sendiri saat form kotor (Simpan tidak boleh lenyap).
    arm() {
      clearTimeout(this.timer);
      if (this.state === 'A' || (this.state === 'B' && this.dirty())) return;
      this.timer = setTimeout(() => { if (this.busy() || !this.list.hidden) this.arm(); else this.set('A'); }, HIDE_MS);
    }

    scrolled() {
      if (this.state === 'A' || Date.now() - this.at < 500 || this.busy()) return;
      if (this.state === 'B' && this.dirty()) return;
      this.set('A');
    }
  }
  Devlib.define("PhoneShell", PhoneShell);
})(window.Devlib);
