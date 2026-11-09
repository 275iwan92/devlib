/* DevLib · SharedCalendar
 * Kalender bersama (x-dl::shared-calendar): membungkus x-dl::calendar yang diberi people / rooms. Mengikuti event dl:calendar dan mengirim PER ACARA ke server:
 * create | update | move = POST data-save {event} (jawaban {event, warnings[]} mengganti acara sementara dan menampilkan peringatan bentrok);
 * delete = DELETE data-delete; rsvp = POST data-rsvp {rsvp}; open (acara task) = pindah ke data-open.
 * Bilah "Show calendar of" (hanya bila data-team ada): ketik @nama, kalender rekan dimuat dari data-team?users=1,2 lewat dlCalendar.setTeam (baca-saja, izin diputuskan server). */
(function (Devlib) {
  'use strict';
  const {$, toast, T} = Devlib.h;

  class SharedCalendar extends Devlib.Component {
    static selector = "[data-dl-shared]";
    init() {
      this.L = this.el.dataset; this.cal = $(this.el, '[data-dl-calendar]');
      this.el.addEventListener('dl:calendar', e => this.on(e.detail));
      if (this.L.team) this.teamBar();
    }

    call(method, url, body) {
      return fetch(url, {method, credentials: 'same-origin', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.L.csrf}, body: body ? JSON.stringify(body) : undefined})
        .then(r => r.json().catch(() => ({})).then(j => { if (!r.ok) throw new Error(j.message || r.status); return j; }));
    }

    on({type, event}) {
      const api = this.cal.dlCalendar, at = tpl => tpl.replace(':id', encodeURIComponent(event.id));
      if (type === 'open') { if (event.kind === 'task' && this.L.open) location.href = this.L.open; return; }
      if (!this.L.save) return; // pratinjau tanpa server
      const req = type === 'delete' ? this.call('DELETE', at(this.L.delete)) : type === 'rsvp' ? this.call('POST', at(this.L.rsvp), {rsvp: event.rsvp}) : this.call('POST', this.L.save, {event});
      req.then(j => { if (j.event) api.replace(event.id, j.event); (j.warnings || []).forEach(w => toast(w, 'warn')); })
        .catch(err => { toast(err.message || this.L.lFailed, 'danger'); if (type === 'create') api.remove(event.id); else setTimeout(() => location.reload(), 1500); });
    }

    teamBar() {
      const people = JSON.parse(this.cal.dataset.people || '[]'), picked = [], api = this.cal.dlCalendar;
      const bar = document.createElement('div'); bar.className = 'dl-shared__bar'; bar.innerHTML = Devlib.h.mentionBox(T('c_team'), T('c_people_ph'), picked);
      this.el.prepend(bar);
      Devlib.h.mention(bar, people, picked, () => {
        if (!picked.length) return api.setTeam([]);
        this.call('GET', this.L.team + '?users=' + picked.map(p => p.id).join(',')).then(j => api.setTeam(j.events || [])).catch(() => toast(this.L.lFailed, 'danger'));
      });
    }
  }
  Devlib.define("SharedCalendar", SharedCalendar);
})(window.Devlib);
