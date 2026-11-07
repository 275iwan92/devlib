/* DevLib · AlarmList + AlarmRunner
 * AlarmList (x-dl::alarm-list): daftar alarm [{id, time 'HH:MM', label, days [0=Min..6=Sab] (kosong = sekali), on}]; tiap perubahan disalin ke localStorage[data-key]
 * dan seluruh daftar dikirim ke data-save (POST JSON {items}). AlarmRunner (x-dl::alarm-runner, tak terlihat): tiap 5 detik mencocokkan jam browser dengan alarm aktif;
 * cocok = Devlib.sound.play('alarm') + toast; alarm sekali jalan dimatikan setelah berbunyi (daftar dikirim ulang). Hanya berjalan di jendela paling atas. */
(function (Devlib) {
  'use strict';
  const {$, $$, toast} = Devlib.h;
  const uid = () => 'a' + Date.now().toString(36) + Math.random().toString(36).slice(2, 5);
  const read = key => { try { return JSON.parse(localStorage.getItem(key)); } catch (e) { return null; } };
  const mirror = (key, items) => { try { localStorage.setItem(key, JSON.stringify(items)); } catch (e) { /* tanpa penyimpanan */ } };
  const send = (el, items, fail) => {
    const url = el.dataset.save; if (!url) return;
    fetch(url, {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': el.dataset.csrf}, body: JSON.stringify({items})})
      .then(r => { if (!r.ok) throw new Error(r.status); }).catch(() => fail && toast(fail, 'danger'));
  };

  class AlarmList extends Devlib.Component {
    static selector = "[data-dl-alarm]";
    init() {
      const d = this.el.dataset;
      this.items = JSON.parse(d.items || '[]'); this.days = JSON.parse(d.lDays); this.list = $(this.el, '[data-dl-alarm-list]'); this.empty = $(this.el, '[data-dl-alarm-empty]');
      $(this.el, '[data-dl-alarm-add]').addEventListener('submit', e => { e.preventDefault(); this.add(e.target); });
      this.draw(); mirror(d.key, this.items);
    }

    add(form) {
      const time = $(form, '[data-dl-alarm-time]').value; if (!time) return;
      const days = $$(form, '[data-dl-alarm-day]:checked').map(c => +c.value);
      this.items.push({id: uid(), time, label: $(form, '[data-dl-alarm-label]').value.trim(), days, on: true});
      this.items.sort((a, b) => a.time.localeCompare(b.time));
      form.reset(); this.commit();
    }

    commit() { this.draw(); mirror(this.el.dataset.key, this.items); clearTimeout(this.t); this.t = setTimeout(() => send(this.el, this.items, this.el.dataset.lFailed), 300); }

    draw() {
      this.list.replaceChildren(...this.items.map(a => {
        const li = document.createElement('li'); li.className = 'dl-alarm__row' + (a.on ? '' : ' is-off');
        const t = document.createElement('b'); t.className = 'dl-alarm__time'; t.textContent = a.time;
        const m = document.createElement('span'); m.className = 'dl-alarm__meta';
        m.textContent = [a.label, (a.days || []).length ? [1, 2, 3, 4, 5, 6, 0].filter(x => a.days.includes(x)).map(x => this.days[x]).join(' ') : this.el.dataset.lOnce].filter(Boolean).join(' · ');
        const sw = document.createElement('input'); sw.type = 'checkbox'; sw.checked = !!a.on; sw.setAttribute('aria-label', a.time + ' ' + (a.label || ''));
        sw.addEventListener('change', () => { a.on = sw.checked; this.commit(); });
        const del = document.createElement('button'); del.type = 'button'; del.className = 'dl-alarm__del'; del.setAttribute('aria-label', this.el.dataset.lDelete); del.title = this.el.dataset.lDelete;
        del.innerHTML = '<i class="fa-solid fa-trash" aria-hidden="true"></i>';
        del.addEventListener('click', () => { this.items = this.items.filter(x => x !== a); this.commit(); });
        li.append(t, m, sw, del); return li;
      }));
      this.empty.hidden = this.items.length > 0;
    }
  }
  Devlib.define("AlarmList", AlarmList);

  class AlarmRunner extends Devlib.Component {
    static selector = "[data-dl-alarm-runner]";
    init() {
      if (window.top !== window) return; // layar di dalam iframe tidak membunyikan alarm: cukup satu pembunyi di halaman induk
      const d = this.el.dataset; this.items = read(d.key) || JSON.parse(d.items || '[]'); this.fired = {};
      window.addEventListener('storage', e => { if (e.key === d.key) this.items = read(d.key) || []; });
      setInterval(() => this.tick(), 5000); this.tick();
    }

    tick() {
      const n = new Date(), hm = String(n.getHours()).padStart(2, '0') + ':' + String(n.getMinutes()).padStart(2, '0'), stamp = n.toDateString() + ' ' + hm;
      let changed = false;
      this.items.forEach(a => {
        if (!a.on || a.time !== hm || this.fired[a.id] === stamp) return;
        if ((a.days || []).length && !a.days.includes(n.getDay())) return;
        this.fired[a.id] = stamp;
        Devlib.sound?.play('alarm');
        toast(a.time + (a.label ? ' · ' + a.label : ''), 'warn', this.el.dataset.lRing);
        document.title = this.el.dataset.lRing + ' ' + a.time + ' · ' + (this.title0 ||= document.title); document.addEventListener('pointerdown', () => { document.title = this.title0; }, {once: true, capture: true});
        if (!(a.days || []).length) { a.on = false; changed = true; }
      });
      if (changed) { mirror(this.el.dataset.key, this.items); send(this.el, this.items); }
    }
  }
  Devlib.define("AlarmRunner", AlarmRunner);
})(window.Devlib);
