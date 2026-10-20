# Situs acuan DevLib (`site/`)

Aplikasi Laravel mandiri yang memperagakan library [DevLib](../README.md) (`jo/devlib`, komponen `x-dl::*`). Situs ini bukan produk: ia dipakai untuk melihat, mencoba, dan memeriksa komponen sebelum dipakai di proyek lain.

## Isi

- **Katalog komponen**: halaman beranda mendaftar modul dan komponen; tiap halaman `/lib/{slug}` menampilkan komponen hidup beserta kode pemakaiannya (ikon `</>`).
- **Demo layar**: contoh form, grid, laporan, dasbor, dokumen cetak, login, papan Gantt/Kanban, dan lain-lain, semuanya disusun dari objek `x-dl::*` (`resources/views/demos/`).
- **Mode Developer**: tekan **F10** (hanya untuk pengguna ber-security Developer/Administrator) untuk melihat asal-usul tiap objek; temuan aturan DevLib per komponen ikut tampil saat `APP_DEBUG=true`.
- **Generator** (`/generator`): form permintaan layar baru (Master / Transactional / Custom). Form tidak menulis kode; ia menyimpan permintaan di database untuk dikerjakan agen (`php artisan gen:requests`).
- **Halaman pendukung**: ringkasan eksekutif (`/summary/{page}`), catatan agen (`/history`, `/agent-log`), QC cepat (Approved / Need improvement per halaman).

## Prasyarat

- PHP 8.3 atau lebih baru (Laravel Herd untuk Windows/macOS sudah menyertakan PHP dan Composer)
- Composer 2
- Node.js dan npm
- MySQL 8 yang berjalan di `127.0.0.1:3306`

## Memasang dari klon bersih

Situs memakai paket `jo/devlib` lewat *path repository* `../` (lihat `repositories` di `composer.json`), jadi folder `site` harus tetap berada **di dalam** klon repo devlib (jangan dipindah keluar):

```
devlib/            <- klon repo (berisi composer.json paket)
├── src/  resources/  skills/
└── site/          <- situs ini
```

1. Siapkan database kosong dan pengguna MySQL-nya (sekali saja, ganti `PASSWORD`):

   ```sql
   CREATE DATABASE devlib CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'devlib_usr'@'localhost' IDENTIFIED BY 'PASSWORD';
   GRANT ALL PRIVILEGES ON devlib.* TO 'devlib_usr'@'localhost';
   ```

   Nama pengguna bebas; yang penting sama dengan `DB_USERNAME` di langkah berikutnya.

2. Dari folder situs, salin dan isi `.env`:

   ```powershell
   cd site
   copy .env.example .env
   ```

   Buka `.env`, isi `DB_USERNAME` dan `DB_PASSWORD` (`DB_DATABASE=devlib`, `DB_HOST=127.0.0.1`, `DB_PORT=3306` sudah bawaan). Baris lain dibiarkan.

3. Pasang dan bangun:

   ```powershell
   composer install
   php artisan key:generate
   php artisan migrate --force
   npm install --ignore-scripts
   npm run build
   ```

   Urutan resminya ada di `composer.json` (skrip `setup`: `composer install`, salin `.env`, `key:generate`, `migrate --force`, `npm install --ignore-scripts`, `npm run build`). Setelah `.env` terisi, `composer setup` menjalankan semuanya sekaligus.

4. Jalankan:

   ```powershell
   php artisan serve
   ```

   lalu buka <http://127.0.0.1:8000>. Alternatif: `composer dev` menjalankan server, antrean (`queue:listen`), dan Vite (`npm run dev`) bersama-sama (daftarnya: `php artisan dev:list`).

## Database

Situs memakai MySQL dengan database `devlib` (sesi, cache, dan antrean di `.env.example` juga memakai tabel database). `php artisan migrate` membuat tabel berikut:

| Tabel | Isi |
|---|---|
| `sec_user` | akun login situs (peran, status aktif, flag developer, bahasa) |
| `dev_item`, `dev_rating` | butir ringkasan eksekutif dan penilaiannya |
| `dev_note` | catatan Agent History |
| `qc_review` | keputusan QC per halaman (dipakai juga oleh `php artisan qc:reviews`) |
| `gen_request`, `gen_request_object` | permintaan Generator |

ditambah tabel bawaan Laravel (`users`, `sessions`, `cache`, `jobs`, dan seterusnya). Setelah `migrate` pada database baru, tabel-tabel ini kosong (tidak ada seeder).

## Login

Bawaan `DEVLIB_LOGIN_BYPASS=true` di `.env`: situs langsung masuk sebagai akun contoh pertama, tanpa layar login. Untuk mencoba alur login contohnya, ubah menjadi `DEVLIB_LOGIN_BYPASS=false`, lalu masuk di `/login` dengan akun contoh **admin / admin** (didefinisikan di `config/devlib.php`, berperan Administrator dengan flag developer). Akun di tabel `sec_user` juga bisa masuk bila ada. Login ini hanya contoh tampilan untuk proyek lain, bukan pengamanan.

## Membuka lewat Laravel Herd (`devlib.test`)

Herd menyajikan folder yang ditautkan di `%USERPROFILE%\.config\herd\config\valet\Sites`. Tautan harus menunjuk ke folder `site` (bukan akar repo). Di Windows, symlink butuh hak administrator; pakai *junction* yang tidak memerlukannya (jalur di bawah contoh, sesuaikan dengan lokasi klon Anda):

```powershell
New-Item -ItemType Junction -Path "$HOME\.config\herd\config\valet\Sites\devlib" -Target "C:\Proyek\devlib\site"
```

Situs lalu terbuka di <http://devlib.test>. Bila memakai Herd, ubah `APP_URL` di `.env` menjadi `http://devlib.test`.

## Perintah penting

Jalankan dari folder `site`:

| Perintah | Fungsi |
|---|---|
| `php artisan test` | seluruh tes; aman, memakai SQLite dalam memori (lihat `phpunit.xml`), tidak menyentuh MySQL |
| `php artisan devlib:lint [jalur]` | memeriksa view Blade terhadap aturan DevLib (grid tanpa `kind`, HTML mentah, hex warna, `<script>` inline, dan seterusnya); bawaan memeriksa `resources/views`; `--json`, `--strict`, `--level=` tersedia. Berakhir dengan kode gagal bila ada temuan error, termasuk pada demo situs ini sendiri |
| `php artisan devlib:manifest` | membuat ulang `../skills/devlib/components.md` (daftar tag, prop, berkas komponen); `--check` hanya membandingkan |
| `php artisan build:catalog` | menyusun ulang `resources/data/build.json` (papan Build Progress) dari katalog dan engine terdaftar; jalankan ulang setelah katalog atau engine berubah |
| `php artisan qc:reviews` | daftar keputusan QC: Need improvement terbuka, siap rilis, Approved terbaru; `--resolve=<id> --by=<nama>` menutup satu catatan |
| `php artisan gen:requests [RQ-0004]` | daftar permintaan Generator yang terbuka, atau isi satu permintaan |

## Catatan Windows

- `php artisan test --filter=A|B` rusak bila `php` dipanggil lewat `php.bat` (Herd), karena karakter `|` ditafsirkan shell. Jalankan satu `--filter` per perintah.
- Tes pertama setelah ada view baru kadang gagal sekali karena berkas view yang terkunci saat dikompilasi; ulangi perintahnya.
- Bila port 8000 sudah terpakai, jalankan `php artisan serve --port=8010`.

## Struktur singkat

| Folder | Isi |
|---|---|
| `app/Support/` | data dan aturan situs (katalog, Generator, QC, demo) |
| `resources/views/demos/` | satu berkas Blade per demo komponen |
| `packages/mockup-parts/` | paket lokal berisi komponen peraga domain (Sales, Inventory, Production, dan lain-lain); tidak ada halaman katalog yang memakainya dan bundelnya tidak dimuat di tata letak situs |
| `tests/` | tes fitur (kontrak komponen, engine, asisten/chat, lint, halaman katalog) |
| `docs/`, `tools/` | berkas pendukung dan skrip bantu |

Dokumentasi library-nya sendiri (cara memakai komponen di proyek lain, engine, aturan baku) ada di [`../README.md`](../README.md) dan [`../skills/devlib/SKILL.md`](../skills/devlib/SKILL.md).
