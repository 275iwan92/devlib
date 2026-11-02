/* DevLib · FloorPlan: pilih titik pada denah (tombol), tandai is-selected, isi input tersembunyi, pancarkan dl:floorplan-select {id}. Titik berupa tautan (href) dibiarkan menavigasi. */
(function (Devlib) {
  'use strict';

  class FloorPlan extends Devlib.Component {
    static selector = "[data-dl-floorplan]";
    init() {
      this.input = this.$('[data-floorplan-input]');
      this.on('click', 'button.dl-floorplan__pin', (e, pin) => this.select(pin.dataset.pin));
      this.show(this.el.dataset.selected || null);
    }

    select(id) {
      this.show(id);
      if (this.input) this.input.value = id;
      this.el.dataset.selected = id;
      this.emit('dl:floorplan-select', {id});
    }

    // Tandai titik terpilih; kartu [data-floorplan-detail="<id>"] hanya yang cocok yang tampil.
    show(id) {
      this.$$('.dl-floorplan__pin').forEach(p => {
        const on = id !== null && p.dataset.pin === id;
        p.classList.toggle('is-selected', on);
        if (p.tagName === 'BUTTON') p.setAttribute('aria-pressed', on ? 'true' : 'false');
      });
      this.$$('[data-floorplan-detail]').forEach(d => { d.hidden = d.dataset.floorplanDetail !== id; });
    }
  }
  Devlib.define("FloorPlan", FloorPlan);
})(window.Devlib);
