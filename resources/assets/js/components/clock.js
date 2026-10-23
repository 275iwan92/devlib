/* DevLib · Clock */
(function (Devlib) {
  'use strict';
  const {$} = Devlib.h;

  class Clock extends Devlib.Component {
    static selector = "[data-dl-clock]";
    init() {
      this.t = $(this.el, '[data-dl-clock-time]');
      this.d = $(this.el, '[data-dl-clock-date]');
      this.timeZone = this.el.dataset.tz || undefined;
      this.tick();setInterval(this.tick, 1000);document.addEventListener('dl:lang', this.tick);
    }

    tick() {
      const now = new Date(), loc = Devlib.lang === 'id' ? 'id-ID' : 'en-US';
      if (this.t) this.t.textContent = now.toLocaleTimeString(loc, {hour12: false, timeZone: this.timeZone});
      if (this.el.classList.contains('dl-clock--line')) {   // satu baris: tanggal + jam tanpa detik; HP = tanggal singkat
        const phone = document.body.classList.contains('dl-on-phone'), z = {timeZone: this.timeZone};
        const date = now.toLocaleDateString(loc, phone ? {weekday: 'short', day: 'numeric', month: 'short', year: 'numeric', ...z} : {weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', ...z});
        const time = now.toLocaleTimeString(loc, {hour: '2-digit', minute: '2-digit', hour12: false, ...z}).replace('.', ':');
        this.d.textContent = date + ' · ' + time;
        return;
      }
      this.d.textContent = now.toLocaleDateString(loc, {weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', timeZone: this.timeZone});
    }
  }
  Devlib.define("Clock", Clock);
})(window.Devlib);
