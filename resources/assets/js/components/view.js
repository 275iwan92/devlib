/* DevLib · View (multi view: OS + keyboard HP) */
(function (Devlib) {
  'use strict';
  const root = document.documentElement;
  const ua = navigator.userAgent;

  // OS sisi klien: iPadOS 13+ menyamar sebagai Mac (UA Macintosh) tetapi punya layar sentuh; server tidak bisa membedakannya (view-nya tetap desktop).
  Devlib.os = /iPhone|iPad|iPod/.test(ua) || (/Macintosh/.test(ua) && navigator.maxTouchPoints > 1) ? 'ios' : /Android/.test(ua) ? 'android' : 'other';
  root.dataset.dlOs = Devlib.os;

  // Aturan LAYAR phone (sama dengan Devlib\Device::PHONE_MEDIA; dijaga tes): lebar < 600, atau HP landscape (tinggi < 480, sentuh, lebar < 1000). Tidak mengubah view yang dirender server.
  Devlib.PHONE_MQ = '(max-width: 599.98px), (max-height: 479.98px) and (max-width: 999.98px) and (pointer: coarse)';
  const mq = window.matchMedia(Devlib.PHONE_MQ);
  Devlib.phoneScreen = () => mq.matches;
  const syncScreen = () => { root.dataset.dlScreen = mq.matches ? 'phone' : 'desktop'; };
  syncScreen();
  mq.addEventListener('change', syncScreen);

  // Keyboard HP: hanya di view phone (body.dl-on-phone atau x-dl::phone-shell). Tinggi yang terlihat = --dl-vvh; isian yang difokus digulir ke tengah area terlihat agar tidak tertutup keyboard.
  document.addEventListener('DOMContentLoaded', () => {
    const served = document.body.className.match(/dl-os-(android|ios)/);   // OS yang dirender server (juga pada pratinjau ?dl-os=) menang atas UA klien
    if (served) root.dataset.dlOs = Devlib.os = served[1];
    const vv = window.visualViewport;
    if (!vv || !(document.body.classList.contains('dl-on-phone') || document.querySelector('.dl-phone'))) return;
    const sync = () => root.style.setProperty('--dl-vvh', vv.height + 'px');
    sync();
    vv.addEventListener('resize', sync);
    document.addEventListener('focusin', e => {
      const t = e.target;
      if (!t.matches || !t.matches('input:not([type=checkbox],[type=radio],[type=button],[type=submit]),select,textarea')) return;
      setTimeout(() => {   // tunggu keyboard selesai naik
        const r = t.getBoundingClientRect();
        if (r.bottom > vv.height - 8 || r.top < 8) t.scrollIntoView({block: 'center', behavior: 'smooth'});
      }, 300);
    });
  });
})(window.Devlib);
