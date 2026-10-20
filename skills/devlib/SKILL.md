---
name: devlib
description: Pakai DevLib (jo/devlib, komponen x-dl::*) di proyek Laravel apa pun milik Iwan. Gunakan SAAT membangun proyek baru, memasang DevLib, atau membuat layar / form / grid / laporan / login / menu — sebelum menulis markup Blade, CSS, atau JS sendiri. Berisi cara pasang, kerangka layout, dan cara mencari komponen yang sudah ada.
---

# DevLib di proyek baru

DevLib = library UI/UX (`x-dl::*`, PHP + JS berpasangan, tema, bahasa, aset) di repo sendiri `C:\Users\iwanp\Herd\devlib`. Dipakai SEMUA proyek (sstock, tmerp, proyek baru) dan situs acuan `site/`. Arah satu jalur: **DevLib membuat, proyek mewarisi** (bukan sebaliknya), lihat `inheritance.md`; contoh baku ada di `examples/`.
Prinsip: **layar = deklarasi `x-dl::*`, bukan bangunan.** Tanpa HTML mentah, JS inline, atau hex warna di layar.

## 1. Pasang (proyek baru / belum ada DevLib)

Cek `composer.json` proyek: bila `jo/devlib` sudah ada, lewati. Bila belum, muat `install.md` (composer path repo, Tailwind `@source`, `config/devlib.php` dengan `back_history=false`, layout, hak akses `can`).

## 2. Membuat layar

1. **Cari objek terdekat dulu** — jangan tulis dari nol:
   - Daftar komponen: `../devlib/src/Components/*.php` (+ `Field/`, `Form/`, `Wizard/`, `SideScreen/`).
   - **Konstruktor kelas = daftar prop (API-nya)**; tampilannya di `../devlib/resources/views/components/<nama-kebab>.blade.php`. Baca kelas yang dipakai saja, bukan semuanya.
   - Contoh baku: `examples/` (`categories/index.blade.php` = grid master, `categories/form.blade.php` = form master, `layout.blade.php`, `login.blade.php`). Katalog lengkap + contoh hidup: situs `devlib.test` (`site/` di repo devlib).
2. Pola baku:
   - Daftar data → `<x-dl::grid id=".." :rows=".." :columns="[..]" actions="master" :new-url=".." :open-url="url('x/{id}')" :stats="[..]" />`
   - Form dokumen/master → `<x-dl::form title=".." kind="master" :action=".." :back=".."> <x-dl::form.row :cols="3"> <x-dl::field.text name=".." label=".." /> ...`; form kecil → `x-dl::action-form`.
   - Isian: `x-dl::field.*` (text, select, date, money, lookup, toggle, ...). Tombol: `x-dl::button preset="save|cancel|delete|print|..."`.
3. **Teks layar ditulis English**; kamus menerjemahkan ke bahasa tampilan. **Nilai data** di prop teks dibungkus `raw()`: `:title="'Order · '.raw($o->no)"`. Aplikasi satu bahasa → `translate_fixed => true` dan kamus aplikasi.
4. Perilaku yang dibutuhkan >1 layar = tambah di **induk** (prop/method baru), bukan salin-tempel. Anak = `extends` induk, hanya menyatakan yang beda.

## Aturan baku (muat berkas rujukan sesuai kebutuhan)

- **Cari komponen dari `components.md`** (manifest otomatis: tag, prop + tipe + bawaan, tujuan, berkas; diperbarui `php artisan devlib:manifest`), bukan membaca kelas satu per satu.
- **Grid** (`x-dl::grid`): pilih `kind="master"` atau `kind="transactional"`, atau `mode="report"`. Aturan lengkap: `grid.md`.
- **Form** (`x-dl::form`): `kind="master|document"`, Back ke grid modul (`:back` + `back_history=false`), isian default tidak kosong. Aturan lengkap: `form.md`.
- **Data transaksional** (DRAFT > CONFIRMED > CANCELLED, SAVE/DELETE/NEW/PRINT, readonly, ADJUSTMENT, authority, baris detail 2 state, MODE di print): `transaction-lifecycle.md`.
- **Help** proyek selalu dua bagian, UG (Indonesia) dan TG (English), flow = SVG: `help.md`.
- **Penegak aturan:** `php artisan devlib:lint` memeriksa view terhadap aturan di atas (grid tanpa kind, form dokumen tanpa back, HTML mentah, hex, script inline, isian tanpa nilai awal); Mode Developer (F10) menampilkan temuan komponen saat `APP_DEBUG` menyala. Jalankan sebelum menyerahkan layar. Pengecualian opt-in per berkas: `config('devlib.lint_except') = ['resources/views/x.blade.php' => ['L003']]` (kosong = semua aturan berlaku; alasan ditulis di komentar config).

## Engine (otomasi proses)

Mesin hitung / aturan bisnis memakai kontrak DevLib `Devlib\Engine\*` (opt-in): mode Suggest/Auto/Calc/Flow, hasil `Result`, `EngineRegistry`, kegagalan `EngineException`, serta alur CANCEL + ADJUSTMENT otomatis. Aturan baku dan contoh: `engine.md`.

## Assistant AI dan Chat (model untuk proyek)

Asisten ("AI Assistant" di `x-dl::shell`) dan chat antar-user adalah **model** DevLib, opt-in, adaptor penyimpanan/model diikat proyek. Asisten: tool = Engine, bantuan dikutip dari Help, model bahasa hanya pemilih, riwayat per user (`src/Assistant`, aturan di `assistant.md`). Chat: kanal, pesan, kehadiran, lampiran berpotong, layar `x-dl::chat` (`src/Chat`, aturan di `chat.md`).

## Kebiasaan lintas proyek

- Label layar boleh English; aplikasi satu bahasa menambah terjemahan ke kamus aplikasi (`Lang::text`), pesan validasi di `lang/<kode>/validation.php`. Mata uang tampil kode (`IDR`), bukan simbol.
- Seeder data contoh idempoten (`updateOrCreate` berkunci kode alami), bukan di migrasi; ubah skema = ubah seeder pada perubahan yang sama.
- Perubahan DevLib harus **opt-in** (default lama tetap jalan): proyek yang sudah memakainya tetap pada perilaku lama. Satu opsi config/prop baru + default lama, bukan mengubah perilaku.
- Selesai satu perubahan: commit (repo proyek dan, bila DevLib berubah, repo devlib). Verifikasi tampilan nyata di `http://<proyek>.test`, bukan hanya tes.

## 3. Kalau komponen belum ada / kurang

Ubah **di DevLib, bukan di proyek**:
- Edit di `C:\Users\iwanp\Herd\devlib`, **commit di repo itu** (git proyek tidak melihatnya). Karena symlink, semua proyek pemakai langsung ikut.
- Paket berdiri sendiri: `src/` tidak boleh memakai `App\...`, model, atau service proyek mana pun. Data masuk lewat props/kontrak. Helper global milik paket di `src/helpers.php`. Rinci arah warisan dan cara berpikir OO: `inheritance.md`.
- Komponen baru: kelas turunan `DevlibComponent` (`tag()`, `source()`, `jsClass()` bila ada JS) + view Blade; JS satu kelas per file di `resources/assets/js/components/`.

## Jangan

- HTML/Tailwind mentah, `<script>` inline, hex warna di layar proyek.
- Menyalin file aset DevLib ke proyek.
- Menambah komponen UI di proyek bila sama bisa jadi objek DevLib.
