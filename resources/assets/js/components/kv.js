/* DevLib · Kv */
(function (Devlib) {
  'use strict';
  const {$, $$, esc, T, fillState} = Devlib.h;

  class Kv extends Devlib.Component {
    static selector = "[data-dl-kv]";
    init() {
      this.body = $(this.el, '[data-dl-kv-body]');
      this.name = this.el.dataset.name;
      this.el.setAttribute('data-dl-reports', '');  // tambah / hapus baris dilaporkan akar ini ke dirty-guard
      $(this.el, '[data-dl-kv-add]').addEventListener('click', () => { const tr = this.row('', ''); this.changed(); $(tr, 'input').focus(); });
      this.body.addEventListener('click', e => { if (e.target.closest('[data-dl-kv-x]')) { e.target.closest('tr').remove(); this.renumber(); this.changed(); } });
    }

    row(k, v) {
      const tr = document.createElement('tr');
      tr.innerHTML = '<td><input type="text"></td><td><input type="text"></td><td><button type="button" data-dl-kv-x title="' + esc(T('l_delete_row')) + '">&times;</button></td>';
      const [ki, vi] = $$(tr, 'input'); ki.value = k ?? ''; vi.value = v ?? '';
      this.body.appendChild(tr); this.renumber();
      return tr;
    }

    changed() { if (!fillState.on) this.el.dispatchEvent(new Event('change', {bubbles: true})); }

    // Baris sekarang [{key, value}] dan penggantinya (Undo / Devlib.fill).
    rows() { return $$(this.body, 'tr').map(tr => { const [k, v] = $$(tr, 'input'); return {key: k.value, value: v.value}; }); }

    set(rows) { this.body.innerHTML = ''; rows.forEach(r => this.row(r.key, r.value)); }

    renumber() {
      return $$(this.body, 'tr').forEach((tr, i) => { const [k, v] = $$(tr, 'input'); k.name = `${this.name}[${i}][key]`; v.name = `${this.name}[${i}][value]`; });
    }
  }
  Devlib.define("Kv", Kv);
})(window.Devlib);
