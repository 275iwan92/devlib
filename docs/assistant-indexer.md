# Rencana: pengindeks latar belakang untuk Asisten

Status: **rancangan, belum diimplementasikan.** Kebijakan data yang berlaku: `skills/devlib/assistant.md` ("Cakupan data dan sensitivitas"). Kode dasar: `src/Assistant`.

## 1. Tujuan

Asisten yang dipasang di program (ERP) makin cepat menemukan menu, layar, dan bantuan karena program membacakan isi dirinya secara berkala, bukan karena model dilatih. Pengguna bertanya "di mana menu buat PO" atau "order masuk hari ini"; pertanyaan di luar program tetap dijawab kalimat tetap (`OUT_OF_SCOPE`). Pengindeks bekerja pelan di latar belakang dan tidak mengganggu pemakai.

## 2. Apa yang diindeks, apa yang tidak

Diindeks (metadata saja): nama menu dan letaknya, label layar, nama kolom/field, judul dan langkah Help, nama fasilitas/laporan, frasa Tool (bukan hasilnya).

**Tidak** pernah diindeks:

- baris data bisnis (isi dokumen, nama pelanggan, angka transaksi);
- gaji dan payroll dalam bentuk apa pun (lihat tingkat d); sumber yang cocok `DataPolicy::forbids()` dilewati dan dicatat sebagai "dilewati", bukan sebagai galat;
- nilai rupiah (tingkat c): yang diindeks hanya NAMA kolom/laporan bernilai, bukan isinya; entri itu diberi syarat `value` (lihat 5).

Hasil angka tetap datang langsung dari `Tool` saat ditanya (membaca langsung, menegakkan hak penanya), tidak dari indeks.

## 3. Kontrak generik DevLib dan bagian program inang

DevLib menyediakan antarmuka dan pelari; program menyediakan sumber dan penyimpanan.

```php
interface IndexSource {                 // diikat program: satu per jenis (menu, layar, Help, Tool)
    public function key(): string;                          // 'menu', 'help', ...
    public function entries(): iterable;                    // IndexEntry: id, text, words, needs (hak), sensitivity, url?
}
final class IndexEntry { public function __construct(
    public string $id, public string $text, public string $hash,       // hash isi: dasar "hanya yang berubah"
    public ?string $module = null, public int $minLevel = 0,           // syarat hak, disimpan bersama entri
    public Sensitivity $sensitivity = Sensitivity::Quantity) {} }
interface IndexStore {                  // diikat program (tabelnya milik program)
    public function hashes(string $source): array;          // id => hash tersimpan
    public function put(IndexEntry $e): void;  public function forget(string $source, array $ids): void;
    public function search(array $tokens, Asker $asker): array;   // SUDAH tersaring hak (lihat 5)
}
final class Indexer { public function pass(IndexSource $s, IndexStore $st, Budget $b): Progress; }   // satu potongan
final class Budget { /* batas waktu per proses, jeda, ukuran potongan */ }
interface UsageLog { public function record(Asker $a, string $q, Reply $r): void; }  // sama dengan HistoryStore + jempol
```

Program menyediakan: implementasi `IndexSource` dari menu/Help/Tool miliknya, `IndexStore` (tabel + pencarian), tempat antrean (queue Laravel prioritas rendah), dan pemicunya. DevLib: kontrak, `Indexer`, `Budget`, penyaring terlarang, tes kontrak.

## 4. "Porsi 10%" yang jujur

PHP tidak menjamin persentase CPU harfiah. Yang bisa dijanjikan adalah **anggaran**, bukan persentase:

- tiap proses dibatasi waktu (mis. 200 ms kerja per potongan) dan jumlah entri;
- setelah satu potongan, jeda minimal (mis. 9x lama kerja, sehingga kira-kira 10% waktu dinding) sebelum potongan berikut dijadwalkan ulang;
- berjalan di antrean prioritas rendah, tidak di permintaan pemakai;
- hanya memproses entri yang hash-nya berubah; penghapusan lewat `forget`;
- pemicu dibatasi: paling sering sekali per jam per program (kunci cache/lock), ditambah satu kali setelah rilis; program dibuka berkali-kali tidak melipatgandakan kerja;
- bila satu potongan melewati anggaran atau galat, berhenti dan lanjut pada giliran berikutnya (idempoten).

Yang dipasang untuk jujur: metrik waktu kerja per hari dan jumlah potongan, supaya "10%" bisa dibuktikan atau dikoreksi, bukan diklaim.

## 5. Keamanan

- Tiap entri menyimpan syarat hak (`module`, `minLevel`) dan `sensitivity`; **penyaringan terjadi saat bertanya**, di `IndexStore::search($tokens, $asker)` lewat `Asker::can`/`level`/`ValueAsker`, bukan saat membangun indeks. Entri yang tidak boleh dilihat penanya tidak ikut ke hasil, tidak ikut ke calon bagi model bahasa, dan tidak ikut ke pesan "tidak ada".
- Sumber terlarang tidak pernah masuk: `Indexer` menolak entri yang `DataPolicy::forbids()` (kunci/modul/judul/path). Hanya mencocokkan teks yang dinyatakan program; labeli dengan benar adalah kewajiban program (jujur, sama seperti Bagian "Cakupan data").
- Tidak ada kebocoran lintas hak: indeks bersama (satu per program), tetapi hasil per penanya. Tes: penanya tanpa modul X tidak pernah mendapat entri X; penanya non-keuangan tidak mendapat entri `value`.
- Indeks tidak berisi data bisnis, jadi bocornya indeks tidak membocorkan transaksi; tetap perlakukan sebagai internal.

## 6. Alur "makin pandai"

1. **Catatan pemakaian:** pertanyaan tak terjawab (`UNKNOWN`), jempol turun/naik, layar yang akhirnya dipilih pengguna (sudah ada di `HistoryStore`; ditambah pencatatan layar yang dibuka setelah jawaban).
2. **Saran:** pekerjaan berkala mengelompokkan pertanyaan tak terjawab yang sering muncul dan menyusun SARAN: tambah kata cari (sinonim) pada layar X, atau topik Help baru. Saran adalah data, bukan perilaku.
3. **Persetujuan orang:** antrean saran di layar admin program; admin menyetujui/menolak/mengubah. Hanya yang disetujui menjadi `words` layar atau Help tambahan (disimpan program, dapat dicabut).
4. **Berlaku** pada pengindeksan berikutnya.

Mencegah "belajar yang salah": tidak ada perubahan otomatis; satu jempol tidak cukup (ambang jumlah pengguna berbeda); saran yang menyentuh kata terlarang dibuang; setiap saran yang berlaku punya jejak (siapa, kapan) dan tombol cabut; pertanyaan berisi data pribadi tidak disalin mentah ke saran (hanya kata cari yang dinormalkan).

## 7. Tahapan

| Tahap | Isi | Selesai bila | Kerja |
|---|---|---|---|
| 1 | `IndexSource`, `IndexEntry`, `IndexStore`, `Indexer`, `Budget`, pemicu terbatas; sumber menu + Help; pencarian tersaring hak dipakai `Assistant` sebelum pencocokan sekarang | tes kontrak (hash tak berubah = tak diproses; anggaran dihormati; terlarang tak masuk; hak tersaring); halaman katalog; ditempel di satu program contoh | sedang |
| 2 | `UsageLog`, penyusun saran, antrean persetujuan (kontrak + layar admin contoh) | saran muncul dari log, tidak ada yang berlaku tanpa persetujuan, bisa dicabut; tes | sedang |
| 3 | pencocokan makna (embedding) opsional lewat adaptor, memakai indeks yang sama | model/embedding mati = jatuh ke pencocokan kata; tak ada data di luar metadata; tes dengan adaptor palsu | besar (dan opsional) |

## 8. Risiko dan keputusan untuk Jo

Risiko: label program salah (modul gaji berlabel biasa) lolos karena DevLib hanya mencocokkan teks; beban di database besar bila program memberi sumber yang boros; saran yang salah bila persetujuan dilewati; "10%" disalahartikan sebagai jaminan CPU.

Perlu keputusan Jo:

1. Setuju bahwa "10%" = anggaran waktu + jeda (bukan jaminan CPU)?
2. Tempat antrean standar: queue Laravel milik program, atau cukup perintah artisan terjadwal?
3. Siapa yang menyetujui saran (admin program, kepala bagian, atau Jo)? Perlu ambang jumlah pengguna?
4. Apakah entri bersensitivitas `value` boleh diindeks sebagai NAMA (tanpa isi), atau dikeluarkan sama sekali?
5. Tahap 3 (embedding): perlu atau cukup tahap 1-2? Bila perlu, penyedia model mana?
6. Perlu tes wajib di tiap program yang memastikan tidak ada sumber HR/gaji berlabel biasa?
