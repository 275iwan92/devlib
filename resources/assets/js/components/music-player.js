/* DevLib · MusicPlayer
 * Pemutar musik latar (pola proyek admin Jo): bawaan DIAM; Play memulai dengan fade 1 detik; trek berurutan; posisi dan pengaturan
 * diingat per perangkat (localStorage[data-key]: mute, vol, i, pos); jeda saat tab tersembunyi. Tiap muat halaman musik DIAM sampai
 * pengguna menekan Play (status "on" tidak dipulihkan). Elemen <audio> milik komponen. */
(function (Devlib) {
  'use strict';
  const {$} = Devlib.h;

  class MusicPlayer extends Devlib.Component {
    static selector = "[data-dl-music]";
    init() {
      const d = this.el.dataset;
      this.key = d.key; this.base = d.base; this.tracks = JSON.parse(d.tracks || '[]');
      this.s = {on: false, mute: false, vol: +d.volume || 25, i: 0, pos: 0};
      try { Object.assign(this.s, JSON.parse(localStorage.getItem(this.key) || '{}')); } catch (e) { /* tanpa penyimpanan: bawaan */ }
      this.s.on = false; // keputusan Jo 2026-11-07: tiap muat halaman DIAM; hanya Play dari pengguna yang menyalakan (volume, bisu, trek, posisi tetap diingat)
      this.audio = new Audio(); this.audio.preload = 'none';
      this.btnPlay = $(this.el, '[data-dl-music-play]'); this.btnMute = $(this.el, '[data-dl-music-mute]');
      this.vol = $(this.el, '[data-dl-music-vol]'); this.credit = $(this.el, '[data-dl-music-credit]');
      this.blocked = false; this.resumeAt = this.s.pos; this.fade = 0;

      this.btnPlay.addEventListener('click', () => { this.s.on = !this.s.on; this.apply(); });
      this.btnMute.addEventListener('click', () => { this.s.mute = !this.s.mute; this.apply(); });
      this.vol.addEventListener('input', () => { this.s.vol = +this.vol.value; this.save(); if (!this.audio.paused) this.audio.volume = this.gain(); });
      this.audio.addEventListener('ended', () => { this.s.i = (this.idx() + 1) % this.tracks.length; this.s.pos = 0; this.save(); this.play(); });
      document.addEventListener('visibilitychange', () => { if (document.hidden) this.audio.pause(); else this.play(); });
      ['pointerdown', 'keydown'].forEach(ev => document.addEventListener(ev, () => { if (this.blocked) this.play(); }, true));
      setInterval(() => { if (!this.audio.paused) { this.s.pos = Math.floor(this.audio.currentTime); this.save(); } }, 2000);
      this.draw(); this.play();
    }

    save() { try { localStorage.setItem(this.key, JSON.stringify(this.s)); } catch (e) { /* abaikan */ } }
    idx() { return this.s.i % this.tracks.length; }
    want() { return this.s.on && !this.s.mute; }
    gain() { return Math.min(1, this.s.vol / 100); }

    // Naik/turun halus (+-0,025 tiap 40 ms = 1 detik penuh), bukan lonjakan.
    ramp(to, done) {
      clearInterval(this.fade);
      this.fade = setInterval(() => {
        const diff = to - this.audio.volume;
        if (Math.abs(diff) < .02) { this.audio.volume = to; clearInterval(this.fade); if (done) done(); } else this.audio.volume = Math.min(1, Math.max(0, this.audio.volume + Math.sign(diff) * .025));
      }, 40);
    }

    load() {
      const t = this.tracks[this.idx()];
      if (!this.audio.src.endsWith(t.file)) {
        this.audio.src = this.base + t.file;
        if (this.resumeAt) this.audio.addEventListener('loadedmetadata', () => { this.audio.currentTime = this.resumeAt; this.resumeAt = 0; }, {once: true});
      }
      this.audio.loop = this.tracks.length === 1;
      this.credit.textContent = '';
      const link = (txt, href) => { if (!href) return document.createTextNode(txt); const a = document.createElement('a'); a.href = href; a.target = '_blank'; a.rel = 'noopener'; a.textContent = txt; return a; };
      this.credit.append('♪ ', t.title, ' — ', link(t.author, t.url));
      if (t.license) this.credit.append(' · ', link(t.license, t.license_url));
      this.credit.title = this.credit.textContent;
    }

    play() {
      if (!this.want() || document.hidden) return;
      this.load(); this.audio.volume = 0;
      this.audio.play().then(() => { this.blocked = false; this.ramp(this.gain()); }).catch(() => { this.blocked = true; });
    }

    apply() {
      this.save(); this.draw();
      if (!this.want()) { this.ramp(0, () => this.audio.pause()); return; }
      if (this.audio.paused) this.play(); else { this.load(); this.ramp(this.gain()); }
    }

    draw() {
      const d = this.el.dataset, on = this.s.on;
      this.btnPlay.setAttribute('aria-pressed', on); this.btnPlay.setAttribute('aria-label', on ? d.lPause : d.lPlay); this.btnPlay.title = on ? d.lPause : d.lPlay;
      this.btnPlay.firstElementChild.className = 'fa-solid ' + (on ? 'fa-pause' : 'fa-play');
      this.btnMute.hidden = this.vol.hidden = this.credit.hidden = !on;
      this.btnMute.setAttribute('aria-pressed', this.s.mute); this.btnMute.setAttribute('aria-label', this.s.mute ? d.lUnmute : d.lMute); this.btnMute.title = this.s.mute ? d.lUnmute : d.lMute;
      this.btnMute.firstElementChild.className = 'fa-solid ' + (this.s.mute ? 'fa-volume-xmark' : 'fa-volume-high');
      this.vol.value = this.s.vol;
      this.el.classList.toggle('is-playing', on && !this.s.mute);
    }
  }
  Devlib.define("MusicPlayer", MusicPlayer);
})(window.Devlib);
