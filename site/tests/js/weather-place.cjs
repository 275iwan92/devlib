// Dipanggil WeatherTest: menjalankan weather.js dengan Devlib tiruan dan menguji rantai tempat Weather.place(env).
const fs = require('fs'), vm = require('vm'), assert = require('assert');
let cls;
const Devlib = {Component: class {}, define: (n, c) => { cls = c; }, h: {$: () => null, WMO: [], T: k => k}};
vm.runInNewContext(fs.readFileSync(process.argv[2], 'utf8'), {window: {Devlib}});
const place = cls.place, NOW = 1e12, HOUR = 3600e3, DAY = 24 * HOUR;
const fixed = {lat: -7.98, lon: 112.63, city: 'Malang'};
const mk = (o = {}) => { const mem = o.mem || {}; return {locate: true, secure: true, fixed, now: NOW, read: k => mem[k] ?? null, write: (k, v) => { mem[k] = v; }, geo: async () => ({lat: -6.2088, lon: 106.8456}), mem, ...o}; };

(async () => {
  // 1. lokasi perangkat berhasil: koordinat dibulatkan 2 desimal
  let p = await place(mk({geo: async () => ({lat: -7.98123, lon: 112.63456})}));
  assert.deepStrictEqual([p.source, p.lat, p.lon], ['device', -7.98, 112.63]);
  // 2. cache segar (< 1 jam) dipakai tanpa meminta lokasi
  p = await place(mk({geo: async () => { throw new Error('tidak boleh dipanggil'); }, mem: {'dl-wx': {lat: 1, lon: 2, city: 'X', temp: 20, code: 1, t: NOW - HOUR / 2}}}));
  assert.deepStrictEqual([p.source, p.city, p.temp], ['cache', 'X', 20]);
  // 3. cache kedaluwarsa diabaikan
  p = await place(mk({mem: {'dl-wx': {lat: 1, lon: 2, t: NOW - 2 * HOUR}}}));
  assert.strictEqual(p.source, 'device');
  // 4. izin ditolak (code 1): cadangan props, penolakan diingat, lalu tidak ditanya lagi
  const e = mk({geo: async () => { const x = new Error('denied'); x.code = 1; throw x; }});
  p = await place(e);
  assert.strictEqual(p.source, 'fixed'); assert.strictEqual(p.city, 'Malang'); assert.strictEqual(e.mem['dl-wx-no'], NOW);
  let asked = false;
  p = await place(mk({mem: {'dl-wx-no': NOW - DAY}, geo: async () => { asked = true; return {lat: 1, lon: 1}; }}));
  assert.strictEqual(p.source, 'fixed'); assert.strictEqual(asked, false);
  // 5. penolakan lama (> 7 hari) tidak berlaku
  p = await place(mk({mem: {'dl-wx-no': NOW - 8 * DAY}}));
  assert.strictEqual(p.source, 'device');
  // 6. timeout / posisi tidak tersedia (code 2/3): cadangan, penolakan tidak dicatat
  const t = mk({geo: async () => { const x = new Error('timeout'); x.code = 3; throw x; }});
  assert.strictEqual((await place(t)).source, 'fixed'); assert.strictEqual(t.mem['dl-wx-no'], undefined);
  // 7. bukan HTTPS / tanpa Geolocation: cadangan tanpa memanggil geo
  assert.strictEqual((await place(mk({secure: false}))).source, 'fixed');
  assert.strictEqual((await place(mk({geo: null}))).source, 'fixed');
  // 8. locate=false: selalu tempat tetap, cache dan lokasi diabaikan
  p = await place(mk({locate: false, mem: {'dl-wx': {lat: 1, lon: 2, t: NOW}}}));
  assert.strictEqual(p.source, 'fixed');
  // 9. tanpa cadangan dan lokasi gagal: null (kartu tidak tampil)
  assert.strictEqual(await place(mk({fixed: null, secure: false})), null);
  assert.strictEqual(await place(mk({fixed: null, locate: false})), null);
  console.log('OK');
})().catch(e => { console.error(e); process.exit(1); });
