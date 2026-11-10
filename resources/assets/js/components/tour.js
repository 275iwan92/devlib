/* DevLib · Tour (x-dl::tour): misi interaktif; klik titik di tangkapan layar = popup penjelas; kemajuan disimpan di localStorage (dl.tour.<key>). */
(function (Devlib) {
  'use strict';
  const {$, $$} = Devlib.h;

  class Tour extends Devlib.Component {
    static selector = '[data-dl-tour]';
    init() {
      this.store = 'dl.tour.' + (this.el.dataset.key || 'default');
      this.total = parseInt(this.el.dataset.total, 10) || 0;
      this.list = $(this.el, '[data-tr-list]');
      this.state = this.load();
      this.on('click', '[data-tr-open]', (e, b) => this.open(b.dataset.trOpen));
      this.on('click', '[data-tr-back]', () => this.close());
      this.on('click', '[data-tr-step]', (e, b) => this.step(b));
      this.on('click', '[data-tr-go]', (e, b) => this.go(b));
      this.on('click', '[data-tr-popx]', () => this.pop(null));
      this.on('click', '[data-tr-reset]', () => { this.state = {done: [], seen: {}}; this.save(); this.render(); this.close(); });
      document.addEventListener('keydown', e => { if (e.key === 'Escape' && this.cur) this.pop(null); });
      this.render();
    }

    load() { try { return JSON.parse(localStorage.getItem(this.store)) || {done: [], seen: {}}; } catch (e) { return {done: [], seen: {}}; } }
    save() { try { localStorage.setItem(this.store, JSON.stringify(this.state)); } catch (e) {} }

    stage(id) { return $(this.el, `[data-tr-stage="${id}"]`); }

    open(id) {
      this.cur = id; this.list.hidden = true;
      $$(this.el, '[data-tr-stage]').forEach(s => { s.hidden = s.dataset.trStage !== id; });
      this.render(); this.el.scrollIntoView?.({block: 'start'});
    }

    close() {
      this.cur = null; this.list.hidden = false;
      $$(this.el, '[data-tr-stage]').forEach(s => { s.hidden = true; });
      this.render();
    }

    // Titik diklik: tandai terlihat, tampilkan popup di dekat titik; semua terlihat = misi selesai.
    step(btn) {
      const id = this.cur, i = parseInt(btn.dataset.trStep, 10);
      const seen = this.state.seen[id] = this.state.seen[id] || [];
      if (!seen.includes(i)) seen.push(i);
      const n = $$(this.stage(id), '[data-tr-step]').length;
      if (seen.length >= n && !this.state.done.includes(id)) this.state.done.push(id);
      this.save(); this.pop(btn); this.render();
    }

    go(b) { const h = $(this.stage(this.cur), `[data-tr-step="${b.dataset.trGo}"]`); h?.scrollIntoView?.({block: 'center'}); h?.click(); }

    // Popup: di sisi kanan titik; bila titik di kanan atau bawah gambar, ke kiri atau atas supaya tetap di dalam gambar.
    pop(btn) {
      const st = this.cur && this.stage(this.cur); if (!st) return;
      const p = $(st, '[data-tr-pop]');
      $$(st, '.dl-tour__hot').forEach(h => h.classList.toggle('is-open', h === btn));
      if (!btn) { p.hidden = true; return; }
      $(p, '[data-tr-pt]').textContent = btn.dataset.t; $(p, '[data-tr-pd]').textContent = btn.dataset.d;
      const x = parseFloat(btn.style.left), y = parseFloat(btn.style.top);
      p.style.left = x > 55 ? 'auto' : (x + 2) + '%'; p.style.right = x > 55 ? (100 - x + 2) + '%' : 'auto';
      p.style.top = y > 60 ? 'auto' : (y + 2) + '%'; p.style.bottom = y > 60 ? (100 - y + 2) + '%' : 'auto';
      p.hidden = false;
    }

    render() {
      const done = this.state.done;
      $$(this.el, '[data-tr-open]').forEach(c => {
        const ok = done.includes(c.dataset.trOpen);
        c.classList.toggle('is-done', ok);
        $(c, '[data-tr-state] i').className = 'fa-solid ' + (ok ? 'fa-check' : 'fa-play');
      });
      $(this.el, '[data-tr-score]').textContent = done.length;
      $(this.el, '[data-tr-scorebar]').style.width = (this.total ? done.length / this.total * 100 : 0) + '%';
      $(this.el, '[data-tr-reset]').hidden = !done.length && !Object.keys(this.state.seen).length;
      $$(this.el, '[data-tr-stage]').forEach(st => {
        const id = st.dataset.trStage, seen = this.state.seen[id] || [];
        $$(st, '[data-tr-step]').forEach(h => h.classList.toggle('is-done', seen.includes(parseInt(h.dataset.trStep, 10))));
        $$(st, '[data-tr-li]').forEach(li => li.classList.toggle('is-done', seen.includes(parseInt(li.dataset.trLi, 10))));
        $(st, '[data-tr-n]').textContent = seen.length;
        const win = $(st, '[data-tr-win]'), ok = done.includes(id);
        win.hidden = !ok;
        if (ok) {
          const nxt = $$(this.el, '[data-tr-open]').find(c => !done.includes(c.dataset.trOpen) && c.dataset.trOpen !== id);
          const box = $(win, '[data-tr-nextbox]'); box.textContent = '';
          if (nxt) { const b = document.createElement('button'); b.type = 'button'; b.className = 'dl-tour__next'; b.dataset.trOpen = nxt.dataset.trOpen; b.textContent = this.el.dataset.lNext + ': ' + $(nxt, 'b').textContent; box.appendChild(b); }
        }
      });
    }
  }
  Devlib.define('Tour', Tour);
})(window.Devlib);
