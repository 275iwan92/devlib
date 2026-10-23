/*
 * REMARK
 * Tujuan       : Antrean pilih di browser: filter status (chip), centang baris (klik di mana saja pada baris), bilah aksi di atas daftar yang menyatakan jumlah, pihak, dan boleh tidaknya digabung.
 * Sumber hulu  : Markup dari server (data-pq-*): state, group, party, full, have, want, unit per baris; templat teks di <form>.
 * Input        : Klik chip, klik baris / kotak centang, submit.
 * Output       : Baris tampil / sembunyi, bilah aksi (teks, tombol aktif / mati), event dl:pick-queue (batal-able) sebelum submit.
 * Dipakai oleh : x-dl::pick-queue.
 * Status       : Aktif (DevLib).
 */
/* DevLib · PickQueue */
(function (Devlib) {
  'use strict';
  const {$, $$} = Devlib.h;

  class PickQueue extends Devlib.Component {
    static selector = "[data-dl-pq]";
    init() {
      this.rows = $$(this.el, '[data-pq-row]');
      this.bar = $(this.el, '[data-pq-bar]');
      this.go = $(this.el, '[data-pq-go]');
      this.el.addEventListener('click', e => this.click(e));
      this.el.addEventListener('change', () => this.update());
      this.el.addEventListener('submit', e => this.submit(e));
      this.apply(this.el.dataset.pqFilter);
    }

    click(e) {
      const chip = e.target.closest('[data-pq-chip]');
      if (chip) return this.apply(chip.dataset.pqChip);
      const row = e.target.closest('[data-pq-row]');
      if (!row || e.target.closest('label,a,button')) return;
      const box = $(row, 'input[type=checkbox]');
      if (box && !box.disabled) { box.checked = !box.checked; this.update(); }
    }

    // Filter aktif: chip bertanda, baris di luar filter disembunyikan dan dilepas centangnya.
    apply(key) {
      this.el.dataset.pqFilter = key;
      let states = null;
      $$(this.el, '[data-pq-chip]').forEach(c => {
        const on = c.dataset.pqChip === key;
        c.classList.toggle('is-active', on);
        c.setAttribute('aria-pressed', on ? 'true' : 'false');
        if (on) states = c.dataset.pqStates === '*' ? null : c.dataset.pqStates.split(',');
      });
      let shown = 0;
      this.rows.forEach(r => {
        const show = states === null || states.includes(r.dataset.pqState);
        r.hidden = !show;
        if (show) shown++;
        else { const b = $(r, 'input[type=checkbox]'); if (b) b.checked = false; }
      });
      const empty = $(this.el, '[data-pq-empty]');
      if (empty) empty.hidden = shown > 0;
      this.update();
    }

    picked() {
      return this.rows.filter(r => !r.hidden && $(r, 'input[type=checkbox]:checked'));
    }

    update() {
      const d = this.el.dataset, rows = this.picked(), n = rows.length;
      this.rows.forEach(r => r.classList.toggle('is-picked', !!$(r, 'input[type=checkbox]:checked')));
      this.bar.hidden = n === 0;
      if (!n) return;
      const same = new Set(rows.map(r => r.dataset.pqGroup)).size === 1, first = rows[0].dataset;
      $(this.bar, '[data-pq-sum]').textContent = d.pqSelected.replace(':n', n) + (same && first.pqParty ? ' · ' + first.pqParty : '');
      const note = $(this.bar, '[data-pq-note]');
      note.textContent = !same ? d.pqMismatch : (n > 1 ? d.pqTogether : '');
      note.classList.toggle('is-bad', !same);
      this.go.disabled = !same;
      this.go.textContent = n > 1 ? d.pqButton.replace(':n', n)
        : first.pqFull === '1' ? d.pqButtonOne
        : d.pqButtonPartial.replace(':have', first.pqHave).replace(':want', first.pqWant).replace(':unit', first.pqUnit);
    }

    submit(e) {
      const rows = this.picked();
      const ids = rows.map(r => $(r, 'input[type=checkbox]').value);
      const ok = ids.length > 0 && !this.go.disabled;
      const ev = new CustomEvent('dl:pick-queue', {bubbles: true, cancelable: true, detail: {ids}});
      if (!ok || !this.el.dispatchEvent(ev) || !this.el.getAttribute('action')) e.preventDefault();
    }
  }
  Devlib.define("PickQueue", PickQueue);
})(window.Devlib);
