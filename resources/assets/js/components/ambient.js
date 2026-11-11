/* DevLib · Ambient (x-dl::ambient): pad ambient lewat Web Audio (tanpa berkas). Akor Am9 > Fmaj7 > Cmaj7 > G6 berganti tiap 8 detik; tiap nada = dua osilator sedikit meleset + filter yang bergoyang pelan. Play/Pause dan Mute; fade 1,2 detik. */
(function (Devlib) {
  'use strict';
  const {$} = Devlib.h;
  // Frekuensi (Hz) tiap akor, empat nada.
  const CHORDS = [[110, 164.81, 246.94, 329.63], [87.31, 130.81, 220, 329.63], [130.81, 196, 246.94, 329.63], [98, 146.83, 246.94, 329.63]];

  class Ambient extends Devlib.Component {
    static selector = '[data-dl-ambient]';
    init() {
      this.vol = (+this.el.dataset.volume || 18) / 100;
      this.key = this.el.dataset.key || 'dl-ambient';
      this.playing = false;
      this.muted = this.read();
      this.paint();
      this.on('click', '[data-am-play]', () => this.playing ? this.stop() : this.start());
      this.on('click', '[data-am-mute]', () => { this.muted = !this.muted; this.write(); this.fade(); this.paint(); });
      document.addEventListener('visibilitychange', () => { if (document.hidden && this.playing) this.stop(); });
    }
    read() { try { return localStorage.getItem(this.key) === 'mute'; } catch (e) { return false; } }
    write() { try { localStorage.setItem(this.key, this.muted ? 'mute' : 'on'); } catch (e) { /* tanpa penyimpanan */ } }
    paint() {
      const p = $(this.el, '[data-am-play]'), m = $(this.el, '[data-am-mute]');
      p.setAttribute('aria-pressed', this.playing); p.classList.toggle('is-on', this.playing);
      p.querySelector('i').className = 'fa-solid ' + (this.playing ? 'fa-pause' : 'fa-play');
      m.setAttribute('aria-pressed', this.muted); m.classList.toggle('is-on', this.muted);
      m.querySelector('i').className = 'fa-solid ' + (this.muted ? 'fa-volume-xmark' : 'fa-volume-high');
    }
    fade(to) {
      if (!this.ctx) return;
      const t = this.ctx.currentTime, g = this.master.gain;
      g.cancelScheduledValues(t); g.setValueAtTime(g.value, t);
      g.linearRampToValueAtTime(to === undefined ? (this.muted ? 0 : this.vol) : to, t + 1.2);
    }
    start() {
      const AC = window.AudioContext || window.webkitAudioContext;
      if (!AC) return;
      if (!this.ctx) {
        this.ctx = new AC();
        this.master = this.ctx.createGain(); this.master.gain.value = 0;
        this.filter = this.ctx.createBiquadFilter(); this.filter.type = 'lowpass'; this.filter.frequency.value = 900;
        const lfo = this.ctx.createOscillator(), lg = this.ctx.createGain();
        lfo.frequency.value = 0.07; lg.gain.value = 350; lfo.connect(lg); lg.connect(this.filter.frequency); lfo.start();
        this.filter.connect(this.master); this.master.connect(this.ctx.destination);
        this.voices = CHORDS[0].flatMap(() => [0, 1].map(k => {
          const o = this.ctx.createOscillator(), g = this.ctx.createGain();
          o.type = k ? 'triangle' : 'sine'; o.detune.value = k ? 7 : -5; g.gain.value = 0.22;
          o.connect(g); g.connect(this.filter); o.start(); return o;
        }));
      }
      this.ctx.resume(); this.playing = true; this.step = 0; this.chord();
      this.timer = setInterval(() => { this.step = (this.step + 1) % CHORDS.length; this.chord(); }, 8000);
      this.fade(); this.paint();
    }
    chord() {
      const t = this.ctx.currentTime;
      CHORDS[this.step].forEach((f, i) => [0, 1].forEach(k => this.voices[i * 2 + k].frequency.linearRampToValueAtTime(f, t + 3)));
    }
    stop() {
      clearInterval(this.timer); this.playing = false; this.fade(0); this.paint();
      setTimeout(() => { if (!this.playing && this.ctx) this.ctx.suspend(); }, 1400);
    }
  }
  Devlib.define('Ambient', Ambient);
})(window.Devlib);
