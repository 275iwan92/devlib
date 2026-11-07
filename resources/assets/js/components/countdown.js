/* DevLib · Countdown
 * Timer hitung mundur (x-dl::countdown). Yang berjalan = localStorage[data-key] {running, endAt (ms), left (ms), total (ms)}, dihitung dari jam, jadi benar setelah pindah
 * layar atau muat ulang. Selesai: Devlib.sound 'timer' + toast + judul tab. Preset [{id, label, minutes}] dikirim utuh ke data-save (POST JSON {items}). */
(function (Devlib) {
  'use strict';
  const {$, toast} = Devlib.h;
  const fmt = ms => { const s = Math.max(0, Math.ceil(ms / 1000)), h = Math.floor(s / 3600), m = Math.floor(s % 3600 / 60), p = n => String(n).padStart(2, '0'); return (h ? h + ':' : '') + p(m) + ':' + p(s % 60); };
  const uid = () => 't' + Date.now().toString(36) + Math.random().toString(36).slice(2, 5);

  class Countdown extends Devlib.Component {
    static selector = "[data-dl-cd]";
    init() {
      const d = this.el.dataset; this.L = d; this.presets = JSON.parse(d.items || '[]'); this.title0 = document.title;
      this.minutes = +d.minutes || 5;
      this.bar = $(this.el, '[data-dl-cd-bar]'); this.time = $(this.el, '[data-dl-cd-time]'); this.input = $(this.el, '[data-dl-cd-minutes]'); this.play = $(this.el, '[data-dl-cd-play]');
      this.list = $(this.el, '[data-dl-cd-presets]');
      this.s = Object.assign({running: false, endAt: 0, left: this.minutes * 60000, total: this.minutes * 60000}, this.read());
      this.minutes = Math.max(1, Math.round(this.s.total / 60000)); this.input.value = this.minutes;

      this.play.addEventListener('click', () => this.toggle());
      $(this.el, '[data-dl-cd-reset]').addEventListener('click', () => this.reset());
      $(this.el, '[data-dl-cd-minus]').addEventListener('click', () => this.set(this.minutes - 1)); $(this.el, '[data-dl-cd-plus]').addEventListener('click', () => this.set(this.minutes + 1));
      this.input.addEventListener('change', () => this.set(+this.input.value || 1));
      $(this.el, '[data-dl-cd-add]').addEventListener('submit', e => { e.preventDefault(); this.addPreset(e.target); });
      window.addEventListener('storage', e => { if (e.key === d.key) { Object.assign(this.s, this.read()); this.draw(); } });
      document.addEventListener('pointerdown', () => { document.title = this.title0; }, true);
      setInterval(() => this.tick(), 250); this.drawPresets(); this.tick(true); this.draw();
    }

    read() { try { return JSON.parse(localStorage.getItem(this.L.key)) || {}; } catch (e) { return {}; } }
    store() { try { localStorage.setItem(this.L.key, JSON.stringify(this.s)); } catch (e) { /* tanpa penyimpanan */ } }
    left() { return this.s.running ? this.s.endAt - Date.now() : this.s.left; }

    set(min) { // ubah lama hitungan: hanya saat berhenti
      if (this.s.running) return;
      this.minutes = Math.min(600, Math.max(1, Math.round(min))); this.s.total = this.s.left = this.minutes * 60000; this.input.value = this.minutes; this.store(); this.draw();
    }

    toggle() {
      Devlib.sound?.unlock();
      if (this.s.running) { this.s.left = this.left(); this.s.running = false; } else { if (!(this.s.left > 0)) this.s.left = this.s.total; this.s.running = true; this.s.endAt = Date.now() + this.s.left; }
      this.store(); this.draw();
    }

    reset() { this.s.running = false; this.s.left = this.s.total; this.store(); this.draw(); }

    tick(boot) {
      if (!this.s.running) return;
      const left = this.left();
      if (left > 0) { this.paint(left); return; }
      const stored = this.read(); if (stored.endAt && stored.endAt !== this.s.endAt) { Object.assign(this.s, stored); this.draw(); return; } // tab lain sudah menutupnya
      this.s.running = false; this.s.left = this.s.total; this.store(); this.draw();
      if (boot && Date.now() - this.s.endAt > 5000) return;
      Devlib.sound?.play('timer'); toast(this.L.lDone, 'warn'); document.title = this.L.lDone + ' · ' + this.title0;
    }

    paint(left) { this.time.textContent = fmt(left); this.bar.setAttribute('stroke-dashoffset', String(Math.max(0, Math.min(100, 100 * (1 - left / this.s.total)))).slice(0, 7)); }

    draw() {
      const run = this.s.running; this.paint(this.left());
      this.play.innerHTML = '<i class="fa-solid ' + (run ? 'fa-pause' : 'fa-play') + '" aria-hidden="true"></i> <span>' + (run ? this.L.lPause : this.L.lStart) + '</span>';
      this.el.classList.toggle('is-running', run); this.minutes = Math.max(1, Math.round(this.s.total / 60000)); this.input.value = this.minutes;
      ['[data-dl-cd-minus]', '[data-dl-cd-plus]'].forEach(q => { $(this.el, q).disabled = run; }); this.input.disabled = run;
    }

    addPreset(form) {
      const name = $(form, '[data-dl-cd-label]'); if (!name.value.trim()) return;
      this.presets.push({id: uid(), label: name.value.trim(), minutes: this.minutes}); name.value = ''; this.savePresets();
    }

    savePresets() {
      this.drawPresets();
      clearTimeout(this.t); this.t = setTimeout(() => {
        const url = this.L.save; if (!url) return;
        fetch(url, {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.L.csrf}, body: JSON.stringify({items: this.presets})})
          .then(r => { if (!r.ok) throw new Error(r.status); }).catch(() => toast(this.L.lFailed, 'danger'));
      }, 300);
    }

    drawPresets() {
      this.list.replaceChildren(...this.presets.map(p => {
        const li = document.createElement('li'), use = document.createElement('button'), del = document.createElement('button');
        use.type = del.type = 'button'; use.className = 'dl-cd__use'; use.textContent = p.label + ' · ' + p.minutes + ' ' + this.L.lMin; use.addEventListener('click', () => this.set(p.minutes));
        del.className = 'dl-cd__del'; del.setAttribute('aria-label', this.L.lDelete + ': ' + p.label); del.title = this.L.lDelete; del.innerHTML = '<i class="fa-solid fa-xmark" aria-hidden="true"></i>';
        del.addEventListener('click', () => { this.presets = this.presets.filter(x => x !== p); this.savePresets(); });
        li.append(use, del); return li;
      }));
    }
  }
  Devlib.define("Countdown", Countdown);
})(window.Devlib);
