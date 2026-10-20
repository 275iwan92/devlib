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

1. frasa `Tool` cocok -> gerbang hak (modul + level) -> `Tool::answer`;
2. ada kata "cara / how" dan topik bantuan cukup cocok -> langkah dari `Knowledge`;
3. nama layar cocok -> layar (tombol Open + How to use);
4. topik bantuan cocok -> kegunaan topik;
5. belum yakin dan model aktif -> model memilih satu dari calon;
6. tidak tahu -> bila `outOfScope` diisi DAN tidak satu kata pun dari pertanyaan ada di kosakata aplikasi (nama/letak/kata cari/kegunaan layar, judul/kata cari/kegunaan topik, judul/frasa Tool) -> `OUT_OF_SCOPE` (kalimat tetap, penilaian murni pencocokan kata, bukan model); selain itu `UNKNOWN`. Satu kata yang cocok saja sudah dianggap dalam lingkup (condong ke `UNKNOWN`, supaya pertanyaan yang sah tidak ditolak). Model bahasa tidak ikut menilai lingkup.
Setiap `ask()` tersimpan di `HistoryStore` (bila diikat); `Reply::$id` dipakai untuk jempol naik/turun.

## Cakupan data dan sensitivitas

Empat tingkat. DevLib netral: **program** yang menetapkan modul/sumber mana masuk tingkat (c) dan (d); DevLib menyediakan penegakannya.

| Tingkat | Isi | Siapa | Penegakan |
|---|---|---|---|
| (a) Pengetahuan | menu, layar, Help, prosedur, struktur | sesuai hak layar (`Navigator` sudah menyaring per penanya) | bawaan |
| (b) Kuantitas | jumlah, stok, order, jumlah baris, kehadiran / absensi | hak modul penanya (`Asker::can`) | `Sensitivity::Quantity` (bawaan Tool) |
| (c) Nilai rupiah | harga, total, piutang, biaya, nilai persediaan, dsb. | hanya penanya yang dinyatakan program sebagai orang keuangan; pengecualian yang ditetapkan Jo: HASIL HPP juga untuk sales (program membagi hak per Tool lewat `ScopedValueAsker`) | `Sensitivity::Value` + `ValueAsker::canSeeValue()` atau `ScopedValueAsker::canSeeValueOf($kunciTool)`; selain itu `Reply::DENIED` |
| (d) Gaji, payroll, formula HPP | semua data gaji dan payroll; rumus / cara hitung / komponen / parameter perhitungan HPP (costing formula) | **tidak seorang pun**, termasuk keuangan dan admin | terlarang total: tidak dibaca, tidak diindeks, tidak dijawab, tidak boleh jadi Tool / Navigator / Knowledge sumber |

Absensi / kehadiran **bukan** payroll dan tetap boleh (tingkat b). Nilai HASIL HPP berupa rupiah adalah tingkat (c), bukan (d): untuk penanya keuangan DAN sales (keputusan Jo, 2026-10-20; sales memakai hasil HPP untuk penawaran); yang terlarang adalah FORMULA-nya. Sales tidak otomatis boleh melihat nilai rupiah lain: hanya Tool yang kuncinya dinyatakan program lewat `ScopedValueAsker`.

**Tanpa modul yang cocok, tidak ada pembatasan.** Larangan (d) hanya berlaku bagi program yang memang punya modul gaji / payroll / formula HPP dan memasang `DataPolicy`; program tanpa modul itu (dan situs katalog DevLib) berperilaku bebas, tidak ada yang diblokir.

Penegakan di kode (semuanya opt-in; yang tidak memakainya berperilaku seperti sebelumnya):

- `Classified` (interface opsional pada `Tool`): `sensitivity(): Sensitivity` (`Quantity`, `Value`, `Forbidden`). Tool tanpa `Classified` dianggap `Quantity`. `EngineTool` menerima argumen `sensitivity:`.
- `ValueAsker extends Asker` (opsional): `canSeeValue()`. `Asker` biasa dianggap **tidak** boleh melihat nilai, jadi bawaan untuk Tool `Value` adalah menolak (paling aman). Gerbang nilai berlaku setelah gerbang modul + level.
- `ScopedValueAsker extends ValueAsker` (opsional): `canSeeValueOf(string $kunciTool)`. Untuk program yang membagi hak nilai per Tool (mis. keuangan melihat semua nilai, sales hanya hasil HPP); bila diimplementasikan, ia menjadi satu-satunya penentu untuk Tool `Value`. Penanya yang hanya `ValueAsker` tetap memakai `canSeeValue()` (global), jadi perilaku lama tidak berubah.
- `Sensitivity::Forbidden`: `new Assistant(...)` melempar `AssistantException`, dengan atau tanpa `DataPolicy`.
- `DataPolicy` (argumen `policy:` pada `Assistant`): daftar kata terlarang bawaan `payroll`, `payslip`, `salary`, `gaji` (mencakup "slip gaji" dan "penggajian") dan frasa formula HPP (`hpp formula`, `formula hpp`, `rumus hpp`, `cara hitung hpp`, `komponen hpp`, `parameter hpp`, `costing formula`, dsb.; tanda baca dan garis bawah dianggap spasi, jadi `hpp_formula` cocok, tetapi `hpp-result` TIDAK, karena hasil HPP adalah tingkat nilai) + kata tambahan program (`new DataPolicy(['bonus'])`). Dengan `policy` terpasang: (1) Tool yang kunci / modul / judul / frasanya memuat kata itu ditolak saat `Assistant` dibuat (`AssistantException`); (2) layar (kunci, label, path) dan topik bantuan (kunci, judul) yang memuatnya tidak pernah ditawarkan; (3) pertanyaan yang menyebut kata itu dijawab `DENIED` tetap, tanpa memanggil Tool. Pengindeks nanti memakai `DataPolicy::forbids()` yang sama (`docs/assistant-indexer.md`).

Batas yang jujur: DevLib **hanya mencocokkan teks yang dinyatakan program** (kunci, modul, judul, frasa, path). Ia tidak memahami arti data dan tidak bisa menangkap Tool yang diberi label salah (mis. Tool total gaji berkunci `report-17` dan bersensitivitas `Quantity`), atau Tool `Quantity` yang isi jawabannya memuat rupiah. **Program bertanggung jawab memberi label yang benar** dan memasang `DataPolicy`; tes proyek sebaiknya memastikan tidak ada Tool bermodul keuangan/HR yang berlabel `Quantity`.

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
- Membuat Tool / layar / topik untuk gaji, payroll, atau formula HPP, atau memberi label `Quantity` pada Tool yang menampilkan rupiah.
- Menjadikan Auto/Flow engine sebagai tool; perubahan data selalu lewat layar + officer.
- Menambah perilaku asisten ke proyek bila bisa jadi perubahan opt-in di DevLib (prop/kontrak baru, default lama).
