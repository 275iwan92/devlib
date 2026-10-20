# Rencana: pengindeks latar belakang untuk Asisten

Status: **tahap 1 selesai (2026-10-20)**; tahap 2 dan 3 belum (tahap 3 tidak akan dikerjakan, lihat 7). Pilot di program inang belum. Kebijakan data yang berlaku: `skills/devlib/assistant.md` ("Cakupan data dan sensitivitas"). Kode dasar: `src/Assistant`.

## 1. Tujuan

Asisten yang dipasang di program (ERP) makin cepat menemukan menu, layar, dan bantuan karena program membacakan isi dirinya secara berkala, bukan karena model dilatih. Pengguna bertanya "di mana menu buat PO" atau "order masuk hari ini"; pertanyaan di luar program tetap dijawab kalimat tetap (`OUT_OF_SCOPE`). Pengindeks bekerja pelan di latar belakang dan tidak mengganggu pemakai.

## 2. Apa yang diindeks, apa yang tidak

Diindeks (metadata saja): nama menu dan letaknya, label layar, nama kolom/field, judul dan langkah Help, nama fasilitas/laporan, frasa Tool (bukan hasilnya).

**Tidak** pernah diindeks:

- baris data bisnis (isi dokumen, nama pelanggan, angka transaksi);
- gaji, payroll, dan formula HPP dalam bentuk apa pun (lihat tingkat d; hasil HPP berupa rupiah adalah tingkat c); sumber yang cocok `DataPolicy::forbids()` dilewati dan dicatat sebagai "dilewati", bukan sebagai galat;
- data pribadi (identitas dan kontak pribadi: KTP / NIK, alamat rumah, tanggal lahir, data keluarga, rekening / NPWP pribadi, kesehatan, kata sandi; definisi di `skills/devlib/assistant.md`) dan entri apa pun yang menyebut orang terlindung program (`DataPolicy::protects()`): `DataPolicy::excludes()` melewatinya, dicatat "dilewati"; tanpa pengecualian untuk pemilik datanya. Akibatnya layar yang namanya atau uraiannya menyebut data pribadi (mis. "Data Pribadi Saya") juga tidak diindeks (pilihan hati-hati tahap 1); layar itu tetap ditemukan lewat `Navigator`, yang tidak berubah;
- nilai rupiah (tingkat c): yang diindeks hanya NAMA kolom/laporan bernilai, bukan isinya; entri itu diberi syarat `value` (lihat 5).

Hasil angka tetap datang langsung dari `Tool` saat ditanya (membaca langsung, menegakkan hak penanya), tidak dari indeks.

## 3. Kontrak generik DevLib dan bagian program inang

DevLib menyediakan antarmuka dan pelari; program menyediakan sumber dan penyimpanan. **Sketsa di bawah adalah rancangan awal; yang jadi (tahap 1) ada di `src/Assistant/Index` dan `src/Console/Index.php`**, bedanya: `IndexEntry` memuat `kind` (screen / help), `title`, `words`, `text`, `steps`, `path`, `url`, `module`, `minLevel`, `sensitivity`, `hash` (dihitung otomatis); `IndexStore::put($sumber, $entri)`; `Indexer` menerima `DataPolicy` di konstruktor; pemicu terbatas lewat `IndexThrottle` dan `IndexRunner`; `UsageLog` baru di tahap 2.

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
- Sumber terlarang tidak pernah masuk: `Indexer` menolak entri yang `DataPolicy::excludes()` (terlarang, data pribadi, atau orang terlindung; kunci/modul/judul/path/teks). Hanya mencocokkan teks yang dinyatakan program; labeli dengan benar adalah kewajiban program (jujur, sama seperti Bagian "Cakupan data").
- Tidak ada kebocoran lintas hak: indeks bersama (satu per program), tetapi hasil per penanya. Tes: penanya tanpa modul X tidak pernah mendapat entri X; penanya non-keuangan tidak mendapat entri `value`.
- Program tanpa modul gaji / payroll / formula HPP dan tanpa orang terlindung tidak terhalang apa pun oleh daftar terlarang; hanya program yang memerlukannya yang memasang `DataPolicy`.
- Hasil pencarian indeks tidak pernah mengembalikan entri `excludes()`, meski indeks lama (dibangun sebelum `DataPolicy` dipasang atau sebelum daftar terlindung bertambah) masih memuatnya: `search()` menyaringnya lagi saat bertanya, dan `Indexer` membuangnya (`forget`) pada putaran berikutnya.
- Indeks tidak berisi data bisnis, jadi bocornya indeks tidak membocorkan transaksi; tetap perlakukan sebagai internal.

## 6. Alur "makin pandai"

1. **Catatan pemakaian:** pertanyaan tak terjawab (`UNKNOWN`), jempol turun/naik, layar yang akhirnya dipilih pengguna (sudah ada di `HistoryStore`; ditambah pencatatan layar yang dibuka setelah jawaban).
2. **Saran:** pekerjaan berkala mengelompokkan pertanyaan tak terjawab yang sering muncul dan menyusun SARAN: tambah kata cari (sinonim) pada layar X, atau topik Help baru. Saran adalah data, bukan perilaku.
3. **Persetujuan orang:** antrean saran di layar admin program; admin menyetujui/menolak/mengubah. Hanya yang disetujui menjadi `words` layar atau Help tambahan (disimpan program, dapat dicabut).
4. **Berlaku** pada pengindeksan berikutnya.

Mencegah "belajar yang salah": tidak ada perubahan otomatis; satu jempol tidak cukup (ambang jumlah pengguna berbeda); saran yang menyentuh kata terlarang dibuang; setiap saran yang berlaku punya jejak (siapa, kapan) dan tombol cabut; pertanyaan berisi data pribadi tidak disalin mentah ke saran (hanya kata cari yang dinormalkan).

## 7. Tahapan

| Tahap | Isi | Status |
|---|---|---|
| 1 | `IndexSource`, `IndexEntry`, `IndexStore` (+ `MemoryIndexStore`), `Indexer`, `Budget`, `Progress`, `IndexRunner`, `IndexThrottle` (+ `CacheThrottle`, `MemoryThrottle`), `IndexAccess`, `IndexAudit`, perintah `devlib:index`; `Assistant` memakai indeks sebagai calon tambahan layar / topik; tes `AssistantIndexTest` | **selesai di DevLib**; belum dipasang di program mana pun (pilot menunggu program) |
| 2 | `UsageLog`, penyusun saran, antrean persetujuan (kontrak + layar admin contoh), ambang minimal 3 pengguna berbeda | belum |
| 3 | pencocokan makna (embedding) | **tidak dikerjakan** (keputusan Jo) |

Sisa tahap 1 yang sengaja ditunda: sumber menu / Help / Tool nyata dan `IndexStore` berbasis database ada di program inang (DevLib netral); metrik waktu kerja per hari (kini hanya `Progress::$ms` per potongan, dicetak `devlib:index`); sumber sangat besar membaca ulang dari awal tiap potongan (kursor menyusul bila perlu).

## 8. Risiko dan keputusan untuk Jo

Risiko: label program salah (modul gaji atau formula HPP berlabel biasa) lolos karena DevLib hanya mencocokkan teks; beban di database besar bila program memberi sumber yang boros; saran yang salah bila persetujuan dilewati; "10%" disalahartikan sebagai jaminan CPU.

Keputusan Jo (2026-10-20, ikut saran) dan penerapannya di tahap 1:

1. "10%" = anggaran waktu + jeda, bukan jaminan CPU. `Budget`: `ms` (200) dan `size` (200) per potongan, jeda = `pause` (9) x lama kerja potongan, `chunks` (20) potongan per jalan.
2. Pelari = perintah artisan terjadwal (tiap jam), bukan keharusan queue. `devlib:index`, dibatasi sekali per jam lewat `IndexThrottle` (bawaan `Cache::add`).
3. Saran dari catatan pemakaian disetujui admin program, ambang minimal 3 pengguna berbeda: tahap 2.
4. Kolom / laporan bernilai rupiah diindeks hanya NAMA-nya dan bersyarat `value` (`IndexEntry::nameOnly()`, `IndexAccess::may`); yang terlarang tidak diindeks sama sekali.
5. Tahap 3 (embedding) tidak dikerjakan.
6. Tiap program wajib punya tes yang memastikan tidak ada sumber terlarang berlabel biasa: `IndexAudit::leaks($sumber, $kebijakanProduksi, $kata, $orang)` harus `[]`.

Keputusan rancangan tahap 1 yang perlu konfirmasi Jo: layar yang menyebut data pribadi tidak diindeks sama sekali (lihat 2); pertanyaan berfrasa data pribadi tidak menyentuh indeks.
