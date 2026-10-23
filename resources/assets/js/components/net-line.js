/* DevLib · NetLine (baris info jaringan kecil + status koneksi ke server; ping ringan berkala, tidak menyimpan apa pun) */
(function (Devlib) {
  'use strict';
  const {$, $$} = Devlib.h;

  class NetLine extends Devlib.Component {
    static selector = "[data-dl-net-line]";
    init() {
      this.T = JSON.parse(this.el.dataset.t || '{}');
      this.url = this.el.dataset.ping;
      this.every = Math.max(3, parseInt(this.el.dataset.interval || '10', 10)) * 1000;
      this.sim = null;       // keadaan simulasi (halaman peraga: tombol [data-nl-sim] di mana pun pada halaman); selama diisi, ping sungguhan berhenti
      this.fails = 0;
      this.busy = false;
      this.timer = null;
      this.status = $(this.el, '[data-nl-status]');
      $$(document, '[data-nl-sim]').forEach(b => b.addEventListener('click', () => { this.sim = b.dataset.nlSim; this.set(this.sim); }));
      window.addEventListener('offline', () => this.set('offline'));
      window.addEventListener('online', () => this.check());
      document.addEventListener('visibilitychange', () => document.visibilityState === 'visible' ? this.check() : clearTimeout(this.timer));
      if (navigator.onLine === false) this.set('offline');
      this.schedule(this.every);
    }

    set(state) {
      this.el.dataset.state = state;
      this.status.textContent = state === 'ok' ? '' : (this.T[state] || '');
    }

    schedule(ms) {
      clearTimeout(this.timer);
      if (document.visibilityState === 'visible') this.timer = setTimeout(() => this.check(), ms);
    }

    // Respons apa pun di bawah 500 = server menjawab. Dua kegagalan berturut-turut = tidak terjangkau; satu keberhasilan = normal.
    async check() {
      if (this.sim || this.busy || document.visibilityState !== 'visible') return;
      if (navigator.onLine === false) { this.set('offline'); return this.schedule(this.every); }
      this.busy = true;
      const ctl = new AbortController(), cut = setTimeout(() => ctl.abort(), 5000);
      try {
        const r = await fetch(this.url + (this.url.includes('?') ? '&' : '?') + '_=' + Date.now(), {cache: 'no-store', signal: ctl.signal, credentials: 'same-origin'});
        this.fails = r.status < 500 ? 0 : this.fails + 1;
      } catch (e) {
        this.fails++;
      }
      clearTimeout(cut);
      this.busy = false;
      if (!this.sim) this.set(this.fails >= 2 ? 'server' : 'ok');
      this.schedule(this.fails ? Math.min(this.every, 3000) : this.every);   // gagal: cek ulang cepat agar pulih (atau jatuh) terlihat segera
    }
  }
  Devlib.define("NetLine", NetLine);
})(window.Devlib);
