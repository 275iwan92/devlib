/* DevLib · Deck (x-dl::deck): presentasi slide; Play = maju sendiri tiap data-interval detik, batang kemajuan, panah/spasi/F, layar penuh. */
(function (Devlib) {
  'use strict';
  const {$, $$} = Devlib.h;

  class Deck extends Devlib.Component {
    static selector = '[data-dl-deck]';
    init() {
      this.slides = $$(this.el, '.dl-deck__slide');
      this.ms = (parseFloat(this.el.dataset.interval) || 7) * 1000;
      this.i = 0; this.playing = false; this.timer = null;
      this.prog = $(this.el, '[data-dk-prog]'); this.num = $(this.el, '[data-dk-n]'); this.playBtn = $(this.el, '[data-dk-play]');
      this.prev = $(this.el, '[data-dk-prev]'); this.next = $(this.el, '[data-dk-next]');
      this.prev.addEventListener('click', () => this.go(this.i - 1));
      this.next.addEventListener('click', () => this.go(this.i + 1));
      this.playBtn.addEventListener('click', () => (this.playing ? this.stop() : this.play()));
      $(this.el, '[data-dk-full]').addEventListener('click', () => (document.fullscreenElement ? document.exitFullscreen() : this.el.requestFullscreen?.()));
      this.el.addEventListener('keydown', e => {
        if (e.key === 'ArrowLeft') this.go(this.i - 1);
        else if (e.key === 'ArrowRight') this.go(this.i + 1);
        else if (e.key === ' ') { e.preventDefault(); this.playBtn.click(); }
      });
      this.go(0);
      if (this.el.dataset.autoplay === '1' && this.slides.length) this.play();
    }

    go(k) {
      const n = this.slides.length; if (!n) return;
      this.i = Math.max(0, Math.min(n - 1, k));
      this.slides.forEach((s, j) => { s.classList.toggle('is-on', j === this.i); s.classList.toggle('is-past', j < this.i); });
      this.num.textContent = (this.i + 1) + ' / ' + n;
      this.prev.disabled = this.i === 0; this.next.disabled = this.i === n - 1;
      if (this.playing) this.schedule(); else this.bar(0, false);
    }

    // Batang kemajuan: 0 -> 100% selama satu interval (transisi linear), diulang tiap slide.
    bar(pct, animate) {
      this.prog.style.transition = 'none'; this.prog.style.width = pct + '%';
      if (animate) { void this.prog.offsetWidth; this.prog.style.transition = 'width ' + this.ms + 'ms linear'; this.prog.style.width = '100%'; }
    }

    schedule() {
      clearTimeout(this.timer);
      this.bar(0, true);
      this.timer = setTimeout(() => { if (this.i < this.slides.length - 1) this.go(this.i + 1); else this.stop(true); }, this.ms);
    }

    play() {
      if (this.i === this.slides.length - 1) this.i = 0;  // sampai akhir lalu Play lagi = ulang dari awal
      this.playing = true; this.icon('pause'); this.go(this.i);
    }

    stop(done) {
      this.playing = false; clearTimeout(this.timer); this.icon('play');
      if (done) this.bar(100, false); else this.bar(0, false);
    }

    icon(name) {
      $(this.playBtn, 'i').className = 'fa-solid fa-' + name;
      this.playBtn.setAttribute('aria-label', this.playBtn.dataset[name === 'play' ? 'lPlay' : 'lPause']);
    }
  }
  Devlib.define('Deck', Deck);
})(window.Devlib);
