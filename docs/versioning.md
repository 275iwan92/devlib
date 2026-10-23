# Kebijakan versi DevLib

DevLib (`jo/devlib`) memakai [Semantic Versioning 2.0](https://semver.org/lang/id/)
dengan tahap pengembangan awal `0.y.z`. Riwayat perubahan ada di
[`CHANGELOG.md`](../CHANGELOG.md).

## Format dan sumber versi

- Versi dimulai dari **`0.1.0`** (bukan `0.0.x`) dan baru naik ke `1.x` bila
  daftar periksa di bawah terpenuhi.
- Tag git berformat **`v0.1.0`**, beranotasi (`git tag -a`), satu baris ringkasan.
- Versi **hanya** diambil dari tag git. `composer.json` sengaja tidak punya
  kolom `version` (Composer menyarankan begitu); jangan ditambahkan.
- `license` di `composer.json` tetap `proprietary`, selaras dengan berkas
  [`LICENSE`](../LICENSE).

## Versi yang tampil di layar (keputusan Jo 2026-10-23)

Satu bentuk, satu pembantu: `Devlib\AppVersion`, ditampilkan `x-dl::env-badge part="version"` di judul logo (kecil, pudar; di menu kiri `x-dl::layer-menu` dan halaman login aplikasi).

| Tempat | Tampil | Sumber | Tooltip |
| --- | --- | --- | --- |
| Pustaka DevLib (situs katalog) | `v0.6.0` | tag rilis terakhir `v*` (`git describe`) | `v0.6.0 + 24 commits` |
| Aplikasi, tahap alpha | `ver 0.433 A` | `0.` + jumlah commit git | `alpha` |
| Aplikasi, tahap beta | `ver 0.452 B` | sama | `beta` |
| Aplikasi, calon rilis | `ver 0.470 RC` | sama | `release candidate` |
| Aplikasi, rilis (UAT lulus) | `ver 1.0.0` | tag rilis terakhir aplikasi, tanpa huruf | `release v1.0.0` |

- Tahap = `APP_STAGE` (`alpha` | `beta` | `rc` | kosong) di `.env`, dibaca lewat `config('devlib.stage')` (aplikasi menulis `'stage' => env('APP_STAGE', 'alpha')` di `config/devlib.php`; kode tidak memanggil `env()` langsung). Nilai lain dianggap kosong (rilis). Alpha = dibangun dan dicoba tim sendiri; beta = UAT berjalan dengan pengguna nyata dan fitur dibekukan.
- Jumlah commit naik sendiri tiap commit; tidak ada berkas yang disunting. `AppVersion` menjalankan `git rev-list --count HEAD` pada jalur tetap aplikasi dan menyimpan hasilnya di `storage/framework/cache` dengan kunci waktu ubah `.git/logs/HEAD`, jadi git hanya dijalankan sekali per commit baru. Tanpa git (mis. server yang hanya menerima berkas) dipakai berkas `VERSION` berisi `0.NNN`, lalu `0.0`.
- Aplikasi mengikat `devlib.version` di `AppServiceProvider`: `app()->bind('devlib.version', fn () => \Devlib\AppVersion::of(base_path()));` dan, bila perlu, memasang `<x-dl::env-badge part="version" variant="version" />` di halaman login. Pustaka: `AppVersion::library(base_path('..'))`.
- Peralihan tahap: ganti `APP_STAGE` di `.env` server. Rilis: kosongkan `APP_STAGE` dan beri tag aplikasi `1.0.0`.

## Arti angka selama 0.x

| Naik | Boleh memuat | Contoh |
| --- | --- | --- |
| minor `0.y.0` | fitur baru **dan** perubahan yang merusak antarmuka | tag komponen `x-dl::*` diganti nama, prop dihapus/diubah arti, kontrak `Devlib\Engine\*` / `Devlib\Assistant\*` / `Devlib\Chat\*` berubah, kunci `Lang` diganti |
| patch `0.y.z` | perbaikan saja, **tanpa** perubahan antarmuka | bug tampilan, perbaikan JS/CSS, tes, dokumen |

Antarmuka yang dihitung: tag dan prop komponen (daftar resmi:
`skills/devlib/components.md`, dihasilkan `php artisan devlib:manifest`),
kontrak engine, kunci `Lang`, opsi `config/devlib.php`, dan perintah artisan
(`devlib:lint`, `devlib:manifest`, `qc:reviews`).

Setiap perubahan yang merusak antarmuka wajib dicatat di `CHANGELOG.md` pada
bagian **Diubah** atau **Dihapus** dengan awalan **Merusak:**, agar proyek
pemakai tahu sebelum menaikkan konstrain.

## Cara proyek memakai

**Sekarang** — semua proyek memakai repositori path dan `@dev`:

```json
"repositories": [{ "type": "path", "url": "../devlib" }],
"require": { "jo/devlib": "@dev" }
```

Ini mengikuti isi folder `../devlib` apa adanya; tidak ada penanda versi stabil.

**Jalur peningkatan** (setelah ada tag): proyek boleh pindah ke versi bertag
lewat repositori VCS (privat, butuh akses GitHub):

```json
"repositories": [{ "type": "vcs", "url": "https://github.com/275iwan92/devlib.git" }],
"require": { "jo/devlib": "^0.1" }
```

Catatan penting: di `0.x`, konstrain caret `^0.1` hanya menerima `0.1.x`
(bukan `0.2`). Ini disengaja: kenaikan minor boleh merusak, jadi proyek harus
menaikkan konstrain secara sadar (`^0.2`) setelah membaca `CHANGELOG.md`.
Tag baru hanya terlihat di proyek bila sudah di-push ke GitHub.

## Versi object dan default

Selain versi library (tag `vX.Y.Z`), sebuah object (komponen, menu, engine, layar, aturan) bisa punya versi sendiri.
Aturan Jo (2026-10-21): **hanya satu default, yaitu versi terakhir**; versi lama dikubur sebagai kode sejarah di
tag `history/<nama>-vN` dan tidak jadi default, acuan, atau pilihan; tidak ada "mundur" (bila versi terakhir tidak
disukai, versi sebelumnya diduplikasi menjadi versi baru dan itu default). Rincian dan cara membangunkan:
`skills/devlib/object.md` bagian 6. Menguburkan komponen publik = perubahan **Merusak** (naik minor selama 0.x).

## Cara merilis

1. Pindahkan butir `[Belum dirilis]` di `CHANGELOG.md` ke bagian versi baru
   bertanggal; kosongkan bagian `[Belum dirilis]`.
2. Pastikan tes situs hijau (`cd site; php artisan test`) dan
   `php artisan devlib:manifest --check` bersih.
3. Commit, lalu `git tag -a vX.Y.Z -m "<satu baris ringkasan>"` pada commit itu.
4. Tag lokal dulu; push tag hanya atas keputusan Jo.

## Pemetaan tonggak ke tag

| Tag | Commit | Keterangan |
| --- | --- | --- |
| tag lama (satu-satunya sebelum ini) | `b9f127b` | penanda titik pemisahan dari proyek induk lama; bukan rilis, dibiarkan |
| `v0.1.0` | `75773cc` (commit yang memuat dokumen ini) | keadaan repo mandiri pertama: komponen, engine, Assistant/Chat, PrintDoc, Lint, manifest, skill modular, situs acuan |
| `v0.2.0` | commit rilis 2026-10-21 | menu katalog 5 bagian + Grid and Form, Technical Guide, kebijakan data dan pengindeks Assistant; **merusak:** `x-dl::master` dan Mode Developer dihapus |
| `v0.3.0` | commit rilis 2026-10-21 | satu default = versi terakhir (Menu Classic dan Dieline v1/v2 dikubur), alur Approval + Form Approval baku, Nested Grid, halaman OO dan Profile, menu profil `without`; **merusak:** `x-dl::shell`, prop `classic`, `:approval`/`:otp-url` dihapus |
| `v0.4.0` | commit rilis 2026-10-22 | Technical Guide lengkap (semua object), engine `LeadTime`, `x-dl::idle-logout` (aman untuk iframe), All Projects di Executive Summary, varian panel Profil, perbaikan kejanggalan field; tidak ada tag/prop yang dihapus, tetapi perilaku `disabled` dan beberapa field berubah (lihat CHANGELOG) |
| `v0.5.0` | commit rilis 2026-10-22 | `x-dl::field.select` prop `add` (Add New), gaya kop + nama badan hukum pada semua cetakan (proporsional di A4), perbaikan baris NPWP kop PPN; semua opt-in, tidak ada yang dihapus |
| `v0.6.0` | commit rilis 2026-10-23 | multi view baku (`Device::view()/os()`, cookie `ui` phone/desktop, `views.md`), tampilan HP lengkap (`x-dl::phone-shell` hamburger melayang + bilah atas, login HP, popup tawaran, form satu kolom, grid kartu, aksi grid/report), pratinjau 'Lihat sebagai', perbaikan cetak; semua opt-in, tidak ada yang dihapus dari 0.5.0 |

Tidak ada tag mundur di riwayat lama: sebelum `b095b3a` (repo mandiri) tidak
ada tonggak yang jelas, hanya rangkaian kerja bersama proyek induk lama.

## Daftar periksa naik ke 1.0.0

- [ ] Antarmuka komponen dibekukan dan terbukti oleh kontrak `devlib:manifest`
      (`skills/devlib/components.md` sinkron dengan kode, tes kontrak hijau).
- [ ] README / panduan pasang mandiri ada dan bisa diikuti dari nol.
- [ ] CI hijau (`.github/workflows`).
- [ ] Keputusan LICENSE final (sekarang `proprietary`; membuka repo = keputusan Jo).
- [ ] Semua proyek pemakai (mis. sstock dan proyek ERP) memakai versi bertag
      tanpa tambalan lokal.
- [ ] `warehouse-desk` tidak lagi mengikat nama tabel milik proyek lain
      (sumber data di prop `source`) (lihat `resources/views/components/warehouse-desk.blade.php`).
- [x] Halaman katalog `p7-assistant` dan `p7-chat-model` sudah disetujui Jo
      (2026-10-20, "untuk saat ini"; tercatat di `Catalog::APPROVED`).
- [ ] `devlib:lint` bersih di situs sendiri.
