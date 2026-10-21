/* DevLib · IdleLogout */
(function (Devlib) {
  'use strict';
  const {$, T, Tf} = Devlib.h;
  const KEY = 'dl-idle-last', KEY_OUT = 'dl-idle-out', CHANNEL = 'dl-idle', MSG = 'dl:idle-activity';
  const ACTIVITY = ['mousemove', 'pointerdown', 'keydown', 'scroll', 'wheel', 'touchstart'];
  const mmss = ms => { const s = Math.max(0, Math.ceil(ms / 1000)); return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0'); };

  /* ======================= IDLE LOGOUT ======================= */
  // Di dalam frame (layar iframe same-origin) tidak ada pencacah, peringatan, ping, atau logout: frame hanya meneruskan aktivitas
  // ke jendela paling atas (postMessage, hanya ke origin yang sama). Jendela atas yang tidak memasang komponen tidak mendengar apa-apa.
  // Satu instans per halaman. Waktu aktivitas terakhir dibagi antar tab (localStorage + BroadcastChannel; keduanya boleh gagal).
  // Peringatan terbuka = hanya tombol di dalamnya yang memperpanjang (gerakan tikus yang tak sengaja tidak). Form tidak disentuh.
  class IdleLogout extends Devlib.Component {
    static selector = "[data-dl-idle]";
    init() {
      if (window !== window.top) return this.relay();
      if (window.dlIdleLogout) { this.el.remove(); return; }
      window.dlIdleLogout = this;
      window.addEventListener('message', e => { if (e.origin === location.origin && e.source !== window && e.data && e.data.type === MSG) this.activity(); });
      const d = this.el.dataset;
      this.idleMs = (parseFloat(d.minutes) || 0) * 60000;
      this.warnMs = Math.min((parseFloat(d.warn) || 0) * 60000, this.idleMs);
      this.pingMs = (parseFloat(d.pingEvery) || 0) * 60000;
      this.preview = 'preview' in d;
      this.last = Date.now(); this.lastPing = this.last; this.local = false; this.state = 'run'; this.at = 0;
      this.msg = $(this.el, '[data-idle-msg]'); this.stay = $(this.el, '[data-idle-stay]');
      try { this.last = Math.max(this.last, +localStorage.getItem(KEY) || 0); } catch (e) {}
      try { this.bc = new BroadcastChannel(CHANNEL); this.bc.onmessage = e => this.heard(e.data); } catch (e) { this.bc = null; }
      window.addEventListener('storage', e => {
        if (e.key === KEY && e.newValue) this.heard({t: 'active', at: +e.newValue});
        if (e.key === KEY_OUT && e.newValue) this.heard({t: 'out'});
      });
      ACTIVITY.forEach(n => document.addEventListener(n, this.activity, {passive: true, capture: true}));
      this.on('click', '[data-idle-stay]', () => this.keep());
      this.on('click', '[data-idle-out]', () => this.expire());
      this.on('click', '[data-idle-again]', () => this.reset());
      this.el.addEventListener('keydown', e => { if (e.key === 'Escape') this.keep(); });
      this.timer = setInterval(this.tick, 1000);
      this.tick();
    }

    // Dipakai di dalam frame: teruskan aktivitas ke jendela atas (dibatasi 1x per detik); elemen peringatan tidak dipakai.
    relay() {
      this.el.remove();
      let at = 0;
      const send = () => {
        const now = Date.now(); if (now - at < 1000) return;
        at = now;
        try { window.top.postMessage({type: MSG}, location.origin); } catch (e) {}
      };
      ACTIVITY.forEach(n => document.addEventListener(n, send, {passive: true, capture: true}));
    }

    // Aktivitas lokal (dibatasi 1x per detik). Peringatan terbuka atau sudah habis: diabaikan.
    activity() {
      const now = Date.now();
      if (this.state !== 'run' || now - this.at < 1000) return;
      this.at = now; this.touch(now);
    }

    touch(now) {
      this.last = now; this.local = true;
      try { localStorage.setItem(KEY, String(now)); } catch (e) {}
      if (this.bc) this.bc.postMessage({t: 'active', at: now});
    }

    // Pesan dari tab lain: aktivitas memperpanjang, logout menutup tab ini juga.
    heard(m) {
      if (!m) return;
      if (m.t === 'active') this.last = Math.max(this.last, m.at);
      else if (m.t === 'out' && this.state !== 'out') this.leave();
    }

    tick() {
      if (this.state === 'out') return;
      const left = this.last + this.idleMs - Date.now();
      if (left <= 0) return this.expire();
      if (left <= this.warnMs) this.warning(left); else if (this.state === 'warn') this.close();
      if (this.state === 'run') this.ping();
    }

    warning(left) {
      if (this.state === 'run') {
        this.state = 'warn'; this.back = document.activeElement;
        this.el.hidden = false; this.stay.focus();
        this.emit('dl:idle-warn', {left});
      }
      this.msg.textContent = Tf('idle_msg', {time: mmss(left)});
    }

    close() {
      this.state = 'run'; this.el.hidden = true;
      if (this.back && document.contains(this.back)) this.back.focus();
    }

    // "Tetap masuk": segarkan sesi server, lalu tutup peringatan. Ping gagal karena sesi sudah habis (401/419) = langsung keluar.
    async keep() {
      if (this.state !== 'warn') return;
      if (await this.ping(true) === 'dead') return this.expire();
      this.touch(Date.now()); this.close();
    }

    // Ping hanya bila ada aktivitas lokal sejak ping terakhir (atau dipaksa). Kembali 'dead' bila sesi server sudah habis.
    async ping(force = false) {
      const url = this.el.dataset.pingUrl, now = Date.now();
      if (!url || this.preview || !(force || (this.local && this.pingMs && now - this.lastPing >= this.pingMs))) return 'skip';
      this.lastPing = now; this.local = false;
      try {
        const r = await fetch(url, {credentials: 'same-origin', cache: 'no-store', headers: {Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest'}});
        return r.status === 401 || r.status === 419 ? 'dead' : 'ok';
      } catch (e) { return 'error'; }
    }

    csrf() {
      return window.DL_CSRF || document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value || '';
    }

    // Habis: beri tahu tab lain, kirim logout (POST), lalu pindah. Pratinjau katalog hanya menampilkan pesan.
    async expire() {
      if (this.state === 'out') return;
      this.state = 'out'; clearInterval(this.timer);
      this.title('idle_ended'); this.el.hidden = false; this.el.classList.add('is-out');
      $(this.el, '[data-idle-stay]').hidden = true; $(this.el, '[data-idle-out]').hidden = true;
      if (this.preview) {
        this.msg.textContent = T('idle_preview');
        const b = $(this.el, '[data-idle-again]') || this.again();
        b.hidden = false; b.focus();
        return;
      }
      this.msg.textContent = T('idle_done');
      try { localStorage.setItem(KEY_OUT, String(Date.now())); } catch (e) {}
      if (this.bc) this.bc.postMessage({t: 'out'});
      let url = this.el.dataset.loginUrl;
      const out = this.el.dataset.logoutUrl;
      if (out) {
        try {
          const body = new FormData(); body.append('_token', this.csrf());
          const r = await fetch(out, {method: 'POST', body, credentials: 'same-origin', headers: {'X-CSRF-TOKEN': this.csrf(), 'X-Requested-With': 'XMLHttpRequest'}});
          url ||= r.redirected ? r.url : '';
        } catch (e) { /* tidak terjangkau: server tetap mengakhiri sesi, halaman dimuat ulang di bawah */ }
      }
      this.emit('dl:idle-logout', {url});
      this.go(url);
    }

    title(key) { const b = $(this.el, '#dl-idle-title'); b.dataset.dlT = key; b.textContent = T(key); }

    leave() { this.state = 'out'; clearInterval(this.timer); this.go(this.el.dataset.loginUrl); }

    go(url) { url ? location.assign(url) : location.reload(); }

    again() {
      const b = document.createElement('button');
      b.type = 'button'; b.className = 'dl-btn dl-btn--md dl-btn--primary'; b.dataset.idleAgain = ''; b.textContent = T('idle_again');
      $(this.el, '.dl-idle__act').appendChild(b);
      return b;
    }

    // Mulai ulang dari nol (pratinjau katalog atau skrip pengujian).
    reset() {
      this.state = 'run'; this.el.hidden = true; this.el.classList.remove('is-out'); this.title('idle_title');
      $(this.el, '[data-idle-stay]').hidden = false; $(this.el, '[data-idle-out]').hidden = false;
      const b = $(this.el, '[data-idle-again]'); if (b) b.hidden = true;
      this.touch(Date.now()); this.lastPing = this.last;
      clearInterval(this.timer); this.timer = setInterval(this.tick, 1000); this.tick();
    }
  }
  Devlib.define("IdleLogout", IdleLogout);
})(window.Devlib);
