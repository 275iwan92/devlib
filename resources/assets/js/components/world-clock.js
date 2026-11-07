/* DevLib · WorldClock
 * Jam dunia (x-dl::world-clock): kartu per kota [{id, tz, label}]; jam, tanggal, hari/malam, dan selisih dari waktu perangkat dihitung browser (Intl.DateTimeFormat timeZone),
 * jadi aturan musim panas ikut benar. Tiap detik hanya teks yang diperbarui. Seluruh daftar dikirim ke data-save (POST JSON {items}). */
(function (Devlib) {
  'use strict';
  const {$, toast} = Devlib.h;
  const uid = () => 'c' + Date.now().toString(36) + Math.random().toString(36).slice(2, 5);

  // Bagian jam-tanggal sebuah zona pada saat tertentu.
  const parts = (tz, at) => Object.fromEntries(new Intl.DateTimeFormat('en-GB', {timeZone: tz, hourCycle: 'h23', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit'})
    .formatToParts(at).filter(p => p.type !== 'literal').map(p => [p.type, +p.value]));
  const wall = (tz, at) => { const p = parts(tz, at); return Date.UTC(p.year, p.month - 1, p.day, p.hour, p.minute, p.second); };

  class WorldClock extends Devlib.Component {
    static selector = "[data-dl-wclock]";
    init() {
      this.L = this.el.dataset; this.items = JSON.parse(this.L.items || '[]'); this.cards = $(this.el, '[data-dl-wclock-cards]'); this.empty = $(this.el, '[data-dl-wclock-empty]'); this.cells = new Map();
      $(this.el, '[data-dl-wclock-add]').addEventListener('submit', e => {
        e.preventDefault(); const sel = $(e.target, '[data-dl-wclock-zone]'); if (!sel.value) return;
        this.items.push({id: uid(), tz: sel.value, label: sel.selectedOptions[0].text}); sel.value = ''; this.commit();
      });
      this.draw(); setInterval(() => this.tick(), 1000);
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
      this.cells.clear();
      this.cards.replaceChildren(...this.items.map(c => {
        const card = document.createElement('div'); card.className = 'dl-wclock__card';
        const city = document.createElement('b'), time = document.createElement('output'), date = document.createElement('span'), off = document.createElement('small'), icon = document.createElement('i');
        city.textContent = c.label || c.tz; time.className = 'dl-wclock__time'; date.className = 'dl-wclock__date'; off.className = 'dl-wclock__off'; icon.setAttribute('aria-hidden', 'true');
        const del = document.createElement('button'); del.type = 'button'; del.className = 'dl-wclock__del'; del.setAttribute('aria-label', this.L.lDelete + ': ' + (c.label || c.tz)); del.title = this.L.lDelete;
        del.innerHTML = '<i class="fa-solid fa-xmark" aria-hidden="true"></i>'; del.addEventListener('click', () => { this.items = this.items.filter(x => x !== c); this.commit(); });
        card.append(icon, city, time, date, off, del); this.cells.set(c, {time, date, off, icon}); return card;
      }));
      this.empty.hidden = this.items.length > 0; this.tick();
    }

    tick() {
      const now = new Date(), here = wall(Intl.DateTimeFormat().resolvedOptions().timeZone, now);
      this.cells.forEach((cell, c) => {
        let p; try { p = parts(c.tz, now); } catch (e) { return; }
        const pad = n => String(n).padStart(2, '0'), diff = Math.round((wall(c.tz, now) - here) / 60000), h = Math.floor(Math.abs(diff) / 60), m = Math.abs(diff) % 60;
        cell.time.textContent = pad(p.hour) + ':' + pad(p.minute) + ':' + pad(p.second);
        cell.date.textContent = new Intl.DateTimeFormat(undefined, {timeZone: c.tz, weekday: 'short', day: 'numeric', month: 'short'}).format(now);
        cell.off.textContent = diff === 0 ? this.L.lSame : h + (m ? ':' + pad(m) : '') + ' h ' + (diff > 0 ? this.L.lAhead : this.L.lBehind);
        cell.icon.className = 'fa-solid ' + (p.hour >= 6 && p.hour < 18 ? 'fa-sun' : 'fa-moon');
      });
    }
  }
  Devlib.define("WorldClock", WorldClock);
})(window.Devlib);
