# Arah warisan: DevLib membuat, proyek mewarisi

Berlaku untuk SEMUA proyek pemakai (sstock, tmerp, proyek baru), bukan hanya satu. Arah satu jalur: **DevLib -> proyek**. DevLib tidak pernah bergantung pada proyek.

## Aturan

1. **DevLib dulu.** Objek, helper, dan perilaku yang bisa dipakai lebih dari satu layar atau proyek dibuat di DevLib (contoh hidup di `site/`, entri katalog di `site/app/Support/Catalog.php`). Proyek hanya memakai atau menurunkannya.
2. **Paket berdiri sendiri.** `src/` tidak memakai `App\...` (tanpa `use`, `new`, panggilan statis), model, service, atau kamus milik proyek mana pun; menyebutnya di komentar boleh. Kebutuhan data masuk lewat props atau kontrak (interface, kelas dasar). Helper global milik paket ada di `src/helpers.php` (dibungkus `function_exists`). Pakai `\Devlib\Lang::text()`, `\Devlib\Lang::raw()`, `\Devlib\Locale::*` di demo dan view paket.
3. **Opt-in.** Perubahan DevLib tidak mengubah perilaku proyek yang sudah memakainya: satu prop/config baru dengan default lama.
4. **Situs acuan harus tampil mandiri.** `devlib.test` (`site/`) berjalan tanpa kode proyek mana pun; tidak membaca folder proyek lain. Data contoh ada di `site/`.
5. **Contoh baku milik DevLib** (`skills/devlib/examples/`), bukan menyalin dari proyek lain.
6. Mengubah DevLib = edit di repo devlib dan **commit di repo itu**; git proyek tidak melihatnya.

## Cara berpikir object oriented

- Akar `Devlib\Components\DevlibComponent`: `tag()`, `source()`, `lineage()`, `can="modul.aksi"`, terjemahan otomatis prop teks, `jsClass()` bila ada JS. Anak = `extends` induk dan hanya menyatakan yang berbeda; induk dapat fitur baru, semua anak ikut.
- PHP + JS berpasangan: `jsClass()` -> `data-dl-js`; satu kelas JS per berkas di `resources/assets/js/components/`.
- Cari objek terdekat dulu, lalu perluas atau susun. Perilaku untuk >1 layar masuk **induk** (prop/method/hook), bukan salin-tempel.
- Layar = deklarasi, bukan bangunan: tanpa HTML mentah, JS inline, hex warna.

## Kode proyek (main code)

- Main code = daftar panggilan yang terbaca seperti alur bisnis; logika, rumus, query, markup tinggal di kelas/service/library/objek `x-dl::*`.
- Satu kelas satu tanggung jawab, method pendek dan bernama; modul tidak bergantung pada kode proyek lain.
- Traceable: kegagalan = exception bernama dengan konteks, bukan `false`/`null` diam-diam; tanpa `catch` kosong; tanpa magic string (konstanta/enum); tipe parameter dan return.
- Yang bertambah = test kecil per kelas.

## Gejala umum

- `Call to undefined function raw()` di demo -> helper hidup di proyek; pindahkan yang netral ke `src/helpers.php`.
- `Class "App\..." not found` di demo -> komponen paket memanggil kelas proyek; balik arahnya: DevLib menerima data lewat props, proyek yang mengisinya.
