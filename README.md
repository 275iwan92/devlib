# DevLib (`jo/devlib`)

Library induk komponen UI/UX ERP Contoh Makmur: objek `x-dl::*` (PHP + JS berpasangan), helper `raw()`, tema, bahasa, dan aset.
Berdiri sendiri: tanpa kode `App\` milik aplikasi mana pun, tanpa database.

Lokasi: `C:\Users\iwanp\Herd\devlib`, setara `tmerp` (dipindah dari `tmerp/packages/devlib` pada 2026-10-19; riwayat 280 commit ikut terbawa,
commit pemisahan diberi tag `split-from-tmerp`).

## Memasang di proyek lain

Di `composer.json` proyek (path relatif terhadap proyek itu):

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

## Skill Claude Code

`skills/devlib/SKILL.md` = skill untuk membangun proyek apa pun dengan DevLib (pasang, layout, pola layar). Sumbernya di repo ini; agar aktif di semua proyek, tautkan ke folder skill pribadi (sekali per mesin):

```powershell
New-Item -ItemType Junction -Path "$HOME\.claude\skills\devlib" -Target "C:\Users\iwanp\Herd\devlib\skills\devlib"
```

## Bekerja di DevLib

- Perubahan DevLib di-commit di repo ini, bukan di git aplikasi yang memakainya.
- Demo dan katalog ada di situs `tmerp/devlib` (devlib.test); proyek lain membuat situs katalognya sendiri bila perlu.
- Aturan: DevLib membuat, aplikasi mewarisi (skill `devlib-oo`, `devlib-inheritance` di repo tmerp).
