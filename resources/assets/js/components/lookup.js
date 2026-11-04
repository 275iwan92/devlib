/* DevLib · Lookup */
(function (Devlib) {
  'use strict';
  const {$, $$, bindPanel, toast, esc, T} = Devlib.h;
  /* ======================= SMALL COMPONENTS ======================= */
  // Lookup: cari di semua kolom, pilih baris -> nilai tersimpan + field lain terisi otomatis.
  class Lookup extends Devlib.Component {
    static selector = "[data-dl-lookup]";
    init() {
      this.rows = JSON.parse(this.el.dataset.rows || '[]');
      this.fill = JSON.parse(this.el.dataset.fill || '{}');
      this.p = bindPanel(this.el, () => { if (this.p.search) { this.p.search.value = ''; this.p.search.dispatchEvent(new Event('input')); this.p.search.focus(); } });
      // Opt-in data-server-search: server yang memutuskan baris mana cocok (kata bebas urutan), jadi klien tidak menyaring ulang,
      // panah/Enter memilih baris tanpa mengirim form, dan isian otomatis tidak menimpa ketikan pengguna dengan nilai kosong.
      const server = this.el.hasAttribute('data-server-search'), list = $(this.el, '[data-dl-dd-list]');
      // Saklar ruang cari (def scopes): nilai terpilih ikut ke server sebagai ?scope=; klik mengganti ruang lalu mencari ulang. Tidak ada saklar = this.scope kosong.
      const scopeEl = server ? $(this.el, '[data-dl-scope]') : null;
      this.scope = scopeEl ? scopeEl.dataset.scope : null;
      if (scopeEl) {
        scopeEl.addEventListener('click', e => {
          const b = e.target.closest('button[data-v]'); if (!b || b.classList.contains('is-on')) return;
          this.scope = b.dataset.v;
          $$(scopeEl, 'button').forEach(x => x.classList.toggle('is-on', x === b));
          if (this.p.search) { this.p.search.dispatchEvent(new Event('input')); this.p.search.focus(); }
        });
      }
      // Mode remote (data-url): ketik min. 2 huruf -> cari ke server, baris diganti hasilnya.
      if (this.el.dataset.url && this.p.search) {
        const cols = JSON.parse(this.el.dataset.columns || '[]');
        let timer = null, seq = 0;
        this.p.search.addEventListener('input', () => {
          clearTimeout(timer);
          if (server) $$(list, '.dl-dd__item').forEach(it => { it.style.display = ''; });
          const q = this.p.search.value.trim(); if (q.length < 2) return;
          timer = setTimeout(async () => {
            const my = ++seq, u = new URL(this.el.dataset.url, location.origin); u.searchParams.set('q', q);
            if (this.scope) u.searchParams.set('scope', this.scope);
            const res = await fetch(u, {headers: {Accept: 'application/json'}, credentials: 'same-origin'}).then(x => x.ok ? x.json() : []).catch(() => []);
            if (my !== seq) return;
            this.rows = res;
            // Baris {_notice: teks} dari server = catatan di dalam daftar (tidak bisa dipilih).
            list.innerHTML = res.map((r, i) => r._notice ? `<tr class="dl-lookup__note"><td colspan="${cols.length}">${esc(r._notice)}</td></tr>`
              : `<tr class="dl-dd__item" data-i="${i}">${cols.map(c => `<td>${Devlib.h.esc(r[c] ?? '')}</td>`).join('')}</tr>`).join('')
              || `<tr><td colspan="${cols.length}">${esc(T('l_not_found'))}.</td></tr>`;
            if (server) { const first = $(list, 'tr.dl-dd__item'); if (first) first.classList.add('is-active'); }
          }, 250);
        });
      }
      if (server && this.p.search) {
        const items = () => $$(list, 'tr.dl-dd__item');
        const move = d => {
          const it = items(); if (!it.length) return;
          const i = Math.max(0, Math.min(it.length - 1, it.findIndex(x => x.classList.contains('is-active')) + d));
          it.forEach(x => x.classList.remove('is-active')); it[i].classList.add('is-active'); it[i].scrollIntoView({block: 'nearest'});
        };
        this.p.search.addEventListener('keydown', e => {
          if (e.key === 'ArrowDown') { e.preventDefault(); move(1); }
          else if (e.key === 'ArrowUp') { e.preventDefault(); move(-1); }
          else if (e.key === 'Enter') { e.preventDefault(); const a = items().find(x => x.classList.contains('is-active')) || items()[0]; if (a) a.click(); }
          else if (e.key === 'Escape' && this.p.panel.classList.contains('is-open')) { e.stopPropagation(); this.p.close(); }
        });
      }
      // Tombol pilihan tampilan di kepala panel (choices): disimpan server (POST {value}), lalu pencarian diulang.
      const choice = $(this.el, '[data-dl-choice]');
      if (server && choice && this.p.search) {
        choice.addEventListener('click', async e => {
          const b = e.target.closest('button[data-v]'); if (!b || b.classList.contains('is-on')) return;
          const res = await fetch(choice.dataset.url, {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': window.DL_CSRF || ''}, body: JSON.stringify({value: b.dataset.v})}).catch(() => null);
          if (!res || !res.ok) return;
          $$(choice, 'button').forEach(x => x.classList.toggle('is-on', x === b));
          this.p.search.dispatchEvent(new Event('input'));
          this.p.search.focus();
        });
      }
      list.addEventListener('click', e => {
        const tr = e.target.closest('tr.dl-dd__item'); if (!tr) return;
        const r = this.rows[+tr.dataset.i];
        $(this.el, '[data-dl-value]').value = r[this.el.dataset.key];
        $(this.el, '[data-dl-dd-text]').textContent = r[this.el.dataset.label];
        this.p.close();
        $(this.el, '[data-dl-value]').dispatchEvent(new Event('change', {bubbles: true}));
        const scope = this.el.closest('form') || document;
        Object.entries(this.fill).forEach(([field, col]) => {
          const box = scope.querySelector(`[data-dl-field="${field}"]`);
          const input = box ? (box.querySelector('[data-dl-value]') || box.querySelector('input,textarea,select')) : scope.querySelector(`[name="${field}"]`);
          if (!input) return;
          const val = r[col] ?? '';
          // server-search: nilai kosong dari server tidak menimpa ketikan pengguna; hanya menggantikan isian otomatis sebelumnya.
          if (server && val === '' && input.value !== '' && input.value !== input.dataset.dlAuto) return;
          input.dataset.dlAuto = val;
          input.value = val;
          input.dispatchEvent(new Event('input', {bubbles: true})); input.dispatchEvent(new Event('change', {bubbles: true}));
          const txt = box && box.querySelector('[data-dl-dd-text]');
          if (txt) txt.textContent = val;
          if (box) { box.classList.remove('is-filled'); void box.offsetWidth; box.classList.add('is-filled'); }
        });
      });
      if (this.el.hasAttribute('data-create')) {
        const btn = $(this.el, '[data-dl-lookup-create]'), label = this.el.dataset.label;
        this.p.search.addEventListener('input', () => {
          const q = this.p.search.value.trim();
          btn.hidden = !q || this.rows.some(r => String(r[label]).toLowerCase() === q.toLowerCase());
          $(btn, '[data-dl-lookup-new]').textContent = q;
        });
        // Tambah baru dari dalam pilihan: baris baru langsung terpilih; aplikasi menyimpannya ke master lewat event dl:lookup-create.
        btn.addEventListener('click', () => {
          const q = this.p.search.value.trim(), r = {[this.el.dataset.key]: 'new:' + q, [label]: q};
          this.rows.push(r);
          const tr = document.createElement('tr'); tr.className = 'dl-dd__item'; tr.dataset.i = this.rows.length - 1;
          tr.innerHTML = $$(this.el, 'thead th').map((th, i) => `<td>${i === 0 ? '(' + esc(T('lu_new')) + ')' : ''}</td>`).join('');
          tr.children[Math.min(1, tr.children.length - 1)].textContent = q;
          $(this.el, '[data-dl-dd-list]').appendChild(tr);
          $(this.el, '[data-dl-value]').value = r[this.el.dataset.key]; $(this.el, '[data-dl-dd-text]').textContent = q; btn.hidden = true; this.p.close();
          $(this.el, '[data-dl-value]').dispatchEvent(new Event('change', {bubbles: true}));
          this.el.dispatchEvent(new CustomEvent('dl:lookup-create', {bubbles: true, detail: {text: q}}));
          toast(T('lu_added') + ': ' + q);
        });
      }
    }
  }
  Devlib.define("Lookup", Lookup);
})(window.Devlib);
