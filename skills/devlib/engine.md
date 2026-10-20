# Engine (mesin hitung / otomasi proses) di DevLib

Acuan baku untuk SEMUA proyek. Opt-in: tidak ada engine terdaftar otomatis; proyek yang tidak memakainya tidak berubah. Kode: `src/Engine/`. Tes: `site/tests/Feature/EngineContractTest.php`.

## Kontrak

| Kelas | Fungsi |
|---|---|
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

`catalog()` mengisi `key`, `name`, `group`, `mode`, `purpose`, `class`, `file`, `lines`, `entries`; bagian lain (`inputs`, `outputs`, `steps`, `reads`, `writes`, ...) boleh ditambahkan aplikasi pada baris itu.

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
