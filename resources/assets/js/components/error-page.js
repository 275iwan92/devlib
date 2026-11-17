/* DevLib · ErrorPage */
(function (Devlib) {
  'use strict';
  // Hitung mundur perkiraan selesai (x-dl::error-page prop eta, mis. kode construction): sisa waktu tiap detik dari jam browser terhadap data-dl-eta (ISO 8601).
  // Format: [N hari ]HH:MM:SS. Habis = tampil teks data-dl-eta-done dan halaman dimuat ulang tiap 60 detik (layar yang sudah dibuka kembali langsung tampil).
  class ErrorPageEta extends Devlib.Component {
    static selector = "[data-dl-eta]";
    init() {
      const end = Date.parse(this.el.dataset.dlEta);
      if (isNaN(end)) { this.el.textContent = ''; return; }
      const pad = n => String(n).padStart(2, '0');
      const tick = () => {
        const left = Math.floor((end - Date.now()) / 1000);
        if (left <= 0) { this.el.textContent = this.el.dataset.dlEtaDone || ''; clearInterval(this.timer); setTimeout(() => location.reload(), 60000); return; }
        const d = Math.floor(left / 86400), h = Math.floor(left % 86400 / 3600), m = Math.floor(left % 3600 / 60), s = left % 60;
        this.el.textContent = (d ? d + 'd ' : '') + pad(h) + ':' + pad(m) + ':' + pad(s);
      };
      tick();
      this.timer = setInterval(tick, 1000);
    }
  }
  Devlib.define("ErrorPageEta", ErrorPageEta);
})(window.Devlib);
