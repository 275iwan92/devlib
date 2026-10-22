/* DevLib · Select Add New */
(function (Devlib) {
  'use strict';
  const {$, $$, T, fieldErr, openLayer, closeLayer} = Devlib.h;
  const csrf = () => window.DL_CSRF || document.querySelector('meta[name="csrf-token"]')?.content || '';

  // Select dengan tombol plus: popup tambah (x-dl::modal, dipindah ke <body> supaya isiannya tidak ikut <form> dokumen),
  // simpan lewat POST JSON, lalu opsi baru ditambahkan ke daftar dan langsung terpilih. Kontrak balasan: lihat resources/guide/field/select.md.
  class SelectAdd extends Devlib.Component {
    static selector = "[data-dl-select-add]";
    init() {
      this.sel = $(this.el, '[data-dl-select]');
      this.btn = $(this.el, '[data-dl-sa-btn]');
      this.modal = $(this.el, '[data-dl-sa-modal]');
      if (!this.sel || !this.btn || !this.modal) return;
      this.url = this.el.dataset.url; this.vk = this.el.dataset.valueKey; this.lk = this.el.dataset.labelKey;
      this.err = $(this.modal, '[data-dl-sa-err]'); this.save = $(this.modal, '[data-dl-sa-save]');
      this.inputs = $$(this.modal, 'input[name], textarea[name]');
      document.body.appendChild(this.modal);
      this.btn.addEventListener('click', () => { this.clearErrors(); openLayer(this.modal.dataset.dlModalId); });
      this.save.addEventListener('click', () => this.submit());
      this.modal.addEventListener('keydown', e => { if (e.key === 'Enter' && e.target.matches('input')) { e.preventDefault(); this.submit(); } });
      // Semua jalan tutup (Esc, X, Batal, klik luar, sukses) berakhir di sini: fokus kembali ke select.
      new MutationObserver(() => { if (!this.modal.classList.contains('is-on') && this.wasOn) { this.wasOn = false; $(this.sel, '[data-dl-dd-toggle]').focus(); } else if (this.modal.classList.contains('is-on')) this.wasOn = true; })
        .observe(this.modal, {attributes: true, attributeFilter: ['class']});
    }
    clearErrors() { this.inputs.forEach(i => fieldErr(i, '')); this.err.textContent = ''; }
    async submit() {
      if (this.busy) return;
      this.clearErrors();
      const body = {}; let bad = false;
      this.inputs.forEach(i => {
        body[i.name] = i.value.trim();
        if (!body[i.name] && i.closest('.dl-field')?.querySelector('.dl-field__req')) { fieldErr(i, T('f_required')); bad = true; }
      });
      if (bad) return;
      this.busy = true; const label = this.save.innerHTML;
      this.save.disabled = true; this.save.textContent = T('sa_saving');
      try {
        if (new URL(this.url, location.href).origin !== location.origin) throw new Error('origin');
        const r = await fetch(this.url, {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf()}, body: JSON.stringify(body)});
        let j = null; try { j = await r.json(); } catch (_) {}
        if (r.status === 422 && j && j.errors && typeof j.errors === 'object') {
          const rest = [];
          Object.entries(j.errors).forEach(([k, m]) => { const i = this.inputs.find(x => x.name === k), t = [].concat(m)[0]; if (i) fieldErr(i, t); else rest.push(t); });
          if (rest.length) this.err.textContent = rest.join(' ');
        } else if (!r.ok || !j) this.err.textContent = T('sa_failed');
        else {
          const v = j[this.vk] ?? j.value, l = j[this.lk] ?? j.label;
          if (v == null || l == null || v === '') this.err.textContent = T('sa_failed');
          else this.pick(String(v), String(l));
        }
      } catch (_) { this.err.textContent = T('sa_failed'); }
      this.busy = false; this.save.disabled = false; this.save.innerHTML = label;
    }
    // Sisipkan opsi (tanpa duplikat), pilih seperti klik biasa (change -> dirty guard dan logika bergantung ikut), tutup, kosongkan isian.
    pick(v, l) {
      const list = $(this.sel, '[data-dl-dd-list]'), h = $(this.sel, '[data-dl-value]');
      let it = $$(list, '.dl-dd__item').find(x => x.dataset.value === v);
      if (!it) { it = document.createElement('div'); it.className = 'dl-dd__item'; it.dataset.value = v; list.appendChild(it); }
      it.textContent = l;
      h.value = v; $(this.sel, '[data-dl-dd-text]').textContent = l;
      this.inputs.forEach(i => { i.value = ''; i.dispatchEvent(new Event('input', {bubbles: true})); i.dispatchEvent(new Event('dl:grow')); });
      closeLayer(this.modal);
      h.dispatchEvent(new Event('change', {bubbles: true}));
    }
  }
  Devlib.define("SelectAdd", SelectAdd);
})(window.Devlib);
