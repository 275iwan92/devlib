/* DevLib · SetupMedia
 * Halaman Setup "Music", "Screen animation" dan "Sound" (x-dl::setup-media). Musik: saklar, volume, pilihan trek mengendalikan x-dl::music-player lewat
 * API-nya (setOn / setVolume / setTrack) dan mengikuti event dl:music. Gerak: tiga pilihan ke Devlib.motion (auto | on | off, disimpan per browser). Bunyi: per jenis dering saklar, volume, dan tombol Test ke Devlib.sound (sound.js). */
(function (Devlib) {
  'use strict';
  const {$, $$} = Devlib.h;
  // Halaman induk bila pengaturan ini dipakai sebagai layar biasa di dalam iframe Layer Menu (pemutar musik dan kotak sapaan ada di halaman induk, satu asal-usul).
  const parentWin = () => { try { return window.parent !== window && window.parent.document ? window.parent : null; } catch (e) { return null; } };

  class SetupMusic extends Devlib.Component {
    static selector = "[data-dl-setup-music]";
    init() {
      this.on = $(this.el, '[data-dl-setup-on]'); this.vol = $(this.el, '[data-dl-setup-vol]'); this.volv = $(this.el, '[data-dl-setup-volv]');
      this.track = $(this.el, '[data-dl-setup-track]'); this.row = $(this.el, '[data-dl-setup-trackrow]');
      const doc = () => document.querySelector('[data-dl-music]') ? document : (parentWin()?.document ?? document);
      const player = () => doc().querySelector('[data-dl-music]')?.dlComponent;
      this.on.addEventListener('change', () => player()?.setOn(this.on.checked));
      this.vol.addEventListener('input', () => player()?.setVolume(+this.vol.value));
      this.track.addEventListener('change', () => player()?.setTrack(this.track.value));
      doc().addEventListener('dl:music', e => this.sync(e.detail));
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
      // Di iframe, kotak sapaan ada di halaman induk: pilihan ikut diterapkan di sana supaya gerak berubah seketika.
      this.radios.forEach(r => r.addEventListener('change', () => { if (r.checked) { Devlib.motion.set(r.value); parentWin()?.Devlib?.motion?.set(r.value); } }));
      document.addEventListener('dl:motion', this.show); this.show();
    }

    show() { const p = Devlib.motion.pref(); this.radios.forEach(r => { r.checked = r.value === p; }); }
  }
  Devlib.define("SetupMotion", SetupMotion);

  class SetupSound extends Devlib.Component {
    static selector = "[data-dl-setup-sound]";
    init() {
      this.rows = $$(this.el, '[data-kind]');
      this.rows.forEach(r => {
        const k = r.dataset.kind, on = $(r, '[data-dl-sound-on]'), vol = $(r, '[data-dl-sound-vol]');
        // Menyalakan = sentuhan pengguna: sekalian membuka AudioContext dan memperdengarkan nadanya satu kali.
        on.addEventListener('change', () => { Devlib.sound.set(k, {on: on.checked}); if (on.checked) Devlib.sound.play(k); });
        vol.addEventListener('input', () => Devlib.sound.set(k, {vol: +vol.value}));
        vol.addEventListener('change', () => Devlib.sound.play(k, {force: true}));
        $(r, '[data-dl-sound-test]').addEventListener('click', () => Devlib.sound.play(k, {force: true}));
      });
      document.addEventListener('dl:sound', () => this.show()); this.show();
    }

    show() {
      this.rows.forEach(r => { const s = Devlib.sound.get(r.dataset.kind); $(r, '[data-dl-sound-on]').checked = s.on; $(r, '[data-dl-sound-vol]').value = s.vol; });
    }
  }
  Devlib.define("SetupSound", SetupSound);
})(window.Devlib);
