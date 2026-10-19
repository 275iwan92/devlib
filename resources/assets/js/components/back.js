/* DevLib · Back */
(function (Devlib) {
  'use strict';
  // Back: ke layar sebelumnya bila datang dari situs ini, selain itu ke alamat fallback. config devlib.back_history = false: SELALU ke alamat tombol (mis. grid),
  // supaya Back setelah Save tidak membawa user kembali ke form isian sebelumnya.
  class Back extends Devlib.Component {
    static selector = "[data-dl-back]";
    init() {
      this.el.addEventListener('click', e => {
        if (window.DL_BACK_HISTORY !== false && document.referrer && new URL(document.referrer).origin === location.origin && history.length > 1) { e.preventDefault(); history.back(); }
      });
    }
  }
  Devlib.define("Back", Back);
})(window.Devlib);
