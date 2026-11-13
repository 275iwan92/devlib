/* REMARK
 * Tujuan       : Perilaku x-dl::transfer (dual list): dua kotak dengan filter masing-masing, pilih banyak (klik, Shift untuk rentang), tombol tengah (pindah terpilih / semua yang tampil) atau drag and drop; baris kanan punya pilihan tambahan (mis. mesin, job).
 * Sumber hulu  : config JSON dari Devlib\Components\Transfer (items, rows, fields, idKey, max, text).
 * Input        : klik baris (pilih), Shift+klik (rentang), klik dua kali (pindah satu), tombol tengah, drag baris, filter, pilihan di baris kanan.
 * Output       : input hidden name[i][idKey] dan name[i][key] di dalam form; form diberi tanda belum disimpan (dirty-guard DevLib).
 * Dipakai oleh : x-dl::transfer. Undo form mengembalikan isi awal lewat fillForm -> el.dlTransfer.reset().
 * Status       : Aktif.
 */
(function (Devlib) {
  'use strict';
  const {$, esc, dirtyForms} = Devlib.h;

  // "TM-00009 · AGUS BUDI" -> kode kecil + nama; tanpa " · " seluruhnya nama. Inisial = dua huruf pertama nama.
  const parts = label => { const s = String(label), i = s.indexOf(' · '); return i > 0 ? {code: s.slice(0, i), name: s.slice(i + 3)} : {code: '', name: s}; };
  const initials = name => name.split(/\s+/).filter(Boolean).slice(0, 2).map(w => w[0]).join('').toUpperCase() || '?';

  class Transfer extends Devlib.Component {
    static selector = "[data-dl-transfer]";
    init() {
      this.cfg = JSON.parse(this.el.dataset.config);
      this.initial = this.cfg.rows.map(r => ({...r}));
      this.rows = this.initial.map(r => ({...r}));
      this.q = {left: '', right: ''};
      this.sel = {left: new Set(), right: new Set()};
      this.anchor = {left: null, right: null};
      this.fresh = new Set();      // id yang baru dipindah (sorotan singkat)
      this.drag = null;            // {ids, from}
      this.el.dlTransfer = this;

      this.el.querySelectorAll('[data-t-filter]').forEach(i => i.addEventListener('input', e => { this.q[i.dataset.tFilter] = e.target.value.trim().toLowerCase(); this.draw(); }));
      this.el.addEventListener('click', e => {
        const act = e.target.closest('[data-t-act]');
        if (act) return this.act(act.dataset.tAct);
        const it = e.target.closest('[data-t-item]');
        if (it && !e.target.closest('select,button')) this.pick(it.dataset.tFrom, it.dataset.tItem, e.shiftKey);
      });
      this.el.addEventListener('dblclick', e => {
        const it = e.target.closest('[data-t-item]');
        if (it && !e.target.closest('select,button')) this.move([it.dataset.tItem], it.dataset.tFrom === 'left' ? 'right' : 'left');
      });
      this.el.addEventListener('keydown', e => {
        const it = e.target.closest('[data-t-item]');
        if (!it || e.target.closest('select')) return;
        if (e.key === ' ') { e.preventDefault(); this.pick(it.dataset.tFrom, it.dataset.tItem, e.shiftKey); }
        if (e.key === 'Enter') { e.preventDefault(); this.move([it.dataset.tItem], it.dataset.tFrom === 'left' ? 'right' : 'left'); }
      });
      this.el.addEventListener('change', e => {
        const s = e.target.closest('[data-t-field]');
        if (!s) return;
        const row = this.rows.find(r => String(r.id) === s.dataset.tRow);
        if (row) { row[s.dataset.tField] = s.value; this.sync(); this.markDirty(); this.flag(); }
      });

      // Drag and drop (HTML5): membawa semua baris terpilih bila baris yang diseret ikut terpilih; kotak tujuan = kotak lawan.
      this.el.addEventListener('dragstart', e => {
        const it = e.target.closest('[data-t-item]');
        if (!it) return;
        const from = it.dataset.tFrom, id = it.dataset.tItem;
        const ids = this.sel[from].has(id) ? [...this.sel[from]] : [id];
        this.drag = {ids, from};
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', ids.join(','));
        this.el.classList.add('is-dragging');
        setTimeout(() => this.el.querySelectorAll('[data-t-item]').forEach(n => n.classList.toggle('is-lifted', n.dataset.tFrom === from && ids.includes(n.dataset.tItem))));
      });
      this.el.addEventListener('dragend', () => {
        this.el.classList.remove('is-dragging');
        this.el.querySelectorAll('.is-over,.is-lifted').forEach(n => n.classList.remove('is-over', 'is-lifted'));
        this.drag = null;
      });
      this.el.addEventListener('dragover', e => {
        const box = e.target.closest('[data-t-box]');
        if (!this.drag || !box || box.dataset.tBox === this.drag.from) return;
        e.preventDefault();
        box.classList.add('is-over');
      });
      this.el.addEventListener('dragleave', e => {
        const box = e.target.closest('[data-t-box]');
        if (box && !box.contains(e.relatedTarget)) box.classList.remove('is-over');
      });
      this.el.addEventListener('drop', e => {
        const box = e.target.closest('[data-t-box]');
        if (!this.drag || !box || box.dataset.tBox === this.drag.from) return;
        e.preventDefault();
        box.classList.remove('is-over');
        this.move(this.drag.ids, box.dataset.tBox);
      });
      this.draw();
    }

    // Isi awal (saat form dibuka) kembali; dipanggil Undo form lewat fillForm.
    reset() {
      this.rows = this.initial.map(r => ({...r}));
      this.sel.left.clear(); this.sel.right.clear();
      this.draw();
    }

    markDirty() {
      const form = this.el.closest('[data-dl-guard]');
      if (form) { dirtyForms.add(form); form.classList.add('is-dirty'); }
    }

    // Yang tampil di tiap kotak (sesudah filter), berurutan seperti digambar.
    shown(side) {
      const q = this.q[side], items = this.cfg.items;
      if (side === 'left') {
        const used = new Set(this.rows.map(r => String(r.id)));
        return Object.keys(items).filter(k => !used.has(k) && (!q || String(items[k]).toLowerCase().includes(q)));
      }
      return this.rows.filter(r => !q || String(items[r.id] ?? r.id).toLowerCase().includes(q)).map(r => String(r.id));
    }

    room() { return this.cfg.max > 0 ? Math.max(0, this.cfg.max - this.rows.length) : Infinity; }

    // Klik: pilih / batal pilih satu baris; Shift+klik: rentang dari klik terakhir di kotak itu.
    pick(side, id, range) {
      const list = this.shown(side), s = this.sel[side];
      if (range && this.anchor[side] && list.includes(this.anchor[side])) {
        const a = list.indexOf(this.anchor[side]), b = list.indexOf(id);
        list.slice(Math.min(a, b), Math.max(a, b) + 1).forEach(k => s.add(k));
      } else { s.has(id) ? s.delete(id) : s.add(id); this.anchor[side] = id; }
      this.draw();
    }

    // Tombol tengah: terpilih atau semua yang tampil, ke kanan atau ke kiri.
    act(a) {
      const [what, to] = a.split('-'), from = to === 'right' ? 'left' : 'right';
      const ids = what === 'all' ? this.shown(from) : this.shown(from).filter(k => this.sel[from].has(k));
      this.move(ids, to);
    }

    // Pindah beberapa id: ke kanan = baris baru (nilai awal dari default, atau baris terakhir untuk field sticky); ke kiri = baris dibuang.
    move(ids, to) {
      ids = ids.map(String);
      this.fresh = new Set();
      if (to === 'right') {
        const used = new Set(this.rows.map(r => String(r.id)));
        ids.filter(id => !used.has(id)).slice(0, this.room()).forEach(id => {
          const last = this.rows[this.rows.length - 1] || {}, row = {id};
          this.cfg.fields.forEach(f => { row[f.key] = f.sticky && last[f.key] ? last[f.key] : (f.default || ''); });
          this.rows.push(row);
          this.fresh.add(id);
        });
      } else {
        this.rows = this.rows.filter(r => !ids.includes(String(r.id)));
        ids.forEach(id => this.fresh.add(id));
      }
      this.sel.left.clear(); this.sel.right.clear();
      this.draw();
      this.markDirty();
      setTimeout(() => { this.fresh = new Set(); this.el.querySelectorAll('.is-fresh').forEach(n => n.classList.remove('is-fresh')); }, 900);
    }

    sync() {
      this.el.querySelectorAll('[data-t-hidden]').forEach(n => n.remove());
      const h = document.createElement('span');
      h.hidden = true;
      h.dataset.tHidden = '';
      h.innerHTML = this.rows.map((r, i) => `<input type="hidden" name="${esc(this.cfg.name)}[${i}][${esc(this.cfg.idKey)}]" value="${esc(r.id)}">`
        + this.cfg.fields.map(f => `<input type="hidden" name="${esc(this.cfg.name)}[${i}][${esc(f.key)}]" value="${esc(r[f.key] ?? '')}">`).join('')).join('');
      this.el.appendChild(h);
    }

    // Baris yang pilihannya belum lengkap diberi garis merah (server tetap yang memutuskan).
    flag() {
      this.el.querySelectorAll('[data-t-item][data-t-from="right"]').forEach(n => {
        const row = this.rows.find(r => String(r.id) === n.dataset.tItem);
        n.classList.toggle('is-missing', !!row && this.cfg.fields.some(f => f.required && !row[f.key]));
      });
    }

    avatar(id, selected) {
      const p = parts(this.cfg.items[id] ?? id);
      return `<span class="dl-transfer__avatar">${selected ? '<i class="fa-solid fa-check"></i>' : esc(initials(p.name))}</span>`;
    }

    who(id) {
      const p = parts(this.cfg.items[id] ?? id);
      return `<span class="dl-transfer__who"><b>${esc(p.name)}</b>${p.code ? `<small>${esc(p.code)}</small>` : ''}</span>`;
    }

    item(side, id) {
      const on = this.sel[side].has(id), fresh = this.fresh.has(id) ? ' is-fresh' : '';
      const base = `class="dl-transfer__item${side === 'right' ? ' dl-transfer__item--row' : ''}${on ? ' is-selected' : ''}${fresh}" draggable="true" tabindex="0" role="option" aria-selected="${on}" data-t-item="${esc(id)}" data-t-from="${side}"`;
      if (side === 'left') return `<div ${base}>${this.avatar(id, on)}${this.who(id)}</div>`;
      const r = this.rows.find(x => String(x.id) === id);
      return `<div ${base}>${this.avatar(id, on)}${this.who(id)}<span class="dl-transfer__fields">${this.cfg.fields.map(f => `<select data-t-field="${esc(f.key)}" data-t-row="${esc(id)}" aria-label="${esc(f.label)}" title="${esc(f.label)}">`
        + `<option value="">${esc(f.label)}...</option>${Object.entries(f.options).map(([k, v]) => `<option value="${esc(k)}"${String(r[f.key]) === k ? ' selected' : ''}>${esc(v)}</option>`).join('')}</select>`).join('')}</span></div>`;
    }

    draw() {
      const T = this.cfg.text, total = {left: Object.keys(this.cfg.items).length - this.rows.length, right: this.rows.length};
      ['left', 'right'].forEach(side => {
        const list = this.shown(side), filtered = !!this.q[side];
        const keep = new Set(list);
        this.sel[side].forEach(k => { if (!keep.has(k)) this.sel[side].delete(k); });   // terpilih yang tersaring hilang dari pilihan
        $(this.el, `[data-t-count="${side}"]`).textContent = filtered ? `${list.length} / ${total[side]}` : String(total[side]);
        const n = this.sel[side].size, selEl = $(this.el, `[data-t-sel="${side}"]`);
        selEl.textContent = n ? T.selected.replace(':n', n) : '';
        selEl.hidden = !n;
        $(this.el, `[data-t-list="${side}"]`).innerHTML = list.map(id => this.item(side, id)).join('')
          || `<div class="dl-transfer__empty"><i class="fa-regular ${side === 'left' ? 'fa-circle-check' : 'fa-hand-pointer'}"></i><span>${esc(side === 'left' ? T.empty_left : T.empty_right)}</span></div>`;
      });
      const can = {
        'all-right': this.shown('left').length > 0 && this.room() > 0, 'sel-right': this.sel.left.size > 0 && this.room() > 0,
        'sel-left': this.sel.right.size > 0, 'all-left': this.shown('right').length > 0,
      };
      this.el.querySelectorAll('[data-t-act]').forEach(b => { b.disabled = !can[b.dataset.tAct]; });
      this.sync();
      this.flag();
    }
  }
  Devlib.define("Transfer", Transfer);
})(window.Devlib);
