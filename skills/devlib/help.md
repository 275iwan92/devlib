# DevLib: Help UG dan TG



Setiap proyek punya Help dengan dua bagian terpisah, dibuat bersama modulnya. Keduanya dibuka dari satu menu Help & support sebagai dua tab (User's Guide, Technical Guide). UG terlihat semua pengguna; TG hanya Administrator (Administrator saja).

- **TG (Technical Guide) — bahasa English.** Untuk developer/administrator. Semua yang teknis di sini: data flow, system flow, workflow (mis. siklus dokumen Draft > Confirmed > Cancelled), tabel dan database (model data, relasi, kolom penting), akses/hak, konvensi, operasional. 
- **UG (User's Guide) — bahasa Indonesia.** Untuk pengguna, tuntunan cara pakai: penjelasan **grid** (cari, filter, kolom, export, klik 2x buka, `+ New Record`, Aktif/Nonaktif), penjelasan **form** (isian wajib, Save, Confirm, Undo, Back, tab Riwayat), cara pakai tiap layar bertahap, **daftar isi modul**, dan **fitur tiap modul**.
- **Flow = gambar SVG** (keputusan user): setiap flow (data flow, system flow, workflow/status, alur pemakaian di UG) berupa diagram SVG, bukan daftar teks/tabel. Sumber di `resources/help/diagrams/*.svg` dan disisipkan lewat Blade (inline atau `<img src="data:image/svg+xml;base64,...">`), BUKAN di `public/`, supaya diagram TG ikut terlindung hak akses Administrator. Label diagram TG English, diagram UG Indonesia; warna dari token tema (terang/gelap terbaca); `viewBox` + `max-width: 100%` agar muat di layar sempit; tiap gambar punya keterangan dan `alt`. Alur harus dicocokkan dengan kode (status, tombol, nama tabel), jangan menebak.
- **Batas:** diagram, tabel database, dan alur sistem tidak masuk UG; langkah pakai dan istilah awam tidak masuk TG.
- Proyek baru: sediakan kedua bagian sejak awal.

## Technical Guide object library (paket DevLib)

Selain TG proyek di atas, paket menyediakan **Technical Guide semua object `x-dl::*`** (English, HTML) dan menggantikan Mode Developer (F10, dihapus).
- Halaman: route `devlib.guide` (`/_devlib-guide`), opt-in lewat `config('devlib.guide_middleware')` (isi middleware, mis. `['web', 'auth']`; `null` = tidak ada). Proyek boleh menunjuk halaman sendiri lewat `config('devlib.guide_url')`. Tautkan ke menu Help (`help-url`).
- Sumber: kerangka tiap object otomatis dari `Devlib\Manifest` (tag, kelas, berkas, prop + tipe + bawaan, tujuan dari REMARK); rincian tulisan tangan = `resources/guide/<tag tanpa x-dl::>.md` (titik tag jadi `/`, mis. `field/text.md`).
- Bentuk rincian: tujuh judul `##` berurutan: Overview, Props, Behavior, Data and events, Rules and pitfalls, Example, Related. Setiap prop dijelaskan, ditulis dalam backtick kebab-case (`new-url`).
- **Object baru wajib punya rincian** (tes `GuideContractTest`). Object lama yang belum ditulis tercatat di `resources/guide/PENDING.txt`; daftar itu hanya boleh menyusut.
- Link judul: JS (`guide-link.js`) membungkus judul form/kartu/wizard/flow/dll dan menambah ikon info di toolbar grid, menuju `config url#anchor` (anchor = tag dengan non-alfanumerik jadi `-`, `Devlib\Guide::anchor()`). Tanpa URL guide, tidak ada link.
