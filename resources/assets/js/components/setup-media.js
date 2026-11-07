/* DevLib · SetupMedia
 * Halaman Setup "Music" dan "Screen animation" (x-dl::setup-media). Musik: saklar, volume, pilihan trek mengendalikan x-dl::music-player lewat
 * API-nya (setOn / setVolume / setTrack) dan mengikuti event dl:music. Gerak: tiga pilihan ke Devlib.motion (auto | on | off, disimpan per browser). */
(function (Devlib) {
  'use strict';
  const {$, $$} = Devlib.h;

  class SetupMusic extends Devlib.Component {
    static selector = "[data-dl-setup-music]";
    init() {
      this.on = $(this.el, '[data-dl-setup-on]'); this.vol = $(this.el, '[data-dl-setup-vol]'); this.volv = $(this.el, '[data-dl-setup-volv]');
      this.track = $(this.el, '[data-dl-setup-track]'); this.row = $(this.el, '[data-dl-setup-trackrow]');
      const player = () => document.querySelector('[data-dl-music]')?.dlComponent;
      this.on.addEventListener('change', () => player()?.setOn(this.on.checked));
      this.vol.addEventListener('input', () => player()?.setVolume(+this.vol.value));
      this.track.addEventListener('change', () => player()?.setTrack(this.track.value));
      document.addEventListener('dl:music', e => this.sync(e.detail));
      const p = player();
      if (p) { this.sync({on: p.s.on, vol: p.s.vol, track: p.s.track, tracks: p.tracks}); } else { this.on.disabled = this.vol.disabled = true; this.row.hidden = true; }
    }

    sync(st) {
      this.on.checked = st.on; this.vol.value = st.vol; this.volv.textContent = st.vol + '%';
      if (this.track.options.length !== st.tracks.length + 1) {
        this.track.replaceChildren(new Option('All tracks in order', 'all'), ...st.tracks.map(t => new Option(t.title + ' — ' + t.author, t.file)));
      }
      this.track.value = st.track; this.row.hidden = st.tracks.length < 2;
    }
  }
  Devlib.define("SetupMusic", SetupMusic);

  class SetupMotion extends Devlib.Component {
    static selector = "[data-dl-setup-motion]";
    init() {
      this.radios = $$(this.el, 'input[name="dl-motion"]');
      this.radios.forEach(r => r.addEventListener('change', () => { if (r.checked) Devlib.motion.set(r.value); }));
      document.addEventListener('dl:motion', this.show); this.show();
    }

    show() { const p = Devlib.motion.pref(); this.radios.forEach(r => { r.checked = r.value === p; }); }
  }
  Devlib.define("SetupMotion", SetupMotion);
})(window.Devlib);
