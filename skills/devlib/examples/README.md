# Contoh baku (milik DevLib)

Cuplikan proyek paling ringkas yang memakai DevLib. Disalin ke proyek baru lalu disesuaikan; proyek lain TIDAK menjadi sumber contoh (arah: DevLib -> proyek).

| Berkas | Untuk |
|---|---|
| `config/devlib.php` | konfigurasi DevLib proyek (brand, company, lang, back_history=false, abilities) |
| `layout.blade.php` | kerangka HTML: aset DevLib, tema, penjaga kirim ganda |
| `login.blade.php` | login (`x-dl::frame` + `x-dl::login-box`) |
| `categories/index.blade.php` | grid master (`kind="master"`) |
| `categories/form.blade.php` | form master (`kind="master"`) |

Nama `sstock`, route, dan gambar latar di dalamnya hanya contoh: ganti sesuai proyek. Bila pola baku berubah, perbarui berkas di sini bersama aturannya di `SKILL.md`.
