# CLAUDE.md: DevLib (jo/devlib)

> Aturan kerja dari Jo (pemilik). Dibaca otomatis oleh setiap sesi yang berjalan di folder ini.
> Bahasa jawaban ke Jo: Indonesia, pendek, jujur. Jo cepat lelah dengan laporan panjang: sebut hasil, lalu keputusan yang ia butuhkan (ya / tidak / pilih A atau B).

## 1. Tujuan DevLib
DevLib menciptakan **aturan baku dan object untuk DIWARISKAN** ke proyek lain. Setiap keputusan diuji dua pertanyaan: (1) aturan baku atau object yang diwariskan? (2) dipakai proyek nyata? Jika tidak, bukan tempatnya.
Aturan sumber ada di `skills/devlib/*.md` (mulai `SKILL.md`; membangun object: `object.md`). Katalog menampilkannya; jangan menduplikasi.

## 2. Sebelum mengubah apa pun yang terlihat (UI/UX, warna, menu, tema, halaman katalog)
1. **PREVIEW dulu.** Tampilkan tangkapan layar (sebelum, dan sesudah bila mungkin) + penjelasan singkat: apa yang berubah, di halaman/menu mana, dan apa yang TIDAK berubah. Kerjakan setelah Jo menyetujui. Tidak setuju: batalkan.
2. **Banyak kemungkinan sasaran = sebut daftarnya dan tanya mana.** Contoh: ada dua menu (Layer Menu dan Menu Classic), banyak halaman, banyak tema, default situs vs pengaturan browser Jo. Jangan memilih sendiri.
3. **Ulangi permintaan dalam satu kalimat** (apa, di mana, berlaku untuk siapa, apa yang tidak diubah) sebelum mengerjakan.
4. **Uji di tampilan yang Jo lihat:** Chrome Jo (Claude in Chrome, tab baru) selain browser bawaan. Pengaturan tersimpan di browser Jo (`localStorage dl-theme`, `dl-density`, `dl-font-scale`, cookie `dl_menu`, `dl_lang`) menimpa bawaan situs, jadi periksa itu. Jangan menilai dari "tes lulus" saja.
5. Halaman di `Catalog::APPROVED` (terutama yang bertanda DIKUNCI, mis. `p5-grid-tx`) tidak diubah tampilan/perilakunya tanpa persetujuan eksplisit Jo.

## 3. Versi lama dikubur (prinsip Jo, berlaku untuk SEMUA: menu, object, engine, layar, aturan, apa pun)
- Setelah sesuatu naik versi, versi lama **ditinggalkan sebagai riwayat** (git). Ia tidak dijadikan kanvas, acuan, contoh, bahan perbandingan, atau topik pembahasan atas permintaan Jo. Yang lama dikubur dan tidak dibicarakan lagi.
- **Setiap permintaan Jo berlaku pada versi terbaru.** Ragu mana yang terbaru: tanya. Jangan memilih yang lama, dan jangan menguji atau mengubah yang lama "sekalian".
- Versi lama hanya dibangunkan bila Jo berkata **"bangunkan"** dan menyebut yang mana; lalu diambil dari riwayat git.
- Kode versi lama yang masih hidup tidak dihapus sendiri: daftarkan ke Jo (jalur dan pemakainya) dan tunggu persetujuan, karena penghapusan butuh izin eksplisit (bagian 4).
- Contoh: **Layer Menu (panel kiri) adalah menu utama (v2)**; Menu Classic adalah versi lama.

**Aturan default dan versi (Jo, 2026-10-21):**
1. **Hanya SATU default**, dan default = **versi terakhir** (nomor tertinggi). Punya v1, v2, v3: yang default v3.
2. Versi lama = **kode sejarah (historical code)**: dikubur di riwayat git (diberi tag `history/<nama>-vN` agar mudah dibangunkan), tidak tinggal sebagai kode aktif, **tidak jadi default, dan tidak ada saklar/pilihan untuk berpindah antar versi**.
3. Nama object/komponen/menu tetap satu (tanpa akhiran versi seperti `V2`/`V3` pada kode aktif); nomor versi dicatat di `CHANGELOG.md`/tag.
4. **Tidak ada "mundur".** Bila Jo tidak suka versi terakhir (vN): ambil versi sebelumnya dari riwayat, **duplikasikan menjadi versi baru (vN+1)**, dan vN+1 menjadi default. vN tetap tercatat sebagai riwayat. Nomor versi selalu maju, dan default selalu yang terbaru.
5. Berlaku untuk semua: menu, object, engine, layar, aturan.
6. **Yang Jo nyatakan SALAH atau ditolak (belum pernah disetujui) DIHAPUS TOTAL**, langsung, tanpa tag `history/` dan tanpa disimpan sebagai riwayat. Tag riwayat hanya untuk versi lama yang pernah dipakai atau disetujui lalu digantikan. Contoh: bentuk Form Approval dengan login simulasi dan OTP ditolak, jadi dihapus; Menu Classic adalah versi lama yang pernah dipakai, jadi dikubur.

## 3b. Scope: hanya devlib (perintah Jo, 2026-10-21)
- **Jangan menyentuh proyek lain**: tmerp (termasuk mockup.test, yang adalah aplikasi tmerp) dan sstock. Jangan mengubah berkasnya, menjalankan tes, artisan, atau perintah di sana, dan jangan membuka mockup.test.
- Satu-satunya yang boleh: **membaca** berkas sumber proyek lain (`git grep`/grep) untuk memastikan perubahan di devlib tidak merusak pewaris. Ada satu hit pemakaian = jangan hapus/ubah yang dipakai itu, laporkan ke Jo.
- Perubahan yang butuh penyesuaian di proyek lain: laporkan ke Jo, jangan dikerjakan.

## 4. Git dan penghapusan
- **Commit dan push ke `origin/main` OTOMATIS sebagai cadangan** (izin berdiri Jo, 2026-10-22: "push always as backup, don't ask again"): setelah satu pekerjaan selesai dan tesnya hijau, commit lalu push tanpa bertanya. **Jangan force-push, jangan menulis ulang riwayat, jangan menghapus berkas/folder/branch** tanpa persetujuan eksplisit Jo.
- **Cacat yang ditemukan SELALU diperbaiki** (izin berdiri Jo, 2026-10-22: "always fix it"): kode salah, tes merah, dokumen usang: perbaiki lalu laporkan, jangan bertanya dulu. Perubahan desain/tampilan BARU atau alur baru tetap lewat preview dan persetujuan (bagian 2).
- Banyak sesi menulis di folder yang sama: jalankan `git status` dulu; commit hanya jalur sendiri `git commit --only -m "..." -- <jalur>` (berkas baru: `git add -N` dulu); cek `git show --stat`. Pesan commit Indonesia, akhiri dengan trailer yang ditetapkan sesi.
- Tag rilis hanya atas keputusan Jo (lihat `docs/versioning.md`).

## 5. Keamanan dan netralitas
- Netral: tanpa kode `App\` milik proyek di `src/`, tanpa nama client/proyek, tanpa nama asli atau login orang sungguhan di berkas (contoh fiktif). Perubahan library **opt-in**: perilaku proyek lama tidak berubah; periksa pemakaian di proyek pewaris sebelum menghapus.
- Assistant AI: gaji, payroll, formula HPP, data pribadi, dan orang terlindung tidak pernah disentuh (`skills/devlib/assistant.md`).
- Jangan memasukkan kata sandi atau kredensial ke bidang mana pun.

## 6. Cara kerja
- Pekerjaan panjang atau berisiko dikerjakan di **sesi terpisah** agar percakapan utama bebas untuk diskusi; laporkan ringkas, lalu tutup sesi yang selesai.
- Baca dokumen/kode dulu, jangan menebak. Ragu atau lebih dari satu tafsir: sebut pilihannya dan tanya.
- Iterasi kecil: satu layar atau fitur per waktu.
- Uji sendiri: `cd site; php artisan test`, `php artisan devlib:lint` (0 error), `php artisan devlib:manifest --check`, dan buka halaman di browser. Windows: `php` lewat PowerShell (`C:\Users\iwanp\.config\herd\bin\php.bat`); `--filter=A|B` rusak di php.bat, satu filter per perintah.
