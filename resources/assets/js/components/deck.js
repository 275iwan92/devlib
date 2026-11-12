/* DevLib · Deck (x-dl::deck): presentasi slide; Play = maju sendiri tiap data-interval detik (slide walk: data-ms), batang kemajuan, panah/spasi/F, layar penuh; slide walk = kamera menyusuri langkah di tangkapan layar.
   Narasi suara (2026-11-12): slide yang membawa data-say dibacakan oleh suara peramban (Web Speech, id-ID, suara perempuan diutamakan); saat Play, slide maju bila bacaan selesai (bukan timer). Tanpa suara id-ID di peramban: teks tetap, timer lama. */
(function (Devlib) {
  'use strict';
  const {$, $$} = Devlib.h;
  // Nama suara perempuan Indonesia yang dikenal (Edge: Gadis; Google/Android: Google Bahasa Indonesia; Apple: Damayanti) dan nama laki-laki yang dihindari.
  const FEMALE = /gadis|damayanti|google bahasa indonesia|female|wanita|perempuan|indah|sari|siti/i, MALE = /ardi|andika|\bmale\b|pria|laki|\bdavid\b|\bmark\b/i;

  class Deck extends Devlib.Component {
    static selector = '[data-dl-deck]';
    init() {
      this.slides = $$(this.el, '.dl-deck__slide');
      this.ms = (parseFloat(this.el.dataset.interval) || 7) * 1000;
      this.i = 0; this.playing = false; this.timer = null; this.walkTimer = null; this.tok = 0; this.touched = false;
      this.prog = $(this.el, '[data-dk-prog]'); this.num = $(this.el, '[data-dk-n]'); this.playBtn = $(this.el, '[data-dk-play]');
      this.prev = $(this.el, '[data-dk-prev]'); this.next = $(this.el, '[data-dk-next]');
      this.voiceBtn = $(this.el, '[data-dk-voice]'); this.vstat = $(this.el, '[data-dk-vs]');
      this.voiceOn = !!this.voiceBtn && 'speechSynthesis' in window;
      ['click', 'keydown'].forEach(ev => this.el.addEventListener(ev, () => { this.touched = true; }, true));  // suara hanya setelah sentuhan pengguna (aturan peramban)
      if (this.voiceBtn) this.voiceBtn.addEventListener('click', () => this.toggleVoice());
      if (this.voiceOn) { speechSynthesis.addEventListener('voiceschanged', () => this.status()); setTimeout(() => { this.late = true; this.status(); }, 2500); }  // daftar suara dimuat belakangan: pesan "tidak ada suara" baru tampil setelah menunggu
      this.prev.addEventListener('click', () => this.go(this.i - 1));
      this.next.addEventListener('click', () => this.go(this.i + 1));
      this.playBtn.addEventListener('click', () => (this.playing ? this.stop() : this.play()));
      $(this.el, '[data-dk-full]').addEventListener('click', () => (document.fullscreenElement ? document.exitFullscreen() : this.el.requestFullscreen?.()));
      this.el.addEventListener('keydown', e => {
        if (e.key === 'ArrowLeft') this.go(this.i - 1);
        else if (e.key === 'ArrowRight') this.go(this.i + 1);
        else if (e.key === ' ') { e.preventDefault(); this.playBtn.click(); }
      });
      if (window.speechSynthesis) window.addEventListener('beforeunload', () => speechSynthesis.cancel());
      this.go(0);
      // Peramban menolak suara tanpa sentuhan pengguna: deck bernarasi menunggu tombol Play.
      if (this.el.dataset.autoplay === '1' && this.slides.length && !this.voiceBtn) this.play();
    }

    // Lama slide ini saat Play: slide walk membawa data-ms sendiri (3,4 detik per langkah + 1,5 detik pembuka), lainnya interval deck.
    dur() { return parseFloat(this.slides[this.i].dataset.ms) || this.ms; }

    // Narasi slide ini: string (slide biasa) atau list per langkah (slide walk); [] bila tidak ada.
    lines() {
      let v = null; try { v = JSON.parse(this.slides[this.i].dataset.say || 'null'); } catch (e) {}
      return v == null ? [] : [].concat(v).filter(Boolean);
    }

    // Suara perempuan id-ID: nama perempuan yang dikenal, lalu suara id bukan laki-laki, lalu suara id apa saja; null bila peramban tidak punya suara id.
    pick() {
      const vs = speechSynthesis.getVoices().filter(v => /^id[-_]/i.test(v.lang));
      return vs.find(v => FEMALE.test(v.name) && !MALE.test(v.name)) || vs.find(v => !MALE.test(v.name)) || vs[0] || null;
    }

    // Narasi aktif = tombol suara menyala, ada teks di slide, dan peramban punya suara id-ID.
    speaks() { return this.voiceOn && this.touched && this.lines().length > 0 && !!this.pick(); }

    // Bacakan satu kalimat; done() dipanggil saat selesai (atau bila gagal / macet, supaya presentasi tidak berhenti).
    say(text, done) {
      speechSynthesis.cancel();
      const u = new SpeechSynthesisUtterance(text), v = this.pick(), tok = this.tok;
      u.lang = 'id-ID'; if (v) u.voice = v; u.rate = 0.95; u.pitch = 1.05;
      let fin = false; const end = () => { if (fin) return; fin = true; clearTimeout(guard); if (tok === this.tok) done(); };
      const guard = setTimeout(end, 3000 + text.length * 170);  // Chrome kadang tidak mengirim onend
      u.onend = end; u.onerror = end;
      speechSynthesis.speak(u);
    }

    toggleVoice() {
      this.voiceOn = !this.voiceOn;
      const ic = $(this.voiceBtn, 'i'); if (ic) ic.className = 'fa-solid fa-' + (this.voiceOn ? 'volume-high' : 'volume-xmark');
      this.voiceBtn.setAttribute('aria-pressed', this.voiceOn ? 'true' : 'false');
      if (!this.voiceOn) speechSynthesis.cancel();
      this.go(this.i);
    }

    // Keterangan di bawah tombol: suara apa yang dipakai, atau bahwa peramban tidak punya suara Indonesia.
    status() {
      if (!this.vstat) return;
      if (this.voiceOn && !this.late && !speechSynthesis.getVoices().length) { this.vstat.textContent = ''; return; }
      const v = this.voiceOn && 'speechSynthesis' in window ? this.pick() : null;
      this.vstat.textContent = !this.voiceOn ? '' : (v ? v.name.replace(/^Microsoft\s+/, '').replace(/\s*Online.*$/, '') : this.vstat.dataset.none);
    }

    go(k) {
      const n = this.slides.length; if (!n) return;
      this.tok++; if (window.speechSynthesis) speechSynthesis.cancel();
      this.i = Math.max(0, Math.min(n - 1, k));
      this.slides.forEach((s, j) => { s.classList.toggle('is-on', j === this.i); s.classList.toggle('is-past', j < this.i); });
      this.num.textContent = (this.i + 1) + ' / ' + n;
      this.prev.disabled = this.i === 0; this.next.disabled = this.i === n - 1;
      this.status();
      this.walk();
      const sp = this.speaks();
      if (sp && !this.slides[this.i].hasAttribute('data-dk-walk')) this.say(this.lines().join(' '), () => this.after());
      if (this.playing) this.schedule(sp); else this.bar(0, false);
    }

    // Slide selesai dibacakan: jeda singkat, lalu slide berikut (atau berhenti di akhir).
    after() {
      if (!this.playing) return;
      const tok = this.tok;
      this.timer = setTimeout(() => { if (tok !== this.tok) return; if (this.i < this.slides.length - 1) this.go(this.i + 1); else this.stop(true); }, 900);
    }

    // Slide walk: mulai dari gambar utuh, lalu kamera zoom ke titik langkah berikutnya, cincin pindah, keterangan berganti. Tanpa narasi: tiap 3,4 detik, berulang selama slide terlihat.
    // Dengan narasi: tiap langkah dibacakan, langkah berikut mulai saat bacaan selesai, lalu slide berakhir (tidak berulang).
    walk() {
      clearTimeout(this.walkTimer);
      const s = this.slides[this.i];
      $$(this.el, '[data-dk-walk]').forEach(w => { const c = $(w, '[data-dk-cam]'); c.classList.remove('is-zoom'); $(w, '[data-dk-ct]').textContent = ''; $(w, '[data-dk-cd]').textContent = ''; });
      if (!s.hasAttribute('data-dk-walk')) return;
      let steps = []; try { steps = JSON.parse(s.dataset.steps); } catch (e) {}
      if (!steps.length) return;
      const cam = $(s, '[data-dk-cam]'), ring = $(s, '[data-dk-ring]'), t = $(s, '[data-dk-ct]'), d = $(s, '[data-dk-cd]');
      const say = this.lines(), voiced = this.speaks(), tok = this.tok;
      let k = -1;
      const tick = () => {
        if (tok !== this.tok) return;
        if (k >= steps.length) { if (voiced) { this.after(); return; } k = -1; }
        if (k < 0) { cam.classList.remove('is-zoom'); t.textContent = ''; d.textContent = ''; }
        else {
          const p = steps[k];
          cam.style.transformOrigin = p.x + '% ' + p.y + '%'; ring.style.left = p.x + '%'; ring.style.top = p.y + '%';
          cam.classList.add('is-zoom'); t.textContent = (k + 1) + '. ' + p.t; d.textContent = p.d;
        }
        k++;
        if (voiced && k > 0) { const line = say[k - 1] || steps[k - 1].d; this.walkTimer = setTimeout(() => this.say(line, () => { this.walkTimer = setTimeout(tick, 500); }), 700); }
        else this.walkTimer = setTimeout(tick, k === 0 ? 1500 : 3400);
      };
      tick();
    }

    // Batang kemajuan: 0 -> 100% selama satu slide (transisi linear), diulang tiap slide; bacaan: perkiraan 75 ms per huruf.
    bar(pct, animate, ms) {
      this.prog.style.transition = 'none'; this.prog.style.width = pct + '%';
      if (animate) { void this.prog.offsetWidth; this.prog.style.transition = 'width ' + (ms || this.dur()) + 'ms linear'; this.prog.style.width = '100%'; }
    }

    schedule(voiced) {
      clearTimeout(this.timer);
      if (voiced) { this.bar(0, true, Math.max(3000, this.lines().join(' ').length * 75)); return; }  // maju ditentukan akhir bacaan (after / walk)
      this.bar(0, true);
      this.timer = setTimeout(() => { if (this.i < this.slides.length - 1) this.go(this.i + 1); else this.stop(true); }, this.dur());
    }

    play() {
      if (this.i === this.slides.length - 1) this.i = 0;  // sampai akhir lalu Play lagi = ulang dari awal
      this.playing = true; this.icon('pause'); this.go(this.i);
    }

    stop(done) {
      this.playing = false; clearTimeout(this.timer); this.icon('play');
      if (window.speechSynthesis) { this.tok++; speechSynthesis.cancel(); }
      if (done) this.bar(100, false); else this.bar(0, false);
    }

    icon(name) {
      $(this.playBtn, 'i').className = 'fa-solid fa-' + name;
      this.playBtn.setAttribute('aria-label', this.playBtn.dataset[name === 'play' ? 'lPlay' : 'lPause']);
    }
  }
  Devlib.define('Deck', Deck);
})(window.Devlib);
