# DevLib: siklus data transaksional (mode dan tombol)



Berlaku untuk SEMUA data transaksional, baik master-detail (dokumen dengan baris) maupun single (satu record). Mode memakai nama persis ini.

**DRAFT**
- Record yang baru dibuat SELALU `DRAFT`.
- Tombol yang harus ada di DRAFT (perilaku lama): **SAVE, DELETE, NEW, PRINT**. Siklus ketat (`:strict`, `grid-form-flow.md`): record baru = SAVE + UNDO; Draft tersimpan = DELETE, EDIT, PRINT, CONFIRM (tanpa NEW); Delete memakai dialog Yes/No.
- Untuk memudahkan pengguna, semua isian diberi **DEFAULT DATA, tidak kosong** (tanggal hari ini, pihak/lokasi/satuan bawaan, nilai awal yang masuk akal); kosong hanya untuk isian tertentu yang memang harus dipilih pengguna.
- **DELETE** = record benar-benar hilang dari sistem (hanya di DRAFT).

**CONFIRMED**
- Menuju CONFIRMED lewat tombol **CONFIRM** (atau **SUBMIT**; pilih satu kata konsisten per proyek, bawaan: Confirm).
- Setelah CONFIRMED record **readonly** (tidak bisa diubah).

**CANCELLED**
- Pada record readonly (CONFIRMED) harus ada tombol **CANCEL** = record dibatalkan; mode menjadi `CANCELLED`.
- Record CANCELLED **selalu tetap tersimpan** (beda dengan DELETE, yang menghapus total).
- Bila pembatalan membuat data tidak cocok (mis. stok, saldo), sistem membuat record **ADJUSTMENT pembalik secara OTOMATIS**. Di DevLib: dokumen implement `Devlib\Engine\Adjustable`, pembatalan lewat `Devlib\Engine\Cancellation::run(...)` (satu transaksi), rinci di `engine.md`.

**PRINT**
- Print out selalu memuat tulisan **MODE** record: `DRAFT` / `CONFIRMED` / `CANCELLED`.

**AUTHORITY (persetujuan atasan)** (diperbarui 2026-10-21: field terpisah)
- Status dokumen (`DRAFT` / `CONFIRMED` / `CANCELLED`) dan status approval (`APPROVED` / `HOLD` / `REJECTED`) adalah **dua field terpisah**. Approval hanya ada saat dokumen `CONFIRMED` (belum diputuskan = AWAITING, bukan status). Kombinasi sah: Confirmed + Approved, Confirmed + Hold, Confirmed + Rejected. `CANCELLED` dan `DRAFT` tidak punya approval.
- Dokumen masuk persetujuan selalu mulai dari CONFIRMED (jadi baca-saja). Authority **bisa bertingkat** (lebih dari satu level, berurutan).
- Pembuat boleh **CANCEL** hanya selama approval belum diputuskan; setelah HOLD/APPROVED/REJECTED tidak. Pemeriksa: HOLD sementara (lanjut Approve/Reject); APPROVED hanya bisa diubah menjadi REJECTED (alasan wajib).
- REJECTED: disembunyikan dari daftar biasa (hanya tab Rejected); pembuat boleh **DUPLICATE** lalu memperbaiki; REJECTED → APPROVED hanya oleh pemeriksa dengan **OTP** yang diterbitkan administrator (administrator sendiri tidak bisa mengubahnya).

**Baris detail (form master-detail, `x-dl::lines :row-state="true"`)** (aturan pemilik, 2026-10-20; sudah didukung DevLib)
- Setiap baris punya 2 STATE. **State awal** (baru diisi): tombol **SAVE** dan **UNDO**. **State telah ditulis**: tombol **EDIT** dan **DELETE**.
- Selama **DRAFT** tombol baris selalu ada; saat **CONFIRMED** dan **CANCELLED** (readonly) tombol baris **HIDDEN** dan seluruh form (header dan baris) baca-saja berwarna.
- Opt-in lewat prop `:row-state="true"` (tanpa itu `x-dl::lines` memakai tombol lama: salin + hapus). Baris tersimpan terkunci sampai EDIT; UNDO membuang baris baru atau mengembalikan isi sebelum EDIT; data semua baris tetap terkirim saat Save form.

**Data MASTER**
- TIDAK ada DRAFT/CONFIRMED. Hanya `ACTIVATED` / `DEACTIVATED` (nama lain: `ENABLE` / `DISABLE`). Di grid master ini adalah Aktif/Nonaktif.
- Data master dipakai transaksi, jadi tidak dihapus biasa: yang ada Deactivate (disembunyikan dari pilihan). **Delete** hanya administrator dan hanya selama belum ada transaksi yang memakainya (`:deletable`).

Di DevLib: `<x-dl::form kind="document" :pipeline="true" :status="'draft|confirmed|cancelled'">` menyediakan tombol dan cetakan bermode ini; authority bertingkat = `:approval-levels` (1-3), `:approval-at`, `:can-approve`. Grid-nya: kolom Status `badge` (lihat Tipe grid).

Catatan: proyek yang perilakunya berbeda dari aturan ini mencatat selisihnya di skill proyek itu sendiri, bukan di DevLib.
