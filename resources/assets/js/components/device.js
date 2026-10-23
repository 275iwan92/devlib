/* DevLib · Device */
(function (Devlib) {
  'use strict';
  const {$, $$} = Devlib.h;

  class Device extends Devlib.Component {
    static selector = "[data-dl-device]";
    init() {
      const dev = $(this.el, '[data-dl-device-dev]');
      if (dev) return this.initSrc(dev);
      this.view = $(this.el, '[data-dl-device-view]');
      $$(this.el, '.dl-device__btn').forEach(b => b.addEventListener('click', () => {
        $$(this.el, '.dl-device__btn').forEach(x => x.classList.toggle('is-active', x === b));
        this.view.className = 'dl-device__view is-' + b.dataset.mode;
      }));
    }
    // Mode `src`: iframe berukuran perangkat, diperkecil (transform) agar muat di panggung; ukuran CSS di dalam iframe tetap yang sebenarnya.
    initSrc(dev) {
      const stage = $(this.el, '[data-dl-device-stage]'), frame = $(dev, 'iframe');
      this.fit = () => {
        const w = dev.offsetWidth, h = dev.offsetHeight;
        const k = Math.min(1, (stage.clientWidth - 24) / w, (stage.clientHeight - 24) / h);
        dev.style.transform = 'scale(' + k + ')';
        dev.style.margin = (-(h * (1 - k)) / 2) + 'px ' + (-(w * (1 - k)) / 2) + 'px';
      };
      $$(this.el, '.dl-device__btn').forEach(b => b.addEventListener('click', () => {
        $$(this.el, '.dl-device__btn').forEach(x => x.classList.toggle('is-active', x === b));
        dev.style.width = b.dataset.w + 'px'; dev.style.height = b.dataset.h + 'px';
        frame.src = b.dataset.src;
        this.fit();
      }));
      window.addEventListener('resize', this.fit);
      this.fit();
    }
  }
  Devlib.define("Device", Device);
})(window.Devlib);
