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
    static selector = ".dl-phone[data-dl-phone]";
    static ICON = {A: 'fa-bars', B: 'fa-floppy-disk', C: 'fa-table-cells-large'};

    // autoReturn: tampilan phone dipilih otomatis (data-auto-return, dari cookie ui_auto) lalu layar melebar -> kirim ui=desktop (+ auto=1) lewat form lembar Menu; sekali per 10 detik agar tidak berputar.
    initAutoReturn() {
      const form = this.el.hasAttribute('data-auto-return') ? $(this.el, 'form.dl-phone__dev') : null;
      if (!form) return;
      const KEY = 'dl-phone-return';
      const go = () => {
        if (Devlib.phoneScreen()) return;
        try { if (Date.now() - (+sessionStorage.getItem(KEY) || 0) < 10000) return; sessionStorage.setItem(KEY, String(Date.now())); } catch (e) { /* tanpa penyimpanan sesi: tetap kirim sekali */ }
        if (!form.querySelector('input[name=auto]')) form.insertAdjacentHTML('beforeend', '<input type="hidden" name="auto" value="1">');
        form.requestSubmit ? form.requestSubmit() : form.submit();
      };
      this.mq = window.matchMedia(Devlib.PHONE_MQ);
      this.mq.addEventListener('change', go);
      window.addEventListener('resize', go);
      go();
    }

    init() {
      this.btn = $(this.el, '[data-dl-phone-menu]');
      this.bar = $(this.el, '[data-dl-phone-bar]');
      this.acts = $(this.el, '[data-dl-phone-acts]');
      this.mainBar = $(this.el, '[data-dl-phone-main]');
      this.drop = $(this.el, '[data-dl-phone-drop]');
      this.list = $(this.el, '[data-dl-phone-list]');
      this.other = $(this.el, '[data-dl-phone-other]');
      this.more = $(this.el, '[data-dl-phone-more]');
      this.panel = $(this.el, '[data-dl-phone-panel]');
      this.sheet = $(this.el, '[data-dl-phone-sheet]');
      this.live = $(this.el, '[data-dl-phone-live]');
      this.frame = $(this.el, '.dl-phone__frame');
      this.content = $(this.el, '.dl-phone__main');
      const d = this.el.dataset;
      this.L = {A: d.lA, B: d.lB, C: d.lC, dirty: d.lDirty, leave: d.lLeave, menu: d.lMenu, search: d.lSearch, ui: JSON.parse(d.lUi || '{}')};
      this.state = 'A';
      this.overflow = [];
      this.initAutoReturn();
      this.btn.addEventListener('click', () => this.set(this.next(), true));
      this.more.addEventListener('click', () => this.toggleList());
      $$(this.el, '[data-dl-phone-close]').forEach(x => x.addEventListener('click', () => this.set('A', true)));
      $(this.el, '[data-dl-phone-close-panel]').addEventListener('click', () => this.closePanel());
      this.el.addEventListener('keydown', e => {
        if (e.key === 'Escape' && this.panelFn) this.closePanel(); else if (e.key === 'Escape' && this.state !== 'A') this.set('A', true);
        this.arm();
      });
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
      // Layar sendiri (bukan form / grid / report): tombol dengan [data-phone-act] ("primary" = item utama berwarna aksen) menjadi aksi bilah B.
      const own = q('[data-phone-act]').filter(b => !b.hidden && !b.closest('[hidden]'));
      if (own.length) return own.map(b => this.item(b, {primary: b.dataset.phoneAct === 'primary'}));
      const form = q('.dl-form__acts .dl-btn').filter(b => !b.hidden && !b.closest('[hidden]'));
      if (form.length) {
        // Urutan bilah: Simpan, Urungkan, Kembali, Baru; aksi lain (Nonaktifkan, Hapus ...) menyusul dan, di atas empat, masuk "Aksi lain".
        const rank = i => { const k = ['save', 'undo', 'back', 'new'].indexOf(i.act); return k < 0 ? 9 : k; };
        return form.map(b => this.item(b, {act: b.dataset.fAct, primary: b.dataset.fAct === 'save'})).sort((x, y) => rank(x) - rank(y));
      }
      const grid = d.querySelector('.dl-grid__toolbar');
      if (grid) {
        const g = s => d.querySelector(s), U = this.L.ui;
        return [
          g('[data-g-search]') && {label: this.L.search, icon: 'fa-solid fa-magnifying-glass', run: () => this.openSearch(d)},
          g('[data-g-new]') && this.item(g('[data-g-new]'), {label: this.label(g('[data-g-new]')).replace(/^\+\s*/, ''), icon: 'fa-solid fa-plus', primary: true}),
          g('[data-g-fopen]') ? this.item(g('[data-g-fopen]'), {label: U.filter}) : {label: U.filter, icon: 'fa-solid fa-sliders', panel: () => this.filterPanel(d)},
          g('[data-g-expmenu]') && {label: U.export, icon: 'fa-solid fa-file-export', panel: () => this.exportPanel(d)},
        ].filter(Boolean);
      }
      const bar = d.querySelector('.dl-print__bar');
      if (bar) {
        const g = s => bar.querySelector(s), U = this.L.ui;
        const print = g('[data-p-print]'), ink = g('[data-p-bw]'), root = bar.closest('.dl-print');
        return [
          print && this.item(print, {primary: true}),
          g('[data-p-paper]') && {label: U.paper, icon: 'fa-regular fa-file', panel: () => this.paperPanel(d)},
          g('[data-p-group]') && {label: U.group, icon: 'fa-solid fa-layer-group', panel: () => this.groupPanel(d)},
          ink && this.item(ink, {label: U.ink, toggle: true, on: root.classList.contains('is-bw')}),
        ].filter(Boolean);
      }
      return [];
    }

    // ---- panel dari atas: isinya dicerminkan dari kontrol halaman (halaman yang menggambar ulang dibaca lagi setelah tiap perubahan) --------------------
    seg(d, sel, h) {
      const o = [...d.querySelectorAll(sel + ' .dl-segment__opt')];
      return o.length ? {h, seg: o.map(b => ({label: b.title || b.textContent.trim(), on: b.classList.contains('is-active'), run: () => b.click()}))} : null;
    }

    filterPanel(d) {
      return () => {
        const U = this.L.ui, q = s => [...d.querySelectorAll(s)];
        const sel = q('[data-g-filter-fields] [data-g-filter]').map(s => ({
          h: s.closest('label')?.querySelector('span')?.textContent || s.dataset.gFilter,
          sel: {value: s.value, opts: [...s.options].map(o => ({v: o.value, t: o.textContent})), set: v => { s.value = v; s.dispatchEvent(new Event('change', {bubbles: true})); }},
        }));
        const cols = q('[data-g-col]');
        return {title: U.filter, apply: true, sections: [this.seg(d, '[data-g-view]', U.view), ...sel, cols.length && {cols: {label: U.cols, items: cols.map(c => ({label: c.parentElement.textContent.trim(), on: c.checked, run: () => c.click()}))}}].filter(Boolean)};
      };
    }

    exportPanel(d) {
      return () => ({title: this.L.ui.export, sections: [{list: [...d.querySelectorAll('[data-g-fmt]')].map(b => ({label: b.textContent.trim(), icon: 'fa-solid fa-file-arrow-down', run: () => b.click()}))}]});
    }

    paperPanel(d) {
      return () => ({title: this.L.ui.paper, sections: [this.seg(d, '[data-p-paper]'), this.seg(d, '[data-p-prices]'), this.seg(d, '[data-p-ppn]')].filter(Boolean)});
    }

    groupPanel(d) {
      return () => ({title: this.L.ui.group, sections: [this.seg(d, '[data-p-group]')].filter(Boolean)});
    }

    openPanel(fn) {
      this.panelFn = fn; this.colsOpen = false;
      this.drawPanel();
      this.panel.hidden = false;
      $(this.sheet, 'button, select, input')?.focus({preventScroll: true});
      this.arm();
    }

    closePanel() {
      this.panelFn = null;
      this.panel.hidden = true;
      this.arm();
    }

    drawPanel() {
      const spec = this.panelFn?.();
      if (!spec) return;
      const U = this.L.ui, box = this.sheet;
      const el = (t, c, txt) => { const e = document.createElement(t); if (c) e.className = c; if (txt != null) e.textContent = txt; return e; };
      const act = fn => () => { fn(); setTimeout(() => { if (this.panelFn) this.drawPanel(); }, 0); };   // halaman menggambar ulang dulu, lalu dibaca lagi
      box.replaceChildren(el('h2', 'dl-phone__gh', spec.title));
      spec.sections.forEach(s => {
        if (s.h) box.append(el('h3', 'dl-phone__ph', s.h));
        if (s.seg) {
          const g = el('div', 'dl-phone__seg');
          s.seg.forEach(o => { const b = el('button', 'dl-phone__opt' + (o.on ? ' is-on' : ''), o.label); b.type = 'button'; b.setAttribute('aria-pressed', String(!!o.on)); b.addEventListener('click', act(o.run)); g.append(b); });
          box.append(g);
        }
        if (s.sel) {
          const f = el('select', 'dl-phone__sel');
          s.sel.opts.forEach(o => { const x = el('option', '', o.t); x.value = o.v; f.append(x); });
          f.value = s.sel.value;
          f.addEventListener('change', act(() => s.sel.set(f.value)));
          box.append(f);
        }
        if (s.list) s.list.forEach(o => {
          const b = el('button', 'dl-phone__row'); b.type = 'button';
          const i = el('i', o.icon); i.setAttribute('aria-hidden', 'true');
          b.append(i, el('span', '', o.label));
          b.addEventListener('click', () => { o.run(); this.set('A'); });
          box.append(b);
        });
        if (s.cols) {
          const t = el('button', 'dl-phone__row'); t.type = 'button'; t.setAttribute('aria-expanded', String(!!this.colsOpen));
          const i = el('i', 'fa-solid fa-table-columns'); i.setAttribute('aria-hidden', 'true');
          t.append(i, el('span', '', s.cols.label));
          t.addEventListener('click', () => { this.colsOpen = !this.colsOpen; this.drawPanel(); });
          box.append(t);
          if (this.colsOpen) s.cols.items.forEach(c => {
            const l = el('label', 'dl-phone__chk'), x = el('input'); x.type = 'checkbox'; x.checked = c.on;
            x.addEventListener('change', act(c.run));
            l.append(x, el('span', '', c.label));
            box.append(l);
          });
        }
      });
      if (spec.apply) { const a = el('button', 'dl-phone__apply', U.apply); a.type = 'button'; a.addEventListener('click', () => this.set('A')); box.append(a); }
    }

    // Lembar cetak selebar kertas diperkecil (variabel CSS, hanya di layar) supaya pas lebar layar; hasil cetak tidak ikut mengecil.
    fitSheets(d) {
      const s = d.querySelector('.dl-print__sheet');
      if (!s) return;
      const root = d.documentElement;
      root.style.removeProperty('--dl-phone-fit');
      const w = root.clientWidth - 24;
      if (s.offsetWidth > w) root.style.setProperty('--dl-phone-fit', (w / s.offsetWidth).toFixed(3));
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
      this.pobs?.disconnect();
      if (d && this.frame) {
        d.body.classList.add('dl-phone-shelled');
        d.addEventListener('scroll', () => this.scrolled(), true);
        d.addEventListener('pointerdown', e => {
          if (!e.target.closest('.dl-grid__search')) { const s = d.querySelector('[data-g-search]'); if (s && !s.value) d.body.classList.remove('dl-phone-search'); }
          if (!e.target.closest('.dl-pop2__panel, .dl-grid__expmenu')) this.outside();
        }, true);
        d.addEventListener('focusout', e => { if (e.target.matches?.('[data-g-search]') && !e.target.value) d.body.classList.remove('dl-phone-search'); });
      }
      const pages = d && d.querySelector('.dl-print__pages');
      if (pages && this.frame) {
        this.fitSheets(d);
        this.pobs = new MutationObserver(() => this.fitSheets(d));
        this.pobs.observe(pages, {childList: true});
        d.defaultView.addEventListener('resize', () => this.fitSheets(d));
      }
      if (d) {
        this.obs = new MutationObserver(() => this.mark());
        this.obs.observe(this.frame ? d.documentElement : this.content, {attributes: true, subtree: true, attributeFilter: ['class', 'disabled']});
      }
      this.set('A');
    }

    outside() { if (this.panelFn) return this.closePanel(); if (this.state !== 'A' && !(this.state === 'B' && this.dirty())) this.set('A'); }

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
      b.type = 'button'; b.className = cls + (it.on ? ' is-on' : ''); b.disabled = !!it.disabled;
      if (it.toggle) b.setAttribute('aria-pressed', String(!!it.on));
      const i = document.createElement('i'); i.className = it.icon; i.setAttribute('aria-hidden', 'true');
      const s = document.createElement('span'); s.textContent = it.label;
      b.append(i, s);
      b.addEventListener('click', () => {
        if (it.panel) return this.openPanel(it.panel());
        it.run();
        if (it.toggle) setTimeout(() => this.renderActs(), 0); else this.set('A');
      });
      return b;
    }

    next() { return {A: this.hasActions() ? 'B' : 'C', B: 'C', C: 'A'}[this.state]; }

    set(s, user) {
      this.state = s;
      this.shown = Date.now();
      this.panelFn = null; this.panel.hidden = true;
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
      if (this.panelFn || this.state === 'A' || (this.state === 'B' && this.dirty())) return;
      this.timer = setTimeout(() => { if (this.busy()) this.arm(); else this.set('A'); }, HIDE_MS);
    }

    scrolled() {
      if (this.state === 'A' || Date.now() - (this.shown || 0) < 500 || this.busy()) return;
      this.outside();
    }
  }
  Devlib.define("PhoneShell", PhoneShell);
})(window.Devlib);
