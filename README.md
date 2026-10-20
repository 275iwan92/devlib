# DevLib (`jo/devlib`)

Library induk komponen UI/UX untuk proyek Laravel, dibuat oleh Jo. Isinya objek `x-dl::*` (komponen Blade berpasangan dengan kelas PHP dan JS), helper `raw()`, tema, bahasa (kamus), aset, kontrak engine (`Devlib\Engine`), model Assistant AI dan Chat, serta penegak aturan layar (`devlib:lint`).

Paket ini berdiri sendiri: tidak memakai kode `App\` milik aplikasi mana pun dan tidak membawa database sendiri. Syarat: PHP `^8.3`, Laravel (`illuminate/*`) `^13.0`.

## Peta folder

| Folder | Isi |
|---|---|
| `src/` | kode paket (namespace `Devlib\`): `Components/` (kelas komponen), `Engine/`, `Assistant/`, `Chat/`, `Lint/`, `PrintDoc/`, `Console/` (perintah artisan), `DevlibServiceProvider.php` |
| `resources/` | view Blade komponen (`views/`) dan aset JS/CSS (`assets/`), disajikan dari paket, tidak disalin ke proyek |
| `skills/` | skill Claude Code dan dokumen aturan: `devlib/` (cara pakai, aturan baku, kontrak engine, manifest komponen) dan `inventory/` (aturan domain inventori) |
| `site/` | situs Laravel mandiri: katalog komponen dan demo hidup; panduan pasangnya di [`site/README.md`](site/README.md) |

## Memasang di proyek lain

Proyek dan klon `devlib` diletakkan bersebelahan. Di `composer.json` proyek (path relatif terhadap proyek itu):

```json
"require": { "jo/devlib": "@dev" },
"repositories": [{ "type": "path", "url": "../devlib", "options": { "symlink": true } }]
```

lalu `composer update jo/devlib`. Service provider `Devlib\DevlibServiceProvider` terdaftar otomatis (package discovery).

Tailwind 4: karena paket berada di luar akar proyek, daftarkan sumbernya di CSS aplikasi (path relatif terhadap berkas CSS itu):

```css
@source '../../../devlib/src';
@source '../../../devlib/resources';
```

Langkah lain (`config/devlib.php`, layout dengan aset DevLib, hak akses `can`) ada di [`skills/devlib/install.md`](skills/devlib/install.md); contoh berkasnya di `skills/devlib/examples/`.

## Perintah untuk agen

`php artisan qc:reviews` (terdaftar otomatis oleh paket) menampilkan keputusan QC cepat aplikasi: Need improvement yang belum dikerjakan, siap rilis, dan Approved terbaru; `--resolve=<id> --by=<nama>` menutup satu catatan. Store bawaan adalah tabel `qc_review` di koneksi bawaan; aplikasi yang tabel atau koneksinya lain mengikat ulang di `AppServiceProvider`:

```php
$this->app->bind(\Devlib\Qc::class, fn () => new \Devlib\Qc('koneksi', 'tabel'));
```

Perintah lain: `php artisan devlib:lint` memeriksa view Blade terhadap aturan baku; `php artisan devlib:manifest` membuat ulang `skills/devlib/components.md`.

## Skill Claude Code

Sumbernya di repo ini:

- [`skills/devlib/SKILL.md`](skills/devlib/SKILL.md): membangun proyek apa pun dengan DevLib (pasang, layout, pola layar, aturan grid, form, dan Help). Intinya pendek, dengan rujukan ke `install.md`, `grid.md`, `form.md`, `transaction-lifecycle.md`, `help.md`, `inheritance.md`, [`engine.md`](skills/devlib/engine.md) (kontrak engine `src/Engine`), `assistant.md`, `chat.md`, dan [`components.md`](skills/devlib/components.md) (manifest otomatis: tag, prop, berkas tiap komponen).
- [`skills/inventory/SKILL.md`](skills/inventory/SKILL.md): aturan domain inventori lintas proyek (kode barang vs batch, FEFO, Reorder Point, satuan stok, beli, dan pakai).

Agar aktif di semua proyek, tautkan ke folder skill pengguna (sekali per mesin). Di PowerShell, dari akar klon `devlib`:

```powershell
New-Item -ItemType Junction -Path "$HOME\.claude\skills\devlib" -Target "$PWD\skills\devlib"
New-Item -ItemType Junction -Path "$HOME\.claude\skills\inventory" -Target "$PWD\skills\inventory"
```

## Aturan pokok

- **DevLib membuat, proyek mewarisi**, satu arah: komponen yang dibutuhkan lebih dari satu layar ditambahkan di DevLib, bukan disalin ke proyek; proyek tidak menjadi sumber contoh bagi DevLib (lihat [`skills/devlib/inheritance.md`](skills/devlib/inheritance.md)).
- **Opt-in**: perubahan DevLib tidak boleh mengubah perilaku proyek yang sudah memakainya; fitur baru lewat prop atau opsi config baru dengan default lama.
- **Tanpa `App\` milik proyek di `src/`**: data masuk lewat props dan kontrak, bukan model atau service proyek.
- **Tanpa nama client** di kode, contoh, atau dokumen.
- Perubahan DevLib di-commit di repo ini, bukan di repo aplikasi yang memakainya.
