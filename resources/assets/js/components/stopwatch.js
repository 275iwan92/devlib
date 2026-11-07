/* DevLib · Stopwatch
 * Stopwatch (x-dl::stopwatch): keadaan localStorage[data-key] {running, startedAt, base, laps}; waktu = base + (sekarang - startedAt), dihitung dari jam jadi benar setelah
 * pindah layar atau muat ulang. Lap menyimpan total saat itu; daftar menampilkan putaran terbaru di atas dengan selisih dari putaran sebelumnya. */
(function (Devlib) {
  'use strict';
  const {$} = Devlib.h;
  const p = (n, w = 2) => String(n).padStart(w, '0');
  const fmt = ms => { const c = Math.floor(ms / 10) % 100, s = Math.floor(ms / 1000) % 60, m = Math.floor(ms / 60000) % 60, h = Math.floor(ms / 3600000); return p(h) + ':' + p(m) + ':' + p(s) + '.' + p(c); };

  class Stopwatch extends Devlib.Component {
    static selector = "[data-dl-sw]";
    init() {
      this.L = this.el.dataset; this.time = $(this.el, '[data-dl-sw-time]'); this.play = $(this.el, '[data-dl-sw-play]'); this.laps = $(this.el, '[data-dl-sw-laps]');
      this.s = Object.assign({running: false, startedAt: 0, base: 0, laps: []}, this.read());
      this.play.addEventListener('click', () => this.toggle());
      $(this.el, '[data-dl-sw-lap]').addEventListener('click', () => { if (this.now() > 0) { this.s.laps.push(this.now()); this.store(); this.draw(); } });
      $(this.el, '[data-dl-sw-reset]').addEventListener('click', () => { this.s = {running: false, startedAt: 0, base: 0, laps: []}; this.store(); this.draw(); });
      window.addEventListener('storage', e => { if (e.key === this.L.key) { Object.assign(this.s, this.read()); this.draw(); } });
      setInterval(() => { if (this.s.running && !document.hidden) this.time.textContent = fmt(this.now()); }, 50);
      this.draw();
    }

    read() { try { return JSON.parse(localStorage.getItem(this.L.key)) || {}; } catch (e) { return {}; } }
    store() { try { localStorage.setItem(this.L.key, JSON.stringify(this.s)); } catch (e) { /* tanpa penyimpanan */ } }
    now() { return this.s.base + (this.s.running ? Date.now() - this.s.startedAt : 0); }

    toggle() {
      if (this.s.running) { this.s.base = this.now(); this.s.running = false; } else { this.s.startedAt = Date.now(); this.s.running = true; }
      this.store(); this.draw();
    }

    draw() {
      const run = this.s.running; this.time.textContent = fmt(this.now());
      this.play.innerHTML = '<i class="fa-solid ' + (run ? 'fa-pause' : 'fa-play') + '" aria-hidden="true"></i> <span>' + (run ? this.L.lStop : this.L.lStart) + '</span>';
      this.el.classList.toggle('is-running', run);
      this.laps.replaceChildren(...this.s.laps.map((t, i) => {
        const li = document.createElement('li'), a = document.createElement('b'), b = document.createElement('span');
        a.textContent = fmt(t); b.textContent = '+' + fmt(t - (this.s.laps[i - 1] || 0)); li.append(a, b); return li;
      }).reverse());
    }
  }
  Devlib.define("Stopwatch", Stopwatch);
})(window.Devlib);
