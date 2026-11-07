/* DevLib · Pomodoro
 * Pewaktu Focus/Break (x-dl::pomodoro). Yang sedang berjalan = localStorage[data-key] {phase, running, endAt (cap waktu akhir, ms), left (ms sisa saat jeda), notify}:
 * dihitung dari jam, bukan dari hitungan detik, jadi tetap benar setelah pindah layar, muat ulang, atau tab di belakang. Durasi dan sesi hari ini
 * disimpan server lewat data-save (POST JSON {focus, break, auto, done} -> {sessions}); selesai ditangani satu tab saja (cek endAt di localStorage).
 * Bunyi: Devlib.sound 'pomodoro' (akhir Focus) / 'pomodoro_break' (akhir Break). Cincin bergerak satu detik; Devlib.motion 'off' mematikannya lewat CSS. */
(function (Devlib) {
  'use strict';
  const {$, $$} = Devlib.h;
  const mmss = ms => { const s = Math.max(0, Math.ceil(ms / 1000)); return String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0'); };

  class Pomodoro extends Devlib.Component {
    static selector = "[data-dl-pomo]";
    init() {
      const d = this.el.dataset;
      this.key = d.key; this.L = d;
      this.cfg = {focus: +d.focus, break: +d.break, auto: d.auto === '1'};
      this.sessions = +d.sessions || 0; this.title0 = document.title;
      this.bar = $(this.el, '[data-dl-pomo-bar]'); this.time = $(this.el, '[data-dl-pomo-time]'); this.play = $(this.el, '[data-dl-pomo-play]');
      this.minus = $(this.el, '[data-dl-pomo-minus]'); this.plus = $(this.el, '[data-dl-pomo-plus]'); this.count = $(this.el, '[data-dl-pomo-sessions]');
      this.auto = $(this.el, '[data-dl-pomo-auto]'); this.notify = $(this.el, '[data-dl-pomo-notify]'); this.radios = $$(this.el, '[data-dl-pomo-phase]');
      this.s = Object.assign({phase: 'focus', running: false, endAt: 0, left: this.ms('focus'), notify: false}, this.read());
      if (!(this.s.left > 0)) this.s.left = this.ms(this.s.phase);

      this.play.addEventListener('click', () => this.toggle());
      this.minus.addEventListener('click', () => this.step(-1)); this.plus.addEventListener('click', () => this.step(1));
      $(this.el, '[data-dl-pomo-reset]').addEventListener('click', () => this.reset(this.s.phase));
      this.radios.forEach(r => r.addEventListener('change', () => { if (r.checked) this.reset(r.value); }));
      this.auto.addEventListener('change', () => { this.cfg.auto = this.auto.checked; this.save(); });
      this.notify.addEventListener('change', () => this.askNotify());
      window.addEventListener('storage', e => { if (e.key === this.key) { this.s = Object.assign(this.s, this.read()); this.draw(); } });
      document.addEventListener('pointerdown', () => { document.title = this.title0; }, true);
      this.timer = setInterval(() => this.tick(), 250);
      this.tick(true); this.draw();
    }

    ms(phase) { return this.cfg[phase] * 60000; }
    read() { try { return JSON.parse(localStorage.getItem(this.key)) || {}; } catch (e) { return {}; } }
    store() { try { localStorage.setItem(this.key, JSON.stringify(this.s)); } catch (e) { /* tanpa penyimpanan: hanya halaman ini */ } }

    left() { return this.s.running ? this.s.endAt - Date.now() : this.s.left; }

    toggle() {
      Devlib.sound?.unlock();
      if (this.s.running) { this.s.left = this.left(); this.s.running = false; } else { this.s.running = true; this.s.endAt = Date.now() + this.s.left; }
      this.store(); this.draw();
    }

    reset(phase) { this.s.phase = phase; this.s.running = false; this.s.left = this.ms(phase); this.store(); this.draw(); }

    // - / +: ubah menit fase yang tampil (1-90), hanya saat jeda; lalu simpan ke server.
    step(dir) {
      if (this.s.running) return;
      const p = this.s.phase; this.cfg[p] = Math.min(90, Math.max(1, this.cfg[p] + dir));
      this.s.left = this.ms(p); this.store(); this.draw();
      clearTimeout(this.saver); this.saver = setTimeout(() => this.save(), 600);
    }

    tick(boot) {
      if (!this.s.running) return;
      const left = this.left();
      if (left > 0) { this.paint(left); return; }
      // Selesai ditangani satu kali: tab lain mungkin sudah memajukan fase (endAt di penyimpanan berbeda dengan milik saya).
      const stored = this.read(); if (stored.endAt && stored.endAt !== this.s.endAt) { this.s = Object.assign(this.s, stored); this.draw(); return; }
      this.finish(boot && Date.now() - this.s.endAt > 5000);
    }

    finish(quiet) {
      const wasFocus = this.s.phase === 'focus', next = wasFocus ? 'break' : 'focus';
      if (wasFocus) { this.sessions++; this.save(true); }
      this.s.phase = next; this.s.left = this.ms(next); this.s.running = wasFocus && this.cfg.auto; this.s.endAt = this.s.running ? Date.now() + this.s.left : 0;
      this.store(); this.draw();
      if (quiet) return;
      Devlib.sound?.play(wasFocus ? 'pomodoro' : 'pomodoro_break');
      const msg = wasFocus ? this.L.lDoneFocus : this.L.lDoneBreak;
      document.title = msg + ' · ' + this.title0;
      if (this.s.notify && window.Notification && Notification.permission === 'granted') { try { new Notification(msg); } catch (e) { /* ponsel: abaikan */ } }
    }

    // Izin notifikasi browser hanya diminta saat pengguna mencentangnya (opt-in).
    askNotify() {
      if (!this.notify.checked) { this.s.notify = false; this.store(); return; }
      if (!window.Notification) { this.notify.checked = false; return; }
      Notification.requestPermission().then(p => { this.s.notify = p === 'granted'; this.notify.checked = this.s.notify; this.store(); }).catch(() => { this.notify.checked = false; });
    }

    // Simpan ke server: durasi + opsi (selalu), dan done=true saat satu sesi Focus selesai (server menghitung sesi hari ini).
    save(done) {
      const url = this.el.dataset.save; if (!url) return;
      fetch(url, {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.el.dataset.csrf},
        body: JSON.stringify({focus: this.cfg.focus, break: this.cfg.break, auto: this.cfg.auto, done: !!done})})
        .then(r => r.ok ? r.json() : null).then(j => { if (j && Number.isFinite(j.sessions)) { this.sessions = j.sessions; this.count.textContent = j.sessions; } }).catch(() => {});
    }

    paint(left) {
      const total = this.ms(this.s.phase);
      this.time.textContent = mmss(left);
      this.bar.setAttribute('stroke-dashoffset', String(Math.max(0, Math.min(100, 100 * (1 - left / total)))).slice(0, 7));
    }

    draw() {
      const run = this.s.running, label = run ? this.L.lPause : this.L.lStart;
      this.paint(this.left());
      this.play.innerHTML = '<i class="fa-solid ' + (run ? 'fa-pause' : 'fa-play') + '" aria-hidden="true"></i>'; this.play.setAttribute('aria-label', label); this.play.title = label;
      this.el.dataset.phase = this.s.phase; this.el.classList.toggle('is-running', run);
      this.radios.forEach(r => { r.checked = r.value === this.s.phase; });
      this.minus.disabled = this.plus.disabled = run; this.auto.checked = this.cfg.auto; this.notify.checked = !!this.s.notify && window.Notification?.permission === 'granted';
      this.count.textContent = this.sessions;
    }
  }
  Devlib.define("Pomodoro", Pomodoro);
})(window.Devlib);
