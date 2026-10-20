# Assistant AI (model untuk proyek) di DevLib

Acuan baku untuk SEMUA proyek yang punya asisten ("AI Assistant" di menu profil `x-dl::shell`). Arah warisan: **DevLib membuat, proyek mewarisi** (`inheritance.md`). Opt-in: tidak ada yang terdaftar otomatis; proyek yang tidak memakainya tidak berubah. Kode: `src/Assistant/`. Tes: `site/tests/Feature/AssistantChatContractTest.php`. Contoh hidup: halaman katalog "AI Assistant model" (`site/app/Support/ModelDemo.php`, data fiktif).

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
- Menjadikan Auto/Flow engine sebagai tool; perubahan data selalu lewat layar + officer.
- Menambah perilaku asisten ke proyek bila bisa jadi perubahan opt-in di DevLib (prop/kontrak baru, default lama).
