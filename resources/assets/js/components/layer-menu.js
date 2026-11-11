/* DevLib · LayerMenu */
(function (Devlib) {
  'use strict';
  const {esc, toast, confirmBox, T, Tf} = Devlib.h;
  const BACK = '<button type="button" class="dl-lm__back" data-lm-back title="Back"><i class="fa-solid fa-arrow-left"></i><span>Back</span></button>';
  /* ============ LAYER MENU (panel kiri berlapis: modul -> menu -> sub -> ...) + Chat & Profile ============ */
  class LayerMenu extends Devlib.Component {
    static selector = "[data-dl-layer-menu]";
    init() {
      // views = beberapa akar menu (mis. Operasional | Laporan Manajemen) yang ditukar lewat ikon di kepala panel.
      this.views = JSON.parse(this.el.dataset.views || '[]');
      this.view = 0;
      this.root = this.views.length ? this.views[0].items : JSON.parse(this.el.dataset.tree || '[]');
      this.track = this.$('[data-lm-track]');
      this.key = 'dl-lm-' + this.el.dataset.id;
      this.path = [];     // cabang yang sedang dibuka, path[d-1] = induk layer d
      this.sel = null;    // key layar akhir terpilih
      // foldGroups: judul kelompok di layer akar bisa dilipat, diingat per browser. '1' = tiap judul sendiri-sendiri (daftar yang terlipat);
      // 'accordion' = satu kelompok terbuka sekali waktu (nama yang terbuka; belum ada = kelompok layar aktif, atau yang pertama).
      this.foldMode = this.el.dataset.foldGroups || '0';
      // inlineGroups: modul yang anaknya ber-group terbuka di tempat (this.exp) sebagai satu daftar panjang, bukan layer baru.
      this.inline = this.el.dataset.inlineGroups === '1'; this.exp = null;
      this.fold = new Set(); this.open = undefined;
      try {
        const f = JSON.parse(localStorage.getItem('dl-lm-' + this.el.dataset.id + '-fold') || 'null');
        if (this.foldMode === 'accordion') this.open = f && !Array.isArray(f) ? f.open : undefined; else if (Array.isArray(f)) this.fold = new Set(f);
      } catch (e) {}
      this.st = {chat: false, dock: false, show: 'menu'};   // show = menu | chat | profile (yang tampil di panel kiri)
      this.initViews();
      this.track.addEventListener('click', e => { const t = e.target.closest('button, a'); if (t) this.click(e, t); else if (e.target.closest('[data-lm-hub]')) this.hub(e.target.closest('[data-lm-hub]')); else if (e.target.closest('[data-lm-cap-home]')) this.goHome(); });
      this.track.addEventListener('keydown', e => { if (e.key === 'Enter' && e.target.matches('[data-lm-hub]')) this.hub(e.target); else if (e.key === 'Enter' && e.target.matches('[data-lm-cap-home]')) this.goHome(); });
      this.el.addEventListener('keydown', e => {
        if (e.key === 'Escape' && this.peek && this.st.show === 'menu') { this.setPeek(false); return; }
        if ((e.key === 'Escape' || e.key === 'ArrowLeft') && this.st.show === 'menu' && this.path.length && e.target.closest('[data-lm-track]')) { e.preventDefault(); this.back(); }
      });
      this.initPanels();
      this.initMini();
      this.restore();
    }

    /* ---------------- mini: panel = satu lajur ikon; menu lengkap melayang (peek) saat perlu ---------------- */
    initMini() {
      this.mini = this.peek = false;
      this.miniBtn = this.$('[data-lm-mini]');
      this.$('[data-lm-search]')?.addEventListener('click', () => this.emit('dl:layer-menu-action', {key: 'palette'}, true));
      // Logo + nama aplikasi (prop home) = kembali ke opening screen; yang menutup tab adalah pemakai event (Shell::goHome).
      const home = this.$('[data-lm-home]'), goHome = () => this.goHome();
      home?.addEventListener('click', goHome);
      home?.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); goHome(); } });
      if (!this.miniBtn) return;
      this.miniBtn.addEventListener('click', () => this.setMini(!this.mini));
      // Klik di luar panel (layar di dalam iframe = jendela kehilangan fokus) menutup menu melayang.
      // composedPath, bukan contains(): klik cabang menggambar ulang panel, target-nya sudah lepas dari DOM saat sampai di sini.
      document.addEventListener('click', e => { const p = e.composedPath(); if (this.peek && !p.includes(this.el) && !(this.dockHost && p.includes(this.dockHost))) this.setPeek(false); });
      window.addEventListener('blur', () => this.setPeek(false));
      // Mini: klik avatar = menu melayang terbuka langsung di Profile.
      this.$('[data-lm-foot]')?.addEventListener('click', e => {
        if (!this.mini || this.peek) return;
        e.stopPropagation(); this.setPeek(true); this.openProfile();
      });
      let on = false; try { on = localStorage.getItem(this.key + '-mini') === '1'; } catch (e) {}
      if (on) this.setMini(true, false);
    }

    setMini(on, save = true) {
      this.mini = on; this.peek = false;
      this.el.classList.toggle('is-mini', on);
      this.miniBtn.title = on ? this.miniBtn.dataset.tMax : this.miniBtn.dataset.tMin;
      this.miniBtn.setAttribute('aria-label', this.miniBtn.title);
      this.miniBtn.setAttribute('aria-pressed', String(on));
      if (save) try { localStorage.setItem(this.key + '-mini', on ? '1' : '0'); } catch (e) {}
      this.apply();
      this.emit('dl:layer-menu-mini', {mini: on});
    }

    // Chat / Profile butuh lebar penuh: selama salah satunya tampil, menu melayang tidak bisa ditutup.
    setPeek(on) {
      if (!this.mini) return;
      this.peek = on || this.st.show !== 'menu';
      this.el.classList.toggle('is-peek', this.peek);
    }

    depthOf(items) { return 1 + Math.max(0, ...items.map(n => n.items ? this.depthOf(n.items) : 0)); }
    count(n) { return n.items ? n.items.reduce((a, x) => a + this.count(x), 0) : 1; }
    nodes(d) { return d ? this.path[d - 1].items : this.exp ? this.flat(this.exp) : this.root; }
    // Modul ber-kelompok: anak punya group, atau anak = cabang yang isinya daun saja (kedalaman 2); lebih dalam tetap turun layer.
    canInline(n) { return this.inline && !!n.items && n.items.some(c => c.group || c.items) && n.items.every(c => !c.items || c.items.every(x => !x.items)); }
    // Daun modul berurutan; cabang bertingkat diratakan (label cabang = judul kelompok), kelompok & ikonnya diwariskan.
    flat(m) {
      if (m._flat) return m._flat;
      const out = [], walk = (items, g, gi) => items.forEach(c => c.items ? walk(c.items, c.group || c.label, c.groupIcon || c.icon) : out.push({...c, group: c.group || g, groupIcon: c.groupIcon || gi}));
      walk(m.items, null, null);
      return m._flat = out;
    }

    // Jalur label ke layar akhir berkunci k (dipakai untuk prop active & posisi yang diingat).
    find(k, items = this.root, trail = []) {
      for (const n of items) {
        if (!n.items && n.key === k) return trail;
        if (n.items) { const r = this.find(k, n.items, [...trail, n]); if (r) return r; }
      }
      return null;
    }

    restore() {
      let saved = {};
      try { saved = JSON.parse(localStorage.getItem(this.key)) || {}; } catch (e) {}
      const k = this.el.dataset.active || saved.sel;
      // View awal: prop view > view yang memuat layar aktif > view terakhir dipakai.
      const want = this.el.dataset.view || (k && this.views.find(v => this.find(k, v.items))?.key) || saved.view;
      this.setView(Math.max(0, this.views.findIndex(v => v.key === want)), false);
      const trail = k ? this.find(k) : null;
      if (trail) { this.path = trail; this.sel = k; }
      else (saved.path || []).every(lbl => { const n = this.nodes(this.path.length).find(x => x.items && x.label === lbl); return n && this.path.push(n); });
      this.draw(false);
    }

    save() { try { localStorage.setItem(this.key, JSON.stringify({path: (this.exp ? [this.exp] : this.path).map(n => n.label), sel: this.sel, view: this.views[this.view]?.key})); } catch (e) {} }

    initViews() {
      const box = this.$('[data-lm-views]');
      if (this.views.length > 1) {
        box.innerHTML = this.views.map((v, i) => `<button type="button" data-lm-view="${i}" title="${esc(v.label)}" aria-label="${esc(v.label)}"><i class="${esc(v.icon)}"></i></button>`).join('');
        box.hidden = false;
        box.addEventListener('click', e => { const b = e.target.closest('[data-lm-view]'); if (b) this.setView(+b.dataset.lmView); });
      }
    }

    // Tukar akar menu: layer kembali ke 1, panel kembali ke menu (bila sedang Chat/Profile).
    setView(i, animate = true) {
      if (!this.views.length) return;
      const changed = i !== this.view;
      this.view = i;
      this.root = this.views[i].items;
      this.$$('[data-lm-view]').forEach(b => { const on = +b.dataset.lmView === i; b.classList.toggle('is-on', on); b.setAttribute('aria-pressed', String(on)); });
      if (!changed && !animate) return;
      this.path = []; this.exp = null;
      if (this.st.show !== 'menu') this.show('menu');
      this.draw(animate);
      this.emit('dl:layer-menu-view', {key: this.views[i].key});
    }

    // Satu panel per layer. Tanpa animasi saat pertama tampil (restore) supaya tidak "terbang" dari layer 1.
    draw(animate = true) {
      if (this.inline && this.path.length && this.canInline(this.path[0])) { this.exp = this.path[0]; this.path = []; } else if (this.path.length) this.exp = null;
      const d = this.path.length;
      while (this.track.children.length > d + 1) this.track.lastChild.remove();
      for (let i = this.track.children.length; i <= d; i++) this.track.insertAdjacentHTML('beforeend', '<div class="dl-lm__pane"></div>');
      [...this.track.children].forEach((p, i) => { p.innerHTML = this.pane(i); p.inert = i !== d; });
      this.slide(this.track, d, animate);
      this.save();
    }

    slide(track, d, animate = true) {
      track.classList.toggle('no-anim', !animate);
      track.style.transform = `translateX(${-100 * d}%)`;
      if (!animate) requestAnimationFrame(() => track.classList.remove('no-anim'));
    }

    // Modul terbuka di tempat: kepala modul (klik = tutup), lalu kelompok bertajuk + ikon dengan garis pembatas dan butir bertitik; modul lain tersembunyi.
    paneInline() {
      const m = this.exp, counts = this.el.dataset.counts === '1';
      let h = `<button type="button" class="dl-lm__a dl-lm__mod" data-lm-exp aria-expanded="true" title="${esc(m.label)}"><span class="dl-lm__ico"><i class="${esc(m.icon)}"></i></span><span class="dl-lm__t">${esc(m.label)}</span>${counts ? `<span class="dl-lm__n">${this.count(m)}</span>` : ''}<i class="fa-solid fa-chevron-up dl-lm__chev"></i></button><div class="dl-lm__il">`;
      let g = null;
      this.flat(m).forEach((n, x) => {
        if (n.group && n.group !== g) { g = n.group; h += `<div class="dl-lm__ilcap">${n.groupIcon ? `<i class="${esc(n.groupIcon)}"></i>` : ''}<span>${esc(g)}</span></div>`; }
        const badge = n.badge && !String(n.badge).startsWith('icon:') ? `<em class="dl-lm__badge is-${esc(n.tone || 'accent')}">${esc(n.badge)}</em>` : '';
        h += `<a href="${esc(n.url || '#')}" class="dl-lm__a dl-lm__bul${n.key === this.sel ? ' is-active' : ''}" data-lm-x="${x}" title="${esc(n.label)}"><span class="dl-lm__dot"></span><span class="dl-lm__t">${esc(n.label)}</span>${badge}</a>`;
      });
      return h + '</div>';
    }

    pane(d) {
      if (!d && this.exp) return this.paneInline();
      const counts = this.el.dataset.counts === '1';
      // Judul seksi layer 1 (mis. OPERATIONAL) = pintu ke opening screen seperti logo, bila prop home aktif (tampilan tetap, hanya hover kecil).
      const cap = this.$('[data-lm-home]') ? ' data-lm-cap-home role="button" tabindex="0"' : '';
      let h = '';
      if (d) {
        const p = this.path[d - 1];
        // p.hub = url halaman modul / grup: judul layer jadi pintu kembali ke halaman itu (tampilan tetap, hanya kursor tangan).
        h += BACK + `<div class="dl-lm__title${p.hub ? ' is-hub' : ''}" title="${esc(p.label)}"${p.hub ? ' data-lm-hub role="button" tabindex="0"' : ''}><i class="${esc(p.icon)}"></i><b>${esc(p.label)}</b></div>`;
      } else h += `<div class="dl-lm__cap"${cap}>${esc(this.views.length ? this.views[this.view].label : this.el.dataset.root)}</div>`;
      let g = null, open = false;
      const foldable = !d && this.foldMode !== '0', shut = () => { if (open) { h += '</div></div>'; open = false; } };
      const nodes = this.nodes(d), size = {};
      if (foldable) {
        let cur = null;
        nodes.forEach(n => { if (n.group) cur = n.group; if (cur && !(n.pin && !d)) size[cur] = (size[cur] || 0) + this.count(n); });
        if (this.foldMode === 'accordion' && this.open === undefined) {
          const trail = this.sel ? this.find(this.sel) : null;
          this.open = trail && trail[0].group ? trail[0].group : (nodes.find(n => n.group) || {}).group || null;
        }
      }
      nodes.forEach((n, x) => {
        if (n.group && n.group !== g) {
          g = n.group; shut();
          if (!foldable) h += `<div class="dl-lm__cap"${d ? '' : cap}>${esc(g)}</div>`;
          else {
            // Judul = tombol lipat; anak menunya dalam satu pembungkus yang menggulung ke atas di belakang judul.
            const folded = this.isFolded(g), tally = counts ? `<em class="dl-lm__cap-n">${size[g] || 0}</em>` : '';
            h += `<button type="button" class="dl-lm__cap dl-lm__cap--fold" data-lm-fold="${esc(g)}" aria-expanded="${folded ? 'false' : 'true'}"><span>${esc(g)}</span>${tally}<i class="fa-solid fa-chevron-down"></i></button><div class="dl-lm__grp${folded ? ' is-folded' : ''}"${folded ? ' inert' : ''}><div>`;
            open = true;
          }
        }
        if (n.pin && !d) shut();
        // badge "icon:<kelas fa>" = ikon saja (tanpa teks), mis. status QC.
        const badge = n.badge ? (String(n.badge).startsWith('icon:')
          ? `<em class="dl-lm__badge dl-lm__badge--icon is-${esc(n.tone || 'accent')}" title="${esc(n.badgeTitle || '')}"><i class="${esc(String(n.badge).slice(5))}"></i></em>`
          : `<em class="dl-lm__badge is-${esc(n.tone || 'accent')}">${esc(n.badge)}</em>`) : '';
        const lbl = `<span class="dl-lm__ico"><i class="${esc(n.icon)}"></i></span><span class="dl-lm__t">${esc(n.label)}</span>${badge}`;
        // pin (hanya akar): baris menempel di dasar panel, dekat baris pengguna (mis. menu Keamanan).
        const row = n.items
          ? `<button type="button" class="dl-lm__a" data-lm-x="${x}" title="${esc(n.label)}">${lbl}${counts ? `<span class="dl-lm__n">${this.count(n)}</span>` : ''}<i class="fa-solid fa-chevron-right dl-lm__chev"></i></button>`
          : `<a href="${esc(n.url || '#')}" class="dl-lm__a${n.key === this.sel ? ' is-active' : ''}" data-lm-x="${x}" title="${esc(n.label)}">${lbl}</a>`;
        h += n.pin && !d ? `<div class="dl-lm__pin">${row}</div>` : row;
      });
      shut();
      return h;
    }

    // Buka menu pada cabang berlabel (mis. ['Sales', 'Sales Order'] dari breadcrumb). Label tidak cocok -> cari lewat daun berlabel leaf.
    goTo(labels, leaf = null) {
      if (this.st.show !== 'menu') this.show('menu');
      this.setPeek(true);
      const path = [];
      let items = this.root;
      for (const l of labels) { const n = items.find(x => x.items && x.label === l); if (!n) break; path.push(n); items = n.items; }
      if (path.length < labels.length && leaf) {
        const hunt = (xs, trail) => { for (const n of xs) { if (!n.items && n.label === leaf) return trail; if (n.items) { const r = hunt(n.items, [...trail, n]); if (r) return r; } } return null; };
        const t = hunt(this.root, []); if (t) path.splice(0, path.length, ...t.slice(0, labels.length));
      }
      this.path = path; this.draw();
      this.track.children[this.path.length]?.querySelector('.dl-lm__a')?.focus({preventScroll: true});
    }

    back() { this.path.pop(); this.draw(); this.track.children[this.path.length]?.querySelector('.dl-lm__a')?.focus({preventScroll: true}); }

    // Lipat / buka satu kelompok tanpa menggambar ulang panel (supaya gulungannya beranimasi).
    isFolded(g) { return this.foldMode === 'accordion' ? this.open !== g : this.fold.has(g); }

    toggleFold(t) {
      const g = t.dataset.lmFold;
      if (this.foldMode === 'accordion') this.open = this.open === g ? null : g;
      else this.fold.has(g) ? this.fold.delete(g) : this.fold.add(g);
      // Semua judul di panel disesuaikan (accordion: yang lain menutup), tanpa menggambar ulang supaya gulungannya beranimasi.
      t.closest('.dl-lm__pane').querySelectorAll('[data-lm-fold]').forEach(b => {
        const shut = this.isFolded(b.dataset.lmFold), grp = b.nextElementSibling;
        b.setAttribute('aria-expanded', String(!shut)); grp.classList.toggle('is-folded', shut); grp.inert = shut;
      });
      try { localStorage.setItem('dl-lm-' + this.el.dataset.id + '-fold', JSON.stringify(this.foldMode === 'accordion' ? {open: this.open} : [...this.fold])); } catch (e) {}
    }

    // Kembali ke opening screen (logo + nama aplikasi, dan judul seksi layer 1 bila prop home aktif); yang menutup tab adalah pemakai event (Shell::goHome).
    goHome() { this.setPeek(false); this.emit('dl:layer-menu-action', {key: 'home'}, true); }

    // Klik judul layer (node jalur yang punya hub): panel kanan membuka halaman node itu, menu kiri tidak bergeser.
    hub(t) {
      const p = this.path[[...this.track.children].indexOf(t.closest('.dl-lm__pane')) - 1];
      if (p) this.emit('dl:layer-menu-open', {key: p.hubKey || p.key || p.label, label: p.hubLabel || p.label, url: p.hub, icon: p.icon}, true);
    }

    click(e, t) {
      if (t.hasAttribute('data-lm-fold')) { this.toggleFold(t); return; }
      if (t.hasAttribute('data-lm-exp')) { this.exp = null; this.draw(false); return; }
      if (t.hasAttribute('data-lm-back')) { this.setPeek(true); this.back(); return; }
      if (!t.hasAttribute('data-lm-x')) return;
      const d = [...this.track.children].indexOf(t.closest('.dl-lm__pane'));
      const n = this.nodes(d)[+t.dataset.lmX];
      if (n.items && this.canInline(n)) { this.setPeek(true); this.exp = n; this.draw(false); return; }
      if (n.items) {
        this.setPeek(true); this.path = [...this.path.slice(0, d), n]; this.draw(); this.track.children[d + 1].querySelector('[data-lm-back]').focus({preventScroll: true});
        // Grup yang punya halaman pembuka (n.open = url): panel kanan ikut membuka halaman itu; menu kiri tetap turun satu layer.
        if (n.open) this.emit('dl:layer-menu-open', {key: n.key, label: n.label, url: n.open, icon: n.icon}, true);
        return;
      }
      if (!n.url || n.url === '#') e.preventDefault();
      this.sel = n.key;
      this.$$('.dl-lm__a.is-active').forEach(a => a.classList.remove('is-active'));
      t.classList.add('is-active');
      this.save();
      this.setPeek(false);
      const detail = {key: n.key, label: n.label, url: n.url || null, path: [...(this.exp ? [this.exp] : this.path).map(p => p.label), n.label]};
      if (!this.emit('dl:layer-menu', detail, true)) { e.preventDefault(); return; }
      if (!n.url || n.url === '#') toast(Tf('open_preview', {x: detail.path.join(' › ')}));
    }

    /* ---------------- roda gigi, Chat & Profile ---------------- */
    initPanels() {
      this.chatOv = this.$('[data-lm-ov="chat"]');
      this.profOv = this.$('[data-lm-ov="profile"]');
      this.chatEl = this.$('[data-lm-chathost] [data-dl-chat]');
      this.chatHost = this.$('[data-lm-chathost]');
      this.$('[data-lm-user]')?.addEventListener('click', e => {
        if (this.mini && !this.peek) return;
        e.stopPropagation(); this.toggleProfile();
      });
      this.$('[data-lm-swap]').addEventListener('click', () => this.show(this.st.show === 'chat' ? 'menu' : 'chat'));
      this.$('[data-lm-dockbtn]').addEventListener('click', () => this.setDock(true));
      if (this.chatEl) {
        // Tombol tutup ditempel di bilah judul chat (bukan bilah kedua) supaya chat tidak dobel judul.
        this.chatEl.querySelector('.dl-wa__bar').insertAdjacentHTML('beforeend', '<button type="button" class="dl-wa__ib" data-lm-chatx title="' + esc(T('lm_close_chat')) + '" aria-label="' + esc(T('lm_close_chat')) + '"><i class="fa-solid fa-xmark"></i></button>');
        this.chatEl.addEventListener('click', e => { if (e.target.closest('[data-lm-chatx]')) this.closeChat(); });
        this.unread = this.chatEl.dlChat?.unread || 0;
        this.chatEl.addEventListener('dl:chat-unread', e => { this.unread = e.detail.count; this.badges(); });
      }
      if (this.profOv) {
        this.pages = this.$('[data-lm-pages]');
        this.ptrack = this.$('[data-lm-ptrack]');
        this.pdet = this.$('[data-lm-pdet]');
        this.pstack = [];
        // data-lm-in="induk": halaman ikut sebagai bagian di dalam halaman induk itu (tidak tampil di daftar).
        this.pages.querySelectorAll(':scope > [data-lm-in]').forEach(x => { x.dataset.lmParent = x.dataset.lmIn; });
        // Halaman induk (data-lm-children="a,b") mendapat daftar anaknya di awal isi; anak tidak tampil di daftar utama, Back-nya kembali ke induk.
        this.pages.querySelectorAll(':scope > [data-lm-page][data-lm-children]').forEach(par => {
          const kids = par.dataset.lmChildren.split(',').map(k => this.page(k.trim())).filter(Boolean);
          kids.forEach(k => { k.dataset.lmParent = par.dataset.lmPage; });
          if (par.hasAttribute('data-lm-modal')) return;
          par.insertAdjacentHTML('afterbegin', `<div class="dl-lm__plist">${kids.map(k => this.row(k)).join('')}</div>`);
        });
        // Daftar Profile = urutan aksi akun (LayerShell::actions, user 2026-10-03); data-sep = garis sebelum baris itu.
        // Aksi yang punya halaman panel tampil sebagai halaman; halaman lain tanpa induk (mis. Setup) disisipkan sesudah Help.
        const acts = this.$$('[data-lm-acts] [data-lm-act]'), keys = acts.map(a => a.dataset.lmAct);
        const rest = [...this.pages.querySelectorAll(':scope > [data-lm-page]')].filter(s => !s.dataset.lmParent && !keys.includes(s.dataset.lmPage));
        const hasChat = !!(this.chatEl || document.querySelector('[data-dl-drawer="chat"]'));
        this.$('[data-lm-plist]').innerHTML = acts.map(a => {
          const k = a.dataset.lmAct; if (k === 'chat' && !hasChat) return '';
          const sep = a.dataset.sep ? '<div class="dl-lm__sep"></div>' : '', pg = this.page(k);
          return sep + (pg ? this.row(pg) : a.outerHTML) + (k === 'help' ? rest.map(s => this.row(s)).join('') : '');
        }).join('') + (keys.includes('help') ? '' : rest.map(s => this.row(s)).join(''));
        this.profOv.addEventListener('click', e => {
          if (e.target.closest('[data-lm-close]')) return this.show(this.st.chat && !this.st.dock ? 'chat' : 'menu');
          const o = e.target.closest('[data-lm-page-open]'); if (o) return this.openPage(o.dataset.lmPageOpen);
          if (e.target.closest('[data-lm-pdet] [data-lm-back]')) return this.closePage();
          const act = e.target.closest('[data-lm-plist] [data-lm-act]'); if (act) this.action(act);
        });
      }
      this.badges();
    }

    row(s) {
      return `<button type="button" class="dl-lm__a" data-lm-page-open="${esc(s.dataset.lmPage)}"><span class="dl-lm__ico"><i class="${esc(this.faOf(s.dataset.icon))}"></i></span><span class="dl-lm__t">${esc(s.dataset.label)}</span><i class="fa-solid fa-chevron-right dl-lm__chev"></i></button>`;
    }

    faOf(icon) { return !icon ? 'fa-solid fa-circle-dot' : icon.includes(' ') ? icon : 'fa-solid ' + icon; }
    page(k) { return this.pages?.querySelector(`:scope > [data-lm-page="${CSS.escape(k)}"]`) || this.pdet?.querySelector(`[data-lm-page="${CSS.escape(k)}"]`) || this.drw?.querySelector(`[data-lm-page="${CSS.escape(k)}"]`); }

    chatVisible() { return this.st.chat && (this.st.dock || this.st.show === 'chat'); }

    show(what) { this.st.show = what; this.apply(); }

    apply() {
      const {chat, dock, show} = this.st;
      this.chatOv?.classList.toggle('is-on', chat && !dock && show === 'chat');
      this.profOv?.classList.toggle('is-on', show === 'profile');
      this.$('[data-lm-user]')?.setAttribute('aria-expanded', String(show === 'profile'));
      [...this.track.children].forEach((p, i) => { p.inert = show !== 'menu' || i !== this.path.length; });
      this.$('[data-lm-swap]').hidden = this.$('[data-lm-dockbtn]').hidden = !(chat && !dock);
      this.$('[data-lm-swap]').title = T(show === 'chat' ? 'lm_show_menu' : 'lm_show_chat');
      const mode = {chat: 'Chat', profile: 'Profile'}[show];
      if (this.mini && show !== 'menu') this.setPeek(true);
      const m = this.$('[data-lm-mode]'); m.hidden = !mode; m.textContent = mode || '';
      const tag = this.$('[data-lm-tag]'); if (tag) tag.hidden = !!mode;
      if (this.dockHost) this.dockHost.classList.toggle('is-on', chat && dock);
      this.badges();
      this.emit('dl:layer-menu-panel', {...this.st});
    }

    badges() {
      const u = this.unread || 0, vis = this.chatVisible();
      this.$$('[data-lm-unread]').forEach(b => {
        const n = {list: u, avatar: vis ? 0 : u, swap: this.st.show === 'menu' ? u : 0}[b.dataset.lmUnread] || 0;
        b.hidden = !n; b.textContent = n > 99 ? '99+' : n;
      });
    }

    openChat() { if (!this.chatEl) return; this.st.chat = true; if (!this.st.dock) this.st.show = 'chat'; this.apply(); }

    closeChat() {
      const wasDock = this.st.dock;
      Object.assign(this.st, {chat: false, dock: false, show: this.st.show === 'chat' ? 'menu' : this.st.show});
      this.apply();
      // Balik ke panel kiri setelah animasi dok selesai, supaya dok menutup bersama isinya.
      if (wasDock) setTimeout(() => this.chatHost.appendChild(this.chatEl), 320);
    }

    // Dok kanan = wadah di layout (prop dock) atau laci tetap di tepi kanan layar. Elemen chat yang SAMA dipindah.
    setDock(on) {
      if (on) {
        if (!this.dockHost) {
          this.dockHost = document.querySelector(this.el.dataset.dock || '') || document.body.appendChild(Object.assign(document.createElement('aside'), {className: 'dl-lm-dock is-fixed'}));
          // Bilah dok milik LayerMenu: tetap terlihat walau percakapan sedang terbuka (bilah daftar chat tersembunyi).
          this.dockHost.insertAdjacentHTML('afterbegin', '<div class="dl-lm-dock__bar"><i class="fa-solid fa-comment-dots"></i><b>Chat</b>'
            + '<button type="button" class="dl-lm__ib" data-lm-undock title="' + esc(T('lm_undock')) + '" aria-label="' + esc(T('lm_undock_chat')) + '"><i class="fa-solid fa-table-columns"></i></button>'
            + '<button type="button" class="dl-lm__ib" data-lm-dockx title="' + esc(T('lm_close_chat')) + '" aria-label="' + esc(T('lm_close_chat')) + '"><i class="fa-solid fa-xmark"></i></button></div>');
          this.dockHost.addEventListener('click', e => { if (e.target.closest('[data-lm-undock]')) this.setDock(false); else if (e.target.closest('[data-lm-dockx]')) this.closeChat(); });
        }
        this.dockHost.appendChild(this.chatEl);
        Object.assign(this.st, {dock: true, show: this.st.show === 'chat' ? 'menu' : this.st.show});
      } else {
        this.chatHost.appendChild(this.chatEl);
        Object.assign(this.st, {dock: false, show: 'chat'});
      }
      // Memindah elemen mereset posisi gulir: kembalikan ke pesan terakhir.
      const box = this.chatEl.querySelector('[data-wa-msgs]'); if (box) box.scrollTop = box.scrollHeight;
      this.apply();
    }

    // Klik avatar / nama: Profile terbuka -> tutup (kembali ke menu atau chat), selain itu buka.
    toggleProfile() {
      if (this.st.show === 'profile') this.show(this.st.chat && !this.st.dock ? 'chat' : 'menu');
      else this.openProfile();
    }

    openProfile(pageKey = null) {
      if (!this.profOv) return;
      this.closePage(false);
      this.st.show = 'profile'; this.apply();
      if (pageKey) { const par = this.page(pageKey)?.dataset.lmParent; if (par) this.pstack = [par]; this.openPage(pageKey, false); }
    }

    // Halaman profil = <section data-lm-page> dari slot, DIPINDAH (bukan disalin) supaya isian form tidak hilang.
    // Dibuka dari halaman lain -> halaman itu dicatat (pstack) supaya Back kembali ke sana, bukan ke daftar.
    openPage(k, remember = true) {
      const s = this.page(k); if (!s) return;
      if (s.hasAttribute('data-lm-modal')) return this.openModal(s);
      const cur = this.pdet.querySelector('[data-lm-page]');
      if (cur) { if (remember) this.pstack.push(cur.dataset.lmPage); this.pages.appendChild(cur); }
      this.pdet.innerHTML = BACK + `<div class="dl-lm__title"><i class="${esc(this.faOf(s.dataset.icon))}"></i><b>${esc(s.dataset.label)}</b></div>`;
      this.pdet.appendChild(s);
      this.slide(this.ptrack, 1);
      this.emit('dl:layer-menu-page', {key: k});
    }

    // Halaman bertanda data-lm-modal (mis. Setup) dibuka sebagai dialog di tengah layar, masuk dari kanan ke kiri. Isinya = halaman anak
    // (data-lm-children) + halaman lain bertanda data-lm-in="<kunci>", ditumpuk sebagai bagian bertajuk. Elemen DIPINDAH, bukan disalin.
    openModal(par) {
      this.closeModal(false);
      const key = par.dataset.lmPage;
      const secs = [...this.pages.querySelectorAll(':scope > [data-lm-page]')].filter(x => x.dataset.lmParent === key);
      if (!this.drw) {
        const d = this.drw = document.createElement('div');
        d.className = 'dl-lm-drw';
        d.innerHTML = '<div class="dl-lm-drw__sc" data-drw-x></div><section class="dl-lm-drw__p" role="dialog" aria-modal="true" tabindex="-1"><header class="dl-lm-drw__h"><i></i><b></b>'
          + '<button type="button" class="dl-mbtn dl-mbtn--c" data-drw-x>' + esc(T('close')) + '</button></header><div class="dl-lm-drw__b"></div></section>';
        document.body.appendChild(d);
        d.addEventListener('click', e => { if (e.target.closest('[data-drw-x]')) this.closeModal(); });
        d.addEventListener('keydown', e => {
          if (e.key === 'Escape') { e.stopPropagation(); this.closeModal(); }
          if (e.key === 'Tab') {
            const f = [...d.querySelectorAll('a[href],button,input,select,textarea,[tabindex="0"]')].filter(x => !x.disabled && x.offsetParent);
            if (!f.length) return;
            const a = document.activeElement, first = f[0], last = f[f.length - 1];
            if (e.shiftKey && (a === first || a === d.querySelector('section'))) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && a === last) { e.preventDefault(); first.focus(); }
          }
        });
      }
      const d = this.drw, p = d.querySelector('.dl-lm-drw__p'), body = d.querySelector('.dl-lm-drw__b');
      this.drwFrom = document.activeElement;
      d.querySelector('header i').className = this.faOf(par.dataset.icon);
      d.querySelector('header b').textContent = par.dataset.label;
      p.setAttribute('aria-label', par.dataset.label);
      body.innerHTML = '';
      secs.forEach(x => {
        const w = document.createElement('section'); w.className = 'dl-lm-drw__s';
        w.innerHTML = `<h4 class="dl-lm-drw__t"><i class="${esc(this.faOf(x.dataset.icon))}"></i>${esc(x.dataset.label)}</h4>`;
        w.appendChild(x); body.appendChild(w);
      });
      d.getBoundingClientRect();
      d.classList.add('is-on');
      setTimeout(() => p.focus(), 30);
      this.emit('dl:layer-menu-page', {key});
    }

    closeModal(animate = true) {
      const d = this.drw; if (!d || !d.classList.contains('is-on')) return;
      d.classList.remove('is-on');
      const back = () => { if (!d.classList.contains('is-on')) d.querySelectorAll('.dl-lm-drw__s > [data-lm-page]').forEach(x => this.pages.appendChild(x)); };
      animate && !matchMedia('(prefers-reduced-motion: reduce)').matches ? setTimeout(back, 320) : back();
      if (this.drwFrom?.isConnected) this.drwFrom.focus();
    }

    closePage(animate = true) {
      this.closeModal(false);
      if (!this.pdet) return;
      if (animate && this.pstack.length) return this.openPage(this.pstack.pop(), false);
      this.pstack = [];
      const s = this.pdet.querySelector('[data-lm-page]'); if (s) this.pages.appendChild(s);
      if (animate) this.slide(this.ptrack, 0); else { this.slide(this.ptrack, 0, false); this.pdet.innerHTML = ''; }
    }

    async action(btn) {
      const k = btn.dataset.lmAct;
      if (this.page(k)) return this.openProfile(k);
      if (k === 'chat' && !this.emit('dl:layer-menu-action', {key: k}, true)) return;
      if (k === 'chat') return this.openChat();
      if (btn.dataset.confirm && !await confirmBox({title: btn.dataset.label, message: btn.dataset.confirm, tone: btn.classList.contains('is-danger') ? 'danger' : 'warn', ok: btn.dataset.label})) return;
      if (this.emit('dl:layer-menu-action', {key: k}, true)) toast(`${btn.dataset.label} (${T('preview')})`);
    }
  }
  Devlib.define("LayerMenu", LayerMenu);
})(window.Devlib);
