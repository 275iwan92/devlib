/* DevLib · StickyNotes
 * Dinding catatan tempel (x-dl::sticky-notes): [{id, text, color}]. Teks lewat <textarea> + textContent (tidak pernah innerHTML). Daftar tidak digambar ulang saat mengetik
 * (fokus tetap); seluruh daftar dikirim ke data-save 600 ms setelah berhenti mengetik (POST JSON {items}). */
(function (Devlib) {
  'use strict';
  const {$, toast} = Devlib.h;
  const uid = () => 'n' + Date.now().toString(36) + Math.random().toString(36).slice(2, 5);

  class StickyNotes extends Devlib.Component {
    static selector = "[data-dl-notes]";
    init() {
      this.L = this.el.dataset; this.items = JSON.parse(this.L.items || '[]'); this.colors = JSON.parse(this.L.colors); this.wall = $(this.el, '[data-dl-notes-wall]'); this.empty = $(this.el, '[data-dl-notes-empty]');
      $(this.el, '[data-dl-notes-add]').addEventListener('click', () => { this.items.unshift({id: uid(), text: '', color: this.colors[this.items.length % this.colors.length]}); this.draw(true); this.save(); });
      this.draw();
    }

    save(now) {
      clearTimeout(this.t);
      const go = () => {
        const url = this.L.save; if (!url) return;
        fetch(url, {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.L.csrf}, body: JSON.stringify({items: this.items})})
          .then(r => { if (!r.ok) throw new Error(r.status); }).catch(() => toast(this.L.lFailed, 'danger'));
      };
      now ? go() : this.t = setTimeout(go, 600);
    }

    draw(focusFirst) {
      this.wall.replaceChildren(...this.items.map(n => {
        const card = document.createElement('div'); card.className = 'dl-notes__note dl-notes__note--' + n.color;
        const ta = document.createElement('textarea'); ta.maxLength = 1000; ta.value = n.text; ta.setAttribute('aria-label', this.L.lNote); ta.rows = 5;
        ta.addEventListener('input', () => { n.text = ta.value; this.save(); }); ta.addEventListener('blur', () => this.save(true));
        const foot = document.createElement('div'); foot.className = 'dl-notes__foot';
        this.colors.forEach(c => {
          const b = document.createElement('button'); b.type = 'button'; b.className = 'dl-notes__swatch dl-notes__swatch--' + c + (c === n.color ? ' is-on' : ''); b.setAttribute('aria-label', this.L.lColor + ': ' + c); b.setAttribute('aria-pressed', String(c === n.color));
          b.addEventListener('click', () => { n.color = c; this.draw(); this.save(true); }); foot.append(b);
        });
        const del = document.createElement('button'); del.type = 'button'; del.className = 'dl-notes__del'; del.setAttribute('aria-label', this.L.lDelete); del.title = this.L.lDelete; del.innerHTML = '<i class="fa-solid fa-trash" aria-hidden="true"></i>';
        del.addEventListener('click', () => { this.items = this.items.filter(x => x !== n); this.draw(); this.save(true); }); foot.append(del);
        card.append(ta, foot); return card;
      }));
      this.empty.hidden = this.items.length > 0;
      if (focusFirst) this.wall.querySelector('textarea')?.focus();
    }
  }
  Devlib.define("StickyNotes", StickyNotes);
})(window.Devlib);
