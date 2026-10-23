# DevLib: multi view (smartphone dan desktop)

Aturan baku (permintaan Jo, 2026-10-23: "SET juga DEVLIB multi VIEW") untuk SEMUA proyek yang mewarisi DevLib: setiap layar dan object hidup di dua view, **phone** dan **desktop**. Pasang di proyek: `phone.md` (menu, login, kerangka HP). Halaman ini = model, aturan, dan padanan; semuanya **opt-in**: proyek yang tidak memakainya tidak berubah, dan view desktop tidak pernah mendapat aturan khusus HP.

## 1. Model: dua view dan satu OS

| View | Perangkat | Ciri |
|---|---|---|
| `phone` | smartphone potret, **Android dan iOS** | lebar CSS 320-448 px (uji acuan 360x790 Android, 393x852 iPhone), sentuh, layar satu tugas |
| `desktop` | laptop, desktop, **dan tablet** | tata letak desktop apa adanya; tablet besar (iPad, tablet Android) dan layar dalam lipat juga memakai view ini |

`Devlib\Device` (satu-satunya sumber; proyek tidak menebak sendiri):

| Panggilan | Hasil |
|---|---|
| `Device::view($request)` | `'phone'` atau `'desktop'`. Urutan: override permintaan (pratinjau), **cookie `ui` pilihan pengguna**, tebakan header |
| `Device::os($request)` | `'android'`, `'ios'`, `'other'` (dari User-Agent; override permintaan menang) |
| `Device::bodyClass($request)` | phone: `dl-on-phone dl-os-android` / `dl-on-phone dl-os-ios`; desktop: kosong |
| `Device::viewport($request)` | isi `<meta name="viewport">`; phone menambah `viewport-fit=cover` |
| `Device::normalize($v)` | nilai cookie yang sah (`phone`, `desktop`; alias lama `hp` = `phone`), selain itu `null` |
| `Device::PHONE`, `DESKTOP`, `CHOICES`, `COOKIE`, `ALIASES` | konstanta; `Device::HP` = nama lama `PHONE` (kode lama tetap jalan) |

Tebakan (hanya bila belum ada pilihan pengguna): `Sec-CH-UA-Mobile` (Chrome, Edge, Opera; hanya lewat HTTPS atau localhost), selain itu User-Agent. **Safari iOS tidak mengirim client hints**: iPhone/iPod = phone lewat User-Agent. Android: ada kata "Mobile" = phone, tidak ada = tablet = desktop. iPad = desktop; iPadOS 13+ mengirim UA Mac, jadi server melihat desktop (benar: tablet = desktop; sisi klien: `Devlib.os` memakai `maxTouchPoints`). Pilihan pengguna di `x-dl::device-pick` / popup `x-dl::phone-offer` disimpan di cookie `ui` (`phone` | `desktop`, satu tahun) oleh satu rute POST proyek; **pilihan pengguna selalu menang**. Cookie lama `ui=hp` tetap terbaca sebagai `phone`; proyek lama cukup ganti `Device::HP` / `fromRequest` ke `PHONE` / `view` (kedua nama lama masih berlaku) dan validasi POST dengan `Device::CHOICES` (+ `array_keys(Device::ALIASES)` bila form lama masih beredar).

## 1b. Aturan layar phone (batas 600 px; sementara)

Keputusan Jo 2026-10-23, berdasarkan riset breakpoint (laporan "Halaman ukur viewport dan breakpoint HP"): batas phone lawan desktop memakai **600 px**, bukan 760, dan ditambah aturan tinggi untuk HP landscape. Angka ini **sementara**: disesuaikan bila kenyataan di perangkat kurang pas (lebar CSS asli OPPO A6 Pro, tablet 8 inci, dan tinggi HP landscape belum terukur).

> Layar = **phone** bila lebar CSS **< 600 px**, ATAU (tinggi **< 480 px** DAN `pointer: coarse` DAN lebar **< 1000 px**) = HP landscape. Selain itu **desktop**: tablet (iPad mini 744 px, iPad 820+), ponsel lipat terbuka (690-755 px), laptop, dan jendela desktop yang pendek.

- Satu sumber: `Device::PHONE_MEDIA` (media query), `Device::screenIsPhone($lebar, $tinggi, $sentuh)` (versi PHP, untuk tes dan logika server), `Devlib.phoneScreen()` dan `html[data-dl-screen]` di `view.js`, serta CSS `x-dl::phone-offer`. Tes menjaga keempatnya sama persis.
- Alasan 600: batas "compact" Android (99,96% HP potret) dan satu-satunya angka yang punya dasar data; 760 / 768 menggolongkan iPad mini dan ponsel lipat sebagai HP. Semua HP potret (<= 448 px) aman di angka mana pun. Aturan tinggi memisahkan HP landscape (800-997 x 360-448) dari tablet; `pointer: coarse` menjaga jendela laptop yang pendek dan lebar `< 1000` menjaga monitor pendek.
- **Hanya untuk keputusan "layar ini layak ditawari tampilan phone"** (popup `x-dl::phone-offer`; `Devlib.phoneScreen()`). View yang dirender (`Device::view()`) tetap memakai tebakan header, cookie, dan pilihan pengguna (bagian 1): pilihan pengguna tetap menang. Layar pendek yang memenuhi aturan tetapi berview desktop ditawari popup; yang tablet / lipat tidak.
- Media query lain milik komponen (`760`, `640`, `900`, dst., mis. login `x-dl::frame`, dashboard, kanban) adalah penyesuaian tata letak sendiri, **bukan** pembeda phone / desktop, dan tidak diubah.
- Perangkat acuan: 599 = phone; 600 = desktop; 744 (iPad mini) = desktop; 761 = desktop; 820x1180 (iPad) = desktop; 690-755 (lipat dalam) = desktop; 800x360 sentuh = phone (landscape); 1280x400 sentuh = desktop.

## 2. Aturan wajib

1. **Setiap layar dan object menyatakan dukungan view-nya**: `phone`, `desktop`, atau keduanya. Tulis di header REMARK (baris `View`, mis. ` * View         : phone, desktop`) pada kelas PHP / berkas Blade / demo. Tanpa pernyataan = hanya `desktop` (kedudukan semua object lama; tidak ada yang rusak). **BELUM ADA penegak otomatis** (lint) untuk baris ini.
2. **Object baru diuji di dua view**: tes kontrak merender di `Device::DESKTOP` dan `Device::PHONE` (`Device::override($request, 'phone', 'ios')` atau cookie `ui`); halaman katalog dibuka lewat saklar **Lihat sebagai** (bagian 6) di Android phone, iPhone, dan Laptop. Tidak ada tampilan yang dinilai dari "tes lulus" saja (`CLAUDE.md` bagian 2).
3. **Phone dan desktop = dua varian perangkat, bukan versi.** Aturan "satu default, tanpa saklar versi" (`object.md` bagian 6) tidak berlaku di sini: pengguna boleh berpindah (cookie `ui`).
4. **Desktop tidak berubah.** Aturan khusus HP hanya boleh bekerja di konteks phone (`body.dl-on-phone`, `.dl-phone`); `CSS` baru tanpa konteks itu = cacat. Tes `DeviceViewTest::test_os_css_is_scoped_to_the_phone_view_only` menjaganya.
5. **Tanpa fungsi yang hanya ada saat hover** di view phone; sasaran sentuh >= 44 px (>= 48 px untuk aksi utama); isian >= 16 px (iOS memperbesar layar bila lebih kecil); jangan bergantung pada tinggi layar pasti (`dvh` / `svh` dengan cadangan `vh`).
6. **Teks dan data netral**: tanpa nama proyek / client; isi layar HP milik proyek, bagian umum milik DevLib.

## 3. Tabel padanan (desktop -> phone)

| Object desktop | Di phone |
|---|---|
| `x-dl::grid` | daftar kartu (`:phone-cards`), ketuk kartu = buka; Filters / Columns / Export = lembar dari bawah; cari selebar layar |
| `x-dl::form` | satu kolom, label di atas, isian 16 px / >= 44 px; aksi Simpan / Urungkan / Kembali di bilah aksi atas (keadaan B), tidak ada tombol menempel di bawah layar |
| report / `x-dl::print-out` | fit-to-sheet (diperkecil sampai muat lembar), bilah cetak di keadaan B |
| menu (`x-dl::layer-shell` / Layer Menu) | `x-dl::phone-shell`: ikon melayang -> bilah menu atas keadaan A (ikon) / B (aksi layar) / C (lima menu utama) + daftar modul |
| login | `x-dl::phone-login` (satu kolom fluid) |
| dashboard, kartu angka | `x-dl::phone-tiles` + `x-dl::phone-tile` (dua kolom) |
| layar yang belum punya versi phone | `x-dl::phone-shell :frame` (halaman desktop di dalam kerangka, tabel lebar menggulir) |

## 4. Khusus per OS (view phone; CSS di `devlib.css` bagian "Multi view", dibatasi `body.dl-on-phone`)

**iOS** (`dl-os-ios`, Safari iPhone):
- `viewport-fit=cover` (lewat `Device::viewport`) lalu `env(safe-area-inset-*)`: padding body (halaman tanpa kerangka) dan bilah / lembar kerangka HP menjaga notch, **Dynamic Island**, dan **home indicator**.
- Isian >= 16 px (`max(16px, 1em)`), jika tidak iOS memperbesar tampilan saat fokus; `-webkit-text-size-adjust: 100%` agar teks tidak membengkak di landscape.
- `100dvh` / `svh` (bilah alamat Safari berubah), cadangan `100vh`; tanpa hover; sasaran >= 44 px.
- Tombol "kembali" bawaan Safari (geser dari tepi) hidup bersama bilah aksi: jangan menaruh aksi geser dari tepi layar.

**Android** (`dl-os-android`, Chrome / Samsung Internet / Firefox):
- Bilah navigasi sistem (3 tombol / gestur): Chrome menaruh viewport di atasnya, jadi jarak bawah cukup `env(safe-area-inset-bottom)`; **tidak ada kontrol penting di tepi bawah layar** (sengaja: menu dan aksi ada di ikon melayang atas).
- **Pull-to-refresh Chrome**: `overscroll-behavior-y: contain` di view phone; jangan membuat gestur geser-turun dari tepi atas.
- **Keyboard HP**: `view.js` menyetel `--dl-vvh` (tinggi `visualViewport`) dan menggulir isian yang difokus ke tengah area terlihat; isian tidak tertutup keyboard.

**Sentuh di luar phone (opsional)**: class `dl-touch` di `<body>` (opt-in proyek) memperbesar `.dl-btn` >= 44 px pada `@media (pointer: coarse)` (tablet, laptop sentuh) tanpa mengubah tata letak desktop.

## 5. Memasang di proyek (ringkas; rincian `phone.md`)

1. Layout: `<meta name="viewport" content="{{ \Devlib\Device::viewport(request()) }}">` dan `<body class="dl-body {{ \Devlib\Device::bodyClass(request()) }}">`. Desktop: isi dan kelas sama seperti sebelumnya.
2. Rute `POST ui` (di luar `auth`) menyimpan `Device::normalize($ui)` di cookie `Device::COOKIE`; pilih view dengan `Device::view($request)`.
3. Bagian umum: `x-dl::device-pick`, `x-dl::phone-offer`, `x-dl::phone-login`, `x-dl::phone-shell`, dst. (`phone.md` bagian 2).
4. Pratinjau (opsional, situs katalog / QA): `$middleware->appendToGroup('web', \Devlib\ViewOverride::class)` lalu `x-dl::view-as` + `x-dl::device-frame :src`. Tanpa middleware itu parameter `dl-view` diabaikan.

## 6. Pratinjau "Lihat sebagai" (katalog DevLib)

Pil di samping tombol `</>` pada setiap halaman katalog: **Android phone 360x790**, **iPhone 393x852**, **Tablet 820x1180**, **Laptop 1366x768**, **Desktop 1920x1080**. Memilih satu membuka `/lib/{halaman}/as?as=<perangkat>`: halaman yang sama di `x-dl::device-frame` (mode `src`: `<iframe>` seukuran perangkat, diperkecil agar muat, jadi `@media` dan `dvh` di dalamnya mengikuti ukuran perangkat). Alamat iframe diberi `?dl-view=phone|desktop&dl-os=android|ios|other`; `Devlib\ViewOverride` meneruskannya ke `Device::override`, sehingga server benar-benar merender view phone atau desktop (bukan sekadar mengecilkan jendela). Tombol perangkat mengganti ukuran tanpa memuat ulang halaman bingkai; **Tutup** kembali ke halaman asli. Pratinjau tidak mengubah cookie pengguna dan hanya berlaku untuk iframe itu: tautan yang dibuka di dalam iframe kehilangan parameter (pratinjau satu halaman; alur multi-halaman dibuka dari awal di perangkat yang dipilih).

Halaman berstatus APPROVED / DIKUNCI (`Catalog::APPROVED`, mis. `p5-grid-tx`) tidak berubah: pil hanya penambahan di luar isi demo, tersembunyi di dalam iframe pratinjau; HTML halaman tanpa pil identik dengan sebelumnya (diuji dengan perbandingan HTML).

## 7. Tes (acuan)

- `DeviceViewTest` (situs katalog): view dan OS dari User-Agent nyata (Chrome Android ponsel / tablet, Safari iPhone, Chrome iPhone, Safari iPad lama, Safari iPad bermode Mac, Safari Mac, Chrome Windows, Edge Windows / Android, Samsung Internet, Firefox Android ponsel / tablet, Firefox Linux), client hint vs UA vs cookie, alias `hp`, override, viewport, cakupan CSS, `device-frame`, `view-as`.
- `PhoneShellTest`: pilihan, kerangka, form / grid di konteks HP.

## 8. Batas yang diketahui

- iPadOS bermode Mac dianggap desktop di server (benar untuk tablet); OS-nya `other` di server, `ios` di `Devlib.os` (klien).
- Layar lipat terbuka = desktop (Chrome melaporkan `?0` atau UA tanpa "Mobile"); pengguna dapat memilih phone.
- Penegak lint untuk baris `View` belum ada.
- Pratinjau emulasi ukuran (CSS px); angka perangkat nyata (DPR, bilah sistem) belum diverifikasi di perangkat Jo.
