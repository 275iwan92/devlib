/* DevLib · PhoneShell */
(function (Devlib) {
  'use strict';
  const {$, $$} = Devlib.h;
  const HIDE_MS = 6000;
  const MAX_ACTS = 4;   // item aksi di bilah (di luar item Menu paling kanan); sisanya masuk daftar "Aksi lain"

  // Keadaan A = hanya tombol hamburger melayang. Diketuk: tombol MENGHILANG dan bilah selebar layar muncul di atas (menggantikannya):
  //   B = bilah AKSI layar (form: .dl-form__acts, grid: .dl-grid__toolbar, report / cetak: .dl-print__bar; hanya bila ada) dengan item Menu paling kanan;
  //   C = bilah LIMA MENU UTAMA dengan item Menu paling kanan (membuka daftar semua modul). Layar tanpa aksi: A -> C.
  // Bilah / daftar menyembunyi sendiri (memilih, ketuk di luar, menggulir, diam 6 detik); aksi form yang kotor tidak menyembunyi sendiri.
  // Aksi diambil dari halaman (iframe atau isi sendiri) dan dijalankan dengan click() pada tombol aslinya.
  class PhoneShell extends Devlib.Component {
    static selector = "[data-dl-phone]";
    static ICON = {A: 'fa-bars', B: 'fa-floppy-disk', C: 'fa-table-cells-large'};

    init() {
      this.btn = $(this.el, '[data-dl-phone-menu]');
      this.bar = $(this.el, '[data-dl-phone-bar]');
      this.acts = $(this.el, '[data-dl-phone-acts]');
      this.mainBar = $(this.el, '[data-dl-phone-main]');
      this.drop = $(this.el, '[data-dl-phone-drop]');
      this.list = $(this.el, '[data-dl-phone-list]');
      this.other = $(this.el, '[data-dl-phone-other]');
      this.more = $(this.el, '[data-dl-phone-more]');
      this.live = $(this.el, '[data-dl-phone-live]');
      this.frame = $(this.el, '.dl-phone__frame');
      this.content = $(this.el, '.dl-phone__main');
      const d = this.el.dataset;
      this.L = {A: d.lA, B: d.lB, C: d.lC, dirty: d.lDirty, leave: d.lLeave, menu: d.lMenu, search: d.lSearch, options: d.lOptions};
      this.state = 'A';
      this.overflow = [];
      this.btn.addEventListener('click', () => this.set(this.next(), true));
      this.more.addEventListener('click', () => this.toggleList());
      $$(this.el, '[data-dl-phone-close]').forEach(x => x.addEventListener('click', () => this.set('A', true)));
      this.el.addEventListener('keydown', e => { if (e.key === 'Escape' && this.state !== 'A') this.set('A', true); this.arm(); });
      this.el.addEventListener('pointerdown', () => this.arm());
      this.list.addEventListener('scroll', () => this.arm(), {passive: true});
      // Pindah layar dari menu: bila form kotor, tanya dulu (Simpan tidak boleh hilang tanpa disadari).
      $$(this.el, '.dl-phone__list a, [data-dl-phone-main] a').forEach(a => a.addEventListener('click', e => { if (this.dirty() && !window.confirm(this.L.leave)) e.preventDefault(); }));
      this.content.addEventListener('scroll', () => this.scrolled(), {passive: true});
      if (!this.frame) this.content.addEventListener('pointerdown', () => this.outside(), true);
      if (this.frame) this.frame.addEventListener('load', () => this.attach()); else this.attach();
    }

    // Dokumen yang memuat aksi layar: iframe (halaman desktop di dalam kerangka) atau isi kerangka sendiri.
    doc() {
      if (!this.frame) return this.content;
      try { return this.frame.contentDocument; } catch (e) { return null; }
    }

    dirty() { return !!this.doc()?.querySelector('form.is-dirty'); }

    // ---- aksi layar, dibaca dari halaman -------------------------------------------------------------------------------------------------
    // Teks tombol tanpa lencana / bagian tersembunyi (mis. jumlah saringan).
    label(el) {
      const c = el.cloneNode(true);
      c.querySelectorAll('[hidden], .dl-grid__badge').forEach(x => x.remove());
      return (c.textContent || el.getAttribute('title') || el.getAttribute('aria-label') || '').replace(/\s+/g, ' ').trim();
    }

    icon(el, fb) { return el.querySelector('i')?.className || fb; }

    off(el) { return !!(el.disabled || el.getAttribute('aria-disabled') === 'true' || el.classList.contains('is-disabled')); }

    item(el, extra = {}) {
      return Object.assign({label: this.label(el), icon: this.icon(el, 'fa-solid fa-circle'), disabled: this.off(el), run: () => el.click()}, extra);
    }

    items() {
      const d = this.doc();
      if (!d) return [];
      const q = s => [...d.querySelectorAll(s)];
      const form = q('.dl-form__acts .dl-btn').filter(b => !b.hidden && !b.closest('[hidden]'));
      if (form.length) {
        const list = form.map(b => this.item(b, {primary: b.dataset.fAct === 'save'}));
        return [...list.filter(i => i.primary), ...list.filter(i => !i.primary)];
      }
      const grid = d.querySelector('.dl-grid__toolbar');
      if (grid) {
        const g = s => d.querySelector(s);
        return [
          g('[data-g-search]') && {label: this.L.search, icon: 'fa-solid fa-magnifying-glass', run: () => this.openSearch(d)},
          g('[data-g-new]') && this.item(g('[data-g-new]'), {label: this.label(g('[data-g-new]')).replace(/^\+\s*/, ''), icon: 'fa-solid fa-plus', primary: true}),
          g('[data-g-toggle="filters"]') && this.item(g('[data-g-toggle="filters"]')),
          g('[data-g-toggle="columns"]') && this.item(g('[data-g-toggle="columns"]')),
          g('[data-g-export]') && this.item(g('[data-g-export]')),
        ].filter(Boolean);
      }
      const bar = d.querySelector('.dl-print__bar');
      if (bar) {
        const print = [...bar.querySelectorAll('button, a')].find(b => b.querySelector('.fa-print'));
        return [print && this.item(print, {primary: true}), {label: this.L.options, icon: 'fa-solid fa-sliders', run: () => d.body.classList.add('dl-phone-print')}].filter(Boolean);
      }
      return [];
    }

    hasActions() { return this.items().length > 0; }

    openSearch(d) {
      d.body.classList.add('dl-phone-search');
      d.querySelector('[data-g-search]')?.focus();
    }

    // ---- siklus layar --------------------------------------------------------------------------------------------------------------------
    // Layar baru (atau iframe selesai dimuat): kembali ke A, tandai dokumen ada di dalam kerangka, pantau form kotor, tutup saat ketuk di luar / menggulir.
    attach() {
      const d = this.doc();
      this.obs?.disconnect();
      if (d && this.frame) {
        d.body.classList.add('dl-phone-shelled');
        d.addEventListener('scroll', () => this.scrolled(), true);
        d.addEventListener('pointerdown', e => {
          if (!e.target.closest('.dl-print__bar')) d.body.classList.remove('dl-phone-print');
          if (!e.target.closest('.dl-grid__search')) { const s = d.querySelector('[data-g-search]'); if (s && !s.value) d.body.classList.remove('dl-phone-search'); }
          if (!e.target.closest('.dl-pop2__panel, .dl-grid__expmenu')) this.outside();
        }, true);
        d.addEventListener('focusout', e => { if (e.target.matches?.('[data-g-search]') && !e.target.value) d.body.classList.remove('dl-phone-search'); });
      }
      if (d) {
        this.obs = new MutationObserver(() => this.mark());
        this.obs.observe(this.frame ? d.documentElement : this.content, {attributes: true, subtree: true, attributeFilter: ['class', 'disabled']});
      }
      this.set('A');
    }

    outside() { if (this.state !== 'A' && !(this.state === 'B' && this.dirty())) this.set('A'); }

    mark() {
      const dirty = this.dirty();
      $$(this.el, '.dl-phone__burger, .dl-phone__q--menu').forEach(b => { b.classList.toggle('has-dirty', dirty); b.title = dirty ? this.L.dirty : ''; });
      if (this.state === 'B') this.renderActs();
    }

    renderActs() {
      const all = this.items();
      this.overflow = all.slice(MAX_ACTS);
      this.acts.replaceChildren(...all.slice(0, MAX_ACTS).map(it => this.btnFor(it, 'dl-phone__q' + (it.primary ? ' is-primary' : ''))));
      const menu = document.createElement('button');
      menu.type = 'button'; menu.className = 'dl-phone__q dl-phone__q--menu';
      menu.innerHTML = '<i class="fa-solid fa-bars" aria-hidden="true"></i><span></span>';
      menu.lastChild.textContent = this.L.menu;
      menu.classList.toggle('has-dirty', this.dirty());
      menu.title = this.dirty() ? this.L.dirty : '';
      menu.addEventListener('click', () => this.set('C', true));
      this.acts.append(menu);
      this.acts.style.setProperty('--n', String(this.acts.children.length));
      this.other.querySelectorAll('.dl-phone__row').forEach(r => r.remove());
      this.overflow.forEach(it => this.other.append(this.btnFor(it, 'dl-phone__row')));
      this.other.hidden = !this.overflow.length;
    }

    btnFor(it, cls) {
      const b = document.createElement('button');
      b.type = 'button'; b.className = cls; b.disabled = !!it.disabled;
      const i = document.createElement('i'); i.className = it.icon; i.setAttribute('aria-hidden', 'true');
      const s = document.createElement('span'); s.textContent = it.label;
      b.append(i, s);
      b.addEventListener('click', () => { it.run(); this.set('A'); });
      return b;
    }

    next() { return {A: this.hasActions() ? 'B' : 'C', B: 'C', C: 'A'}[this.state]; }

    set(s, user) {
      this.state = s;
      this.shown = Date.now();
      this.el.dataset.state = s;
      this.bar.hidden = s === 'A';
      this.acts.hidden = s !== 'B';
      this.mainBar.hidden = s !== 'C';
      this.drop.hidden = true;
      this.more.setAttribute('aria-expanded', 'false');
      if (s === 'B') this.renderActs();
      this.btn.setAttribute('aria-expanded', String(s !== 'A'));
      this.btn.setAttribute('aria-label', this.L[s]);
      this.btn.firstElementChild.className = 'fa-solid fa-bars';
      this.live.textContent = this.L[s];
      this.mark();
      this.arm();
      if (!user) return;
      if (s === 'A') this.btn.focus({preventScroll: true});
      else $(this.bar, '.dl-phone__q:not([disabled])')?.focus({preventScroll: true});
    }

    toggleList() {
      if (this.state !== 'C') return;
      this.drop.hidden = !this.drop.hidden;
      this.more.setAttribute('aria-expanded', String(!this.drop.hidden));
      this.arm();
    }

    // Teks sedang diketik di halaman (cari): jangan ditutup.
    busy() {
      const a = this.doc()?.activeElement;
      return !!a && /^(INPUT|TEXTAREA|SELECT)$/.test(a.tagName) && !!a.closest?.('.dl-grid__toolbar, .dl-print__bar');
    }

    // Tutup sendiri setelah diam; bilah aksi form TIDAK tertutup sendiri saat form kotor (Simpan tidak boleh lenyap).
    arm() {
      clearTimeout(this.timer);
      if (this.state === 'A' || (this.state === 'B' && this.dirty())) return;
      this.timer = setTimeout(() => { if (this.busy()) this.arm(); else this.set('A'); }, HIDE_MS);
    }

    scrolled() {
      if (this.state === 'A' || Date.now() - (this.shown || 0) < 500 || this.busy()) return;
      this.outside();
    }
  }
  Devlib.define("PhoneShell", PhoneShell);
})(window.Devlib);
