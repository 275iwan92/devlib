---
name: devlib
description: Pakai DevLib (jo/devlib, komponen x-dl::*) di proyek Laravel apa pun milik Iwan. Gunakan SAAT membangun proyek baru, memasang DevLib, atau membuat layar / form / grid / laporan / login / menu — sebelum menulis markup Blade, CSS, atau JS sendiri. Berisi cara pasang, kerangka layout, dan cara mencari komponen yang sudah ada.
---

# DevLib di proyek baru

DevLib = library UI/UX (`x-dl::*`, PHP + JS berpasangan, tema, bahasa, aset) di repo sendiri `C:\Users\iwanp\Herd\devlib`. Proyek yang memakainya: `tmerp`, `sstock` (contoh paling ringkas), situs `devlib.test`.
Prinsip: **layar = deklarasi `x-dl::*`, bukan bangunan.** Tanpa HTML mentah, JS inline, atau hex warna di layar.

## 1. Pasang (proyek baru / belum ada DevLib)

Cek dulu `composer.json` proyek: bila `jo/devlib` sudah ada, lewati langkah ini.

1. `composer.json`:
   ```json
   "require": { "jo/devlib": "@dev" },
   "repositories": [{ "type": "path", "url": "../devlib", "options": { "symlink": true } }]
   ```
   lalu `composer update jo/devlib` (provider terdaftar otomatis).
2. Tailwind 4, di CSS aplikasi (path relatif terhadap berkas CSS itu):
   ```css
   @source '../../../devlib/src';
   @source '../../../devlib/resources';
   ```
3. `config/devlib.php` — salin dari `../sstock/config/devlib.php` lalu ubah `brand`, `company`, `lang`, `timezone`. Kunci: `brand`, `company`, `lang`, `translate_fixed` (aplikasi satu bahasa), `back_history` (**set `false`**, lihat bagian Form), `timezone`, `timezone_label`, `media_path/url`, `lookup_middleware`, `abilities`.
4. Layout — salin `../sstock/resources/views/layout.blade.php`. Intinya di `<head>`:
   ```blade
   <link rel="stylesheet" href="{{ \Devlib\DevlibServiceProvider::asset('devlib.css') }}">
   <x-dl::theme-boot />
   <script src="{{ \Devlib\DevlibServiceProvider::asset('devlib.js') }}" defer></script>
   ```
   `<body class="dl-body">`. Aset disajikan dari paket (`/_devlib/{file}`); jangan disalin ke proyek.
5. Hak akses `can="modul.aksi"`: pasang sekali di `AppServiceProvider` lewat `\Devlib\Access::resolveUsing(fn ($ability) => ...)`; tanpa itu dipakai `config('devlib.abilities')`.

## 2. Membuat layar

1. **Cari objek terdekat dulu** — jangan tulis dari nol:
   - Daftar komponen: `../devlib/src/Components/*.php` (+ `Field/`, `Form/`, `Wizard/`, `SideScreen/`).
   - **Konstruktor kelas = daftar prop (API-nya)**; tampilannya di `../devlib/resources/views/components/<nama-kebab>.blade.php`. Baca kelas yang dipakai saja, bukan semuanya.
   - Contoh pemakaian nyata: `../sstock/resources/views/` (`categories/index.blade.php` = grid master, `categories/form.blade.php` = form master, `layout.blade.php`, `login.blade.php`). Katalog lengkap + demo: situs `devlib.test` (`../tmerp/devlib`).
2. Pola baku:
   - Daftar data → `<x-dl::grid id=".." :rows=".." :columns="[..]" actions="master" :new-url=".." :open-url="url('x/{id}')" :stats="[..]" />`
   - Form dokumen/master → `<x-dl::form title=".." kind="master" :action=".." :back=".."> <x-dl::form.row :cols="3"> <x-dl::field.text name=".." label=".." /> ...`; form kecil → `x-dl::action-form`.
   - Isian: `x-dl::field.*` (text, select, date, money, lookup, toggle, ...). Tombol: `x-dl::button preset="save|cancel|delete|print|..."`.
3. **Teks layar ditulis English**; kamus menerjemahkan ke bahasa tampilan. **Nilai data** di prop teks dibungkus `raw()`: `:title="'Order · '.raw($o->no)"`. Aplikasi satu bahasa → `translate_fixed => true` dan kamus aplikasi.
4. Perilaku yang dibutuhkan >1 layar = tambah di **induk** (prop/method baru), bukan salin-tempel. Anak = `extends` induk, hanya menyatakan yang beda.

## Tipe grid (`x-dl::grid`) — pilih sebelum menulis kolom

Dua grid entry: **`kind="master"`** dan **`kind="transactional"`** — `kind` mengatur `actions` dan gaya kolom pertama otomatis (kolom pertama master = `box`, transaksional = polos; `type => doc` lama ikut jadi polos). Tulis `kind`, tidak perlu `actions`/`box`/`avatar` manual. Tanpa `kind` = perilaku lama. Report tetap `mode="report"`.

Semua dari satu komponen; bedanya `mode`, `actions`, dan kolom depan. Daftar prop & tipe kolom lengkap: komentar kepala `src/Components/Grid.php`.

| Tipe | Pengaturan | Kolom depan | Aksi baris |
|---|---|---|---|
| **Master** (data sedikit: barang, kategori, supplier, ...) | `mode="entry"` (bawaan), `actions="master"`, `:toggle-url="url('x/{id}/toggle')"` | kotak warna-warni berisi nama tebal + kode kecil: `'type'=>'identity','code'=>'<kolom kode>','box'=>true` (`'hue'=>'hue'` bila warna bermakna) | Edit + Aktif/Nonaktif |
| **Transaksional** (bisa ribuan baris, harus cepat: PO, penerimaan, ...) | `mode="entry"`, `actions="worklist"` | nomor dokumen **tebal saja, TANPA kotak/avatar warna**: `'type'=>'identity','code'=>'party','avatar'=>false` (nama pihak = baris kecil di bawah nomor; jangan `type => doc`) | **Buka saja** |
| **Report** | `mode="report"` (otomatis: tanpa centang/Aksi/New Record, rapat, filter terbuka) | bebas | tidak ada |
| **Daftar lain** (bukan master/transaksi: grup, modul, pengguna keamanan) | `actions="worklist"` atau `none` | `'type'=>'identity','code'=>..,'avatar'=>false` | Buka / tidak ada |

Kriteria:
- **Alasan pembedaan (keputusan user):** data transaksi bisa ribuan baris, jadi kolom pertama polos (tebal saja) supaya tampil cepat. Data master jauh lebih sedikit, jadi kolom pertama boleh berkotak warna-warni.
- **Master**: grid master baru wajib berkotak (`box => true`), jangan `avatar => false`. Aktif/Nonaktif hanya untuk master dan **wajib** `toggle-url`, kalau tidak hanya pratinjau.
- **Transaksional**: aturan lama tmerp "persegi dokumen" (`type => doc`) TIDAK dipakai di proyek baru. Kolom nilai `money` dengan `'sum'=>true` memberi baris Total. **5–8 kolom tampil** (sisanya tersembunyi), **tanpa kolom jumlah item/baris**; Expand (`detail`) = tabel anak **maks 7 kolom, selalu memuat nama item/product**, tanpa harga, judul tanpa jumlah baris. Wajib kolom **Status** `type => badge` + `tones` (satu-satunya warna di baris; hijau terkonfirmasi/disetujui, abu draf, kuning menunggu, merah batal/ditolak). Warna baris: `rowTone` + kolom teks yang menyebut artinya (jangan hanya warna). **Confirm / Approve / Cancel / Delete TIDAK ada di grid**, hanya di form dokumen. Transaksi tidak pernah dinonaktifkan.
- **Report**: periode (`Period + Show`) bila laporan berbasis tanggal; cetakan lewat `x-dl::report`.
- Semua grid entry: `+ New Record` via `:new-url`; sembunyikan (`:new-record="null"`) bagi yang tak berhak; `:open-url="url('x/{id}')"` (klik 2x/Buka/Edit); `search` menyebut kolom yang dicari; `stats` = kotak hitung yang berfungsi sebagai filter. Toolbar (cari, filter, kolom, export CSV) datang otomatis — jangan dibuat manual. Bulk hanya bila diminta.

## Form: hal yang sering terlewat

- **Tombol Back harus ke grid modulnya.** Isi `:back="route('x.index')"` di `x-dl::form` **dan** set `'back_history' => false` di `config/devlib.php`. Tanpa itu Back memakai `history.back()` → setelah Save, "layar sebelumnya" adalah form isian (risiko input ganda). Jangan pakai `history.back()` / `url()->previous()` di form.
- Form dokumen: `kind="document"` (+ `:pipeline`, `:status`); tombol Save/Confirm/Delete/Cancel/Print/Back to Draft **datang dari status DevLib** — jangan dibuat manual. Controller membaca `_action` (save, confirm, delete, cancel, approve, reject). Pakai kata **Confirm**, bukan Submit. Form master: `kind="master"`.
- Isian wajib: `required`; error server: `:error="$errors->first('x')"`; nilai ulang: `old()`. Isian di dalam modal bersarang tidak ikut validasi form induk.
- Cegah kirim ganda: layout memuat penjaga `submit` (ada di `sstock/resources/views/layout.blade.php`) — jangan dibuang.
- Riwayat: tab History otomatis; isi `:created-at`, `:updated-at`, `:log`.

## Help: selalu dua bagian, UG dan TG

Setiap proyek punya Help dengan dua bagian terpisah, dibuat bersama modulnya. Keduanya dibuka dari satu menu Help & support sebagai dua tab (User's Guide, Technical Guide). UG terlihat semua pengguna; TG hanya Administrator (seperti sstock).

- **TG (Technical Guide) — bahasa English.** Untuk developer/administrator. Semua yang teknis di sini: data flow, system flow, workflow (mis. siklus dokumen Draft > Confirmed > Cancelled), tabel dan database (model data, relasi, kolom penting), akses/hak, konvensi, operasional. Contoh: `../sstock/resources/views/help/technical-guide.blade.php`.
- **UG (User's Guide) — bahasa Indonesia.** Untuk pengguna, tuntunan cara pakai: penjelasan **grid** (cari, filter, kolom, export, klik 2x buka, `+ New Record`, Aktif/Nonaktif), penjelasan **form** (isian wajib, Save, Confirm, Undo, Back, tab Riwayat), cara pakai tiap layar bertahap, **daftar isi modul**, dan **fitur tiap modul**.
- **Batas:** diagram, tabel database, dan alur sistem tidak masuk UG; langkah pakai dan istilah awam tidak masuk TG.
- Proyek baru: sediakan kedua bagian sejak awal. sstock saat ini baru punya TG (UG belum dibuat).

## Kebiasaan lintas proyek

- Label layar boleh English; aplikasi satu bahasa menambah terjemahan ke kamus aplikasi (`Lang::text`), pesan validasi di `lang/<kode>/validation.php`. Mata uang tampil kode (`IDR`), bukan simbol.
- Seeder data contoh idempoten (`updateOrCreate` berkunci kode alami), bukan di migrasi; ubah skema = ubah seeder pada perubahan yang sama.
- Perubahan DevLib harus **opt-in** (default lama tetap jalan): proyek lain (tmerp) memakai perilaku lama. Satu opsi config/prop baru + default lama, bukan mengubah perilaku.
- Selesai satu perubahan: commit (repo proyek dan, bila DevLib berubah, repo devlib). Verifikasi tampilan nyata di `http://<proyek>.test`, bukan hanya tes.

## 3. Kalau komponen belum ada / kurang

Ubah **di DevLib, bukan di proyek**:
- Edit di `C:\Users\iwanp\Herd\devlib`, **commit di repo itu** (git proyek tidak melihatnya). Karena symlink, semua proyek pemakai langsung ikut.
- Paket berdiri sendiri: `src/` tidak boleh memakai `App\...`, model, atau service proyek. Data masuk lewat props/kontrak. Helper global milik paket di `src/helpers.php`.
- Komponen baru: kelas turunan `DevlibComponent` (`tag()`, `source()`, `jsClass()` bila ada JS) + view Blade; JS satu kelas per file di `resources/assets/js/components/`.

## Jangan

- HTML/Tailwind mentah, `<script>` inline, hex warna di layar proyek.
- Menyalin file aset DevLib ke proyek.
- Menambah komponen UI di proyek bila sama bisa jadi objek DevLib.
