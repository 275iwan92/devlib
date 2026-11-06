/* DevLib · Tabs */
(function (Devlib) {
  'use strict';
  const {$$} = Devlib.h;

  class Tabs extends Devlib.Component {
    static selector = "[data-dl-tabs]";
    init() {
      const tabs = () => $$(this.el, ':scope > .dl-tabs__bar .dl-tabs__tab');
      // Varian switch: pil aksen digeser ke tab aktif lewat --dl-thumb-x / --dl-thumb-w (CSS yang menganimasikan).
      const thumb = this.el.querySelector(':scope > .dl-tabs__bar > .dl-tabs__thumb');
      const place = () => {
        const a = [...tabs()].find(x => x.classList.contains('is-active'));
        if (!thumb || !a) return;
        thumb.style.setProperty('--dl-thumb-x', a.offsetLeft + 'px');
        thumb.style.setProperty('--dl-thumb-w', a.offsetWidth + 'px');
      };
      tabs().forEach(t => t.addEventListener('click', () => {
        tabs().forEach(x => x.classList.toggle('is-active', x === t));
        $$(this.el, ':scope > .dl-tabs__panel').forEach(p => p.classList.toggle('is-active', p.dataset.panel === t.dataset.tab));
        place();
      }));
      if (thumb) {
        place();
        // Ukuran tab berubah (huruf, bahasa, lebar layar, tab disembunyikan lalu tampil): pil ikut; animasi baru aktif setelah penempatan awal.
        if (window.ResizeObserver) new ResizeObserver(place).observe(this.el);
        requestAnimationFrame(() => thumb.classList.add('is-ready'));
      }
      // ?tab=<key> membuka tab itu (mis. kembali dari dialog simpan di tab yang sama); kunci yang tidak ada di tab ini diabaikan.
      const want = new URLSearchParams(location.search).get('tab');
      if (want) [...tabs()].find(t => t.dataset.tab === want)?.click();
    }
  }
  Devlib.define("Tabs", Tabs);
})(window.Devlib);
