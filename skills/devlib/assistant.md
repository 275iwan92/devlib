# Assistant AI (model untuk proyek) di DevLib

Acuan baku untuk SEMUA proyek yang punya asisten ("AI Assistant" di menu profil `x-dl::shell`). Arah warisan: **DevLib membuat, proyek mewarisi** (`inheritance.md`). Opt-in: tidak ada yang terdaftar otomatis; proyek yang tidak memakainya tidak berubah. Kode: `src/Assistant/`. Tes: `site/tests/Feature/AssistantChatContractTest.php`. Contoh hidup: halaman katalog "AI Assistant model" (`site/app/Support/ModelDemo.php`, data fiktif).

## Model yang "belajar" dari program inang

Assistant adalah MODEL di DevLib yang dipasang di program mana pun; ia tidak dilatih dan DevLib tidak menyimpan pengetahuan. Yang ia "pelajari" adalah isi program tempat ia dipasang, lewat kontrak yang diikat program: layar dari `Navigator`, bantuan dari `Knowledge` (Help/UG), angka dari `Tool` (engine Calc buatan program), hak akses dari `Asker`. Pertanyaan yang belum terjawab (`UNKNOWN`) tercatat di `HistoryStore` program sebagai bahan menambah Help program itu. Contoh di halaman katalog memakai program inang fiktif.

## Prinsip

1. **Angka dan hasil tidak pernah dihitung model bahasa.** Satu kemampuan = satu `Tool`; tool yang menghitung memakai **Engine DevLib** (`EngineTool`), jadi bisa diuji dan terlihat di katalog Engine.
2. **Bantuan hanya dikutip** dari `Knowledge` (Help/UG proyek). Tidak ada jawabannya = `Reply::UNKNOWN`, tercatat di riwayat sebagai bahan menambah Help.
3. **Hak akses milik proyek.** Semua "boleh / tidak" ditanyakan ke `Asker` (modul + level). Di luar hak = `Reply::DENIED`, tool tidak dijalankan.
4. **Model bahasa hanya pemilih.** Ia diberi pertanyaan dan calon terdekat (maks 5), lalu menjawab SATU kunci. Ia tidak menulis SQL, tidak menghitung, tidak menyusun jawaban. Tanpa model (`NullModel`, bawaan) atau saat mati, asisten tetap jalan dengan pencocokan kata kunci.
5. **Asisten tidak menulis data.** `EngineTool` hanya menerima engine mode `Calc` dan `Suggest`; `Auto` / `Flow` ditolak (`AssistantException`). Hasil Suggest tampil sebagai draf: officer yang Save/Confirm di layar.
6. **Di luar program dijawab satu kalimat tetap, bukan "tidak tahu".** "Belum ada jawabannya di Help" (`UNKNOWN`, bahan menambah Help) berbeda dari "pertanyaan ini tidak berhubungan dengan program" (`OUT_OF_SCOPE`). Opt-in: hanya bila aplikasi mengisi `outOfScope` (kalimatnya milik aplikasi, mis. "Saya hanya memahami perihal program ini, lainnya saya belum paham"); tanpa itu perilaku lama (`UNKNOWN`).
7. **Kegagalan tidak diam.** `EngineException` dari tool menjadi `Reply::UNKNOWN` bertuliskan sebabnya (bukan `catch` kosong, bukan `null`).

## Kontrak (`Devlib\Assistant`)

| Kelas | Peran | Diikat aplikasi? |
|---|---|---|
| `Asker` (interface) | siapa yang bertanya: `id()`, `name()`, `level()`, `can($modul)` | ya |
| `Tool` (interface) | satu kemampuan: `key/title/phrases/module/minLevel/answer` | ya (atau `EngineTool`) |
| `EngineTool` | membungkus engine Calc/Suggest dari `EngineRegistry` menjadi `Tool`; input disusun closure dari penanya + kalimat | dipakai aplikasi |
| `Navigator` (interface) | layar yang boleh dibuka penanya: `key,label,url,path?,words?,about?,help?,icon?` | ya |
| `Knowledge` (interface) | topik bantuan: `key,title,purpose?,words?,steps?` | ya |
| `LanguageModel` (interface) + `NullModel` | adaptor model bahasa: `available()`, `choose($pertanyaan, $pilihan, $riwayat)` | ya, opsional |
| `HistoryStore` (interface) + `MemoryHistory` | riwayat percakapan per user + jempol (`store`, `recent`, `feedback`) | ya |
| `Sensitivity` + `Classified` + `ValueAsker` + `DataPolicy` | kebijakan data: tingkat Tool, penanya keuangan, daftar terlarang (lihat "Cakupan data dan sensitivitas") | opsional |
| `Reply` | jawaban sebagai data (`SCREEN`, `HELP`, `NUMBER`, `DENIED`, `UNKNOWN`, `OUT_OF_SCOPE`); `toArray()` = bentuk yang dibaca laci AI Assistant `x-dl::shell` | - |
| `Assistant` | mesin jawab: `ask()`, `topic()`, `history()`, `feedback()`, `suggestions()` | dibuat aplikasi |

## Urutan keputusan (`Assistant::ask`)

0. (opt-in `DataPolicy`) pertanyaan menyebut gaji / payroll / formula HPP atau orang terlindung -> `DENIED` tetap; pertanyaan berfrasa data pribadi -> tanpa `Tool` dan tanpa model, hanya layar / topik yang cocok, selain itu `DENIED`. Pemeriksaan ini mendahului semua langkah lain;
1. frasa `Tool` cocok -> gerbang hak (modul + level) -> `Tool::answer`;
2. ada kata "cara / how" dan topik bantuan cukup cocok -> langkah dari `Knowledge`;
3. nama layar cocok -> layar (tombol Open + How to use);
4. topik bantuan cocok -> kegunaan topik;
5. belum yakin dan model aktif -> model memilih satu dari calon;
6. tidak tahu -> bila `outOfScope` diisi DAN tidak satu kata pun dari pertanyaan ada di kosakata aplikasi (nama/letak/kata cari/kegunaan layar, judul/kata cari/kegunaan topik, judul/frasa Tool) -> `OUT_OF_SCOPE` (kalimat tetap, penilaian murni pencocokan kata, bukan model); selain itu `UNKNOWN`. Satu kata yang cocok saja sudah dianggap dalam lingkup (condong ke `UNKNOWN`, supaya pertanyaan yang sah tidak ditolak). Model bahasa tidak ikut menilai lingkup.
Setiap `ask()` tersimpan di `HistoryStore` (bila diikat); `Reply::$id` dipakai untuk jempol naik/turun.

## Cakupan data dan sensitivitas

Empat tingkat. DevLib netral: **program** yang menetapkan modul/sumber mana masuk tingkat (c) dan (d) dan siapa orang terlindungnya; DevLib menyediakan penegakannya.

| Tingkat | Isi | Siapa | Penegakan |
|---|---|---|---|
| (a) Pengetahuan | menu, layar, Help, prosedur, struktur | sesuai hak layar (`Navigator` sudah menyaring per penanya) | bawaan |
| (b) Kuantitas | jumlah, stok, order, jumlah baris, kehadiran / absensi | hak modul penanya (`Asker::can`) | `Sensitivity::Quantity` (bawaan Tool) |
| (c) Nilai rupiah | harga, total, piutang, biaya, nilai persediaan, dsb. | hanya penanya yang dinyatakan program sebagai orang keuangan; pengecualian yang ditetapkan Jo: HASIL HPP juga untuk sales (program membagi hak per Tool lewat `ScopedValueAsker`) | `Sensitivity::Value` + `ValueAsker::canSeeValue()` atau `ScopedValueAsker::canSeeValueOf($kunciTool)`; selain itu `Reply::DENIED` |
| (d) Gaji, payroll, formula HPP, **data pribadi** | semua data gaji dan payroll; rumus / cara hitung / komponen / parameter perhitungan HPP (costing formula); data pribadi masing-masing orang (definisi di bawah) | **tidak seorang pun**, termasuk keuangan, HR, admin, dan pemilik data itu sendiri | terlarang total: tidak dibaca, tidak diindeks, tidak dijawab, tidak boleh jadi Tool sumber; layar dan bantuan tempat orang mengelola datanya sendiri tetap boleh ditunjukkan (lihat "Data pribadi") |

Absensi / kehadiran **bukan** payroll dan tetap boleh (tingkat b). Nilai HASIL HPP berupa rupiah adalah tingkat (c), bukan (d): untuk penanya keuangan DAN sales (keputusan Jo, 2026-10-20; sales memakai hasil HPP untuk penawaran); yang terlarang adalah FORMULA-nya. Sales tidak otomatis boleh melihat nilai rupiah lain: hanya Tool yang kuncinya dinyatakan program lewat `ScopedValueAsker`.

**Tanpa modul atau orang yang cocok, tidak ada pembatasan.** Larangan (d) hanya berlaku bagi program yang memasang `DataPolicy`; program tanpa modul gaji / payroll / formula HPP dan tanpa orang terlindung (dan situs katalog DevLib) berperilaku bebas, tidak ada yang diblokir. Satu-satunya yang ikut berlaku bagi setiap program yang memasang `DataPolicy` adalah frasa data pribadi di bawah, dan itu spesifik (kata "alamat" saja tidak terlarang).

### Data pribadi (tingkat d)

Satu hal yang tidak boleh disentuh Assistant: **data pribadi masing-masing orang**. Definisi yang dipakai: identitas dan kontak pribadi seseorang, yaitu nomor KTP / NIK, nomor kartu keluarga, paspor, alamat rumah, tanggal / tempat lahir, nomor telepon atau email pribadi, data keluarga (istri / suami / ibu kandung), nomor rekening atau NPWP pribadi, data kesehatan (rekam medis, riwayat penyakit), dan kata sandi atau akun masuk, serta yang sejenis. Asisten tidak membaca, tidak mengindeks, dan tidak menjawab isinya, untuk siapa pun, termasuk pemilik datanya sendiri.

- **Absensi / kehadiran bukan data pribadi** dalam arti ini: tetap tingkat (b), sesuai hak modul (keputusan Jo sebelumnya).
- **Alamat pelanggan / pengiriman, telepon dan email pemasok, NPWP perusahaan adalah data bisnis**, bukan data pribadi: tetap dijawab sesuai hak modul. Karena itu daftar bawaan memuat FRASA spesifik (`data pribadi`, `personal data`, `alamat rumah`, `nomor ktp`, `ktp`, `nik`, `tanggal lahir`, `rekening pribadi`, `kata sandi`, `password`, dsb.; lihat `DataPolicy::PERSONAL`), dicocokkan sebagai kata utuh ("nik" tidak cocok dengan "unik"), bukan kata umum seperti "alamat", "telepon", "email", "rekening". Program menambah frasanya sendiri: `new DataPolicy(personal: ['nomor sim'])`.
- **Data diri sendiri (keputusan rancangan, perlu konfirmasi Jo):** bila penanya menanyakan datanya sendiri, asisten TIDAK membacakan datanya, tetapi boleh menunjukkan LAYAR tempat ia mengelola datanya (jawaban `SCREEN` / langkah bantuan dari `Knowledge`: itu pengetahuan menu, bukan data). Untuk pertanyaan berfrasa data pribadi, `Assistant` melewati semua `Tool` dan model bahasa; hanya `Navigator` / `Knowledge` yang dicocokkan, dan bila tidak ada layar / topik yang cocok jawabannya `DENIED`. DevLib tidak bisa membedakan "data saya" dari "data orang lain" (pencocokan teks); jadi layar yang cocok ditunjukkan untuk keduanya, dan layar itu sendiri tunduk pada hak penanya.

### Orang yang dilindungi

Mekanisme umum, opt-in, diisi **program inang**: `new DataPolicy(protected: ['Budi Santoso', 'budi'])`. DevLib tidak menyimpan nama siapa pun. Dipakai untuk melindungi pemilik atau orang tertentu yang ditunjuk program; pada tmerp nanti program mengisinya dengan pemilik (nama lengkap, login, dan panggilannya, dibaca dari konfigurasi program, bukan dari DevLib).

- Pertanyaan yang menyebut orang terlindung dijawab `Reply::DENIED` tetap, **sebelum** Tool / Navigator / Knowledge / model bahasa menyentuhnya. Tool tidak dijalankan, model bahasa tidak menerima pertanyaannya, dan pertanyaan itu juga dibuang dari riwayat yang dikirim ke model pada pertanyaan berikutnya.
- Pencocokan: tanpa peka huruf besar-kecil, tanda baca dan garis bawah dianggap spasi (`budi-santoso`, `budi_santoso`, `BUDI  SANTOSO` sama), urutan kata bebas (`Santoso, Budi`), kata utuh (login `budi` tidak mengenai "budiman"). Satu orang cocok bila SEMUA kata entrinya ada di pertanyaan.
- **Sebagian nama saja tidak cocok sendirian** (keputusan hati-hati): "Budi" saja akan memblokir setiap pertanyaan tentang orang lain yang senama. Program yang ingin panggilan atau login pendek terlindungi mendaftarkannya sebagai entri sendiri (`'budi'`); jangan mendaftarkan angka atau kata umum.
- Tidak ada pengecualian untuk pemilik itu sendiri maupun untuk keuangan / HR / admin.
- Layar atau topik yang kunci / judulnya memuat orang terlindung tidak ditawarkan, dan Tool yang kunci / modul / judul / frasanya memuatnya ditolak saat `Assistant` dibuat. Entri apa pun yang terkait orang itu tidak pernah masuk indeks (`docs/assistant-indexer.md`).

Penegakan di kode (semuanya opt-in; yang tidak memakainya berperilaku seperti sebelumnya):

- `Classified` (interface opsional pada `Tool`): `sensitivity(): Sensitivity` (`Quantity`, `Value`, `Forbidden`). Tool tanpa `Classified` dianggap `Quantity`. `EngineTool` menerima argumen `sensitivity:`.
- `ValueAsker extends Asker` (opsional): `canSeeValue()`. `Asker` biasa dianggap **tidak** boleh melihat nilai, jadi bawaan untuk Tool `Value` adalah menolak (paling aman). Gerbang nilai berlaku setelah gerbang modul + level.
- `ScopedValueAsker extends ValueAsker` (opsional): `canSeeValueOf(string $kunciTool)`. Untuk program yang membagi hak nilai per Tool (mis. keuangan melihat semua nilai, sales hanya hasil HPP); bila diimplementasikan, ia menjadi satu-satunya penentu untuk Tool `Value`. Penanya yang hanya `ValueAsker` tetap memakai `canSeeValue()` (global), jadi perilaku lama tidak berubah.
- `Sensitivity::Forbidden`: `new Assistant(...)` melempar `AssistantException`, dengan atau tanpa `DataPolicy`.
- `DataPolicy` (argumen `policy:` pada `Assistant`; argumen `protected:` dan `personal:` untuk orang terlindung dan frasa pribadi tambahan): daftar kata terlarang bawaan `payroll`, `payslip`, `salary`, `gaji` (mencakup "slip gaji" dan "penggajian") dan frasa formula HPP (`hpp formula`, `formula hpp`, `rumus hpp`, `cara hitung hpp`, `komponen hpp`, `parameter hpp`, `costing formula`, dsb.; tanda baca dan garis bawah dianggap spasi, jadi `hpp_formula` cocok, tetapi `hpp-result` TIDAK, karena hasil HPP adalah tingkat nilai) + kata tambahan program (`new DataPolicy(['bonus'])`). Dengan `policy` terpasang: (1) Tool yang kunci / modul / judul / frasanya memuat kata itu, frasa data pribadi, atau orang terlindung ditolak saat `Assistant` dibuat (`AssistantException`); (2) layar (kunci, label, path) dan topik bantuan (kunci, judul) yang memuat kata terlarang atau orang terlindung tidak pernah ditawarkan; (3) pertanyaan yang menyebut kata terlarang atau orang terlindung dijawab `DENIED` tetap, tanpa memanggil Tool; (4) pertanyaan berfrasa data pribadi melewati Tool dan model bahasa (lihat "Data pribadi"). Pengindeks nanti memakai `DataPolicy::excludes()` (= `forbids()` + `personal()` + `protects()`) yang sama (`docs/assistant-indexer.md`).

Batas yang jujur: DevLib **hanya mencocokkan teks yang dinyatakan program** (kunci, modul, judul, frasa, path, pertanyaan), bukan makna. Ia tidak bisa menangkap Tool yang diberi label salah (mis. Tool total gaji berkunci `report-17` dan bersensitivitas `Quantity`, atau Tool kontak berkunci `lookup-3` yang sebenarnya mengembalikan nomor KTP), Tool `Quantity` yang isi jawabannya memuat rupiah atau data pribadi, pertanyaan data pribadi yang diparafrasekan tanpa frasa yang dikenal, dan orang terlindung yang disebut dengan ejaan salah, singkatan, atau panggilan yang tidak didaftarkan. Riwayat (`HistoryStore`) milik program menyimpan teks pertanyaan apa adanya, termasuk yang ditolak; jaga akses ke tabelnya. **Program bertanggung jawab memberi label yang benar, mengisi daftar terlindung, dan memasang `DataPolicy`**; tes proyek sebaiknya memastikan tidak ada Tool bermodul keuangan/HR yang berlabel `Quantity` dan tidak ada Tool yang mengembalikan data pribadi.

## Memasang di proyek

```php
// AppServiceProvider::boot() / register()
$this->app->singleton(\Devlib\Assistant\Assistant::class, fn ($app) => new \Devlib\Assistant\Assistant(
    navigator: $app->make(MyNavigator::class),          // implements Navigator (dari menu + hak akses proyek)
    knowledge: $app->make(MyKnowledge::class),          // implements Knowledge (dari Help / UG proyek)
    tools: [new \Devlib\Assistant\EngineTool($app->make(\Devlib\Engine\EngineRegistry::class), 'reorder-point',
        ['reorder point', 'titik pesan ulang'], fn ($asker, $q) => ['item' => ...], module: 'inventory')],
    model: $app->make(MyLanguageModel::class),          // opsional; implements LanguageModel
    history: $app->make(MyHistoryStore::class),         // opsional; implements HistoryStore (tabel milik proyek)
    outOfScope: 'Saya hanya memahami perihal program ini, lainnya saya belum paham',   // opsional; kalimat tetap pertanyaan di luar program
));
```

- Rute tipis milik proyek: `POST ask {q}` -> `{id, reply: $assistant->ask($asker, $q)->toArray()}`; `POST feedback {id, value}` -> `$assistant->feedback(...)`; `topic` -> `$assistant->topic(...)`. Layar membacanya lewat prop `assistant` di `x-dl::shell` (`api`, `hello`, `chips` = `suggestions()`, `history` = `history()`).
- `LanguageModel` bebas penyedianya (Ollama lokal, Laravel AI SDK, API lain): cukup implementasikan `choose()`; proyek yang memakai Laravel AI SDK membungkusnya di adaptor milik proyek, DevLib tidak bergantung pada SDK mana pun. Timeout/galat model = kembalikan `null` (asisten turun ke "tidak tahu"), jangan melempar ke layar.
- Masa simpan riwayat, tabel, dan jadwal `prune` adalah urusan adaptor `HistoryStore` milik proyek.

## Jangan

- Menaruh nama proyek / data client di `src/Assistant` atau contoh `site/`. Adaptor yang menyebut tabel proyek hidup di proyek, bukan di DevLib.
- Membiarkan model menghasilkan angka, SQL, atau teks bantuan.
- Membuat Tool untuk gaji, payroll, formula HPP, atau data pribadi (layar / topik bantuan untuk gaji dan formula HPP juga jangan), atau memberi label `Quantity` pada Tool yang menampilkan rupiah.
- Menaruh nama orang sungguhan (pemilik, karyawan) di DevLib: daftar terlindung diisi program dari konfigurasinya.
- Menjadikan Auto/Flow engine sebagai tool; perubahan data selalu lewat layar + officer.
- Menambah perilaku asisten ke proyek bila bisa jadi perubahan opt-in di DevLib (prop/kontrak baru, default lama).
