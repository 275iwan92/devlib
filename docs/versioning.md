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
