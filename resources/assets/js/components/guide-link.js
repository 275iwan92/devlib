/* DevLib · GuideLink */
(function (Devlib) {
  'use strict';
  // Judul object -> link ke entri object itu di Technical Guide (window.DL_GUIDE = config devlib.guide_url / route devlib.guide; kosong = tanpa link).
  // tag => [pemilih elemen judul, 'title' = isi judul dibungkus link | 'icon' = ikon info ditambahkan di awal elemen].
  const TITLES = {
    'x-dl::form': ['.dl-form__title', 'title'], 'x-dl::card': ['.dl-card__head b', 'title'], 'x-dl::wizard': ['.dl-wizard__title', 'title'],
    'x-dl::flow': ['.dl-flow__title', 'title'], 'x-dl::wh-map': ['.dl-whmap__title', 'title'], 'x-dl::pivot': ['.dl-pivot__title', 'title'],
    'x-dl::coverage': ['.dl-cov__title', 'title'], 'x-dl::code-generator': ['.dl-codegen__title', 'title'], 'x-dl::status-card': ['.dl-scard__title', 'title'],
    'x-dl::side-screen': ['.dl-ss__title', 'title'], 'x-dl::member-list': ['.dl-members__title', 'title'], 'x-dl::notification-list': ['.dl-notif__title', 'title'],
    // Grid tidak punya judul sendiri (judulnya milik layar/tab): ikon kecil di toolbar.
    'x-dl::grid': ['.dl-grid__toolbar', 'icon'],
  };
  const anchor = tag => tag.replace(/[^a-z0-9]+/gi, '-').replace(/^-|-$/g, '');

  function guideLinks(root = document) {
    const base = window.DL_GUIDE;
    if (!base) return;
    root.querySelectorAll('[data-dl]').forEach(obj => {
      const tag = obj.dataset.dl.split(' ')[0];  // tag() beberapa object memuat id: "x-dl::grid id=employee"
      const spec = TITLES[tag];
      if (!spec || obj.dataset.dlGuide) return;
      const host = obj.querySelector(spec[0]);
      if (!host || host.closest('[data-dl]') !== obj) return;
      obj.dataset.dlGuide = '1';
      const a = document.createElement('a');
      a.className = 'dl-guide-link' + (spec[1] === 'icon' ? ' dl-guide-link--icon' : '');
      a.href = base + '#' + anchor(tag);
      a.target = '_blank'; a.rel = 'noopener';
      a.title = 'Technical Guide: ' + tag;
      if (spec[1] === 'icon') { a.innerHTML = '<i class="fa-solid fa-circle-info"></i>'; host.prepend(a); }
      else { while (host.firstChild) a.appendChild(host.firstChild); host.appendChild(a); }
    });
  }

  // Di dalam tab Shell (iframe): minta Shell membuka tab Help (Technical Guide) pada anchor itu, bukan jendela baru.
  document.addEventListener('click', e => {
    const a = e.target.closest && e.target.closest('a.dl-guide-link');
    if (!a || window.self === window.top) return;
    e.preventDefault();
    try { window.parent.postMessage({dl: 'help', hash: a.hash.slice(1)}, location.origin); } catch (err) { window.open(a.href, '_blank', 'noopener'); }
  });

  Devlib.guideLinks = guideLinks;
})(window.Devlib);
