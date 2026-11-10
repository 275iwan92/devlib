/* DevLib · Chapters (x-dl::chapters): papan kartu + bab bergambar; cari, saring kelompok, buka bab (#ch-<id> bisa dibagikan), kembali, loncat ke bagian. */
(function (Devlib) {
  'use strict';
  const {$, $$} = Devlib.h;

  class Chapters extends Devlib.Component {
    static selector = '[data-dl-chapters]';
    init() {
      this.home = $(this.el, '[data-ch-home]');
      this.group = '';
      this.on('input', '[data-ch-search]', () => this.filter());
      this.on('click', '[data-ch-group]', (e, b) => { this.group = b.dataset.chGroup; $$(this.el, '[data-ch-group]').forEach(c => c.classList.toggle('is-on', c === b)); this.filter(); });
      this.on('click', '[data-ch-open]', (e, b) => this.open(b.dataset.chOpen));
      this.on('click', '[data-ch-back]', () => this.close());
      this.on('click', '[data-ch-jump]', (e, b) => document.getElementById(b.dataset.chJump)?.scrollIntoView({behavior: 'smooth', block: 'start'}));
      const m = /^#ch-(.+)$/.exec(location.hash);
      if (m && $(this.el, `[data-ch-art="${m[1]}"]`)) this.open(m[1], true);
    }

    // Kartu tampil bila cocok dengan kelompok terpilih DAN semua kata pencarian ada di teksnya.
    filter() {
      const words = ($(this.el, '[data-ch-search]').value || '').toLowerCase().split(/\s+/).filter(Boolean);
      let shown = 0;
      $$(this.el, '[data-ch-card]').forEach(c => {
        const ok = (!this.group || c.dataset.g === this.group) && words.every(w => c.dataset.t.includes(w));
        c.hidden = !ok; if (ok) shown++;
      });
      $(this.el, '[data-ch-empty]').hidden = shown > 0;
    }

    open(id, quiet) {
      this.home.hidden = true;
      $$(this.el, '[data-ch-art]').forEach(a => { a.hidden = a.dataset.chArt !== id; });
      if (!quiet) { try { history.replaceState(null, '', '#ch-' + id); } catch (e) {} this.el.scrollIntoView?.({block: 'start'}); }
    }

    close() {
      this.home.hidden = false;
      $$(this.el, '[data-ch-art]').forEach(a => { a.hidden = true; });
      try { history.replaceState(null, '', location.pathname + location.search); } catch (e) {}
      this.el.scrollIntoView?.({block: 'start'});
    }
  }
  Devlib.define('Chapters', Chapters);
})(window.Devlib);
