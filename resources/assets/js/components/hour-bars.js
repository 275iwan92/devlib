/* REMARK
 * Tujuan       : Perilaku x-dl::hour-bars: batang jam per hari pada sumbu 00-24; tarik di area kosong = buat, geser batang = pindah, tarik ujung = ubah masuk / pulang, klik nama hari = libur; istirahat tampil di dalam batang.
 * Sumber hulu  : config JSON dari Devlib\Components\HourBars (fields, days, from, to, snap, readonly, text); nilai dibaca dari isian jam per hari di form yang sama.
 * Input        : pointer (mouse, pena, sentuh) pada track; perubahan isian jam di form (isi sekali, ketik manual, Undo).
 * Output       : isian jam per hari ditulis HH:MM (event input + change dikirim), form diberi tanda belum disimpan (dirty-guard DevLib).
 * Dipakai oleh : x-dl::hour-bars (form Master Shift tab Working hours).
 * Status       : Aktif.
 */
(function (Devlib) {
  'use strict';
  const {$, esc, dirtyForms} = Devlib.h;

  const toMin = v => { const m = /^(\d{1,2}):(\d{2})$/.exec(String(v || '').trim()); return m ? (+m[1]) * 60 + (+m[2]) : null; };
  const hhmm = m => String(Math.floor(m / 60)).padStart(2, '0') + ':' + String(m % 60).padStart(2, '0');

  class HourBars extends Devlib.Component {
    static selector = "[data-dl-hbars]";
    init() {
      this.cfg = JSON.parse(this.el.dataset.config);
      this.form = this.el.closest('form');
      this.lo = this.cfg.from * 60;
      this.hi = this.cfg.to * 60;
      this.drag = null;
      this.el.style.setProperty('--hb-n', this.cfg.to - this.cfg.from);   // garis jam di track
      this.drawAxis();
      // Isian berubah (isi sekali, ketik manual, Undo): gambar ulang.
      if (this.form) ['input', 'change'].forEach(ev => this.form.addEventListener(ev, e => { if (!this.drag && e.target.name && this.isMine(e.target.name)) this.drawAll(); }));
      this.el.addEventListener('click', e => { const b = e.target.closest('[data-hb-off]'); if (b && !this.cfg.readonly) this.setDay(b.dataset.hbOff, {start: '', bout: '', bin: '', end: ''}); });
      if (!this.cfg.readonly) {
        this.el.addEventListener('pointerdown', e => this.down(e));
        document.addEventListener('pointermove', e => this.move(e));
        document.addEventListener('pointerup', () => this.up());
        document.addEventListener('pointercancel', () => this.up());
      }
      this.drawAll();
      // Undo form mengisi ulang isian lewat fillForm tanpa event input: segarkan juga sesudah klik tombol form.
      this.form?.addEventListener('click', () => setTimeout(() => { if (!this.drag) this.drawAll(); }, 60));
    }

    isMine(name) {
      return Object.values(this.cfg.fields).some(p => Object.keys(this.cfg.days).some(d => p.replace('{day}', d) === name));
    }
    inp(role, day) { return this.form?.querySelector(`[name="${CSS.escape(this.cfg.fields[role].replace('{day}', day))}"]`); }
    val(role, day) { return toMin(this.inp(role, day)?.value); }

    drawAxis() {
      const n = this.cfg.to - this.cfg.from;
      $(this.el, '[data-hb-axis]').innerHTML = '<span class="dl-hbars__corner"></span><div class="dl-hbars__ticks">'
        + Array.from({length: Math.floor(n / 3) + 1}, (_, i) => this.cfg.from + i * 3).map(h => `<i style="left:${(h - this.cfg.from) / n * 100}%">${String(h).padStart(2, '0')}</i>`).join('') + '</div><span></span>';
    }

    pct(m) { return (m - this.lo) / (this.hi - this.lo) * 100; }

    drawAll() { Object.keys(this.cfg.days).forEach(d => this.drawDay(d)); }

    // Satu baris: batang masuk..pulang (dua ruas bila lewat tengah malam), ruas istirahat di dalamnya, jam bersih di kanan.
    drawDay(day) {
      const T = this.cfg.text, s = this.val('start', day), e = this.val('end', day), bo = this.val('bout', day), bi = this.val('bin', day);
      const track = $(this.el, `[data-hb-track="${day}"]`), net = $(this.el, `[data-hb-net="${day}"]`);
      let html = '';
      if (s === null || e === null) {
        net.textContent = T.off; net.classList.add('is-off');
      } else {
        const night = e <= s, brk = bo !== null && bi !== null ? (bi > bo ? bi - bo : bi - bo + 1440) : 0;
        const total = (night ? e + 1440 - s : e - s) - brk;
        net.classList.remove('is-off');
        net.textContent = T.hours.replace(':h', (Math.round(total / 60 * 100) / 100).toFixed(2));
        const seg = (a, b, cls) => `<div class="dl-hbars__bar ${cls}" data-hb-bar="${day}" style="left:${this.pct(a)}%;width:${this.pct(b) - this.pct(a)}%" ${night ? `title="${esc(T.night)}"` : ''}>`
          + (cls.includes('is-first') || !night ? '<b class="dl-hbars__h dl-hbars__h--l" data-hb-h="l"></b>' : '') + `<span>${esc(hhmm(s))} - ${esc(hhmm(e))}</span>` + (cls.includes('is-last') || !night ? '<b class="dl-hbars__h dl-hbars__h--r" data-hb-h="r"></b>' : '') + '</div>';
        html = night ? seg(s, this.hi, 'is-night is-first') + seg(this.lo, e, 'is-night is-last') : seg(s, e, '');
        if (bo !== null && bi !== null && !night && bo >= s && bi <= e && bi > bo) html += `<div class="dl-hbars__brk" style="left:${this.pct(bo)}%;width:${this.pct(bi) - this.pct(bo)}%" title="${esc(hhmm(bo))} - ${esc(hhmm(bi))}"></div>`;
      }
      track.innerHTML = html;
    }

    setDay(day, v) {
      Object.entries(v).forEach(([role, value]) => {
        const i = this.inp(role, day);
        if (!i) return;
        i.value = value;
        i.dispatchEvent(new Event('input', {bubbles: true}));
        i.dispatchEvent(new Event('change', {bubbles: true}));
      });
      const g = this.el.closest('[data-dl-guard]');
      if (g) { dirtyForms.add(g); g.classList.add('is-dirty'); }
      this.drawAll();
    }

    minutesAt(track, clientX) {
      const r = track.getBoundingClientRect(), sn = this.cfg.snap;
      const m = this.lo + (clientX - r.left) / r.width * (this.hi - this.lo);
      return Math.max(this.lo, Math.min(this.hi, Math.round(m / sn) * sn));
    }

    down(e) {
      const track = e.target.closest('[data-hb-track]');
      if (!track || e.button > 0) return;
      const day = track.dataset.hbTrack, bar = e.target.closest('[data-hb-bar]'), at = this.minutesAt(track, e.clientX);
      const s = this.val('start', day), en = this.val('end', day), bo = this.val('bout', day), bi = this.val('bin', day);
      e.preventDefault();
      if (bar) {
        if (s === null || en === null || en <= s) return;   // lewat tengah malam: ubah lewat isian jam
        const h = e.target.closest('[data-hb-h]')?.dataset.hbH;
        this.drag = {day, track, kind: h === 'l' ? 'l' : h === 'r' ? 'r' : 'move', s, e: en, bo, bi, at};
      } else {
        this.drag = {day, track, kind: 'new', s: at, e: at, bo: null, bi: null, at};
      }
      track.setPointerCapture?.(e.pointerId);
    }

    move(e) {
      const d = this.drag;
      if (!d) return;
      const at = this.minutesAt(d.track, e.clientX), sn = this.cfg.snap;
      let s = d.s, en = d.e, bo = d.bo, bi = d.bi;
      if (d.kind === 'new') { s = Math.min(d.at, at); en = Math.max(d.at, at); }
      else if (d.kind === 'l') { s = Math.min(at, d.e - sn); }
      else if (d.kind === 'r') { en = Math.max(at, d.s + sn); }
      else {
        const dur = d.e - d.s, ns = Math.max(this.lo, Math.min(this.hi - dur, d.s + (at - d.at)));
        const delta = ns - d.s; s = ns; en = ns + dur;
        if (bo !== null && bi !== null) { bo += delta; bi += delta; }
      }
      // Istirahat yang keluar dari jam kerja baru dibuang (diisi ulang lewat isian bila perlu).
      if (bo !== null && bi !== null && (bo < s || bi > en)) { bo = null; bi = null; }
      d.cur = {s, e: en, bo, bi};
      this.preview(d.day, d.cur);
    }

    // Selama diseret hanya batang yang digambar; isian ditulis saat dilepas.
    preview(day, c) {
      const track = $(this.el, `[data-hb-track="${day}"]`);
      if (c.e <= c.s) { track.innerHTML = ''; return; }
      track.innerHTML = `<div class="dl-hbars__bar is-drag" style="left:${this.pct(c.s)}%;width:${this.pct(c.e) - this.pct(c.s)}%"><span>${hhmm(c.s)} - ${hhmm(c.e)}</span></div>`
        + (c.bo !== null ? `<div class="dl-hbars__brk" style="left:${this.pct(c.bo)}%;width:${this.pct(c.bi) - this.pct(c.bo)}%"></div>` : '');
    }

    up() {
      const d = this.drag;
      if (!d) return;
      this.drag = null;
      const c = d.cur;
      if (!c) { this.drawDay(d.day); return; }
      if (c.e - c.s < this.cfg.snap) { this.drawDay(d.day); return; }   // klik tanpa tarik: tidak mengubah apa pun
      this.setDay(d.day, {start: hhmm(c.s), end: hhmm(c.e), bout: c.bo !== null ? hhmm(c.bo) : '', bin: c.bi !== null ? hhmm(c.bi) : ''});
    }
  }
  Devlib.define("HourBars", HourBars);
})(window.Devlib);
