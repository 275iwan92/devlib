/* DevLib · Upload */
(function (Devlib) {
  'use strict';
  const {$, esc, T} = Devlib.h;

  class Upload extends Devlib.Component {
    static selector = "[data-dl-upload]";
    init() {
      this.file = $(this.el, 'input[type=file]');
      this.list = $(this.el, '[data-dl-upload-list]');
      this.preview = this.el.hasAttribute('data-preview');
      this.files = [];
      this.initial = this.list.innerHTML;  // daftar berkas tersimpan dari server: kembali ke sini saat reset
      this.el.classList.toggle('has-file', !!this.list.children.length);
      // Terkunci (disabled/readonly, atau dimatikan layar lewat input.disabled) = klik & drop ditolak.
      this.el.addEventListener('click', e => { if (!this.file.disabled && !e.target.closest('button') && !e.target.closest('.dl-upload__file') && !e.target.closest('.dl-upload__progress')) this.file.click(); });
      this.file.addEventListener('click', e => e.stopPropagation());
      this.file.addEventListener('change', () => { if (this.file.files.length) this.take(this.file.files); });
      this.list.addEventListener('click', e => { const b = e.target.closest('button[data-i]'); if (b) { e.stopPropagation(); this.files.splice(+b.dataset.i, 1); this.draw(); } });
      ['dragover', 'dragenter'].forEach(ev => this.el.addEventListener(ev, e => { e.preventDefault(); if (!this.file.disabled) this.el.classList.add('is-drag'); }));
      ['dragleave', 'drop'].forEach(ev => this.el.addEventListener(ev, e => { e.preventDefault(); this.el.classList.remove('is-drag'); }));
      this.el.addEventListener('drop', e => { if (!this.file.disabled && e.dataTransfer.files.length) this.take(e.dataTransfer.files); });
      if (this.el.hasAttribute('data-progress') && this.el.closest('form')) this.el.closest('form').addEventListener('submit', e => this.send(e));
    }

    // Prop progress: form dikirim lewat XMLHttpRequest supaya kemajuan unggah terukur (submit biasa tidak bisa).
    // Jawaban server (halaman hasil redirect, lengkap dengan pesan flash) menggantikan halaman ini lewat document.write;
    // window.DevlibReady dikosongkan dulu karena boot.js menggantinya dengan objek dan skrip baru mengharapkan antrean (array).
    // Tanpa berkas dipilih: submit biasa jalan (server yang menolak). Contoh: 1754 KB, terkirim 877 KB = 50%.
    send(e) {
      if (!this.files.length || e.defaultPrevented) return;
      e.preventDefault();
      const form = e.target, box = $(this.el, '[data-dl-upload-progress]'), bar = $(box, '[data-dl-upload-bar]'), pct = $(box, '[data-dl-upload-pct]'), txt = $(box, '[data-dl-upload-pt]');
      const lock = form.querySelectorAll('button,[type=submit]'); lock.forEach(b => { b.disabled = true; });
      box.hidden = false; this.el.classList.add('is-sending');
      const fail = () => { lock.forEach(b => { b.disabled = false; }); box.hidden = true; this.el.classList.remove('is-sending'); Devlib.toast(box.dataset.failed, 'danger'); };
      const xhr = new XMLHttpRequest();
      xhr.open('POST', form.action);
      xhr.upload.onprogress = ev => {
        const p = ev.lengthComputable ? Math.round(ev.loaded / ev.total * 100) : 0;
        bar.style.width = p + '%'; pct.textContent = p + '%';
        if (p >= 100) txt.textContent = box.dataset.processing;  // terkirim semua; server masih membaca berkas
      };
      xhr.onerror = fail;
      xhr.onload = () => { const html = xhr.responseText; history.replaceState(null, '', xhr.responseURL || location.href); window.DevlibReady = undefined; document.open(); document.write(html); document.close(); };
      xhr.send(new FormData(form));
    }

    draw() {
      this.el.classList.toggle('has-file', this.files.length > 0);
      this.list.innerHTML = '';
      this.files.forEach((f, i) => {
        const row = document.createElement('div'); row.className = 'dl-upload__file';
        const isImg = this.preview && f.type.startsWith('image/');
        row.innerHTML = (isImg ? '<img class="dl-upload__thumb" alt="">' : '<span>📄</span>') + `<span class="dl-upload__name">${esc(f.name)}</span><small>${Math.max(1, Math.round(f.size / 1024))} KB</small><button type="button" data-i="${i}" title="${esc(T('up_remove'))}">✕</button>`;
        if (isImg) { const img = $(row, 'img'); img.src = URL.createObjectURL(f); img.onload = () => URL.revokeObjectURL(img.src); }
        this.list.appendChild(row);
      });
      const dt = new DataTransfer(); this.files.forEach(f => dt.items.add(f)); this.file.files = dt.files;
      // Satu kabar untuk pilih / tarik-lepas / hapus (input.files diisi program tidak memicu 'change').
      this.el.dispatchEvent(new CustomEvent('dl:upload', {bubbles: true, detail: {files: [...this.files]}}));
      // input (bukan change: change membaca input.files dan memanggil take() lagi) supaya dirty-guard dan pesan Required ikut.
      this.file.dispatchEvent(new Event('input', {bubbles: true}));
    }

    // Undo / Devlib.fill: buang berkas yang dipilih sesudah form dibuka, daftar tersimpan kembali.
    reset() { this.files = []; this.draw(); this.list.innerHTML = this.initial; this.el.classList.toggle('has-file', !!this.list.children.length); }

    take(incoming) { this.files = this.file.multiple ? this.files.concat([...incoming]) : [...incoming].slice(0, 1); this.draw(); }
  }
  Devlib.define("Upload", Upload);
})(window.Devlib);
