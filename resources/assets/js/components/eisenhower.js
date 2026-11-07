/* DevLib · Eisenhower
 * Matriks Eisenhower (x-dl::eisenhower): [{id, text, q 1..4, done}]. Tugas ditambah per kuadran, dicentang selesai, dipindah lewat <select>, dihapus; "Clear completed" membuang yang selesai.
 * Teks lewat textContent (tidak pernah innerHTML). Seluruh daftar dikirim ke data-save 300 ms setelah perubahan terakhir (POST JSON {items}). */
(function (Devlib) {
  'use strict';
  const {$, $$, toast} = Devlib.h;
  const uid = () => 'k' + Date.now().toString(36) + Math.random().toString(36).slice(2, 5);

  class Eisenhower extends Devlib.Component {
    static selector = "[data-dl-eis]";
    init() {
      this.L = this.el.dataset; this.items = JSON.parse(this.L.items || '[]'); this.names = JSON.parse(this.L.lQ);
      $$(this.el, '[data-dl-eis-q]').forEach(sec => $(sec, '[data-dl-eis-add]').addEventListener('submit', e => {
        e.preventDefault(); const f = $(e.target, 'input'); if (!f.value.trim()) return;
        this.items.push({id: uid(), text: f.value.trim(), q: +sec.dataset.dlEisQ, done: false}); f.value = ''; this.commit();
      }));
      $(this.el, '[data-dl-eis-clear]').addEventListener('click', () => { this.items = this.items.filter(t => !t.done); this.commit(); });
      this.draw();
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
          li.append(cb, tx, mv, del); return li;
        }));
      });
    }
  }
  Devlib.define("Eisenhower", Eisenhower);
})(window.Devlib);
