/* DevLib · ScreenInfo (diagnostik layar, murni sisi klien: tidak mengirim atau menyimpan apa pun) */
(function (Devlib) {
  'use strict';
  const {$, $$} = Devlib.h;

  class ScreenInfo extends Devlib.Component {
    static selector = "[data-dl-screen-info]";
    init() {
      this.T = JSON.parse(this.el.dataset.t || '{}');
      this.out = {};
      $$(this.el, '[data-sinfo]').forEach(d => { this.out[d.dataset.sinfo] = d; });
      this.sum = $(this.el, '[data-sinfo-sum]');
      this.status = $(this.el, '[data-sinfo-status]');
      this.text = $(this.el, '[data-sinfo-text]');
      this.details = $(this.el, '[data-sinfo-details]');
      // Elemen ukur tak terlihat: satuan tinggi (svh / lvh / dvh) dan area aman (env(safe-area-inset-*)).
      this.probe = document.createElement('div');
      this.probe.setAttribute('aria-hidden', 'true');
      this.probe.style.cssText = 'position:fixed;left:0;top:0;width:1px;visibility:hidden;pointer-events:none;padding:env(safe-area-inset-top) env(safe-area-inset-right) env(safe-area-inset-bottom) env(safe-area-inset-left)';
      this.el.appendChild(this.probe);
      this.queued = false;
      this.model = '';
      const again = () => this.queue();
      window.addEventListener('resize', again);
      window.addEventListener('orientationchange', again);
      window.addEventListener('pageshow', again);
      document.addEventListener('visibilitychange', again);
      if (window.visualViewport) { window.visualViewport.addEventListener('resize', again); window.visualViewport.addEventListener('scroll', again); }
      [Devlib.PHONE_MQ, '(pointer: coarse)', '(hover: hover)', '(orientation: portrait)'].forEach(q => { try { matchMedia(q).addEventListener('change', again); } catch (e) { /* browser lama */ } });
      $(this.el, '[data-sinfo-copy]').addEventListener('click', () => this.copy());
      this.measure();
      this.highEntropy();
    }
    t(k) { return this.T[k] || k; }
    queue() { if (this.queued) return; this.queued = true; requestAnimationFrame(() => { this.queued = false; this.measure(); }); }
    num(x) { return String(Math.round(x * 100) / 100); }
    // Tinggi satu satuan CSS lewat elemen ukur; 0 / tak didukung = null.
    unit(u) {
      if (!window.CSS || !CSS.supports('height', '100' + u)) return null;
      const p = this.probe.style.cssText;
      this.probe.style.height = '100' + u;
      const h = this.probe.getBoundingClientRect().height;
      this.probe.style.cssText = p;
      return h;
    }
    mq(q) { try { return matchMedia(q).matches; } catch (e) { return false; } }
    // Nilai satu fitur media (mis. pointer: none | fine | coarse).
    feature(name, values) { return values.find(v => this.mq('(' + name + ': ' + v + ')')) || this.t('none'); }
    measure() {
      const w = window.innerWidth, h = window.innerHeight, dpr = window.devicePixelRatio || 1, de = document.documentElement;
      const orient = this.mq('(orientation: portrait)') ? this.t('portrait') : this.t('landscape');
      const coarse = this.mq('(pointer: coarse)');
      const phone = this.mq(Devlib.PHONE_MQ);
      const vv = window.visualViewport;
      const cs = getComputedStyle(this.probe);
      const px = v => Math.round(parseFloat(v) || 0);
      const u = x => { const v = this.unit(x); return v === null ? this.t('not supported') : this.num(v) + ' px'; };
      const bps = [320, 360, 400, 440, 480, 600, 640, 700, 760, 768, 900, 1000, 1024, 1440].filter(b => this.mq('(max-width: ' + b + 'px)'));
      const sw = this.el.dataset;
      const rule = phone
        ? 'phone: ' + (w < 600 ? this.t('width under 600 px') : this.t('phone sideways: height under 480 px, touch, width under 1000 px'))
        : 'desktop: ' + (h < 480 && w < 1000 && !coarse ? this.t('height under 480 px but the main pointer is not touch') : this.t('width 600 px or more, not a phone sideways'));
      const v = {
        css: w + ' x ' + h,
        dpr: this.num(dpr),
        orient,
        phys: Math.round(w * dpr) + ' x ' + Math.round(h * dpr),
        inner: w + ' / ' + de.clientWidth + ' (' + de.clientWidth + ' x ' + de.clientHeight + ')',
        screen: screen.width + ' x ' + screen.height + ' (' + screen.availWidth + ' x ' + screen.availHeight + ')',
        svh: u('svh'), lvh: u('lvh'), dvh: u('dvh'),
        vv: vv ? this.num(vv.width) + ' x ' + this.num(vv.height) + ', ' + this.num(vv.scale) + 'x' : this.t('not supported'),
        pointer: this.feature('pointer', ['coarse', 'fine']),
        anypointer: this.feature('any-pointer', ['coarse', 'fine']),
        hover: this.feature('hover', ['hover']),
        safe: [cs.paddingTop, cs.paddingRight, cs.paddingBottom, cs.paddingLeft].map(px).join(' / ') + ' px',
        rule,
        view: sw.view + ' (' + this.t(({override: 'preview override', cookie: 'cookie ui (your choice)', 'client-hint': 'client hint (Sec-CH-UA-Mobile)'})[sw.viewSource] || 'User-Agent guess') + ')',
        os: sw.os + ' / ' + Devlib.os,
        bp: bps.length ? bps.map(b => '<=' + b).join(', ') : this.t('none'),
        ua: navigator.userAgent,
        uad: this.uaData(),
        secure: this.t(window.isSecureContext ? 'yes' : 'no') + (window.isSecureContext ? '' : ' (' + this.t('only works on https or localhost: the copy uses the fallback') + ')'),
        time: new Date().toISOString(),
      };
      this.values = v;
      Object.keys(v).forEach(k => { if (this.out[k]) this.out[k].textContent = v[k]; });
      this.summary = w + ' x ' + h + ' - DPR ' + this.num(dpr) + ' - ' + orient;
      this.sum.textContent = this.summary + ' - ' + (phone ? 'phone' : 'desktop');
      this.text.value = this.block();
    }
    uaData() {
      const d = navigator.userAgentData;
      if (!d) return this.t('not available');
      return d.brands.map(b => b.brand + ' ' + b.version).join(', ') + '; mobile ' + this.t(d.mobile ? 'yes' : 'no') + '; ' + (d.platform || '') + (this.model ? '; ' + this.model : '');
    }
    // Model dan versi platform hanya bila browser mau memberi (hanya konteks aman); tampil di baris userAgentData.
    highEntropy() {
      const d = navigator.userAgentData;
      if (!d || !d.getHighEntropyValues) return;
      d.getHighEntropyValues(['model', 'platformVersion']).then(r => {
        this.model = [r.model, r.platformVersion].filter(Boolean).join(' ');
        this.measure();
      }).catch(() => {});
    }
    // Satu blok teks polos; baris pertama = ringkasan angka kunci.
    block() {
      const lines = [this.summary + ' - ' + (this.values.rule.split(':')[0])];
      $$(this.el, '[data-sinfo-group]').forEach(g => {
        lines.push('', '[' + $(g, 'h3').textContent + ']');
        $$(g, '.dl-sinfo__r').forEach(r => lines.push($(r, 'dt').textContent + ': ' + $(r, 'dd').textContent));
      });
      return lines.join('\n');
    }
    say(msg, bad) { this.status.textContent = msg; this.status.classList.toggle('is-bad', !!bad); }
    // Salin: Clipboard API (hanya secure context), lalu textarea + execCommand('copy'), lalu teks terpilih untuk disalin manual.
    async copy() {
      this.measure();
      const text = this.text.value;
      if (window.isSecureContext && navigator.clipboard && navigator.clipboard.writeText) {
        try { await navigator.clipboard.writeText(text); return this.say(this.t('Copied. Paste it in the chat.')); } catch (e) { /* lanjut ke cadangan */ }
      }
      this.details.open = true;
      this.text.removeAttribute('readonly');
      this.text.focus();
      this.text.select();
      this.text.setSelectionRange(0, text.length);
      let ok = false;
      try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
      this.text.setAttribute('readonly', '');
      if (ok) return this.say(this.t('Copied. Paste it in the chat.'));
      this.say(this.t('Copy did not work here. The text below is selected: copy it by hand (long press, then Copy).'), true);
      this.text.select();
      this.text.scrollIntoView({block: 'center'});
    }
  }
  Devlib.define("ScreenInfo", ScreenInfo);
})(window.Devlib);
