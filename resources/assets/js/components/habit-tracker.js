/* DevLib · HabitTracker
 * Pelacak kebiasaan (x-dl::habit-tracker): [{id, name, log: ['YYYY-MM-DD']}]. Tombol hari ini mencentang/membatalkan; kisi 14 hari terakhir bisa diklik untuk mengoreksi;
 * streak = hari berurutan sampai hari ini (atau sampai kemarin bila hari ini belum dicentang). Seluruh daftar dikirim ke data-save (POST JSON {items}) 300 ms setelah perubahan terakhir. */
(function (Devlib) {
  'use strict';
  const {$, toast} = Devlib.h;
  const uid = () => 'h' + Date.now().toString(36) + Math.random().toString(36).slice(2, 5);
  const ymd = d => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
  const ago = n => { const d = new Date(); d.setDate(d.getDate() - n); return ymd(d); };
  const KEEP = 400;

  class HabitTracker extends Devlib.Component {
    static selector = "[data-dl-habit]";
    init() {
      this.L = this.el.dataset; this.items = JSON.parse(this.L.items || '[]'); this.list = $(this.el, '[data-dl-habit-list]'); this.empty = $(this.el, '[data-dl-habit-empty]');
      $(this.el, '[data-dl-habit-add]').addEventListener('submit', e => { e.preventDefault(); const f = $(e.target, '[data-dl-habit-name]'); if (f.value.trim()) { this.items.push({id: uid(), name: f.value.trim(), log: []}); f.value = ''; this.commit(); } });
      this.draw();
    }

    streak(h) {
      const set = new Set(h.log); let n = 0, i = set.has(ago(0)) ? 0 : 1;
      while (set.has(ago(i))) { n++; i++; }
      return n;
    }

    toggle(h, day) {
      const at = h.log.indexOf(day);
      if (at >= 0) h.log.splice(at, 1); else h.log.push(day);
      const cut = ago(KEEP); h.log = h.log.filter(d => d >= cut).sort(); this.commit();
    }

    commit() {
      this.draw(); clearTimeout(this.t);
      this.t = setTimeout(() => {
        const url = this.L.save; if (!url) return;
        fetch(url, {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.L.csrf}, body: JSON.stringify({items: this.items})})
          .then(r => { if (!r.ok) throw new Error(r.status); }).catch(() => toast(this.L.lFailed, 'danger'));
      }, 300);
    }

    draw() {
      const today = ago(0);
      this.list.replaceChildren(...this.items.map(h => {
        const li = document.createElement('li'); li.className = 'dl-habit__card';
        const done = h.log.includes(today);
        const name = document.createElement('b'); name.className = 'dl-habit__name'; name.textContent = h.name;
        const st = document.createElement('span'); st.className = 'dl-habit__streak'; st.textContent = this.streak(h) + ' ' + this.L.lStreak;
        const btn = document.createElement('button'); btn.type = 'button'; btn.className = 'dl-btn ' + (done ? 'dl-btn--primary' : 'dl-btn--ghost'); btn.setAttribute('aria-pressed', String(done));
        btn.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i> '; btn.append(done ? this.L.lDone : this.L.lUndo); btn.addEventListener('click', () => this.toggle(h, today));
        const del = document.createElement('button'); del.type = 'button'; del.className = 'dl-habit__del'; del.setAttribute('aria-label', this.L.lDelete + ': ' + h.name); del.title = this.L.lDelete;
        del.innerHTML = '<i class="fa-solid fa-trash" aria-hidden="true"></i>'; del.addEventListener('click', () => { this.items = this.items.filter(x => x !== h); this.commit(); });
        const grid = document.createElement('div'); grid.className = 'dl-habit__grid';
        for (let i = 13; i >= 0; i--) {
          const day = ago(i), d = document.createElement('button'); d.type = 'button'; d.className = 'dl-habit__day' + (h.log.includes(day) ? ' is-done' : '');
          d.title = day; d.setAttribute('aria-label', day); d.setAttribute('aria-pressed', String(h.log.includes(day))); d.addEventListener('click', () => this.toggle(h, day)); grid.append(d);
        }
        const head = document.createElement('div'); head.className = 'dl-habit__head'; head.append(name, st, btn, del);
        li.append(head, grid); return li;
      }));
      this.empty.hidden = this.items.length > 0;
    }
  }
  Devlib.define("HabitTracker", HabitTracker);
})(window.Devlib);
