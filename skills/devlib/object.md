# DevLib: aturan membangun object

Object = satuan yang DevLib buat dan proyek warisi. Dokumen ini daftar periksa membuat satu object baru. Arah warisan: `inheritance.md`; mencari object yang sudah ada: `SKILL.md` §2 dan `components.md`. Butir bertanda **BELUM ADA ATURAN** belum tertulis di mana pun: jangan dikarang, putuskan bersama Jo.

## 1. Apa itu object DevLib

- **Komponen UI** = kelas turunan `Devlib\Components\DevlibComponent` di `src/Components/` (subfolder `Field/`, `Form/`, `Wizard/`, `SideScreen/`), dipanggil `<x-dl::nama>` (`DevlibServiceProvider::boot`: `Blade::componentNamespace`). Tag diturunkan dari nama kelas: `Field\Text` → `x-dl::field.text` (`DevlibComponent::describe`).
- Satu object = **pasangan**: kelas PHP (prop, data) + tampilan (`resources/views/components/<kebab>.blade.php`, atau `render()` inline seperti `Timer`) + JS bila ada perilaku (`resources/assets/js/components/<nama>.js`) + CSS di `resources/assets/devlib.css`.
- **Model non-UI** = kontrak `Devlib\Engine`, `Devlib\Assistant`, `Devlib\Chat`: opt-in, adaptor diikat proyek (`engine.md`, `assistant.md`, `chat.md`).

## 2. Langkah membuat komponen baru

Urutan kerja; tiap langkah ada penegaknya bila disebut.

1. **Cari yang terdekat dulu** (`components.md`, `SKILL.md` §2). Perilaku yang dibutuhkan >1 layar masuk **induk** (prop/method baru), bukan salinan. Lolos uji di §5.
2. **Kelas PHP** `src/Components/<Nama>.php` (contoh pendek: `Timer.php`):
   - Header `/* REMARK ... */` di paling atas: `Tujuan`, `Sumber hulu`, `Input`, `Output`, `Dipakai oleh`, `Status`. Yang ditegakkan hanya awalan `REMARK` dan baris `Tujuan :` (`Manifest::purpose`, `ComponentContractTest`); isi bidang lain mengikuti header kelas yang ada. **BELUM ADA ATURAN** tertulis siapa/apa yang mengisi `Dipakai oleh` (tidak ada generatornya di repo).
   - Komentar `//` satu paragraf tepat di atas `class` (tujuan + cara pakai).
   - `extends DevlibComponent`; semua prop = parameter konstruktor **bertipe** dengan default aman (kecuali `value`, `valueTo`, `valueEnd`).
   - `tag()` = `'x-dl::<kebab>'`; `source()` = asal data (string, boleh `'—'`).
   - `render()`: tampilan dari berkas view, atau heredoc Blade inline. Elemen akar memakai `{{ $attributes->class([...]) }}` dan `{!! $dl() !!}` (menghasilkan `data-dl`, rantai induk, sumber, `data-dl-js`; `data-dl` dipakai link judul ke Technical Guide).
   - Prop teks (`label`, `title`, `hint`, dst.: `TEXT_PROPS`) diterjemahkan otomatis; istilah tetap English (tombol/status) = kosongkan `TEXT_PROPS` seperti `Button`. Atribut `can="modul.aksi"` sudah bekerja di semua komponen.
3. **JS** hanya bila perlu (`resources/assets/js/components/<nama>.js`): satu kelas per berkas, `class X extends Devlib.Component { static selector = "[data-dl-x]"; init() {} }`, `Devlib.define("X", X)` (contoh: `timer.js`). Berkas otomatis masuk bundel `devlib.js` (`DevlibServiceProvider::scripts`), tanpa langkah build. Pasangkan ke PHP lewat `jsClass()` (nama kelas JS; tes kontrak memeriksa kelasnya ada).
4. **CSS** di `resources/assets/devlib.css`: kelas berawalan `dl-`, warna dan ukuran **hanya lewat token** `--dl-*` (mis. `calc(13px * var(--dl-font-scale))`); tema hanya menimpa token (`Theme.php`), komponen tidak tahu tema. Teks hitam/terang tanpa abu-abu, abu-abu hanya `--dl-disabled` (kepala `devlib.css`).
5. **Manifest:** `php artisan devlib:manifest` (jangan edit `components.md` dengan tangan); `--check` dipakai tes.
6. **Tes kontrak:** `cd site; php artisan test --filter=ComponentContractTest` (turunan `DevlibComponent`, tag `x-dl::`, view atau `render()`, prop bertipe, `jsClass` valid dan ada, komentar tujuan, header REMARK, manifest mutakhir). Logika baru yang tidak sepele = tes kecil (acuan: `inheritance.md` bagian Kode proyek, `engine.md` butir 7).
7. **Technical Guide:** tulis rincian `resources/guide/<tag tanpa x-dl::>.md` (tujuh judul baku, semua prop dijelaskan; bentuk dan aturan: `help.md`). `GuideContractTest` gagal bila object baru belum punya rincian; jangan menambah baris ke `resources/guide/PENDING.txt`.
8. **Halaman contoh di katalog** (`site/`): demo `site/resources/views/demos/<slug>.blade.php` + entri di `Catalog::groups()` (`site/app/Support/Catalog.php`). `CatalogPagesTest` memastikan halaman terbuka dan tag ter-compile; `php artisan devlib:lint` memeriksa demo; `php artisan build:catalog` memperbarui `resources/data/build.json`. Halaman baru berstatus **Review**: hanya Jo yang menaikkannya ke `Catalog::APPROVED`.
9. **Catat** di `CHANGELOG.md` `[Belum dirilis]`; perubahan yang merusak antarmuka diawali **Merusak:** (`docs/versioning.md`).
10. **Commit di repo devlib** (git proyek tidak melihatnya; `inheritance.md` butir 7).

## 3. Aturan baku object

| Aturan | Sumber / penegak |
|---|---|
| Netral: `src/` tanpa `App\...`, model, service, kamus proyek; data masuk lewat prop atau kontrak; helper global di `src/helpers.php` | `inheritance.md` butir 2. Penegak ada di tes proyek pemakai; **BELUM ADA** tes setara di repo devlib |
| Opt-in dan kompatibel mundur: perubahan = prop/config baru dengan default lama | `inheritance.md` butir 3; `SKILL.md` Kebiasaan lintas proyek |
| Menamai ulang tag, menghapus prop, mengubah kontrak = **minor** `0.y.0` + `Merusak:` | `docs/versioning.md` |
| Teks layar ditulis English, kamus menerjemahkan; nilai data di prop teks dibungkus `raw()` | `SKILL.md` §2.3; `DevlibComponent::TEXT_PROPS` |
| Layar/demo = deklarasi `x-dl::*`: tanpa HTML mentah (L001), `<script>` inline (L002), hex warna (L003) | `php artisan devlib:lint` (`ViewLinter`) |
| Nilai warna/ukuran hanya di `devlib.css` (token `--dl-*`) | kepala `devlib.css` |
| Satu kelas satu tanggung jawab, method pendek, kegagalan = exception bernama, tanpa `catch` kosong, tanpa magic string | `inheritance.md` bagian Kode proyek; `engine.md` butir 5 dan 6 |
| Model non-UI: tidak terdaftar otomatis; data/hak/penyimpanan lewat kontrak yang diikat proyek | `assistant.md`, `chat.md`, `engine.md` |
| Multi view: setiap layar / object menyatakan dukungan view (phone, desktop, atau keduanya; tanpa pernyataan = desktop) dan object baru diuji di dua view (`Device::PHONE` dan `DESKTOP`); aturan khusus HP hanya di konteks phone | `views.md`; `DeviceViewTest`. Penegak lint untuk pernyataan view **BELUM ADA** |
| Aksesibilitas | **BELUM ADA ATURAN** tertulis; ikuti praktik komponen terdekat (mis. `aria-label` berterjemah di `layer-menu`) |
| Tanpa nama client/proyek atau nama orang sungguhan di kode, contoh, dan demo | `assistant.md` bagian Jangan; contoh baku fiktif |

## 4. Mewariskan (sisi proyek)

- **Turunan PHP:** `class X extends Kanban` di proyek; konstruktor memanggil `parent::__construct(...)` dengan nilai bawaan baru (properti ditimpa), `tag()` sendiri, `jsClass()` bila ada perilaku JS. Contoh: `site/app/View/Components/Demo/KanbanProduksi.php`.
- **Turunan JS:** `class X extends Devlib.classes.Kanban { ... super ... }` lalu `Devlib.register(X)` (method ditimpa). Contoh: `site/resources/views/demos/oo-override.blade.php`; halaman katalog `p5-override`.
- Perilaku yang berguna bagi proyek lain **naik ke DevLib** (induk), tidak disimpan di anak (`SKILL.md` §3).
- Model non-UI: proyek mengimplementasi kontrak dan mengikatnya (`AppServiceProvider`): `engine.md` Daftar dan katalog, `assistant.md` Memasang di proyek.
- Keluarga tag sendiri (paket turunan) mendaftar lewat `DevlibComponent::family()` agar katalog bisa menampilkan objeknya.
- Dilarang: menyalin aset DevLib ke proyek, atau membuat komponen UI di proyek bila bisa jadi object DevLib (`SKILL.md` Jangan).

## 5. Kapan sesuatu layak menjadi object DevLib

Tujuan DevLib (Jo, 2026-10-20): **menciptakan aturan dan object untuk diwariskan.** Dua pertanyaan sebelum menambah:

1. Apakah ini **aturan baku** atau **object yang diwariskan** ke proyek lain? (`inheritance.md` butir 1)
2. Apakah proyek **benar-benar memakainya**?

Salah satu jawabannya "tidak" = bukan tempatnya di DevLib; letakkan di proyek pemakai. Contoh penerapan: grup Special Forms dan `x-dl::master` dikeluarkan dari DevLib (`CHANGELOG.md`, bagian Dihapus). Apa yang dihitung "dipakai" (berapa proyek, seberapa sering): **BELUM ADA ATURAN**.

## 6. Versi dan rilis

Versi dari tag git (`vX.Y.Z`), SemVer `0.y.z`: minor boleh merusak, patch hanya perbaikan. Merilis: pindahkan butir `[Belum dirilis]`, tes hijau, `php artisan devlib:manifest --check` bersih, commit, `git tag -a`. Tag lokal dulu; push hanya atas keputusan Jo. Rinci: `docs/versioning.md`.

### Versi object dan default (aturan Jo, 2026-10-21)

1. **Hanya satu default**, dan default = **versi terakhir**. Punya v1, v2, v3: yang default v3.
2. Versi lama = **kode sejarah**: dikubur di riwayat git dengan tag `history/<nama>-vN`; tidak jadi kode aktif, tidak jadi default, dan **tanpa saklar atau pilihan antar versi**.
3. Nama object tetap satu pada kode aktif (tanpa akhiran `V2`/`V3`); nomor versi dicatat di `CHANGELOG.md` dan tag.
4. **Tidak ada "mundur".** Bila versi terakhir (vN) tidak disukai: ambil versi sebelumnya dari tag, **duplikasikan menjadi versi baru (vN+1)**, dan jadikan default. vN tetap riwayat.
5. Membangunkan yang dikubur (hanya atas perintah Jo): `git show history/<nama>-vN:<jalur>` untuk melihat, atau `git checkout history/<nama>-vN -- <jalur>` untuk mengambilnya, lalu jadikan versi baru (butir 4).

Tag riwayat yang ada: `history/menu-classic-v1` (Menu Classic: `x-dl::shell`), `history/dieline-v1` dan `history/dieline-v2` (Dieline lama; `Dieline` kini = versi terakhir, v3).
