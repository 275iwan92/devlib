/* DevLib · Tags */
(function (Devlib) {
  'use strict';
  const {$, $$, esc, fillState} = Devlib.h;

  class Tags extends Devlib.Component {
    static selector = "[data-dl-tags]";
    init() {
      this.input = $(this.el, '.dl-tags__input');
      this.box = $(this.el, '.dl-tags__box');
      this.name = this.el.dataset.name;
      this.el.setAttribute('data-dl-reports', '');  // chip tidak berubah lewat isian bernama: akar ini yang melapor ke dirty-guard
      this.input.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ',') { e.preventDefault(); this.add(this.input.value); }
        else if (e.key === 'Backspace' && !this.input.value) { const last = [...this.box.querySelectorAll('.dl-tags__chip')].pop(); if (last && !this.locked()) { last.remove(); this.changed(); } }
      });
      this.input.addEventListener('change', () => this.add(this.input.value));
      this.box.addEventListener('click', e => { if (e.target.closest('[data-dl-tag-x]')) { if (!this.locked()) { e.target.closest('.dl-tags__chip').remove(); this.changed(); } } else this.input.focus(); });
    }

    locked() { return this.input.disabled || this.input.readOnly; }

    // Tambah / hapus chip = isian berubah (dirty-guard, Undo, hapus pesan Required); pengisian oleh program (fillForm) tidak.
    changed() { if (!fillState.on) this.el.dispatchEvent(new Event('change', {bubbles: true})); }

    chip(v) {
      const chip = document.createElement('span'); chip.className = 'dl-tags__chip';
      chip.innerHTML = `${esc(v)}<input type="hidden" name="${esc(this.name)}[]" value="${esc(v)}"><button type="button" data-dl-tag-x>&times;</button>`;
      this.box.insertBefore(chip, this.input);
    }

    // Ganti semua chip (Undo / Devlib.fill).
    set(values) { $$(this.box, '.dl-tags__chip').forEach(c => c.remove()); values.forEach(v => this.chip(v)); this.input.value = ''; }

    add(v) {
      v = v.trim().replace(/,$/, ''); if (!v) return;
      if ($$(this.el, 'input[type=hidden]').some(h => h.value.toLowerCase() === v.toLowerCase())) { this.input.value = ''; return; }
      this.chip(v); this.input.value = ''; this.changed();
    }
  }
  Devlib.define("Tags", Tags);
})(window.Devlib);
