/* DevLib · HeroFx + Devlib.motion
 * Gerak kotak sapaan (x-dl::greeting-card, opt-in board['animate']): konstelasi titik di latar yang menjauh dari kursor dan
 * menyambung ke kursor (canvas), angka bulat di ringkasan menghitung naik. Pola dari proyek admin Jo. Berhenti total saat
 * tab tersembunyi. Warna dari token --dl-*.
 * Devlib.motion = pilihan gerak pengguna (pola admin Jo): 'auto' (ikuti perangkat: prefers-reduced-motion), 'on', 'off'; disimpan
 * per browser di localStorage['dl-motion']; hasil efektif ('on' | 'off') ditulis ke <html data-motion> dan dikabarkan lewat event 'dl:motion'.
 * CSS gerak hanya hidup di html[data-motion="on"]. Pengatur: x-dl::setup-media. */
(function (Devlib) {
  'use strict';
  const {$, $$} = Devlib.h;

  Devlib.motion = Devlib.motion || (() => {
    const mq = matchMedia('(prefers-reduced-motion: reduce)'), KEY = 'dl-motion';
    const pref = () => { try { const v = localStorage.getItem(KEY); return v === 'on' || v === 'off' ? v : 'auto'; } catch (e) { return 'auto'; } };
    const mode = () => { const p = pref(); return p === 'auto' ? (mq.matches ? 'off' : 'on') : p; };
    const apply = () => { document.documentElement.dataset.motion = mode(); document.dispatchEvent(new CustomEvent('dl:motion', {detail: mode()})); };
    const set = p => { try { if (p === 'on' || p === 'off') localStorage.setItem(KEY, p); else localStorage.removeItem(KEY); } catch (e) { /* tanpa penyimpanan: hanya sesi ini */ } apply(); };
    mq.addEventListener('change', apply); apply();
    return {pref, mode, set};
  })();

  class HeroFx extends Devlib.Component {
    static selector = "[data-dl-hero-fx]";
    init() {
      this.cv = $(this.el, '.dl-dash__fx');
      if (!this.cv) return;
      this.ctx = this.cv.getContext('2d'); this.dots = []; this.mouse = {x: -999, y: -999}; this.raf = 0;
      const css = getComputedStyle(this.el), tok = (n, f) => css.getPropertyValue(n).trim() || f;
      this.line = tok('--dl-on-dark-link', 'currentColor'); this.warm = tok('--dl-on-dark-warn', 'currentColor');
      this.el.addEventListener('pointermove', e => { const r = this.el.getBoundingClientRect(); this.mouse.x = e.clientX - r.left; this.mouse.y = e.clientY - r.top; });
      this.el.addEventListener('pointerleave', () => { this.mouse.x = this.mouse.y = -999; });
      new ResizeObserver(this.run).observe(this.el);
      document.addEventListener('visibilitychange', this.run);
      document.addEventListener('dl:motion', this.run);
      if (Devlib.motion.mode() === 'on') this.countUp();
      this.run();
    }

    size() {
      const r = this.el.getBoundingClientRect(), d = devicePixelRatio || 1;
      this.w = r.width; this.h = r.height; this.cv.width = this.w * d; this.cv.height = this.h * d; this.ctx.setTransform(d, 0, 0, d, 0, 0);
      this.dots.length = 0;
      for (let i = 0, n = Math.min(70, Math.round(this.w * this.h / 9000)); i < n; i++) this.dots.push({x: Math.random() * this.w, y: Math.random() * this.h, vx: (Math.random() - .5) * .35, vy: (Math.random() - .5) * .35, r: 1 + Math.random() * 1.6});
    }

    run() {
      cancelAnimationFrame(this.raf);
      if (document.hidden || Devlib.motion.mode() !== 'on') { this.ctx.clearRect(0, 0, this.cv.width, this.cv.height); return; }
      this.size(); this.raf = requestAnimationFrame(this.draw);
    }

    draw() {
      const {ctx, w, h, dots, mouse} = this;
      ctx.clearRect(0, 0, w, h);
      for (const a of dots) {
        a.x += a.vx; a.y += a.vy;
        if (a.x < 0 || a.x > w) a.vx *= -1;
        if (a.y < 0 || a.y > h) a.vy *= -1;
        const dm = Math.hypot(a.x - mouse.x, a.y - mouse.y);
        if (dm < 150) { a.x += (a.x - mouse.x) * .004; a.y += (a.y - mouse.y) * .004; }
        ctx.globalAlpha = .7; ctx.fillStyle = this.line; ctx.beginPath(); ctx.arc(a.x, a.y, a.r, 0, 6.283); ctx.fill();
        if (dm < 170) { ctx.globalAlpha = .55 * (1 - dm / 170); ctx.strokeStyle = this.warm; ctx.beginPath(); ctx.moveTo(a.x, a.y); ctx.lineTo(mouse.x, mouse.y); ctx.stroke(); }
        for (const b of dots) {
          const d = Math.hypot(a.x - b.x, a.y - b.y);
          if (d < 105 && a < b) { ctx.globalAlpha = .3 * (1 - d / 105); ctx.strokeStyle = this.line; ctx.beginPath(); ctx.moveTo(a.x, a.y); ctx.lineTo(b.x, b.y); ctx.stroke(); }
        }
      }
      this.raf = requestAnimationFrame(this.draw);
    }

    // Angka bulat murni di ringkasan (mis. "9") naik dari 0 dalam 1 detik; nilai berhuruf (IDR 1 jt) dibiarkan.
    countUp() {
      $$(this.el, '.dl-dash__brief b').forEach(b => {
        if (!/^\d+$/.test(b.textContent.trim())) return;
        const end = parseInt(b.textContent, 10), t0 = performance.now();
        const tick = t => { const k = Math.min(1, (t - t0) / 1000); b.textContent = Math.round(end * (1 - Math.pow(1 - k, 3))).toLocaleString(Devlib.lang === 'id' ? 'id-ID' : 'en-US'); if (k < 1) requestAnimationFrame(tick); };
        requestAnimationFrame(tick);
      });
    }
  }
  Devlib.define("HeroFx", HeroFx);
})(window.Devlib);
