/* DevLib · Calculator (x-dl::calculator)
 * Kalkulator pop up ala Windows 11. Tab Calculator: eksekusi langsung kiri-ke-kanan (2 + 3 × 4 = 20), % = angka pertama × angka kedua / 100.
 * Tab Converter: satuan (rumus nilai_dasar = nilai × f + o ; hasil = (nilai_dasar − o2) / f2, suhu memakai o) dan kurs
 * (hasil = nilai × kurs_asal / kurs_tujuan; kurs null = belum diisi, tidak dihitung). Tab History: riwayat hitungan per perangkat.
 * Data besaran / mata uang dari atribut data-quantities / data-currencies. Angka tampil ikut bahasa (Devlib.h.NUM). */
(function (Devlib) {
  'use strict';
  const {$, $$, esc, NUM} = Devlib.h;
  const MAX_DIGITS = 16, MAX_HIST = 30;

  // Angka -> teks datar 12 digit signifikan, tanpa notasi ilmiah (0.1 + 0.2 = 0.3).
  const plain = v => {
    let s = String(+(+v).toPrecision(12));
    if (/e/i.test(s)) s = (+s).toLocaleString('en-US', {useGrouping: false, maximumFractionDigits: 12});
    return s === '-0' ? '0' : s;
  };
  // Teks datar ("-1234.5", "12.") -> tampilan: ribuan dan desimal ikut bahasa.
  const show = s => {
    const neg = s.startsWith('-'), [i, f] = s.replace('-', '').split('.');
    return (neg ? '−' : '') + i.replace(/\B(?=(\d{3})+(?!\d))/g, NUM.grp) + (f !== undefined ? NUM.dec + f : '');
  };

  class Calculator extends Devlib.Component {
    static selector = "[data-dl-wcalc]";
    init() {
      // Modal dipindah ke body: dipasang di kotak sapaan (overflow + tumpukan sendiri), lapisan gelapnya tidak menutup jam dan cuaca.
      const mov = this.el.closest('.dl-mov'); if (mov && mov.parentElement !== document.body) document.body.appendChild(mov);
      const d = this.el.dataset;
      this.d = d; this.key = d.key; this.base = d.base;
      this.quantities = JSON.parse(d.quantities || '[]'); this.featured = JSON.parse(d.featured || '[]');
      this.currencies = this.prepCurrencies(JSON.parse(d.currencies || '[]'));
      this.hist = []; try { this.hist = JSON.parse(localStorage.getItem(this.key) || '[]'); } catch (e) { /* tanpa penyimpanan: riwayat kosong */ }
      this.reset(); this.cin = '0'; this.cat = null;
      this.disp = $(this.el, '[data-wc-disp]'); this.expr = $(this.el, '[data-wc-expr]');
      this.panes = $$(this.el, '[data-wc-pane]'); this.tabs = $$(this.el, '[data-wc-tab]');

      this.tabs.forEach(t => t.addEventListener('click', () => this.tab(t.dataset.wcTab)));
      this.el.addEventListener('click', e => {
        const k = e.target.closest('[data-wc-k]'); if (k) return this.key_(k.dataset.wcK);
        const c = e.target.closest('[data-wc-ck]'); if (c) return this.ckey(c.dataset.wcCk);
        const cat = e.target.closest('[data-wc-cat]'); if (cat) return this.setCat(cat.dataset.wcCat);
        const h = e.target.closest('[data-wc-hi]'); if (h) { this.cur = this.hist[+h.dataset.wcHi].r; this.acc = null; this.op = null; this.fresh = true; this.expr.textContent = ''; this.draw(); return this.tab('calc'); }
        if (e.target.closest('[data-wc-swap]')) return this.swap();
        if (e.target.closest('[data-wc-clear]')) { this.hist = []; this.saveHist(); this.drawHist(); }
      });
      $(this.el, '[data-wc-from]').addEventListener('change', () => this.conv());
      $(this.el, '[data-wc-to]').addEventListener('change', () => this.conv());
      this.el.addEventListener('keydown', e => this.keyboard(e));
      this.buildCats(); this.setCat('CUR'); this.draw();
    }

    // ---------- Calculator ----------
    reset() { this.acc = null; this.op = null; this.cur = '0'; this.fresh = true; this.err = false; }
    apply(a, op, b) { return op === '+' ? a + b : op === '−' ? a - b : op === '×' ? a * b : b === 0 ? null : a / b; }

    key_(k) {
      if (this.err) this.reset();
      const c = this.cur;
      if (/^\d$/.test(k)) { if (this.fresh) { this.cur = k; this.fresh = false; } else if (c.replace(/\D/g, '').length < MAX_DIGITS) this.cur = c === '0' ? k : c === '-0' ? '-' + k : c + k; }
      else if (k === '.') { if (this.fresh) { this.cur = '0.'; this.fresh = false; } else if (!c.includes('.')) this.cur = c + '.'; }
      else if (k === '±') { if (c !== '0') this.cur = c.startsWith('-') ? c.slice(1) : '-' + c; }
      else if (k === '⌫') { if (!this.fresh) this.cur = c.length > 1 && c !== '-0' && !(c.length === 2 && c.startsWith('-')) ? c.slice(0, -1) : '0'; }
      else if (k === 'CE') { this.cur = '0'; this.fresh = true; }
      else if (k === 'C') { this.reset(); this.expr.textContent = ''; }
      else if (k === '%') { this.cur = this.acc === null ? '0' : plain(this.acc * parseFloat(c) / 100); this.fresh = false; }
      else if (k === '=') this.equals();
      else this.operator(k);
      this.draw();
    }

    operator(k) {
      if (this.op && this.fresh) { this.op = k; this.expr.textContent = show(plain(this.acc)) + ' ' + k; return; }
      let v = parseFloat(this.cur);
      if (this.op) { v = this.apply(this.acc, this.op, v); if (v === null) return this.fail(); }
      this.acc = v; this.op = k; this.fresh = true;
      this.expr.textContent = show(plain(v)) + ' ' + k;
    }

    equals() {
      if (!this.op) { this.expr.textContent = show(this.cur) + ' ='; this.fresh = true; return; }
      const b = parseFloat(this.cur), r = this.apply(this.acc, this.op, b);
      if (r === null) return this.fail();
      const row = {a: plain(this.acc), op: this.op, b: plain(b), r: plain(r)};
      this.hist.unshift(row); this.hist.length = Math.min(this.hist.length, MAX_HIST); this.saveHist();
      this.expr.textContent = show(row.a) + ' ' + row.op + ' ' + show(row.b) + ' =';
      this.cur = row.r; this.acc = null; this.op = null; this.fresh = true;
    }

    fail() { this.reset(); this.err = true; this.expr.textContent = ''; }

    draw() {
      this.disp.textContent = this.err ? this.d.lErr : show(this.cur);
      const n = this.disp.textContent.length;   // tulisan mengecil bila angka panjang agar tidak terpotong
      this.disp.style.fontSize = n > 14 ? '60%' : n > 10 ? '80%' : '';
    }

    // ---------- History ----------
    saveHist() { try { localStorage.setItem(this.key, JSON.stringify(this.hist)); } catch (e) { /* abaikan */ } }
    drawHist() {
      const ul = $(this.el, '[data-wc-hist]');
      ul.innerHTML = this.hist.length
        ? this.hist.map((h, i) => `<li><button type="button" data-wc-hi="${i}"><span>${esc(show(h.a))} ${h.op} ${esc(show(h.b))} =</span><b>${esc(show(h.r))}</b></button></li>`).join('')
        : `<li class="dl-wcalc__none">${esc(this.d.lEmpty)}</li>`;
      $(this.el, '[data-wc-clear]').hidden = !this.hist.length;
    }

    tab(name) {
      this.tabs.forEach(t => { const on = t.dataset.wcTab === name; t.classList.toggle('is-on', on); t.setAttribute('aria-selected', on); });
      this.panes.forEach(p => { p.hidden = p.dataset.wcPane !== name; });
      if (name === 'hist') this.drawHist();
    }

    // ---------- Converter ----------
    // Mata uang: yang sudah punya kurs di depan; mata uang dasar (base) selalu ada dengan kurs 1.
    prepCurrencies(list) {
      const l = list.map(c => Object.assign({}, c, c.c === this.base ? {r: 1} : {}));
      if (!l.some(c => c.c === this.base)) l.unshift({c: this.base, n: this.base, r: 1});
      return [...l.filter(c => c.r != null), ...l.filter(c => c.r == null)];
    }

    buildCats() {
      const box = $(this.el, '[data-wc-cats]'), byKey = Object.fromEntries(this.quantities.map(q => [q.key, q]));
      const chips = [['CUR', this.d.lCurrency], ...this.featured.filter(k => byKey[k]).map(k => [k, byKey[k].label])];
      box.innerHTML = chips.map(([k, l]) => `<button type="button" data-wc-cat="${esc(k)}">${esc(l)}</button>`).join('')
        + `<select data-wc-all aria-label="${esc(this.d.lAll)}"><option value="">${esc(this.d.lAll)}</option>${this.quantities.map(q => `<option value="${esc(q.key)}">${esc(q.label)}</option>`).join('')}</select>`;
      $(box, '[data-wc-all]').addEventListener('change', e => { if (e.target.value) this.setCat(e.target.value); });
    }

    items() { return this.cat === 'CUR' ? this.currencies : this.quantities.find(q => q.key === this.cat).units; }

    setCat(key) {
      this.cat = key;
      $$(this.el, '[data-wc-cat]').forEach(b => b.classList.toggle('is-on', b.dataset.wcCat === key));
      const all = $(this.el, '[data-wc-all]'); all.value = $(this.el, `[data-wc-cat="${key}"]`) ? '' : key; all.classList.toggle('is-on', !!all.value);
      const items = this.items(), opt = (x, i) => `<option value="${i}">${esc(x.c)} — ${esc(x.n)}${this.cat === 'CUR' && x.r == null ? ' (' + esc(this.d.lNorate) + ')' : ''}</option>`;
      const from = $(this.el, '[data-wc-from]'), to = $(this.el, '[data-wc-to]');
      from.innerHTML = to.innerHTML = items.map(opt).join('');
      // Bawaan: mata uang = pertama yang berkurs selain dasar -> dasar; satuan = pertama -> kedua.
      const fi = this.cat === 'CUR' ? Math.max(0, items.findIndex(c => c.r != null && c.c !== this.base)) : 0;
      from.value = fi; to.value = this.cat === 'CUR' ? items.findIndex(c => c.c === this.base) : Math.min(1, items.length - 1);
      this.conv();
    }

    swap() { const f = $(this.el, '[data-wc-from]'), t = $(this.el, '[data-wc-to]'), v = f.value; f.value = t.value; t.value = v; this.conv(); }

    ckey(k) {
      const c = this.cin;
      if (/^\d$/.test(k)) { if (c.replace(/\D/g, '').length < MAX_DIGITS) this.cin = c === '0' ? k : c === '-0' ? '-' + k : c + k; }
      else if (k === '.') { if (!c.includes('.')) this.cin = c + '.'; }
      else if (k === '±') { if (c !== '0') this.cin = c.startsWith('-') ? c.slice(1) : '-' + c; }
      else if (k === '⌫') this.cin = c.length > 1 && !(c.length === 2 && c.startsWith('-')) ? c.slice(0, -1) : '0';
      else if (k === 'C') this.cin = '0';
      this.conv();
    }

    conv() {
      const items = this.items(), a = items[+$(this.el, '[data-wc-from]').value], b = items[+$(this.el, '[data-wc-to]').value];
      $(this.el, '[data-wc-cin]').textContent = show(this.cin);
      const out = $(this.el, '[data-wc-cout]'), note = $(this.el, '[data-wc-cnote]');
      const v = parseFloat(this.cin) || 0;
      note.textContent = '';
      if (!a || !b) { out.textContent = '–'; return; }
      let r, one;
      if (this.cat === 'CUR') {
        if (a.r == null || b.r == null) {   // kurs belum ada: tidak ada angka karangan, tunjuk layar pengisinya
          out.textContent = '–';
          const miss = [a, b].filter(x => x.r == null).map(x => x.c).join(', ');
          note.innerHTML = `<span class="dl-wcalc__warn">${esc(this.d.lNorate)}: ${esc(miss)}</span>` + (this.d.rateUrl ? ` <a href="${esc(this.d.rateUrl)}">${esc(this.d.lSetrate)}</a>` : '');
          return;
        }
        r = v * a.r / b.r; one = a.r / b.r;
        note.textContent = `${this.d.lRate}: 1 ${a.c} = ${show(plain(one))} ${b.c}`;
      } else {
        r = ((v * a.f + a.o) - b.o) / b.f; one = ((a.f + a.o) - b.o) / b.f;
        note.textContent = `1 ${a.c} = ${show(plain(one))} ${b.c}`;
      }
      out.textContent = show(plain(r)) + ' ' + b.c;
    }

    // ---------- Keyboard ----------
    keyboard(e) {
      if (e.ctrlKey || e.altKey || e.metaKey || e.target.matches('select')) return;
      const pane = this.panes.find(p => !p.hidden)?.dataset.wcPane, k = e.key;
      const map = {'*': '×', x: '×', '/': '÷', '-': '−', ',': '.', Enter: '=', Backspace: '⌫', Delete: 'CE'};
      const m = map[k] ?? k;
      if (pane === 'calc' && /^(\d|\.|\+|−|×|÷|=|%|⌫|CE)$/.test(m)) { e.preventDefault(); this.key_(m); }
      else if (pane === 'conv' && /^(\d|\.|⌫)$/.test(m)) { e.preventDefault(); this.ckey(m); }
      else if (pane === 'conv' && k === 'Delete') { e.preventDefault(); this.ckey('C'); }
    }
  }
  Devlib.define("Calculator", Calculator);
})(window.Devlib);
