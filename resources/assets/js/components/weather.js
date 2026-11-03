/* DevLib · Weather */
(function (Devlib) {
  'use strict';
  const {$, WMO} = Devlib.h;

  const HOUR = 3600 * 1000, DAY = 24 * HOUR;
  const KEY = 'dl-wx', DENIED = 'dl-wx-no';
  const round2 = n => Math.round(n * 100) / 100;

  // Rantai tempat: (1) cache jam terakhir, (2) lokasi perangkat (Geolocation, hanya HTTPS / localhost dan bila belum ditolak), (3) lokasi dari
  // IP pengakses (layanan gratis, tanpa izin browser, jalan di HTTP biasa; lapis tengah, keputusan Jo 2026-11-03), (4) tempat tetap dari props
  // (cadangan), (5) tidak ada = null (kartu disembunyikan). Murni: semua yang dibutuhkan masuk lewat env.
  //   env = {locate, secure, geo() -> Promise<{lat, lon}> | null, ip() -> Promise<{lat, lon, city}> | null, fixed: {lat, lon, city} | null, read(key), write(key, value), now}
  async function place(env) {
    if (env.locate) {
      const hit = env.read(KEY);
      if (hit && env.now - hit.t < HOUR) return {lat: hit.lat, lon: hit.lon, city: hit.city, temp: hit.temp, code: hit.code, source: 'cache'};
      const no = env.read(DENIED);
      if (env.secure && env.geo && !(no && env.now - no < 7 * DAY)) {
        try {
          const p = await env.geo();
          return {lat: round2(p.lat), lon: round2(p.lon), source: 'device'};
        } catch (e) {
          if (e && e.code === 1) env.write(DENIED, env.now);   // izin ditolak: jangan tanya lagi selama seminggu
        }
      }
      if (env.ip) {
        try {
          const p = await env.ip();
          if (p) return {lat: round2(p.lat), lon: round2(p.lon), city: p.city || '', source: 'ip'};
        } catch (e) { /* layanan IP gagal/timeout: diam-diam lanjut ke tempat tetap */ }
      }
    }
    return env.fixed ? Object.assign({source: 'fixed'}, env.fixed) : null;
  }

  class Weather extends Devlib.Component {
    static selector = "[data-dl-weather]";
    init() {
      const d = this.el.dataset;
      this.w = null;
      document.addEventListener('dl:lang', this.paint);
      const fixed = d.lat !== undefined && d.lat !== '' && d.lon !== undefined && d.lon !== '' ? {lat: +d.lat, lon: +d.lon, city: d.city || ''} : null;
      const store = {
        read: k => { try { return JSON.parse(localStorage.getItem(k)); } catch (e) { return null; } },
        write: (k, v) => { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) { /* penyimpanan tidak tersedia: tanpa cache */ } },
      };
      place({
        locate: d.locate === '1', secure: window.isSecureContext === true, fixed, now: Date.now(), ...store,
        // maximumAge enam jam, timeout pendek, tanpa akurasi tinggi: cukup tingkat kota.
        geo: navigator.geolocation ? () => new Promise((ok, no) => navigator.geolocation.getCurrentPosition(p => ok({lat: p.coords.latitude, lon: p.coords.longitude}), no, {maximumAge: 6 * HOUR, timeout: 8000, enableHighAccuracy: false})) : null,
        ip: d.ipFallback === '0' ? null : this.ipLocate,
      }).then(p => p ? this.load(p, store) : null);
    }

    // Lokasi dari IP pengakses: ipapi.co (gratis, CORS, HTTPS, tanpa kunci), jalan di HTTP biasa karena tidak lewat Geolocation browser.
    ipLocate() {
      const ctl = new AbortController(), cut = setTimeout(() => ctl.abort(), 4000);
      return fetch('https://ipapi.co/json/', {signal: ctl.signal}).then(r => r.json()).finally(() => clearTimeout(cut))
        .then(j => j && j.latitude != null && j.longitude != null ? {lat: j.latitude, lon: j.longitude, city: j.city || ''} : null);
    }

    async load(p, store) {
      this.el.hidden = false;
      this.el.dataset.src = p.source;
      this.setCity(p.city || '');
      if (p.source === 'cache') { this.w = {temp: p.temp, code: p.code}; return this.paint(); }
      const url = `https://api.open-meteo.com/v1/forecast?latitude=${p.lat}&longitude=${p.lon}&current=temperature_2m,weather_code&timezone=auto`;
      try {
        const [j, city] = await Promise.all([fetch(url).then(r => r.json()), p.source === 'device' ? this.cityOf(p) : p.city]);
        this.w = {temp: j.current.temperature_2m, code: j.current.weather_code};
        this.setCity(city || '');
        this.paint();
        if (p.source === 'device' || p.source === 'ip') store.write(KEY, {lat: p.lat, lon: p.lon, city: city || '', temp: this.w.temp, code: this.w.code, t: Date.now()});
      } catch (e) {
        $(this.el, '[data-dl-weather-desc]').textContent = Devlib.h.T('wx_unavailable');
      }
    }

    // Nama kota dari koordinat: BigDataCloud reverse-geocode-client (tanpa kunci, memang untuk dipanggil dari browser pengguna). Gagal = tanpa nama kota.
    async cityOf(p) {
      try {
        const j = await fetch(`https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${p.lat}&longitude=${p.lon}&localityLanguage=${Devlib.lang === 'id' ? 'id' : 'en'}`).then(r => r.json());
        return j.city || j.locality || j.principalSubdivision || '';
      } catch (e) {
        return '';
      }
    }

    setCity(name) {
      $(this.el, '[data-dl-weather-city]').textContent = name ? ' · ' + name : '';
    }

    paint() {
      if (!this.w) return;
      const m = WMO.find(x => this.w.code <= x[0]) || WMO[WMO.length - 1];
      $(this.el, '[data-dl-weather-icon]').textContent = m[1];
      $(this.el, '[data-dl-weather-temp]').textContent = Math.round(this.w.temp) + '°C';
      $(this.el, '[data-dl-weather-desc]').textContent = Devlib.lang === 'id' ? m[2] : m[3];
    }
  }
  Weather.place = place;
  Devlib.define("Weather", Weather);
})(window.Devlib);
