# DevLib: pasang di proyek baru

Dimuat dari SKILL.md hanya saat proyek belum memakai `jo/devlib` (cek `composer.json`).

Cek dulu `composer.json` proyek: bila `jo/devlib` sudah ada, lewati langkah ini.

1. `composer.json`:
   ```json
   "require": { "jo/devlib": "@dev" },
   "repositories": [{ "type": "path", "url": "../devlib", "options": { "symlink": true } }]
   ```
   lalu `composer update jo/devlib` (provider terdaftar otomatis).
2. Tailwind 4, di CSS aplikasi (path relatif terhadap berkas CSS itu):
   ```css
   @source '../../../devlib/src';
   @source '../../../devlib/resources';
   ```
3. `config/devlib.php` — salin dari `examples/config/devlib.php` (folder skill ini) lalu ubah `brand`, `company`, `lang`, `timezone`. Kunci: `brand`, `company`, `lang`, `translate_fixed` (aplikasi satu bahasa), `back_history` (**set `false`**, lihat bagian Form), `timezone`, `timezone_label`, `media_path/url`, `lookup_middleware`, `abilities`.
4. Layout — salin `examples/layout.blade.php`. Intinya di `<head>`:
   ```blade
   <link rel="stylesheet" href="{{ \Devlib\DevlibServiceProvider::asset('devlib.css') }}">
   <x-dl::theme-boot />
   <script src="{{ \Devlib\DevlibServiceProvider::asset('devlib.js') }}" defer></script>
   ```
   `<body class="dl-body">`. Aset disajikan dari paket (`/_devlib/{file}`); jangan disalin ke proyek.
5. Hak akses `can="modul.aksi"`: pasang sekali di `AppServiceProvider` lewat `\Devlib\Access::resolveUsing(fn ($ability) => ...)`; tanpa itu dipakai `config('devlib.abilities')`.
