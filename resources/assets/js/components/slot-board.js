/* REMARK
 * Tujuan       : Perilaku x-dl::slot-board: kolam orang (kiri) dan matriks baris x kolom (kanan); seret orang ke petak, centang banyak lalu klik petak, klik dua kali untuk mengembalikan; saring nama dan kelompok.
 * Sumber hulu  : config JSON dari Devlib\Components\SlotBoard (rows, cols, people, placed, locked, text).
 * Input        : seret token (HTML5 drag and drop), klik token (centang), klik petak (tempatkan yang dicentang), klik dua kali token di petak (kembalikan), ketik nama, klik chip kelompok.
 * Output       : input hidden name[id] = "rowKey|colKey" di dalam form; form diberi tanda belum disimpan (dirty-guard DevLib); event dl:slot-change {detail: {placed}}.
 * Dipakai oleh : x-dl::slot-board. Undo form mengembalikan isi awal lewat fillForm -> el.dlSlots.reset().
 * Status       : Aktif.
 */
(function (Devlib) {
  'use strict';
  const {$, esc, dirtyForms} = Devlib.h;

  const initials = name => String(name).split(/\s+/).filter(Boolean).slice(0, 2).map(w => w[0]).join('').toUpperCase() || '?';

  class SlotBoard extends Devlib.Component {
    static selector = "[data-dl-slots]";
    init() {
      this.cfg = JSON.parse(this.el.dataset.config);
      this.locked = this.cfg.locked;
      this.byId = Object.fromEntries(this.cfg.people.map(p => [String(p.id), p]));
      this.initial = {...this.cfg.placed};
      this.placed = {...this.initial};     // {id: "rowKey|colKey"}
      this.sel = new Set();
      this.q = '';
      this.group = '';
      this.drag = null;
      this.el.dlSlots = this;

      $(this.el, '[data-s-search]').addEventListener('input', e => { this.q = e.target.value.trim().toLowerCase(); this.drawPool(); });
      this.el.addEventListener('click', e => {
        if (this.locked) return;
        const chip = e.target.closest('[data-s-group]');
        if (chip) { this.group = chip.dataset.sGroup; return this.drawPool(); }
        const tok = e.target.closest('[data-s-id]');
        if (tok) { const id = tok.dataset.sId; this.sel.has(id) ? this.sel.delete(id) : this.sel.add(id); return this.draw(); }
        const cell = e.target.closest('[data-s-cell]');
        if (cell && this.sel.size) this.place([...this.sel], cell.dataset.sCell);
      });
      this.el.addEventListener('dblclick', e => {
        const tok = e.target.closest('[data-s-id]');
        if (!this.locked && tok && this.placed[tok.dataset.sId]) this.place([tok.dataset.sId], null);
      });

      // Seret: yang dicentang ikut semua bila token yang diseret termasuk yang dicentang; petak atau kolam = tujuan.
      this.el.addEventListener('dragstart', e => {
        const tok = e.target.closest('[data-s-id]');
        if (!tok || this.locked) return e.preventDefault();
        const id = tok.dataset.sId;
        this.drag = this.sel.has(id) ? [...this.sel] : [id];
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', this.drag.join(','));
        setTimeout(() => this.el.querySelectorAll('[data-s-id]').forEach(n => n.classList.toggle('is-lifted', this.drag?.includes(n.dataset.sId))));
      });
      this.el.addEventListener('dragend', () => {
        this.drag = null;
        this.el.querySelectorAll('.is-over,.is-lifted').forEach(n => n.classList.remove('is-over', 'is-lifted'));
      });
      this.el.addEventListener('dragover', e => {
        const t = e.target.closest('[data-s-cell],[data-s-pool]');
        if (!this.drag || !t) return;
        e.preventDefault();
        t.classList.add('is-over');
      });
      this.el.addEventListener('dragleave', e => {
        const t = e.target.closest('[data-s-cell],[data-s-pool]');
        if (t && !t.contains(e.relatedTarget)) t.classList.remove('is-over');
      });
      this.el.addEventListener('drop', e => {
        const t = e.target.closest('[data-s-cell],[data-s-pool]');
        if (!this.drag || !t) return;
        e.preventDefault();
        t.classList.remove('is-over');
        this.place(this.drag, t.dataset.sCell || null);
      });
      this.draw();
    }

    // Isi awal (saat form dibuka) kembali; dipanggil Undo form lewat fillForm.
    reset() {
      this.placed = {...this.initial};
      this.sel.clear();
      this.draw();
    }

    markDirty() {
      const form = this.el.closest('[data-dl-guard]');
      if (form) { dirtyForms.add(form); form.classList.add('is-dirty'); }
    }

    // Tempatkan beberapa orang di satu petak (cell = "rowKey|colKey"); cell null = kembali ke kolam.
    place(ids, cell) {
      ids.forEach(id => { cell ? this.placed[id] = cell : delete this.placed[id]; });
      this.sel.clear();
      this.draw();
      this.markDirty();
      this.el.dispatchEvent(new CustomEvent('dl:slot-change', {bubbles: true, detail: {placed: {...this.placed}}}));
    }

    sync() {
      this.el.querySelectorAll('[data-s-hidden]').forEach(n => n.remove());
      const h = document.createElement('span');
      h.hidden = true;
      h.dataset.sHidden = '';
      h.innerHTML = Object.entries(this.placed).map(([id, cell]) => `<input type="hidden" name="${esc(this.cfg.name)}[${esc(id)}]" value="${esc(cell)}">`).join('');
      this.el.appendChild(h);
    }

    tok(p, cls = '') {
      const id = String(p.id), on = this.sel.has(id);
      return `<button type="button" class="dl-slots__tok${on ? ' is-sel' : ''}${p.flag ? ' is-flag' : ''}${cls}" ${this.locked ? 'disabled' : 'draggable="true"'} data-s-id="${esc(id)}" title="${esc(p.name)}${p.code ? ' · ' + esc(p.code) : ''}" aria-pressed="${on}">${on ? '<i class="fa-solid fa-check"></i>' : esc(initials(p.name))}</button>`;
    }

    drawPool() {
      const T = this.cfg.text, free = this.cfg.people.filter(p => !this.placed[String(p.id)]);
      const groups = [...new Set(this.cfg.people.map(p => p.group).filter(Boolean))];
      const shown = free.filter(p => (!this.group || p.group === this.group) && (!this.q || (p.name + ' ' + (p.code || '')).toLowerCase().includes(this.q)));
      $(this.el, '[data-s-count="pool"]').textContent = String(free.length);
      $(this.el, '[data-s-chips]').innerHTML = groups.length > 1 ? [''].concat(groups).map(g => `<button type="button" class="dl-slots__chip${this.group === g ? ' is-on' : ''}" data-s-group="${esc(g)}">${esc(g || T.all)}</button>`).join('') : '';
      $(this.el, '[data-s-tokens]').innerHTML = shown.map(p => this.tok(p)).join('') || `<div class="dl-slots__empty">${esc(T.pool_empty)}</div>`;
      const n = this.sel.size;
      $(this.el, '[data-s-hint]').textContent = this.locked ? T.locked : (n ? T.selected.replace(':n', n) + ' · ' : '') + T.hint;
    }

    drawMatrix() {
      const T = this.cfg.text, {rows, cols} = this.cfg;
      const per = {};   // "r|c" -> [people]
      Object.entries(this.placed).forEach(([id, cell]) => { (per[cell] ||= []).push(this.byId[id] || {id, name: id, short: id}); });
      let html = `<div class="dl-slots__corner">${esc(this.cfg.rowTitle || '')}</div>` + cols.map(c => `<div class="dl-slots__colh"><b>${esc(c.label)}</b>${c.sub ? `<small>${esc(c.sub)}</small>` : ''}</div>`).join('');
      rows.forEach(r => {
        const total = cols.reduce((n, c) => n + (per[r.key + '|' + c.key] || []).length, 0);
        const need = r.need > 0 ? r.need * cols.filter(c => (per[r.key + '|' + c.key] || []).length).length : 0;
        const state = need ? (total >= need ? `<span class="dl-slots__tag is-ok">${esc(T.enough)}</span>` : `<span class="dl-slots__tag is-bad">${esc(T.short.replace(':n', need - total))}</span>`) : '';
        html += `<div class="dl-slots__rowh"><b>${esc(r.label)}</b>${r.sub ? `<small>${esc(r.sub)}</small>` : ''}<small>${esc(T.people.replace(':n', total))}</small>${state}</div>`;
        cols.forEach(c => {
          const key = r.key + '|' + c.key, list = per[key] || [], low = r.need > 0 && list.length > 0 && list.length < r.need;
          html += `<div class="dl-slots__cell${low ? ' is-low' : ''}" data-s-cell="${esc(key)}">`
            + (r.need > 0 && list.length ? `<span class="dl-slots__cnt${low ? ' is-low' : ''}">${list.length} / ${r.need}</span>` : '')
            + list.map(p => `<span class="dl-slots__p">${this.tok(p)}<small>${esc(p.short || String(p.name).split(' ')[0])}</small></span>`).join('')
            + (this.sel.size && !this.locked ? `<span class="dl-slots__drop">${esc(T.drop)}</span>` : '') + `</div>`;
        });
      });
      const m = $(this.el, '[data-s-matrix]');
      m.style.setProperty('--dl-slots-cols', cols.length);
      m.innerHTML = html;
    }

    draw() {
      this.drawPool();
      this.drawMatrix();
      this.sync();
    }
  }
  Devlib.define("SlotBoard", SlotBoard);
})(window.Devlib);
