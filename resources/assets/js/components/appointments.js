/* DevLib · Appointments
 * Janji pribadi (x-dl::appointments): membungkus x-dl::calendar. Mendengarkan event dl:calendar (create | update | move | delete), menjaga daftar [{id, title, start, end, allDay, tone, desc}]
 * dan mengirim seluruh daftar ke data-save (POST JSON {items}) 300 ms setelah perubahan terakhir. */
(function (Devlib) {
  'use strict';
  const {toast} = Devlib.h;

  class Appointments extends Devlib.Component {
    static selector = "[data-dl-appt]";
    init() {
      this.L = this.el.dataset; this.items = JSON.parse(this.L.items || '[]');
      this.el.addEventListener('dl:calendar', e => {
        const {type, event} = e.detail, at = this.items.findIndex(x => String(x.id) === String(event.id));
        if (type === 'delete') { if (at >= 0) this.items.splice(at, 1); } else if (at >= 0) { this.items[at] = event; } else { this.items.push(event); }
        this.save();
      });
    }

    save() {
      clearTimeout(this.t);
      this.t = setTimeout(() => {
        const url = this.L.save; if (!url) return;
        fetch(url, {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.L.csrf}, body: JSON.stringify({items: this.items})})
          .then(r => { if (!r.ok) throw new Error(r.status); }).catch(() => toast(this.L.lFailed, 'danger'));
      }, 300);
    }
  }
  Devlib.define("Appointments", Appointments);
})(window.Devlib);
