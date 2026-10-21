# Engine (mesin hitung / otomasi proses) di DevLib

Acuan baku untuk SEMUA proyek. Opt-in: tidak ada engine terdaftar otomatis; proyek yang tidak memakainya tidak berubah. Kode: `src/Engine/`. Tes: `site/tests/Feature/EngineContractTest.php`.

## Kontrak

| Kelas | Fungsi |
|---|---|
| `Described` (interface, opsional) | `details()`: penjelasan engine untuk katalog (input, output, langkah) |
| `Engine` (interface) | `key()`, `name()`, `group()`, `mode()`, `purpose()`, `run(array $input): Result` |
| `Mode` (enum) | `Suggest` (hasil DRAF, officer memutuskan), `Auto` (jalan sendiri, langsung berlaku), `Calc` (hitungan murni, tanpa tulis database), `Flow` (alur dokumen) |
| `Result` | `Result::draft()`, `applied()`, `rejected($reason)`; membawa `data`, `reasons` (alasan tiap keputusan), `warnings` (temuan yang tidak memblokir) |
| `EngineRegistry` | `register()`, `find()`, `run($key, $input)`, `catalog()`; singleton di container |
| `EngineException` | satu-satunya kegagalan engine; selalu membawa kunci engine |
| `Adjustable`, `Cancellation`, `DocumentCancelled` | alur CANCEL + ADJUSTMENT otomatis (di bawah) |

## Aturan baku

1. **Suggest = draf.** Engine mode Suggest hanya mengembalikan `Result::draft`; `EngineRegistry::run` melempar `EngineException` bila ia mengembalikan `applied`. Draf TIDAK menulis ke database; penulisan terjadi setelah officer Save/Confirm.
2. **Hasil officer tidak pernah ditimpa.** Isian yang sudah diputuskan officer (OVERRIDE / MANUAL) dilewati engine; engine hanya mengisi yang belum diputuskan. (Ditegakkan di tiap engine, bukan di registry.)
3. **Tiap keputusan punya alasan** di `reasons`, tampil ke officer; temuan yang tidak memblokir masuk `warnings`.
4. **Engine otomatis jalan di latar** (mode Auto/Suggest tanpa tombol Process wajib); tombol "Check / Process" hanya untuk menghitung ulang setelah perubahan manual.
5. **Gagal = `EngineException`** (nama engine + sebab), bukan `false` / `null` diam-diam; tanpa `catch` kosong. `EngineRegistry::run` membungkus kegagalan lain menjadi `EngineException`.
6. **Satu engine, satu tanggung jawab**; method pendek, input/output bertipe. Engine tidak membaca `App\...` milik proyek lewat DevLib; data masuk lewat `$input`.
7. **Yang bertambah = test kecil per engine.**

## Daftar dan katalog

```php
// AppServiceProvider::boot()
app(\Devlib\Engine\EngineRegistry::class)->register(new ImpositionEngine, new BomEngine);

// jalankan
$result = app(\Devlib\Engine\EngineRegistry::class)->run('imposition', ['inquiry' => $id]);

// katalog layar Engine (tanda tangan diambil dari kode, penjelasan tambahan = data aplikasi)
<x-dl::engine-catalog :engines="app(\Devlib\Engine\EngineRegistry::class)->catalog(base_path())" />
```

`catalog()` mengisi `key`, `name`, `group`, `mode`, `purpose`, `class`, `file`, `lines`, `entries`. Engine yang mengimplementasikan `Described` (`details()`) menambahkan `inputs`, `outputs`, `steps`, `reads`, `writes`, ... dari kodenya sendiri, jadi penjelasan tidak pernah terpisah dari engine.

## CANCEL dan ADJUSTMENT otomatis

Aturan pemilik: CANCEL membuat mode `CANCELLED`, record **tetap tersimpan** (beda dengan DELETE); bila pembatalan membuat data tidak cocok (stok, saldo), record **ADJUSTMENT pembalik dibuat OTOMATIS**.

```php
class StockIssue extends Model implements \Devlib\Engine\Adjustable
{
    public function createAdjustment(): ?\Devlib\Engine\Result
    {
        // null = data masih cocok, tidak perlu pembalik
        $adj = StockAdjustment::reverse($this);

        return \Devlib\Engine\Result::applied($adj->toArray(), ['Reverses stock issue '.$this->no]);
    }
}

\Devlib\Engine\Cancellation::run($issue, fn ($d) => $d->update(['status' => 'cancelled']));
```

- Pembatalan dan pembalik berjalan dalam **satu transaksi**: pembalik ditolak (`Result::rejected`) atau melempar = pembatalan ikut batal.
- Setelah berhasil, event `DocumentCancelled($document, $adjustment)` dikirim; aplikasi boleh mendengarkannya (notifikasi, log).
- Dokumen yang bukan `Adjustable` tetap CANCELLED tanpa pembalik.

## Engine siap pakai: LeadTime (lead time pengadaan)

`Devlib\Engine\LeadTime` (hitungan murni, immutable, tanpa database) mencatat lama dari barang DIPESAN sampai MASUK dari data nyata dan memberi perkiraan tiba. `LeadTimeEngine` (key `lead-time`, mode Calc) hanya pembungkus untuk `EngineRegistry` / katalog; daftarkan bila perlu (opt-in).

```php
$lt = \Devlib\Engine\LeadTime::of($orders, ['samples' => 5, 'min_samples' => 3, 'fallback_days' => 7, 'late_tolerance_days' => 1]);
// $orders = [['start' => '2026-10-01', 'end' => '2026-10-06', 'key' => 'PO-1'], ['start' => '2026-10-10', 'end' => null], ...]

$lt->effective();                       // rata-rata `samples` catatan selesai terakhir (urut tanggal masuk), dibulatkan ke atas; < min_samples = fallback_days; null bila tak ada
$lt->source(); $lt->basis();            // 'data' | 'manual' | 'none'; jumlah catatan yang dipakai
$lt->count(); $lt->average(); $lt->min(); $lt->max(); $lt->median(); $lt->last();   // statistik catatan selesai
$lt->expected('2026-10-10');            // '2026-10-15' (tanggal pesan + effective) atau null
$lt->status('2026-10-10', $today);      // elapsed_days, typical_days, expected_at, state: on_time | late | unknown
$lt->suggestReorderPoint(4.0, 10);      // SARAN: pemakaian harian x effective + stok pengaman (tidak mengubah apa pun)
$lt->describe(); $lt->describeExpected($start); $lt->describeStatus($start, $today);   // kalimat Lang (kunci leadtime_*)
```

- Hari = selisih **tanggal kalender**; jam dan zona dibuang (`'2026-10-05 23:59'` ke `'2026-10-06 00:01'` = 1 hari). Tanggal boleh `Y-m-d`, `Y-m-d H:i:s`, ISO, atau `DateTimeInterface`.
- Catatan belum selesai (`end` null), terbalik (`end < start`), atau bertanggal tak sah diabaikan tanpa galat.
- Aplikasi memegang lead time manual dan keputusan memakainya; engine hanya menyarankan.
