/* DevLib · Devlib.sound
 * Satu modul bunyi bersama untuk SEMUA dering aplikasi (Pomodoro, alarm, timer, notifikasi/lonceng, chat, alarm facility). Nada disintesis
 * Web Audio (tanpa berkas audio), lembut, SATU kali bunyi (tidak diulang). Pilihan per jenis (aktif, volume) disimpan per perangkat di
 * localStorage['dl-sound'] dan BAWAAN MATI sampai pengguna mengaktifkannya (halaman Setup > Sound, x-dl::setup-media).
 * AudioContext dibuat/diaktifkan saat pengguna menyentuh halaman (batasan autoplay browser): sentuhan pertama membuka kunci; dering yang
 * diminta sebelum itu dilewati, tidak ditumpuk. Dipakai: Devlib.sound.play('chat') / ('notif') / ('pomodoro') / ('pomodoro_break') / ('alarm') /
 * ('timer') / ('facility'); event 'dl:sound' (detail: {kind, on, vol}) saat pilihan berubah. play(tone, {force:true}) = tombol Test (abaikan saklar). */
(function (Devlib) {
  'use strict';
  if (Devlib.sound) return;
  const KEY = 'dl-sound';
  // Jenis yang diatur pengguna: bawaan volume (%). Satu jenis boleh punya beberapa nada (Pomodoro: akhir Focus dan akhir Break).
  const KINDS = {pomodoro: 25, alarm: 25, timer: 25, notif: 20, chat: 20, facility: 25};
  // Nada: kind = jenis pengaturannya; notes = [Hz, mulai (detik)]; len = peluruhan tiap nada (detik).
  const TONES = {
    pomodoro: {kind: 'pomodoro', len: 1.8, notes: [[523, 0], [659, .35]]},          // C5 lalu E5: chime rendah, akhir Focus
    pomodoro_break: {kind: 'pomodoro', len: 1.0, notes: [[659, 0], [523, .25]]},    // lebih pendek, akhir Break
    alarm: {kind: 'alarm', len: 1.1, notes: [[698, 0], [880, .22], [1047, .44]]},   // naik tiga nada, jelas tapi lembut
    timer: {kind: 'timer', len: .7, notes: [[880, 0], [880, .26]]},                 // dua ketuk pendek
    notif: {kind: 'notif', len: .5, notes: [[988, 0]]},                             // satu "ding"
    chat: {kind: 'chat', len: .4, notes: [[880, 0], [1175, .14]]},                  // dering chat lama
    facility: {kind: 'facility', len: 1.0, notes: [[587, 0], [784, .2]]},
  };
  let ac = null;

  const load = () => { try { return JSON.parse(localStorage.getItem(KEY)) || {}; } catch (e) { return {}; } };
  const get = kind => { const s = load()[kind] || {}; return {on: s.on === true, vol: Math.min(100, Math.max(0, Number.isFinite(s.vol) ? s.vol : KINDS[kind] ?? 25))}; };
  const set = (kind, patch) => {
    if (!(kind in KINDS)) return;
    const all = load(), cur = get(kind), next = {on: patch.on ?? cur.on, vol: Math.min(100, Math.max(0, +(patch.vol ?? cur.vol)))};
    all[kind] = next;
    try { localStorage.setItem(KEY, JSON.stringify(all)); } catch (e) { /* tanpa penyimpanan: hanya sesi ini */ }
    document.dispatchEvent(new CustomEvent('dl:sound', {detail: {kind, ...next}}));
  };
  const ctx = () => { const C = window.AudioContext || window.webkitAudioContext; try { return C ? (ac ||= new C()) : null; } catch (e) { return null; } };

  // Bunyikan satu nada. Mengembalikan true bila diminta diputar (pilihan aktif atau force).
  const play = (tone, opt = {}) => {
    const t = TONES[tone]; if (!t) return false;
    const s = get(t.kind); if (!s.on && !opt.force) return false;
    const c = ctx(); if (!c) return false;
    const asked = Date.now(), peak = Math.max(.0002, s.vol / 100), go = () => {
      if (Date.now() - asked > 1500) return;
      t.notes.forEach(([hz, at]) => {
        const o = c.createOscillator(), g = c.createGain(), t0 = c.currentTime + at;
        o.type = 'sine'; o.frequency.value = hz;
        g.gain.setValueAtTime(.0001, t0); g.gain.exponentialRampToValueAtTime(peak, t0 + .02); g.gain.exponentialRampToValueAtTime(.0001, t0 + t.len);
        o.connect(g).connect(c.destination); o.start(t0); o.stop(t0 + t.len + .05);
      });
    };
    c.state === 'running' ? go() : c.resume().then(go).catch(() => {});
    return true;
  };

  // Sentuhan pertama pengguna membuka kunci AudioContext supaya dering terjadwal (alarm, akhir Pomodoro) boleh berbunyi kemudian.
  const unlock = () => { const c = ctx(); if (c && c.state !== 'running') c.resume().catch(() => {}); };
  ['pointerdown', 'keydown'].forEach(ev => document.addEventListener(ev, unlock, {once: true, capture: true}));

  Devlib.sound = {kinds: Object.keys(KINDS), get, set, play, unlock};
})(window.Devlib);
