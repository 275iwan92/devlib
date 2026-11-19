/* DevLib · PrintDoc — cetakan dokumen transaksi (x-dl::print-doc). Teks yang terlalu panjang dikecilkan bertahap
   sampai muat (batas 6,5 pt), bukan dipotong: nama barang, baris kedua / ketiga, sel tabel, nilai field, nama penanda tangan. */
(function (Devlib) {
  'use strict';
  const {$$} = Devlib.h;
  const MIN_PT = 6.5;

  class PrintDoc extends Devlib.Component {
    static selector = '[data-dl-pdoc]';

    init() {
      this.zoomBar();
      this.paginate();
      this.fit();
      if (document.fonts && document.fonts.ready) document.fonts.ready.then(() => { this.paginate(); this.fit(); });
    }

    // Zoom lembar x-dl::print-sheet (.dl-psheet): batang - / persen / + / Fit di atas lembar, Ctrl+roda mouse; awal = Fit lebar (tidak lebih dari 100%).
    // CSS zoom dipakai pada .dl-psheet__page; saat cetak CSS memaksa 100%. Lembar x-dl::print-doc (.dl-pdoc) tidak disentuh.
    zoomBar() {
      const root = this.el, page = root.querySelector(':scope > .dl-psheet__page');
      if (!root.classList.contains('dl-psheet') || !page) return;
      const bar = document.createElement('div');
      bar.className = 'dl-psheet__zoom';
      bar.innerHTML = '<button type="button" data-z="-" title="Zoom out" aria-label="Zoom out">&minus;</button><output>100%</output>'
        + '<button type="button" data-z="+" title="Zoom in" aria-label="Zoom in">+</button><button type="button" data-z="fit" title="Fit width">Fit</button>';
      root.insertBefore(bar, root.firstChild);
      const out = bar.querySelector('output');
      let z = 1;
      const set = v => { z = Math.min(2, Math.max(0.3, Math.round(v * 100) / 100)); page.style.zoom = z; out.textContent = Math.round(z * 100) + '%'; };
      const fit = () => {
        page.style.zoom = 1;
        const cs = getComputedStyle(root), room = root.clientWidth - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight);
        set(Math.min(1, room / page.offsetWidth));
      };
      bar.addEventListener('click', e => {
        const b = e.target.closest('button[data-z]');
        if (!b) return;
        b.dataset.z === 'fit' ? fit() : set(z + (b.dataset.z === '+' ? 0.1 : -0.1));
      });
      root.addEventListener('wheel', e => { if (e.ctrlKey) { e.preventDefault(); set(z + (e.deltaY < 0 ? 0.1 : -0.1)); } }, {passive: false});
      fit();
    }

    // Gaya Color / B&W: pecah ulang baris ke lembar memakai tinggi yang TERUKUR (bukan perkiraan server), jadi item tidak pernah menimpa
    // total / tanda tangan. Item mengalir sampai ujung bawah lembar; blok total + tanda tangan hanya di lembar terakhir, SESUDAH item di
    // aliran dokumen, dan lembar terakhir membawa minimal 2 item (tidak pernah hanya total). Lembar lanjutan = salinan <template data-pd-cont>.
    paginate() {
      const root = this.el, tpl = root.querySelector('template[data-pd-cont]');
      const sheets = $$(root, ':scope > .dl-pdoc__sheet[data-pd-sheet]');
      if (!tpl || !sheets.length || !sheets[0].className.includes('is-sty-')) return;
      const body = sh => sh.querySelector('.dl-pdoc__items tbody');
      const rows = sheets.flatMap(sh => [...body(sh).rows]);
      const lastSheet = sheets[sheets.length - 1];
      const tail = lastSheet.querySelector('.dl-pdoc__tail'), sign = lastSheet.querySelector('.dl-pdoc__sign');
      if (!tail || !sign) return;
      // Muat = kaki lembar masih di dalam kotak lembar (scrollHeight tidak dipakai: tanda air absolut menggelembungkannya).
      const fits = sh => sh.querySelector('.dl-pdoc__foot').getBoundingClientRect().bottom <= sh.getBoundingClientRect().bottom - parseFloat(getComputedStyle(sh).paddingBottom) + 1;
      const cont = () => { const sh = tpl.content.firstElementChild.cloneNode(true); root.insertBefore(sh, root.querySelector('.dl-pdoc__appx') || null); return sh; };
      const place = sh => { const foot = sh.querySelector('.dl-pdoc__foot'); sh.insertBefore(tail, foot); sh.insertBefore(sign, foot); };
      sheets.slice(1).forEach(sh => sh.remove());
      const pages = [sheets[0]];
      tail.remove(); sign.remove();
      body(pages[0]).replaceChildren();
      rows.forEach(tr => {
        const b = body(pages[pages.length - 1]);
        b.append(tr);
        if (!fits(pages[pages.length - 1]) && b.rows.length > 1) { tr.remove(); const sh = cont(); pages.push(sh); body(sh).append(tr); }
      });
      let last = pages[pages.length - 1];
      place(last);
      if (!fits(last) && body(last).rows.length > 1) {
        // total + tanda tangan tidak muat di bawah item terakhir: lembar baru untuk keduanya, ditemani item-item terakhir (minimal 2).
        const sh = cont(); pages.push(sh); place(sh);
        const prev = () => body(pages[pages.length - 2]);
        while ((body(sh).rows.length < 2 || !fits(sh)) && prev().rows.length > 1) body(sh).prepend(prev().lastElementChild);
        last = sh;
      } else if (pages.length > 1 && body(last).rows.length < 2) {
        const pb = body(pages[pages.length - 2]), r = pb.lastElementChild;
        if (pb.rows.length > 1) { body(last).prepend(r); if (!fits(last)) pb.append(r); }
      }
      pages.forEach((sh, i) => {
        const f = sh.querySelector('[data-pd-foot]');
        if (f) f.textContent = `${f.dataset.pdFoot} ${i + 1} / ${pages.length}`;
      });
    }

    fit() {
      const shrink = (el, over) => { let s = parseFloat(getComputedStyle(el).fontSize) * .75; while (over() && s > MIN_PT) el.style.fontSize = (s -= .25) + 'pt'; };
      const wide = el => () => el.scrollWidth > el.clientWidth + 1;
      $$(this.el, '.dl-pdoc__n1, .dl-pdoc__n2, .dl-pdoc__n3, .dl-pdoc__w, .dl-pdoc__notes').forEach(el => shrink(el, () => el.scrollHeight > el.clientHeight + 1 || el.scrollWidth > el.clientWidth + 1));
      $$(this.el, '.dl-pdoc__items td:not(.is-name):not(.is-no):not(.is-wrap), .dl-pdoc__items th, .dl-pdoc__f > b, .dl-pdoc__nb, .dl-pdoc__sign > div').forEach(el => shrink(el, wide(el)));
    }
  }
  Devlib.define('PrintDoc', PrintDoc);
})(window.Devlib);
