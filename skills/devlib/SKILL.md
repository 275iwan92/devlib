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
3. `config/devlib.php` — salin dari `../sstock/config/devlib.php` lalu ubah `brand`, `company`, `lang`, `timezone`. Kunci: `brand`, `company`, `lang`, `translate_fixed` (aplikasi satu bahasa), `back_history`, `timezone`, `timezone_label`, `media_path/url`, `lookup_middleware`, `abilities`.
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

## 3. Kalau komponen belum ada / kurang

Ubah **di DevLib, bukan di proyek**:
- Edit di `C:\Users\iwanp\Herd\devlib`, **commit di repo itu** (git proyek tidak melihatnya). Karena symlink, semua proyek pemakai langsung ikut.
- Paket berdiri sendiri: `src/` tidak boleh memakai `App\...`, model, atau service proyek. Data masuk lewat props/kontrak. Helper global milik paket di `src/helpers.php`.
- Komponen baru: kelas turunan `DevlibComponent` (`tag()`, `source()`, `jsClass()` bila ada JS) + view Blade; JS satu kelas per file di `resources/assets/js/components/`.

## Jangan

- HTML/Tailwind mentah, `<script>` inline, hex warna di layar proyek.
- Menyalin file aset DevLib ke proyek.
- Menambah komponen UI di proyek bila sama bisa jadi objek DevLib.
