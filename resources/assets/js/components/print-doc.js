/* DevLib · PrintDoc — cetakan dokumen transaksi (x-dl::print-doc). Teks yang terlalu panjang dikecilkan bertahap
   sampai muat (batas 6,5 pt), bukan dipotong: nama barang, baris kedua / ketiga, sel tabel, nilai field, nama penanda tangan. */
(function (Devlib) {
  'use strict';
  const {$$} = Devlib.h;
  const MIN_PT = 6.5;

  class PrintDoc extends Devlib.Component {
    static selector = '[data-dl-pdoc]';

    init() {
      this.paginate();
      this.fit();
      if (document.fonts && document.fonts.ready) document.fonts.ready.then(() => { this.paginate(); this.fit(); });
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
