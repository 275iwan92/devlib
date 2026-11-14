/* REMARK
 * Tujuan       : Perilaku x-dl::week-fill: chip hari (toggle, preset Weekdays / All / Clear), tombol Apply menyalin empat isian jam sumber ke isian hari yang dipilih di form yang sama; ringkasan jam bersih per hari dan per minggu.
 * Sumber hulu  : config JSON dari Devlib\Components\WeekFill (map, days, selected, text).
 * Input        : klik chip / preset / Apply; perubahan isian sumber (menghitung ringkasan).
 * Output       : nilai isian target diisi (event input + change dikirim), form diberi tanda belum disimpan (dirty-guard DevLib); teks ringkasan.
 * Dipakai oleh : x-dl::week-fill (form Master Shift tab Working hours).
 * Status       : Aktif.
 */
(function (Devlib) {
  'use strict';
  const {$, dirtyForms} = Devlib.h;

  const minutes = v => { const m = /^(\d{1,2}):(\d{2})$/.exec(String(v || '').trim()); return m ? (+m[1]) * 60 + (+m[2]) : null; };
  const span = (a, b) => { const d = b - a; return d > 0 ? d : d + 1440; };   // selesai <= mulai = lewat tengah malam

  class WeekFill extends Devlib.Component {
    static selector = "[data-dl-weekfill]";
    init() {
      this.cfg = JSON.parse(this.el.dataset.config);
      this.form = this.el.closest('form');
      this.on = new Set(this.cfg.selected);
      this.el.addEventListener('click', e => {
        const chip = e.target.closest('[data-wf-day]');
        if (chip) { this.toggle(chip.dataset.wfDay); return; }
        const pre = e.target.closest('[data-wf-preset]');
        if (pre) { this.preset(pre.dataset.wfPreset); return; }
        if (e.target.closest('[data-wf-apply]')) this.apply();
      });
      this.el.addEventListener('input', () => this.net());
      this.el.addEventListener('change', () => this.net());
      this.draw();
    }

    field(name) { return this.form ? this.form.querySelector(`[name="${CSS.escape(name)}"]`) : null; }
    src(role) { return this.field(this.cfg.map[role]?.from)?.value ?? ''; }

    toggle(day) { this.on.has(day) ? this.on.delete(day) : this.on.add(day); this.draw(); }

    preset(kind) {
      const all = Object.keys(this.cfg.days);
      this.on = new Set(kind === 'all' ? all : kind === 'weekdays' ? all.slice(0, 5) : []);
      this.draw();
    }

    draw() {
      this.el.querySelectorAll('[data-wf-day]').forEach(b => {
        const on = this.on.has(b.dataset.wfDay);
        b.classList.toggle('is-on', on);
        b.setAttribute('aria-pressed', on ? 'true' : 'false');
      });
      this.net();
    }

    // Jam bersih per hari = (pulang - masuk) - (selesai istirahat - mulai istirahat); per minggu = per hari x hari terpilih.
    net() {
      const s = minutes(this.src('start')), e = minutes(this.src('end')), bo = minutes(this.src('bout')), bi = minutes(this.src('bin'));
      const out = $(this.el, '[data-wf-net]');
      if (s === null || e === null) { out.textContent = ''; return; }
      const day = span(s, e) - (bo !== null && bi !== null ? span(bo, bi) : 0);
      const h = x => (Math.round(x / 60 * 100) / 100).toFixed(2);
      out.textContent = this.cfg.text.net.replace(':h', h(day)).replace(':n', this.on.size).replace(':w', h(day * this.on.size));
    }

    // Salin empat nilai sumber ke tiap hari terpilih; widget jam ikut karena event input dan change dikirim.
    apply() {
      const out = $(this.el, '[data-wf-net]');
      if (!this.on.size) { out.textContent = this.cfg.text.none; return; }
      this.on.forEach(day => {
        Object.entries(this.cfg.map).forEach(([role, m]) => {
          const t = this.field(m.to.replace('{day}', day));
          if (!t) return;
          t.value = this.src(role);
          t.dispatchEvent(new Event('input', {bubbles: true}));
          t.dispatchEvent(new Event('change', {bubbles: true}));
        });
      });
      const guard = this.el.closest('[data-dl-guard]');
      if (guard) { dirtyForms.add(guard); guard.classList.add('is-dirty'); }
      this.net();
      out.textContent = this.cfg.text.applied.replace(':n', this.on.size) + ' ' + out.textContent;
    }
  }
  Devlib.define("WeekFill", WeekFill);
})(window.Devlib);
