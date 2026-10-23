# DevLib: tampilan HP (smartphone potret)

Panduan memasang tampilan HP di proyek baru atau lama. Tampilan HP dan desktop adalah dua varian PERANGKAT (bukan versi lama / baru: aturan "satu default, tanpa saklar versi" di `object.md` tidak berlaku di sini). Semua bagian umum ada di DevLib dan **opt-in**: proyek yang tidak memakainya tidak berubah. Proyek hanya membuat isi layar HP-nya sendiri.

Uji acuan: Chrome Android potret **360 x 790 px CSS** (OPPO A6 Pro). Tata letak mengalir dari **320 sampai sekitar 440 px** tanpa titik patah di dalam rentang itu; tinggi memakai `dvh` (bilah alamat Chrome mengubah tinggi nyata, jangan bergantung pada tinggi pasti). Batas "layar sempit" untuk popup tawaran: `<= 760 px`.

## 1. Apa milik DevLib, apa milik proyek

| DevLib (generik, jangan disalin) | Proyek (isi dan aturan sendiri) |
|---|---|
| `Devlib\Device`: tebakan perangkat + cookie `ui` | Rute `POST /ui` yang menyimpan cookie, dan pemilihan view menurut perangkat |
| `x-dl::device-pick`: dua radio `ui` | Isi menu (modul, tab), hak akses |
| `x-dl::phone-offer`: popup tawaran di login desktop sempit | Layar HP: isi Beranda, daftar, layar kerja (mis. hitung stok satu barang per kartu), JS-nya |
| `x-dl::phone-login`: halaman login HP | Data tab dan modul (`tabs`, `modules`) untuk `x-dl::phone-shell` |
| `x-dl::phone-shell`: bilah atas, tab bawah, lembar Menu, `frame` (halaman desktop di dalam kerangka) | Teks layar (kamus bahasa proyek) |
| `x-dl::phone-tile(s)`, `x-dl::phone-link`, `x-dl::phone-bar`: kartu angka, baris tautan, bilah tombol menempel di bawah | |
| Aksi "Phone view" di `x-dl::layer-shell` (prop `deviceAction`) | |

Pola layar kerja satu-barang-per-kartu (progress, cari/pindai, simpan per barang dengan antrean tahan putus koneksi) belum jadi komponen: logikanya terikat pada endpoint proyek. Bila proyek kedua memerlukannya, naikkan ke DevLib sebagai komponen opt-in (aturan `object.md` §5).

## 2. Urutan memasang (proyek baru atau lama)

1. **Cookie dan rute.** Satu rute POST untuk tamu dan pengguna masuk (di luar grup `auth`, tetap CSRF):
   ```php
   Route::post('ui', fn (Request $r) => redirect()->route('home')->withCookie(cookie(Device::COOKIE, $r->validate(['ui' => ['required', Rule::in(Device::CHOICES)]])['ui'], 60 * 24 * 365)))->name('ui.set');
   ```
   (`home` bagi tamu akan dialihkan ke login oleh middleware `auth`.)
2. **Memilih view.** `Device::fromRequest($request)` = `hp` | `desktop`: cookie `ui` yang sah menang; selain itu `Sec-CH-UA-Mobile`, lalu User-Agent (ponsel saja; tablet dan "situs desktop" = desktop).
   ```php
   return view(Device::fromRequest($request) === Device::HP ? 'login-hp' : 'login', [...]);
   ```
3. **Login HP.** View `login-hp`: `@extends` layout proyek, lalu
   ```blade
   <x-dl::phone-login :action="route('login.attempt')" :error="$error" :username="$username" :forgot-url="route('password.request')" :device-action="route('ui.set')">
       @if (session('status'))<x-dl::alert tone="success" :message="session('status')" />@endif
   </x-dl::phone-login>
   ```
4. **Login desktop.** Tambahkan di bawah `x-dl::frame`: `<x-dl::phone-offer :action="route('ui.set')" />` (hanya tertulis bila belum ada pilihan dan perangkat desktop; CSS menampilkannya hanya pada lebar <= 760 px).
5. **Kerangka HP.** Satu layout `hp/layout.blade.php` berisi `<x-dl::phone-shell :title :back :active :tabs :modules :user :logout-action :device-action :frame>@yield('content')</x-dl::phone-shell>`. `tabs` = 4 tab tautan (`key`, `label`, `icon`, `url`; tab Menu ditambah komponen), `modules` = bentuk sama dengan `x-dl::layer-shell` (saring menurut hak akses di server). Layar yang belum punya tampilan HP: `:frame="url-halaman-desktop"` (iframe di dalam kerangka).
6. **Isi layar HP** dengan `x-dl::phone-tiles` + `x-dl::phone-tile`, `x-dl::phone-link`, `x-dl::phone-bar`, `x-dl::alert`, `x-dl::button`; isian standar (`type=text inputmode=decimal`, 16 px) supaya keyboard HP, pen, dan pemindai barcode Bluetooth/USB (bertingkah seperti keyboard + Enter) bekerja. Tanpa fungsi yang hanya muncul saat hover.
7. **Beranda.** Controller Beranda: bila `hp`, kembalikan view HP (data sama, tampilan lain). Desktop tidak disentuh.
8. **Saklar di desktop (opsional).** `x-dl::layer-shell :device-action="route('ui.set')"` menambah aksi "Phone view" di menu profil.
9. **Tes.** Cek: login ponsel = login HP; komputer tanpa cookie = login desktop + popup; cookie menang atas tebakan di dua arah; POST `ui` menolak nilai lain; menu HP hanya berisi modul yang boleh dibuka; desktop identik (tidak ada `dl-phone`). Acuan: `PhoneViewTest` di proyek pertama dan `site/tests/Feature/PhoneShellTest.php`.
10. **Kamus.** Teks komponen ada di kamus DevLib (`phone_offer*`) atau memakai teks English yang diterjemahkan kamus proyek (`Phone view`, `Desktop view`, `Display`, `Apply`, `Menu`, `Sign out`).

### Form dan layar desktop di dalam kerangka HP

Layar desktop yang dibuka lewat `:frame` (iframe) tidak tahu bahwa ia ada di HP. Tambahkan `Devlib\Device::bodyClass(request())` ke `<body class="...">` layout proyek: hasilnya `dl-on-phone` bila perangkat `hp` (kosong di desktop). Dengan kelas itu CSS DevLib memadatkan `x-dl::form` menjadi satu kolom (label di atas, isian 16 px / >= 44 px, plus tambah-cepat sebaris, bilah Simpan / Urungkan / Kembali menempel di bawah di atas bilah tab, panel statistik dua kolom). Tanpa kelas itu (desktop) tidak ada aturan yang cocok, dan pembungkus tombol `dl-form__acts` adalah `display: contents` sehingga tata letak desktop identik.

## 3. Yang opt-in dan yang tidak berubah

- Tanpa memakai satu pun komponen di atas, proyek lama tidak berubah. `x-dl::login-box` bagian `device` dan `x-dl::layer-shell` prop `deviceAction` default mati; markup lama identik (dibuktikan tes).
- Tidak ada dependensi baru (Composer atau npm). Deteksi: header + `User-Agent`, tanpa pustaka.

## 4. Batas yang diketahui

- `Sec-CH-UA-Mobile` hanya dikirim Chrome lewat HTTPS atau `localhost`; lewat HTTP biasa (alamat LAN) deteksi memakai User-Agent.
- "Situs desktop" di Chrome HP mengirim User-Agent desktop dan lebar ~980 px: dianggap desktop; pengguna memilih lewat tautan "Phone view" di profil desktop (atau halaman login HP jika sudah memilih).
- Layar desktop di dalam `frame` tidak dirapikan untuk HP; tabel lebar menggulir di dalamnya. Beri layar yang sering dipakai versi HP sendiri.
- Tinggi nyata di Chrome Android lebih kecil dari 790 px (bilah alamat/status): jangan menaruh aksi penting di tepi bawah selain bilah tab; `x-dl::phone-bar` aman karena berada di atas bilah tab.
- Lebar fluid 320 sampai 440 px teruji di emulasi; angka DPR OPPO A6 Pro belum diverifikasi di perangkat.

## 5. Daftar komponen HP

`x-dl::phone-shell` (kerangka), `x-dl::phone-login`, `x-dl::phone-offer`, `x-dl::device-pick`, `x-dl::phone-tiles` + `x-dl::phone-tile`, `x-dl::phone-link`, `x-dl::phone-bar`; kelas `Devlib\Device`. Rincian prop: `components.md` dan Technical Guide (`resources/guide/phone-*.md`). Halaman peraga katalog: `p7-phone` (status Review).
