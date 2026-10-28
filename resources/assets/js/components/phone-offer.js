/* DevLib · PhoneOffer (opt-in `auto`: layar yang memenuhi aturan phone langsung memilih tampilan phone; tanpa `auto` popup ini murni HTML + CSS) */
(function (Devlib) {
  'use strict';
  const {$} = Devlib.h;
  const KEY = 'dl-phone-offer-auto';

  class PhoneOffer extends Devlib.Component {
    static selector = "[data-dl-phone-offer][data-auto]";
    init() {
      const form = $(this.el, 'input[name=ui][value=phone]')?.form;
      if (!form) return;
      const go = () => {
        if (!Devlib.phoneScreen()) return;
        // Pengaman putaran: cookie ditolak browser = halaman kembali dengan popup yang sama; satu kiriman per 10 detik.
        try { if (Date.now() - (+sessionStorage.getItem(KEY) || 0) < 10000) return; sessionStorage.setItem(KEY, String(Date.now())); } catch (e) { /* tanpa penyimpanan sesi: tetap kirim sekali */ }
        form.requestSubmit ? form.requestSubmit() : form.submit();
      };
      go();
      window.matchMedia(Devlib.PHONE_MQ).addEventListener('change', go);
    }
  }
  Devlib.define("PhoneOffer", PhoneOffer);
})(window.Devlib);
