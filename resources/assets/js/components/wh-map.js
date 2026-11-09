/* DevLib · WhMap */
(function (Devlib) {
  'use strict';
  const {$, $$, esc} = Devlib.h;

  // Peta gudang: klik bin = panel isi (kapasitas, QR, item, aksi); cari = sorot bin yang cocok; legenda = saring status;
  // tab lantai; mode Edit Layout = geser / ubah ukuran area lalu simpan ke server (posisi yang berlaku = yang tersimpan).
  class WhMap extends Devlib.Component {
    static selector = "[data-dl-whmap]";
    init() {
      try { this.cfg = JSON.parse(this.el.dataset.config || '{}'); } catch (e) { this.cfg = {}; }
      this.bins = this.cfg.bins || {}; this.tx = this.cfg.text || {}; this.panel = $(this.el, '[data-wm-panel]');
      this.on('click', '[data-wm-cell]', (e, c) => { if (!this.el.classList.contains('is-edit') && !this.armed(c)) this.pick(c); });
      this.on('keydown', '[data-wm-cell]', (e, c) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); if (!this.armed(c)) this.pick(c); } });
      this.on('click', '[data-wm-zone]', (e, z) => { if (!this.el.classList.contains('is-edit') && !e.target.closest('[data-wm-cell]')) this.zone(z); });
      this.on('click', '[data-wm-goto]', (e, b) => {
        const cell = $$(this.el, '[data-wm-cell]').find(c => c.dataset.wmCell.split(',').includes(b.dataset.wmGoto));
        if (cell) this.pick(cell, b.dataset.wmGoto);
      });
      this.on('click', '[data-wm-floor]', (e, b) => this.floor(b.dataset.wmFloor));
      this.on('click', '[data-wm-only]', (e, b) => {
        const on = !b.classList.contains('is-active');
        $$(this.el, '[data-wm-only]').forEach(x => x.classList.remove('is-active'));
        b.classList.toggle('is-active', on); this.only = on ? b.dataset.wmOnly : null; this.filter();
      });
      this.on('input', '[data-wm-search]', (e, i) => { this.q = i.value.trim().toLowerCase(); this.filter(); });
      this.on('click', '[data-wm-level]', (e, b) => this.show(b.dataset.wmLevel));
      this.on('click', '[data-wm-post]', (e, b) => this.post(b));
      this.on('click', '[data-wm-edit]', (e, b) => { const on = this.el.classList.toggle('is-edit'); b.classList.toggle('dl-btn--primary', on); b.classList.toggle('dl-btn--secondary', !on); });
      this.drag();
      this.move();
      if (this.cfg.selected != null && this.bins[this.cfg.selected]) {
        const cell = $$(this.el, '[data-wm-cell]').find(c => c.dataset.wmCell.split(',').includes(String(this.cfg.selected)));
        if (cell) { const svg = cell.closest('[data-wm-svg]'); if (svg) this.floor(svg.dataset.wmSvg); this.pick(cell, String(this.cfg.selected)); }
      }
    }

    floor(no) {
      $$(this.el, '[data-wm-svg]').forEach(s => { s.hidden = s.dataset.wmSvg !== String(no); });
      $$(this.el, '[data-wm-floor]').forEach(b => b.classList.toggle('is-active', b.dataset.wmFloor === String(no)));
    }

    // Cari + saring status: sel yang tidak cocok diredupkan (tetap terlihat supaya posisi tetap terbaca).
    filter() {
      let hit = 0, firstFloor = null;
      const active = !!(this.q || this.only);
      $$(this.el, '[data-wm-cell]').forEach(c => {
        const okQ = !this.q || c.dataset.find.includes(this.q);
        const flag = {DEAD: 'dead', EXPIRY: 'expiry', REMNANT: 'remnant', BURIED: 'buried'}[this.only];
        const okS = !this.only || (flag ? c.dataset[flag] === '1' : (c.dataset.status === this.only && c.dataset.dead !== '1'));
        const ok = okQ && okS;
        c.classList.toggle('is-dim', !ok); c.classList.toggle('is-hit', ok && active);
        if (ok && active) { hit++; firstFloor ??= c.closest('[data-wm-svg]').dataset.wmSvg; }
      });
      const found = $(this.el, '[data-wm-found]');
      if (found) found.textContent = active ? (hit ? hit + ' ' + (this.tx.matches || '') : (this.tx.nomatch || '')) : '';
      // Hasil hanya ada di lantai lain: pindah ke lantai itu.
      if (hit && firstFloor && !$$(this.el, '[data-wm-svg]:not([hidden]) .is-hit').length) this.floor(firstFloor);
    }

    pick(cell, id = null) {
      $$(this.el, '[data-wm-cell].is-sel').forEach(c => c.classList.remove('is-sel'));
      cell.classList.add('is-sel');
      this.ids = cell.dataset.wmCell.split(',').filter(Boolean);
      this.show(id && this.ids.includes(id) ? id : this.ids[0]);
    }

    // Klik area (bukan kotak bin) = ringkasan semua bin berisi di area itu; klik satu bin = buka panel bin tersebut.
    zone(z) {
      const t = this.tx;
      $$(this.el, '[data-wm-cell].is-sel').forEach(c => c.classList.remove('is-sel'));
      const ids = $$(z, '[data-wm-cell]').flatMap(c => c.dataset.wmCell.split(',').filter(Boolean));
      const used = ids.filter(i => this.bins[i] && ((this.bins[i].items || []).length || (this.bins[i].stack || []).length));
      const rows = used.map(i => {
        const b = this.bins[i], list = (b.stack || []).length
          ? b.stack.map(u => `<li>${esc(u.name || '')}<small>${esc(u.sub || '')}</small></li>`).join('')
          : (b.items || []).map(x => `<li>${esc(x.name || '')}<small>${esc(x.code || '')} · ${esc(x.qty || '')}</small></li>`).join('');
        return `<div class="dl-whmap__zbin"><button type="button" data-wm-goto="${esc(i)}">${esc(b.code || i)}</button><ul>${list}</ul></div>`;
      }).join('');
      const name = $(z, '.dl-whmap__zname');
      this.panel.innerHTML = `<div class="dl-whmap__phead"><div><small>${esc(t.area || 'Area')}</small><b>${esc(name ? name.textContent : '')}</b>`
        + `<span>${used.length} / ${ids.length} ${esc(t.bins || 'bins')}</span></div></div>`
        + (rows || `<p class="dl-whmap__hint">${esc(t.nostock)}</p>`);
    }

    show(id) {
      const b = this.bins[id]; if (!b) return;
      const t = this.tx, st = b.dead ? 'DEAD' : (b.status || 'EMPTY'), stText = b.dead ? t.dead : (this.cfg.status[b.status] || b.status);
      const levels = this.ids && this.ids.length > 1
        ? '<div class="dl-whmap__levels">' + this.ids.map(i => {
          const x = this.bins[i] || {};
          return `<button type="button" class="${String(i) === String(id) ? 'is-active' : ''} ${x.dead ? 'is-dead' : ''}" data-wm-level="${esc(i)}">${esc(x.level || x.code || i)}</button>`;
        }).join('') + '</div>'
        : '';
      // Baris kapasitas: caps (berat, volume, luas tapak, ...) atau satu baris lama capacity + pct.
      const fill = pct => pct != null ? `<i><em class="dl-whmap__fill dl-whmap__fill--${pct > 100 ? 'over' : (st === 'DEAD' ? 'dead' : st.toLowerCase())}" style="width:${Math.min(100, Math.max(0, pct))}%"></em></i>` : '';
      const caps = (b.caps && b.caps.length ? b.caps : (b.capacity ? [{label: t.capacity, value: b.capacity, pct: b.pct}] : []));
      const cap = caps.length ? '<div class="dl-whmap__cap">' + caps.map(c => `<span>${esc(c.label || '')}</span><b>${esc(c.value || '')}</b>${fill(c.pct)}${c.note ? `<small>${esc(c.note)}</small>` : ''}`).join('') + '</div>' : '';
      const tags = i => (i.tags || []).map(g => `<em class="dl-whmap__tag ${g.tone ? 'dl-whmap__tag--' + esc(g.tone) : ''}">${esc(g.label || '')}</em>`).join('');
      const rows = (b.items || []).map(i => {
        const idle = i.dead ? `<span class="dl-badge dl-badge--danger dl-badge--pill">${esc(t.dead)}</span><small>${esc(i.idle || '')}</small>` : esc(i.idle || '');
        return `<tr class="${i.dead ? 'is-dead' : ''}"><td><b>${esc(i.name || '')}</b><small>${esc(i.code || '')}${i.lot ? ' · ' + esc(i.lot) : ''}</small>${tags(i) ? `<small>${tags(i)}</small>` : ''}</td><td class="num">${esc(i.qty || '')}</td><td>${idle}</td></tr>`;
      }).join('');
      // Tumpukan tampak samping: satuan paling atas di baris pertama, nomor 1 = paling bawah; palet digambar dengan alas kayu.
      const swap = (u, dir, ok) => ok && this.cfg.restackUrl
        ? `<button type="button" class="dl-whmap__swap" title="${esc(t[dir] || '')}" data-wm-post="${esc(String(this.cfg.restackUrl).replace('{id}', encodeURIComponent(u.id)).replace('{dir}', dir))}"><i class="fa-solid fa-arrow-${dir}"></i></button>`
        : '';
      const stack = (b.stack || []).length
        ? `<div class="dl-whmap__stack"><small>${esc(t.stack || '')}</small>` + b.stack.map(u =>
          `<div class="dl-whmap__unit dl-whmap__unit--${esc(u.kind || 'pallet')} ${u.tone === 'danger' ? 'is-dead' : ''}"><b class="dl-whmap__uno">${esc(u.no)}</b>`
          + `<div class="dl-whmap__ubody"><b>${esc(u.name || '')}</b><small>${esc(u.sub || '')}</small>${tags(u) ? `<small>${tags(u)}</small>` : ''}</div>`
          + `<span class="dl-whmap__uacts">${swap(u, 'up', u.up)}${swap(u, 'down', u.down)}</span></div>`
          + (u.kind === 'pallet' ? '<i class="dl-whmap__wood"></i>' : '')).join('') + '<i class="dl-whmap__ground"></i></div>'
        : '';
      const table = rows
        ? `<table class="dl-whmap__items"><thead><tr><th>${esc(t.item)}</th><th class="num">${esc(t.qty)}</th><th>${esc(t.idle)}</th></tr></thead><tbody>${rows}</tbody></table>`
        : `<p class="dl-whmap__hint">${esc(t.nostock)}</p>`;
      const has = (b.items || []).length > 0;
      const acts = (this.cfg.actions || [])
        .filter(a => !a.when || (a.when === 'stock' && has) || (a.when === 'usable' && b.status !== 'BLOCKED') || (a.when === 'blocked' && b.status === 'BLOCKED'))
        .map(a => {
          const url = String(a.url || '').replace('{id}', encodeURIComponent(id)).replace('{code}', encodeURIComponent(b.code || ''));
          const cls = `dl-btn dl-btn--sm dl-btn--${a.tone || 'secondary'}`, ic = a.icon ? `<i class="fa-solid ${esc(a.icon)}"></i> ` : '';
          return a.post
            ? `<button type="button" class="${cls}" data-wm-post="${esc(url)}" data-wm-confirm="${esc(a.confirm || '')}" data-wm-reason="${a.reason ? 1 : 0}">${ic}${esc(a.label)}</button>`
            : `<a class="${cls}" href="${esc(url)}"${a.blank ? ' target="_blank"' : ''}>${ic}${esc(a.label)}</a>`;
        }).join('');
      const qr = b.qr
        ? `<div class="dl-whmap__qr"><span data-dl-qrcode data-text="${esc(b.qr)}" data-size="92"></span><div><small>${esc(t.label)}</small><b>${esc(b.code || '')}</b>${b.capacityLabel ? `<span>${esc(b.capacityLabel)}</span>` : ''}</div></div>`
        : '';
      this.panel.innerHTML = `<div class="dl-whmap__phead"><div><small>${esc(t.bin)}</small><b>${esc(b.code || '')}</b><span>${esc(b.title || '')}</span></div><span class="dl-badge dl-badge--pill dl-whmap__st dl-whmap__st--${st.toLowerCase()}">${esc(stText)}</span></div>`
        + levels + cap + (b.note ? `<p class="dl-whmap__note">${esc(b.note)}</p>` : '') + stack + qr + table + (acts ? `<div class="dl-whmap__acts">${acts}</div>` : '');
      Devlib.mountOne('Qrcode', this.panel);
      this.emit('dl:whmap-select', {id, code: b.code});
    }

    send(url, body) {
      return fetch(url, {method: 'POST', credentials: 'same-origin', body: JSON.stringify(body),
        headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.cfg.csrf || window.DL_CSRF || ''}})
        .then(r => r.json().catch(() => ({})).then(j => r.ok ? j : Promise.reject(j.message || '')));
    }

    post(btn) {
      const go = why => this.send(btn.dataset.wmPost, {reason: why === true ? '' : why})
        .then(j => { if (j.redirect) location.href = j.redirect; else location.reload(); })
        .catch(m => Devlib.toast(typeof m === 'string' && m ? m : this.tx.failed, 'danger'));
      if (!btn.dataset.wmConfirm) return go(true);
      Devlib.confirm({message: btn.dataset.wmConfirm, reason: btn.dataset.wmReason === '1'}).then(ok => { if (ok) go(ok); });
    }

    // ---- Move Stock: seret bin berisi ke bin lain (atau klik asal lalu klik tujuan = jalan tanpa seret, WCAG 2.5.7) -> pop up qty -> POST moveUrl.
    // Posting stok sebenarnya dilakukan server (Stock Transfer); peta hanya titik masuk. Sukses = muat ulang peta + bar Undo (pembatalan dokumen yang sama).
    hasStock(cell) { return cell.dataset.wmCell.split(',').filter(Boolean).some(i => ((this.bins[i] || {}).items || []).length); }

    // Mode Move Stock aktif: klik pertama pada sel berisi = asal (return false -> panel bin tetap terbuka), klik kedua pada sel lain = tujuan (return true).
    armed(cell) {
      if (!this.el.classList.contains('is-move')) return false;
      if (this.src && this.src !== cell) { const from = this.src; this.disarm(); this.moveOpen(from, cell); return true; }
      this.disarm();
      if (this.hasStock(cell)) { this.src = cell; cell.classList.add('is-dragsrc'); Devlib.toast(this.tx.moveClick, 'info'); }
      return false;
    }
    disarm() { if (this.src) this.src.classList.remove('is-dragsrc'); this.src = null; }

    move() {
      if (!this.cfg.moveUrl) return;
      const tx = this.tx, modalId = this.cfg.id + '-move';
      const modal = () => document.querySelector('[data-dl-modal-id="' + modalId + '"]');
      this.on('click', '[data-wm-movemode]', (e, b) => {
        const on = this.el.classList.toggle('is-move'); b.classList.toggle('dl-btn--primary', on); b.classList.toggle('dl-btn--secondary', !on);
        if (!on) this.disarm(); else Devlib.toast(tx.moveHint, 'info');
      });
      // Seret: ambang 5 px supaya klik biasa tidak jadi seret; Esc membatalkan; tab lantai di bawah penunjuk = ganti lantai (tunda 450 ms).
      let d = null, dwell = null;
      const under = e => document.elementFromPoint(e.clientX, e.clientY);
      const clear = () => { clearTimeout(dwell); dwell = null; $$(this.el, '.is-drop').forEach(x => x.classList.remove('is-drop')); };
      const stop = () => { if (d && d.ghost) d.ghost.remove(); if (d) d.cell.classList.remove('is-dragsrc'); clear(); d = null; };
      this.el.addEventListener('pointerdown', e => {
        if (!this.el.classList.contains('is-move') || e.button) return;
        const cell = e.target.closest('[data-wm-cell]');
        if (cell && this.hasStock(cell)) d = {cell, x: e.clientX, y: e.clientY, on: false};
      });
      document.addEventListener('pointermove', e => {
        if (!d) return;
        if (!d.on) {
          if (Math.hypot(e.clientX - d.x, e.clientY - d.y) < 5) return;
          d.on = true; this.disarm(); d.cell.classList.add('is-dragsrc');
          d.ghost = document.createElement('div'); d.ghost.className = 'dl-whmap__ghost';
          d.ghost.textContent = d.cell.dataset.wmCell.split(',').map(i => (this.bins[i] || {}).code).filter(Boolean)[0] || '';
          document.body.appendChild(d.ghost);
        }
        d.ghost.style.left = e.clientX + 12 + 'px'; d.ghost.style.top = e.clientY + 12 + 'px';
        const t = under(e), cell = t && t.closest ? t.closest('[data-wm-cell]') : null, tab = t && t.closest ? t.closest('[data-wm-floor]') : null;
        $$(this.el, '.is-drop').forEach(x => x.classList.remove('is-drop'));
        if (cell && cell !== d.cell && this.el.contains(cell)) cell.classList.add('is-drop');
        if (tab && !tab.classList.contains('is-active')) { if (!dwell) dwell = setTimeout(() => { this.floor(tab.dataset.wmFloor); dwell = null; }, 450); } else { clearTimeout(dwell); dwell = null; }
      });
      document.addEventListener('pointerup', e => {
        if (!d) return;
        const was = d, t = was.on ? under(e) : null; stop();
        const cell = t && t.closest ? t.closest('[data-wm-cell]') : null;
        if (was.on && cell && cell !== was.cell && this.el.contains(cell)) this.moveOpen(was.cell, cell);
      });
      document.addEventListener('pointercancel', stop);
      document.addEventListener('keydown', e => { if (e.key === 'Escape') { stop(); this.disarm(); } });

      // Tombol tingkat (rak) di pop up: pilih bin asal / tujuan lain di sel yang sama.
      document.addEventListener('click', e => {
        const b = e.target.closest('[data-wm-mv-level]'); if (!b || !this.mv || !modal() || !modal().contains(b)) return;
        this.mv[b.dataset.wmMvSide] = b.dataset.wmMvLevel; this.moveRender();
      });
      const ok = modal() && modal().querySelector('[data-wm-mv-ok]');
      if (ok) ok.addEventListener('click', () => this.moveGo(modalId));
      this.undoBar();
    }

    // Pop up: asal -> tujuan (rak bertingkat: pilih tingkat), satu kolom qty per item (bawaan = seluruh stok).
    moveOpen(srcCell, tgtCell) {
      const ids = c => c.dataset.wmCell.split(',').filter(Boolean);
      const src = ids(srcCell).filter(i => ((this.bins[i] || {}).items || []).length), tgt = ids(tgtCell);
      if (!src.length || !tgt.length) return;
      const usable = tgt.filter(i => (this.bins[i] || {}).status !== 'BLOCKED');
      this.mv = {src, tgt, from: src[0], to: usable[0] || tgt[0], floor: (srcCell.closest('[data-wm-svg]') || {dataset: {}}).dataset.wmSvg};
      this.moveRender();
      Devlib.open(this.cfg.id + '-move');
    }

    moveRender() {
      const m = this.mv, t = this.tx, root = document.querySelector('[data-dl-modal-id="' + this.cfg.id + '-move"]'); if (!root) return;
      const lv = (side, list, cur) => list.length > 1 ? '<div class="dl-whmap__levels">' + list.map(i => '<button type="button" class="' + (i === cur ? 'is-active' : '') + '" data-wm-mv-side="' + side + '" data-wm-mv-level="' + esc(i) + '">' + esc((this.bins[i] || {}).level || (this.bins[i] || {}).code || i) + '</button>').join('') + '</div>' : '';
      const f = this.bins[m.from] || {}, g = this.bins[m.to] || {};
      $(root, '[data-wm-mv-help]').textContent = t.moveHelp;
      $(root, '[data-wm-mv-route]').innerHTML = '<div><small>' + esc(t.moveFrom) + '</small><b>' + esc(f.code || '') + '</b><span>' + esc(f.title || '') + '</span>' + lv('from', m.src, m.from) + '</div>'
        + '<i class="fa-solid fa-arrow-right"></i>'
        + '<div><small>' + esc(t.moveTo) + '</small><b>' + esc(g.code || '') + '</b><span>' + esc(g.title || '') + '</span>' + (g.capacity ? '<span>' + esc(g.capacity) + '</span>' : '')
        + (g.status === 'BLOCKED' ? '<em class="dl-whmap__tag dl-whmap__tag--danger">' + esc(this.cfg.status.BLOCKED) + '</em>' : '') + lv('to', m.tgt, m.to) + '</div>';
      const box = $(root, '[data-wm-mv-lines]'), tpl = $(root, '[data-wm-mv-row]');
      box.innerHTML = '';
      (f.items || []).forEach((it, n) => {
        const row = document.createElement('div'); row.className = 'dl-whmap__mvline'; row.dataset.key = it.key || '';
        row.innerHTML = '<div><b>' + esc(it.name || '') + '</b><small>' + esc(it.code || '') + (it.lot ? ' · ' + esc(it.lot) : '') + ' · ' + esc(t.moveOnHand) + ': ' + esc(it.qty || '') + '</small></div><div data-wm-mv-in></div>';
        $(row, '[data-wm-mv-in]').appendChild(document.importNode(tpl.content, true));
        const input = $(row, 'input[name=qty]'); input.value = it.qtyIn || ''; input.id = this.cfg.id + '-mvq-' + n;
        box.appendChild(row);
      });
      Devlib.mountOne('Field', box);
      this.moveErr('');
    }
    moveErr(msg) {
      const r = document.querySelector('[data-dl-modal-id="' + this.cfg.id + '-move"]'), a = r && $(r, '[data-wm-mv-err]');
      if (a) { a.textContent = msg; a.classList.toggle('is-show', !!msg); }
    }

    moveGo(modalId) {
      const m = this.mv, root = document.querySelector('[data-dl-modal-id="' + modalId + '"]'), ok = $(root, '[data-wm-mv-ok]');
      const lines = $$(root, '.dl-whmap__mvline').map(r => ({key: r.dataset.key, qty: $(r, 'input[name=qty]').value.trim()})).filter(l => /[1-9]/.test(l.qty));
      if (!lines.length) return this.moveErr(this.tx.moveNone);
      ok.disabled = true;
      this.send(this.cfg.moveUrl, {from: +m.from, to: +m.to, lines})
        .then(j => {
          try { sessionStorage.setItem('dl-whmap-undo', JSON.stringify({message: j.message || this.tx.moveDone, undo: j.undo || null, floor: m.floor})); } catch (e) {}
          location.reload();
        })
        .catch(msg => { ok.disabled = false; this.moveErr(typeof msg === 'string' && msg ? msg : this.tx.failed); });
    }

    // Sesudah muat ulang: bar "Moved ... [Undo]" (sekali tampil); Undo = POST ke alamat pembatal dari server, lalu muat ulang lagi.
    undoBar() {
      let o = null; try { o = JSON.parse(sessionStorage.getItem('dl-whmap-undo') || 'null'); sessionStorage.removeItem('dl-whmap-undo'); } catch (e) {}
      if (!o) return;
      if (o.floor) this.floor(o.floor);
      const bar = $(this.el, '[data-wm-undo]'); if (!bar) return;
      bar.hidden = false;
      bar.innerHTML = '<i class="fa-solid fa-circle-check"></i> <span>' + esc(o.message) + '</span>'
        + (o.undo ? ' <button type="button" class="dl-btn dl-btn--secondary dl-btn--sm" data-wm-undo-go><i class="fa-solid fa-rotate-left"></i> ' + esc(this.tx.undo) + '</button>' : '')
        + ' <button type="button" class="dl-whmap__x" data-wm-undo-x aria-label="Close">&times;</button>';
      bar.querySelector('[data-wm-undo-x]').addEventListener('click', () => { bar.hidden = true; });
      const go = bar.querySelector('[data-wm-undo-go]');
      if (go) go.addEventListener('click', () => {
        go.disabled = true;
        this.send(o.undo, {})
          .then(() => { try { sessionStorage.setItem('dl-whmap-undo', JSON.stringify({message: this.tx.undoDone, undo: null, floor: o.floor})); } catch (e) {} location.reload(); })
          .catch(msg => { bar.hidden = true; Devlib.toast(typeof msg === 'string' && msg ? msg : this.tx.failed, 'danger'); });
      });
    }

    // Edit Layout: geser area (badan) atau ubah ukurannya (pegangan sudut kanan-bawah); lompat tiap 0,5 m, tidak keluar kanvas.
    drag() {
      if (!this.cfg.saveUrl) return;
      const S = this.cfg.scale || 10, snap = v => Math.round(v * 2) / 2;
      let cur = null;
      const pt = (svg, e) => { const p = svg.createSVGPoint(); p.x = e.clientX; p.y = e.clientY; const m = p.matrixTransform(svg.getScreenCTM().inverse()); return {x: m.x / S, y: m.y / S}; };
      this.el.addEventListener('pointerdown', e => {
        if (!this.el.classList.contains('is-edit')) return;
        const z = e.target.closest('[data-wm-zone]'); if (!z) return;
        const svg = z.closest('svg'), p = pt(svg, e);
        cur = {z, svg, resize: !!e.target.closest('[data-wm-grip]'), sx: p.x, sy: p.y, x: +z.dataset.x, y: +z.dataset.y, w: +z.dataset.w, h: +z.dataset.h};
        z.classList.add('is-drag'); e.preventDefault();
      });
      this.el.addEventListener('pointermove', e => {
        if (!cur) return;
        const p = pt(cur.svg, e), dx = p.x - cur.sx, dy = p.y - cur.sy, rect = $(cur.z, '.dl-whmap__zrect'), grip = $(cur.z, '[data-wm-grip]');
        if (cur.resize) {
          cur.nw = Math.max(1, snap(cur.w + dx)); cur.nh = Math.max(1, snap(cur.h + dy));
          rect.setAttribute('width', cur.nw * S); rect.setAttribute('height', cur.nh * S);
          if (grip) { grip.setAttribute('x', cur.nw * S - 3); grip.setAttribute('y', cur.nh * S - 3); }
        } else {
          cur.nx = Math.max(0, snap(cur.x + dx)); cur.ny = Math.max(0, snap(cur.y + dy));
          cur.z.setAttribute('transform', `translate(${cur.nx * S} ${cur.ny * S})`);
        }
      });
      const end = () => {
        if (!cur) return;
        const c = cur; cur = null; c.z.classList.remove('is-drag');
        const body = {zone: c.z.dataset.wmZone, x: c.nx ?? c.x, y: c.ny ?? c.y, w: c.nw ?? c.w, h: c.nh ?? c.h};
        if (body.x === c.x && body.y === c.y && body.w === c.w && body.h === c.h) return;
        this.send(this.cfg.saveUrl, body)
          .then(() => {
            Object.assign(c.z.dataset, {x: body.x, y: body.y, w: body.w, h: body.h}); Devlib.toast(this.tx.saved, 'success');
            // Ukuran berubah = letak bin di dalam area dihitung ulang server.
            if (c.resize) { try { sessionStorage.setItem('dl-whmap-edit', '1'); } catch (e) {} location.reload(); }
          })
          .catch(m => { Devlib.toast(typeof m === 'string' && m ? m : this.tx.failed, 'danger'); location.reload(); });
      };
      this.el.addEventListener('pointerup', end); this.el.addEventListener('pointercancel', end);
      try { if (sessionStorage.getItem('dl-whmap-edit')) { sessionStorage.removeItem('dl-whmap-edit'); $(this.el, '[data-wm-edit]')?.click(); } } catch (e) {}
    }
  }
  Devlib.define("WhMap", WhMap);
})(window.Devlib);
