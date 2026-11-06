/* DevLib · Tabs */
(function (Devlib) {
  'use strict';
  const {$$} = Devlib.h;

  class Tabs extends Devlib.Component {
    static selector = "[data-dl-tabs]";
    init() {
      const tabs = () => $$(this.el, ':scope > .dl-tabs__bar .dl-tabs__tab');
      const panels = () => $$(this.el, ':scope > .dl-tabs__panel, :scope > .dl-tabs__viewport > .dl-tabs__track > .dl-tabs__panel');
      // Varian switch: pil meluncur ke tab aktif (--dl-thumb-x / --dl-thumb-w), warna mengikuti data-tone tab, panel bergeser (--dl-tab-index), tinggi viewport mengikuti panel aktif.
      const thumb = this.el.querySelector(':scope > .dl-tabs__bar > .dl-tabs__thumb');
      const view = this.el.querySelector(':scope > .dl-tabs__viewport');
      const watch = window.ResizeObserver ? new ResizeObserver(() => fit()) : null;
      const active = () => [...tabs()].find(x => x.classList.contains('is-active'));
      const place = () => {
        const a = active();
        if (!thumb || !a) return;
        thumb.style.setProperty('--dl-thumb-x', a.offsetLeft + 'px');
        thumb.style.setProperty('--dl-thumb-w', a.offsetWidth + 'px');
      };
      const fit = () => {
        const p = panels().find(x => x.classList.contains('is-active'));
        if (view && p) view.style.height = p.offsetHeight + 'px';
      };
      const watchPanel = () => { if (!watch) return; watch.disconnect(); watch.observe(panels().find(x => x.classList.contains('is-active'))); watch.observe(this.el); };
      tabs().forEach((t, i) => t.addEventListener('click', () => {
        tabs().forEach(x => x.classList.toggle('is-active', x === t));
        panels().forEach(p => p.classList.toggle('is-active', p.dataset.panel === t.dataset.tab));
        if (thumb) {
          this.el.dataset.tone = t.dataset.tone || 'accent';
          this.el.style.setProperty('--dl-tab-index', i);
          place(); fit(); watchPanel();
        }
      }));
      if (thumb) {
        place(); fit(); watchPanel();
        // Ukuran tab berubah (huruf, bahasa, lebar layar): pil ikut; animasi baru aktif setelah penempatan awal.
        if (window.ResizeObserver) new ResizeObserver(place).observe(this.el);
        requestAnimationFrame(() => { thumb.classList.add('is-ready'); this.el.classList.add('is-ready'); });
      }
      // ?tab=<key> membuka tab itu (mis. kembali dari dialog simpan di tab yang sama); kunci yang tidak ada di tab ini diabaikan.
      const want = new URLSearchParams(location.search).get('tab');
      if (want) [...tabs()].find(t => t.dataset.tab === want)?.click();
    }
  }
  Devlib.define("Tabs", Tabs);
})(window.Devlib);
