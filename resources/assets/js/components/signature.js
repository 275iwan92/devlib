/* DevLib · Signature */
(function (Devlib) {
  'use strict';
  const {$, toast, T} = Devlib.h;
  // Tanda tangan: goresan disimpan per garis (untuk Undo), digambar ulang saat ukuran berubah.
  class Signature extends Devlib.Component {
    static selector = "[data-dl-sign]";
    init() {
      this.cv = $(this.el, 'canvas');
      this.ctx = this.cv.getContext('2d');
      this.hidden = $(this.el, '[data-dl-value]');
      this.ph = $(this.el, '[data-dl-sign-ph]');
      this.st = $(this.el, '[data-dl-sign-status]');
      this.ro = this.el.classList.contains('is-readonly');
      this.strokes = [];
      this.cur = null;
      this.bg = null;
      if (!this.ro) {
        this.cv.addEventListener('pointerdown', e => { this.cv.setPointerCapture(e.pointerId); this.cur = [this.pos(e)]; this.strokes.push(this.cur); this.draw(); });
        this.cv.addEventListener('pointermove', e => { if (!this.cur) return; this.cur.push(this.pos(e)); this.draw(); });
        const end = () => { if (this.cur) { this.cur = null; this.sync(); } };
        this.cv.addEventListener('pointerup', end); this.cv.addEventListener('pointercancel', end);
        $(this.el, '[data-dl-sign-undo]').addEventListener('click', () => { if (this.strokes.length) this.strokes.pop(); else this.bg = null; this.draw(); this.sync(); });
        $(this.el, '[data-dl-sign-clear]').addEventListener('click', () => { this.strokes = []; this.bg = null; this.draw(); this.sync(); });
      }
      $(this.el, '[data-dl-sign-png]').addEventListener('click', () => { if (!this.hidden.value) { toast(T('sg_none'), 'warn'); return; } const a = document.createElement('a'); a.href = this.hidden.value; a.download = 'tanda-tangan.png'; a.click(); });
      // Devlib.fill / Undo menulis hidden lalu menembak input: kanvas menggambar ulang nilai itu.
      this.hidden.addEventListener('input', () => this.load());
      new ResizeObserver(this.draw).observe(this.cv);this.draw();
      if (this.hidden.value) this.load(); else this.sync(true);
    }

    // Gambar nilai hidden (tanda tangan tersimpan) ke kanvas. Nilainya sendiri tidak ditulis ulang dan tidak ada event: bukan perubahan pengguna.
    load() {
      const v = this.hidden.value; this.strokes = []; this.cur = null; this.bg = null;
      if (!v) { this.draw(); this.ui(false); return; }
      const im = new Image(); im.onload = () => { if (this.hidden.value !== v) return; this.bg = im; this.draw(); this.ui(true); }; im.src = v;
    }

    ui(has) { this.ph.hidden = has; this.st.textContent = T(has ? 'sg_signed' : 'sg_unsigned'); this.el.classList.toggle('is-signed', has); }

    ink() {
      return getComputedStyle(this.el).getPropertyValue('--dl-ink-strong').trim() || '#111';
    }

    draw() {
      const r = this.cv.getBoundingClientRect(), dpr = window.devicePixelRatio || 1;
      this.cv.width = r.width * dpr; this.cv.height = r.height * dpr; this.ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      if (this.bg) this.ctx.drawImage(this.bg, 0, 0, r.width, r.height);
      this.ctx.lineCap = this.ctx.lineJoin = 'round'; this.ctx.strokeStyle = this.ink();
      this.strokes.forEach(p => { this.ctx.lineWidth = 2.2; this.ctx.beginPath(); this.ctx.moveTo(p[0][0], p[0][1]); for (let i = 1; i < p.length - 1; i++) this.ctx.quadraticCurveTo(p[i][0], p[i][1], (p[i][0] + p[i + 1][0]) / 2, (p[i][1] + p[i + 1][1]) / 2); const l = p[p.length - 1]; this.ctx.lineTo(l[0], l[1] + (p.length === 1 ? 0.1 : 0)); this.ctx.stroke(); });
    }

    // quiet = keadaan awal, bukan perubahan pengguna (tanpa event). Kanvas 0x0 (field di tab tersembunyi) menghasilkan "data:,": nilai dibiarkan.
    sync(quiet) {
      const has = this.strokes.length > 0 || !!this.bg;
      this.ui(has);
      if (has && !(this.cv.width && this.cv.height)) return;
      this.hidden.value = has ? this.cv.toDataURL('image/png') : '';
      if (!quiet) this.hidden.dispatchEvent(new Event('change', {bubbles: true}));
    }

    pos(e) { const r = this.cv.getBoundingClientRect(); return [e.clientX - r.left, e.clientY - r.top]; }
  }
  Devlib.define("Signature", Signature);
})(window.Devlib);
