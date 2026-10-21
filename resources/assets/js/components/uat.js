/* DevLib · Uat */
(function (Devlib) {
  'use strict';
  const {$} = Devlib.h;

  class Uat extends Devlib.Component {
    static selector = "[data-dl-uat]";
    init() {
      $(this.el, '[data-dl-uat-toggle]').addEventListener('click', e => { e.stopPropagation(); this.el.classList.toggle('is-open'); });
      this.el.addEventListener('click', e => {
        e.stopPropagation();
        const row = e.target.closest('[data-dl-uat-fill]'); if (!row) return;
        const box = this.el.closest('[data-dl-loginbox]') || document;
        [['login', row.dataset.login], ['password', row.dataset.password]].forEach(([n, v]) => { const i = box.querySelector('input[name="' + n + '"]'); if (i) { i.value = v; i.dispatchEvent(new Event('input', {bubbles: true})); } });
        this.el.classList.remove('is-open');
      });
      document.addEventListener('click', () => this.el.classList.remove('is-open'));
    }
  }
  Devlib.define("Uat", Uat);
})(window.Devlib);
