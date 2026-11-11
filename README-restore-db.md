# Restore database ke laptop ini: indeks

Kerjakan **berurutan**: TAHAP-1, lalu TAHAP-2, lalu TAHAP-3. Selesaikan dan centang satu tahap sebelum pindah ke tahap berikutnya. Jangan melompat.

| Tahap | Berkas | Isi | Syarat selesai |
|---|---|---|---|
| 1 | `TAHAP-1-restore-database.md` | impor `.sql` ke MySQL | jumlah tabel semua database cocok |
| 2 | `TAHAP-2-user-dan-hak.md` | user aplikasi dan hak aksesnya | tiap user bisa login dan hanya melihat databasenya |
| 3 | `TAHAP-3-env-dan-proyek.md` | `.env`, kode proyek, uji situs | situs `.test` terbuka tanpa error DB |

## Keadaan saat dokumen ini ditulis (2026-11-03)

- **Tahap 1, 2, dan 3 sudah dikerjakan dari laptop sumber (LENOVO-M2) lewat SSH pada 2026-11-03.** Semua database terimpor, user dan hak dibuat, `.env` dipasang, link Herd dibuat, `composer install` dan `npm` berjalan, dan berkas di luar git dikirim. Keenam situs `.test` menjawab sama dengan laptop sumber (devlib 200; jays, adminku, stoku, tmerp, tmmockup 302 ke halaman login).
- Tugas Anda di setiap tahap: **memeriksa**, bukan mengulang. Ulangi hanya bila ada yang kurang.
- **Satu hal masih menunggu manusia:** arsip besar di flashdisk (lihat bagian "Berkas di luar git" di `TAHAP-3-env-dan-proyek.md`). Isi dan cara pakainya ada di `L1flashdisk\CARA-PAKAI.md` pada flashdisk.

## Aturan kerja

- Bahasa jawaban: Indonesia, pendek, jujur.
- Berkas `.sql` berisi data asli: jangan masuk git, jangan diunggah, jangan dilampirkan ke email.
- Jangan menyentuh database `sstock` yang sudah ada.
- Jangan menghapus database atau berkas tanpa persetujuan eksplisit pemilik.
- Sandi tidak ada di dokumen ini. Sandi user aplikasi diambil dari `.env` laptop sumber (diberikan pemilik), jangan ditebak dan jangan dicatat di berkas yang masuk git.
- Alat: `"C:\Program Files\MySQL\MySQL Server 8.4\bin\mysql.exe"`, login `root` tanpa sandi.

## Gambaran dua laptop

| | Laptop sumber | Laptop ini (JIP-L1) |
|---|---|---|
| Server | MySQL 8.0 port 3306, plus MariaDB 3307 (khusus `tm2crp`) | MySQL 8.4.9, service Windows `MySQL84`, port 3306 |
| Herd | ada | ada (PHP 8.5.12) |
| Folder proyek | devlib, tmerp, mockuptm, jays, stoku | hanya devlib, stoku, tmerp |

## Pembersihan setelah semua tahap selesai (minta persetujuan pemilik dulu)

- Hapus kunci SSH `claude-devlib-to-laptop2` dari `C:\ProgramData\ssh\administrators_authorized_keys`.
- Hapus skrip bantu (`impor.bat`, `imp.bat`, `old.bat`, `ddt.bat`, `v.sql`) dan, bila sudah tidak diperlukan, seluruh folder `C:\db-dump` (berisi data asli).
