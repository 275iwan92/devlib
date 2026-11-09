/* DevLib · Eisenhower + TaskAlarmRunner
 * Matriks Eisenhower (x-dl::eisenhower): [{id, text, q 1..4, done, due 'Y-m-d H:i' | undefined, alarm bool}]. Tugas ditambah per kuadran, dicentang selesai, diberi due date (dd-Mmm-yyyy HH:mm) dan alarm on/off, dipindah lewat <select>, dihapus; "Clear completed" membuang yang selesai.
 * Teks lewat textContent (tidak pernah innerHTML). Seluruh daftar dikirim ke data-save 300 ms setelah perubahan terakhir (POST JSON {items}) dan disalin ke localStorage[data-key] untuk pembunyi alarm.
 * TaskAlarmRunner (x-dl::task-alarm-runner, tak terlihat, SATU di halaman induk): tiap 5 detik mencocokkan jam browser dengan due tugas ber-alarm yang belum selesai; due tiba (paling lama 12 jam lalu) = Devlib.sound.play('notif') + toast, sekali per tugas+due (dicatat di localStorage[key + '-rung']). */
(function (Devlib) {
  'use strict';
  const {$, $$, toast} = Devlib.h;
  const uid = () => 'k' + Date.now().toString(36) + Math.random().toString(36).slice(2, 5);
  const read = key => { try { return JSON.parse(localStorage.getItem(key)); } catch (e) { return null; } };
  const mirror = (key, v) => { try { localStorage.setItem(key, JSON.stringify(v)); } catch (e) { /* tanpa penyimpanan */ } };
  const pad = n => String(n).padStart(2, '0');
  const dueDate = s => s ? new Date(s.replace(' ', 'T')) : null; // 'Y-m-d H:i' = waktu lokal browser
  const inputValue = s => s ? s.replace(' ', 'T') : '';

  class Eisenhower extends Devlib.Component {
    static selector = "[data-dl-eis]";
    init() {
      this.L = this.el.dataset; this.items = JSON.parse(this.L.items || '[]'); this.names = JSON.parse(this.L.lQ); this.months = JSON.parse(this.L.lMonths || '[]');
      $$(this.el, '[data-dl-eis-q]').forEach(sec => $(sec, '[data-dl-eis-add]').addEventListener('submit', e => {
        e.preventDefault(); const f = $(e.target, 'input'); if (!f.value.trim()) return;
        this.items.push({id: uid(), text: f.value.trim(), q: +sec.dataset.dlEisQ, done: false}); f.value = ''; this.commit();
      }));
      $(this.el, '[data-dl-eis-clear]').addEventListener('click', () => { this.items = this.items.filter(t => !t.done); this.commit(); });
      this.draw(); if (this.L.key) mirror(this.L.key, this.items);
    }

    commit() {
      this.draw(); if (this.L.key) mirror(this.L.key, this.items); clearTimeout(this.t);
      this.t = setTimeout(() => {
        const url = this.L.save; if (!url) return;
        fetch(url, {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.L.csrf}, body: JSON.stringify({items: this.items})})
          .then(r => { if (!r.ok) throw new Error(r.status); }).catch(() => toast(this.L.lFailed, 'danger'));
      }, 300);
    }

    // dd-Mmm-yyyy HH:mm
    label(s) { const d = dueDate(s); return pad(d.getDate()) + '-' + (this.months[d.getMonth()] || pad(d.getMonth() + 1)) + '-' + d.getFullYear() + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes()); }

    dueControl(t) {
      const wrap = document.createElement('span'); wrap.className = 'dl-eis__dueWrap';
      const inp = document.createElement('input'); inp.type = 'datetime-local'; inp.className = 'dl-eis__dueIn'; inp.value = inputValue(t.due); inp.tabIndex = -1; inp.setAttribute('aria-label', this.L.lDue + ': ' + t.text);
      inp.addEventListener('change', () => { if (inp.value) t.due = inp.value.replace('T', ' ').slice(0, 16); else { delete t.due; t.alarm = false; } this.commit(); });
      const btn = document.createElement('button'); btn.type = 'button'; btn.className = 'dl-eis__due'; btn.title = this.L.lDue;
      const n = new Date(), d = dueDate(t.due);
      if (d && !t.done) btn.classList.add(d < n ? 'is-late' : d.toDateString() === n.toDateString() ? 'is-today' : 'is-set'); else if (d) btn.classList.add('is-set');
      btn.innerHTML = '<i class="fa-regular fa-calendar" aria-hidden="true"></i>'; btn.append(' ' + (d ? this.label(t.due) : this.L.lDue));
      btn.addEventListener('click', () => { try { inp.showPicker(); } catch (e) { inp.focus(); inp.click(); } });
      wrap.append(btn, inp); return wrap;
    }

    alarmControl(t) {
      const b = document.createElement('button'); b.type = 'button'; b.className = 'dl-eis__bell' + (t.alarm ? ' is-on' : ''); b.disabled = !t.due;
      b.setAttribute('aria-pressed', t.alarm ? 'true' : 'false'); b.setAttribute('aria-label', this.L.lAlarm + ': ' + t.text); b.title = this.L.lAlarm;
      const ic = document.createElement('i'); ic.className = 'fa-solid ' + (t.alarm ? 'fa-bell' : 'fa-bell-slash'); ic.setAttribute('aria-hidden', 'true'); b.append(ic);
      b.addEventListener('click', () => { t.alarm = !t.alarm; this.commit(); }); return b;
    }

    draw() {
      $$(this.el, '[data-dl-eis-q]').forEach(sec => {
        const q = +sec.dataset.dlEisQ;
        $(sec, '[data-dl-eis-list]').replaceChildren(...this.items.filter(t => t.q === q).map(t => {
          const li = document.createElement('li'); li.className = t.done ? 'is-done' : '';
          const cb = document.createElement('input'); cb.type = 'checkbox'; cb.checked = t.done; cb.setAttribute('aria-label', t.text); cb.addEventListener('change', () => { t.done = cb.checked; this.commit(); });
          const tx = document.createElement('span'); tx.textContent = t.text;
          const mv = document.createElement('select'); mv.setAttribute('aria-label', this.L.lMove + ': ' + t.text);
          [1, 2, 3, 4].forEach(n => mv.add(new Option(n + ' · ' + this.names[n], n, false, n === t.q))); mv.addEventListener('change', () => { t.q = +mv.value; this.commit(); });
          const del = document.createElement('button'); del.type = 'button'; del.className = 'dl-eis__del'; del.setAttribute('aria-label', this.L.lDelete + ': ' + t.text); del.title = this.L.lDelete;
          del.innerHTML = '<i class="fa-solid fa-xmark" aria-hidden="true"></i>'; del.addEventListener('click', () => { this.items = this.items.filter(x => x !== t); this.commit(); });
          li.append(cb, tx, this.dueControl(t), this.alarmControl(t), mv, del); return li;
        }));
      });
    }
  }
  Devlib.define("Eisenhower", Eisenhower);

  class TaskAlarmRunner extends Devlib.Component {
    static selector = "[data-dl-task-alarm]";
    init() {
      if (window.top !== window) return; // satu pembunyi di halaman induk, bukan di iframe layar
      const d = this.el.dataset; this.key = d.key; this.items = read(d.key) || JSON.parse(d.items || '[]'); this.rung = read(d.key + '-rung') || {};
      mirror(d.key, this.items); // tanpa kunjungan ke Tools > Task pun, daftar dari server tersedia
      window.addEventListener('storage', e => { if (e.key === d.key) this.items = read(d.key) || []; });
      setInterval(() => this.tick(), 5000); this.tick();
    }

    tick() {
      const now = Date.now(); let changed = false;
      this.items.forEach(t => {
        const d = t.alarm && !t.done && t.due ? dueDate(t.due) : null, k = t.id + '|' + t.due;
        if (!d || this.rung[k] || d.getTime() > now || now - d.getTime() > 12 * 3600e3) return;
        this.rung[k] = now; changed = true;
        Devlib.sound?.play('notif');
        toast(t.text, 'warn', this.el.dataset.lRing);
        document.title = this.el.dataset.lRing + ' · ' + (this.title0 ||= document.title); document.addEventListener('pointerdown', () => { document.title = this.title0; }, {once: true, capture: true});
      });
      if (changed) mirror(this.key + '-rung', this.rung);
    }
  }
  Devlib.define("TaskAlarmRunner", TaskAlarmRunner);
})(window.Devlib);
