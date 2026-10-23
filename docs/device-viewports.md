# Resolusi smartphone dan viewport CSS (acuan tampilan HP DevLib/Stoku)

Dibuat 2026-10-23. Data mentah: [device-viewports.json](device-viewports.json) (321 baris, 290 HP potret). Yang menentukan tata letak adalah **lebar viewport CSS** (fisik / DPR), bukan piksel fisik.

## Ringkas

1. **Lebar CSS HP potret hampir seluruhnya 320 sampai 448.** Di Indonesia dominan **360** (±24% dari trafik, 360x800 saja 11,5%), lalu 384 sampai 393.
2. **Viewport uji:** 360x800 (kecil), 393x873 (tengah), 430x932 (besar), plus ekstrem 320x568 dan 375x667. OPPO A6 Pro milik Jo = acuan utama, **angkanya belum terukur** (lihat bagian 6).
3. **Batas HP vs tablet: lebar < 600 px = tampilan HP.** HP potret terlebar yang terukur 448; layar dalam lipat mulai 690.
4. **Satu model bisa punya beberapa lebar** (Ukuran tampilan Android, Display Zoom iPhone). Lantai desain tetap **320**.
5. Banyak nilai di tabel masih **perkiraan** (136 dari 290 HP). Hanya baris berdasar "terukur" (56) yang kuat.

## 1. Rentang dan klaster lebar

Lalu lintas web mobile Indonesia ([StatCounter](https://gs.statcounter.com/screen-resolution-stats/mobile/indonesia), rata-rata Okt 2025 sampai Sep 2026; hanya 20 ukuran teratas diterbitkan, sisanya 44% "Other"):

| Lebar CSS | 12 bulan | Sep 2026 | Perangkat khas (kesimpulan penulis dari data terukur) |
|---|---|---|---|
| 360 | 24,0% | 17,6% | HP HD+ 720 px DPR 2 (OPPO A, Realme C/Note, Redmi 13C/14C, Infinix/Tecno murah); Samsung S dasar |
| 384 dan 385 | 10,5% | 11,8% | Samsung A-series 1080 px dan Ultra (384x832); Samsung HD+ dan vivo lama (385x854) |
| 390 dan 393 | 9,2% | 9,2% | iPhone 12 sampai 16 dasar; Redmi/realme/vivo/OPPO 1080 px (393x873) |
| 412 dan 414 | 7,9% | 8,7% | iPhone XR/11 (414), Pixel dan Samsung "lebih kecil" (412) |
| 375 | 2,7% | n/a | iPhone X/XS/mini/11 Pro |
| 424 | 2,0% | 1,5% | tidak teridentifikasi |

Ukuran teratas: 360x800 (11,5%), 393x873 (5,2%), 385x854 (4,1%), 414x896 (3,9%), 360x806 (3,7%), 384x832 (3,1%). StatCounter tidak menyebut model; kolom perangkat adalah inferensi.

Pembanding global ([DeviceAtlas](https://deviceatlas.com/blog/viewport-resolution-diagonal-screen-size-and-dpi-most-popular-smartphones), 20,4 juta rekaman, Mei 2026): 360 = 18,2%, 384 = 17,4%, 412 = 17,1%, **320 = 7,0%**. Pita: ≤360 31%, 361 sampai 393 34%, 394 sampai 412 19%, >412 17%.

**Pangsa merek di Indonesia** (StatCounter, trafik web 12 bulan, bukan penjualan): Apple 16,5%; Samsung 15,3%; OPPO 13,6%; Xiaomi/Redmi/POCO 11,1%; vivo 10,3%; Realme 5,0%; Infinix 4,4%; Google 1,1%; Tecno 0,5%; tak dikenal 20,9%. Android 83,3% / iOS 16,5%; Chrome 81,7%, Safari 13,6%. Pengiriman unit (sekunder, belum diverifikasi): Omdia 2025 Xiaomi 19%, Transsion 18%, Samsung 17%, OPPO 16%, vivo 15%. **Pangsa per model: tidak ditemukan.**

## 2. Viewport uji yang disarankan

| Peran | CSS (lebar x tinggi) | DPR untuk emulasi | Alasan |
|---|---|---|---|
| Kecil | **360x800** | 3 (atau 2) | lebar dominan Indonesia; kemungkinan sama dengan OPPO A6 Pro |
| Tengah | **393x873** | 2,75 | Redmi/realme/vivo 1080 px terukur; antara Samsung 384 dan iPhone 390 |
| Besar | **430x932** | 3 | iPhone Plus/Pro Max (430 sampai 440); Pixel Pro XL 448 sebagai batas atas |
| Acuan Jo | OPPO A6 Pro, ukur dulu | ukur | kira-kira 360x791 bila DPR 3; pola OPPO 1080 px lain memberi 393x863 |
| Ekstrem lebar | 320x568 | 2 | 7% trafik global; Ukuran tampilan terbesar, iPhone Larger Text, SE 1 |
| Ekstrem tinggi | 375x667 | 2 | iPhone SE: tinggi pendek untuk bilah bawah dan papan ketik |

Emulasi di Chrome desktop: DevTools, mode perangkat, **Add custom device** (jendela Chrome sendiri tidak bisa dikecilkan sampai 360). Isi lebar, tinggi, DPR seperti di tabel; pilih "Mobile". Tetap uji di HP sungguhan.

## 3. Titik patah media query (potret)

- **`< 600px` = tampilan HP**, `>= 600px` = desktop/tablet. Alasan: HP terlebar terukur 448; layar dalam lipat 690 (Fold5), 707 (Fold6, dihitung), 750 (Fold7), 755 (Pixel 9 Pro Fold); iPad mini 744. Android memakai kelas "compact" untuk lebar < 600dp ([sumber](https://developer.android.com/develop/ui/compose/layouts/adaptive/use-window-size-classes)). Layar cover lipat (344 sampai 444) tetap tampilan HP.
- **`< 360px`**: mode sempit untuk 320 sampai 359.
- **Tinggi `< 700px`**: mode pendek (SE, papan ketik terbuka).
- Antara 360 dan 599 tata letak **cair**, tanpa titik patah per perangkat; batasi lebar isi (mis. 480 px, tengah) agar 430 sampai 599 tidak kosong.
- Sasaran sentuh: minimal 44x44 CSS px untuk aksi utama, jarak antar sasaran minimal 8 px. WCAG 2.2 AA mewajibkan minimal 24x24 ([sumber](https://www.w3.org/WAI/WCAG22/Understanding/target-size-minimum.html)); 44 pt (Apple) dan 48 dp (Android) adalah pengetahuan umum penulis, tidak dibuka dari sumbernya.

## 4. Catatan praktis

- **Area aman iPhone.** Inset portrait: iPhone 17 / 17 Pro / 17 Pro Max atas 62 px, iPhone Air atas 68 px, bawah 34 px ([sumber](https://useyourloaf.com/blog/iphone-17-screen-sizes/)). Nilai `env(safe-area-inset-*)` baru terisi bila `<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">`; tulis dengan cadangan, mis. `env(safe-area-inset-bottom, 0px)`. Jangan pakai `user-scalable=no`.
- **Bilah alamat Chrome mengubah tinggi.** `100vh` sama dengan `lvh` (bilah tersembunyi), sehingga bagian bawah terpotong saat bilah tampil ([MDN](https://developer.mozilla.org/en-US/docs/Web/CSS/length)). Aturan: halaman penuh seperti login `min-height: 100svh` (aman, bilah tampil); kerangka aplikasi tetap `100dvh` (mengikuti bilah, nilainya berubah saat gulir); tulis `100vh` dulu sebagai cadangan. Dukungan svh/dvh (Chrome 108, Safari 15.4) dari pengetahuan umum; tabel MDN tidak terbaca, cek caniuse sebelum dikunci.
- **Papan ketik.** Sejak Chrome 108 Android, papan ketik hanya mengecilkan viewport visual; satuan vh/dvh tidak menyusut dan elemen `fixed` bisa tertutup. Pulihkan perilaku lama dengan `interactive-widget=resizes-content` pada meta viewport ([sumber](https://developer.chrome.com/blog/viewport-resize-behavior)).
- **Tinggi.** Tinggi tidak tetap (bilah alamat, papan ketik, bilah sistem). Rancang layar penuh untuk tinggi minimal 667 dan biarkan menggulir; jangan mengunci pada 800 atau 873. Angka tinggi yang dimakan bilah alamat Chrome: tidak ditemukan, ukur di HP.
- **Ukuran tampilan Android mengubah lebar CSS.** Pengaturan "Ukuran tampilan" (Screen zoom) mengubah densitas, sehingga DPR dan lebar berubah ([kode Settings](https://git.replicant.us/replicant-next/packages_apps_Settings/tree/src/com/android/settings/display/ScreenZoomSettings.java), [Chromium](https://chromium.googlesource.com/chromium/src/+/refs/heads/main/ui/android/java/src/org/chromium/ui/display/PhysicalDisplayAndroid.java)). Terukur pada Galaxy S24 Ultra: bawaan 384 (36% lalu lintas model itu), lebih kecil 412, lebih besar 360, terbesar 320. Resolusi layar (FHD+ atau WQHD+) sendiri tidak mengubah lebar pada ukuran yang sama. iPhone: Display Zoom "Larger Text" 393 menjadi 320, 440 menjadi 375.
- **Ukuran huruf Android** hanya memperbesar teks (autosizing), tidak mengubah `innerWidth` atau DPR menurut dokumentasi Chromium ([sumber](https://chromium.googlesource.com/chromium/src/+show/56ed97b900f0943480077ee4fa1b94de137e97ff/android_webview/docs/web-page-layout.md)); **belum diuji di perangkat**. Akibat praktis: teks membungkus lebih banyak dan halaman lebih tinggi, jadi jangan beri tinggi tetap pada kotak teks.
- **Resolusi layar flagship.** Samsung QHD+ (Ultra, S25+) dikirim dalam FHD+; 1440/DPR 3 = 480 adalah angka hitungan yang tidak pernah terukur. Abaikan 480.
- **Gambar.** DPR 2 sampai 3,75: sediakan aset 2x/3x untuk ikon dan logo.
- **Lipat.** Layar cover dan dalam adalah dua viewport pada satu sesi; tangani lewat resize, bukan deteksi perangkat. Layar dalam = area tablet.

## 5. Tabel acuan (pilihan; lengkap di JSON)

Arti "Dasar": **terukur** = lebar yang dilaporkan peramban pada lalu lintas nyata (DeviceAtlas); **dikutip** = angka tertulis di satu agregator (yesviz atau sejenis); **dihitung** = fisik / DPR dari sumber; **perkiraan** = pola dari model berpanel sama (keyakinan rendah). Kategori: kecil < 375, menengah 375 sampai 413, besar ≥ 414. Tahun baris yesviz = pengetahuan umum penulis.

| Merek | Model | Th | Inci | Fisik (px) | DPR | CSS potret | Dasar | Kat |
|---|---|---|---|---|---|---|---|---|
| OPPO | A6 Pro | 2025 | 6.57 | 1080x2372 | 3.0 | 360x791 | perkiraan | kecil |
| OPPO | A5 | 2025 | 6.67 | 720x1604 | 2.0 | 360x802 | dihitung | kecil |
| OPPO | A3 | 2024 | 6.67 | 720x1604 | 2.0 | 360x802 | dihitung | kecil |
| OPPO | A60 | 2024 | 6.67 | 720x1604 | 2.0 | 360x802 | dihitung | kecil |
| OPPO | A38 | 2023 | 6.56 | 720x1612 | 2.0 | 360x806 | terukur | kecil |
| OPPO | Reno13 F | 2025 | 6.67 | 1080x2400 | 2.75 | 393x873 | perkiraan | menengah |
| OPPO | Reno14 | 2025 | 6.59 | 1256x2760 | 3.0 | 419x920 | perkiraan | besar |
| OPPO | Reno7 Pro 5G | 2022 | 6.55 | 1080x2400 | 2.75 | 393x873 | terukur | menengah |
| Samsung | Galaxy A06 | 2024 | 6.7 | 720x1600 | 1.87 | 385x854 | perkiraan | menengah |
| Samsung | Galaxy A12 | 2020 | 6.5 | 720x1600 | 1.87 | 385x854 | terukur | menengah |
| Samsung | Galaxy A16 | 2024 | 6.7 | 1080x2340 | 2.8125 | 384x832 | perkiraan | menengah |
| Samsung | Galaxy A26 5G | 2025 | 6.7 | 1080x2340 | 2.8125 | 384x832 | perkiraan | menengah |
| Samsung | Galaxy A36 | 2025 | 6.7 | 1080x2408 | 2.8125 | 384x856 | perkiraan | menengah |
| Samsung | Galaxy A56 5G | 2025 | 6.7 | 1080x2340 | 2.8125 | 384x832 | perkiraan | menengah |
| Samsung | Galaxy A54 | 2023 | 6.4 | 1080x2340 | 2.8125 | 384x832 | terukur | menengah |
| Samsung | Galaxy S24 | 2024 | 6.2 | 1080x2340 | 3.0 | 360x780 | terukur | kecil |
| Samsung | Galaxy S25 | 2025 | 6.2 | 1080x2340 | 3.0 | 360x780 | dikutip | kecil |
| Samsung | Galaxy S24 Ultra | 2024 | 6.8 | 1440x3120 | 2.8125 | 384x832 | terukur | menengah |
| Samsung | Galaxy S25 Ultra | 2025 | 6.9 | 1440x3120 | 2.8125 | 384x832 | terukur | menengah |
| Apple | iPhone SE (3rd gen, 2022) | 2022 | 4.7 | 750x1334 | 2.0 | 375x667 | terukur | menengah |
| Apple | iPhone 11 | 2019 | 6.1 | 828x1792 | 2.0 | 414x896 | terukur | besar |
| Apple | iPhone 12 mini | 2020 | 5.4 | 1080x2340 | 2.88 | 375x812 | terukur | menengah |
| Apple | iPhone 13 | 2021 | 6.1 | 1170x2532 | 3.0 | 390x844 | terukur | menengah |
| Apple | iPhone 16e | 2025 | 6.1 | 1170x2532 | 3 | 390x844 | terukur | menengah |
| Apple | iPhone 16 | 2024 | 6.1 | 1179x2556 | 3.0 | 393x852 | terukur | menengah |
| Apple | iPhone 16 Pro | 2024 | 6.3 | 1206x2622 | 3.0 | 402x874 | terukur | menengah |
| Apple | iPhone 16 Plus | 2024 | 6.7 | 1290x2796 | 3.0 | 430x932 | terukur | besar |
| Apple | iPhone 16 Pro Max | 2024 | 6.9 | 1320x2868 | 3.0 | 440x956 | terukur | besar |
| Apple | iPhone 17 | 2025 | 6.3 | 1206x2622 | 3.0 | 402x874 | terukur | menengah |
| Apple | iPhone Air | 2025 | 6.5 | 1260x2736 | 3.0 | 420x912 | dikutip | besar |
| Apple | iPhone 17 Pro Max | 2025 | 6.9 | 1320x2868 | 3.0 | 440x956 | terukur | besar |
| Redmi | Redmi A3 | 2024 | 6.71 | 720x1650 | 2.0 | 360x825 | dikutip | kecil |
| Redmi | Redmi 13C (4G) | 2023 | 6.74 | 720x1600 | 2.0 | 360x800 | perkiraan | kecil |
| Redmi | Redmi 14C (4G) | 2024 | 6.88 | 720x1640 | 2.0 | 360x820 | terukur | kecil |
| Redmi | Redmi 13 (4G) | 2024 | 6.79 | 1080x2460 | 2.75 | 393x895 | dikutip | menengah |
| Redmi | Redmi Note 13 4G | 2024 | 6.67 | 1080x2400 | 2.75 | 393x873 | terukur | menengah |
| Redmi | Redmi Note 14 5G | 2025 | 6.67 | 1080x2400 | 2.75 | 393x873 | dikutip | menengah |
| Redmi | Redmi Note 14 Pro 5G | 2025 | 6.67 | 1220x2712 | 3.0 | 407x904 | dikutip | menengah |
| POCO | POCO C75 (4G) | 2024 | 6.88 | 720x1640 | 2.0 | 360x820 | perkiraan | kecil |
| POCO | POCO X7 | 2025 | 6.67 | 1220x2712 | 3.0 | 407x904 | perkiraan | menengah |
| vivo | Y19s (4G) | 2024 | 6.68 | 720x1608 | 2.0 | 360x804 | terukur | kecil |
| vivo | Y29 4G | 2025 | 6.68 | 720x1608 | 2.0 | 360x804 | perkiraan | kecil |
| vivo | Y36 | 2023 | 6.64 | 1080x2388 | 2.75 | 393x869 | terukur | menengah |
| vivo | Y400 (5G) | 2025 | 6.67 | 1080x2400 | 2.75 | 393x873 | perkiraan | menengah |
| vivo | V50 | 2025 | 6.77 | 1080x2392 | 2.75 | 393x870 | perkiraan | menengah |
| Realme | Note 50 | 2024 | 6.7 | 720x1600 | 2.0 | 360x800 | terukur | kecil |
| Realme | C63 | 2024 | 6.75 | 720x1600 | 2.0 | 360x800 | dihitung | kecil |
| Realme | C67 | 2023 | 6.72 | 1080x2400 | 2.75 | 393x873 | terukur | menengah |
| Realme | 14 5G | - | 6.67 | 1080x2400 | 2.75 | 393x873 | perkiraan | menengah |
| Infinix | Hot 60i | 2025 | 6.75 | 720x1600 | 2.0 | 360x800 | perkiraan | kecil |
| Infinix | Hot 50 4G | 2024 | 6.78 | 1080x2460 | 2.75 | 393x895 | perkiraan | menengah |
| Infinix | Note 50 4G | 2025 | 6.78 | 1080x2436 | 2.75 | 393x886 | perkiraan | menengah |
| Tecno | Spark 40 | 2025 | 6.75 | 720x1600 | 2.0 | 360x800 | perkiraan | kecil |
| Google | Pixel 9 | 2024 | 6.3 | 1080x2424 | 2.625 | 412x924 | terukur | menengah |
| Google | Pixel 9a | 2025 | 6.3 | 1080x2424 | 2.625 | 412x924 | terukur | menengah |
| Google | Pixel 9 Pro XL | 2024 | 6.8 | 1344x2992 | 3.0 | 448x998 | terukur | besar |
| Samsung | Galaxy Z Fold5 (cover) | 2023 | 6.2 | 904x2316 | 2.625 | 344x882 | dikutip | cover |
| Samsung | Galaxy Z Fold7 (cover) | 2025 | 6.5 | 1080x2520 | 2.625 | 412x960 | terukur | cover |
| Google | Pixel 9 Pro Fold (cover) | 2024 | 6.3 | 1080x2424 | 2.43 | 444x995 | terukur | cover |
| Samsung | Galaxy Z Fold5 (dalam) | 2023 | 7.6 | 1812x2176 | 2.625 | 690x829 | dikutip | dalam |
| Samsung | Galaxy Z Fold7 (dalam) | 2025 | 8.0 | 1968x2184 | 2.625 | 750x832 | terukur | dalam |
| Google | Pixel 9 Pro Fold (dalam) | 2024 | 8.0 | 2076x2152 | 2.75 | 755x783 | terukur | dalam |
| Apple | iPad mini 7 | 2024 | 8.3 | 1488x2266 | 2.0 | 744x1133 | dikutip | tablet |

Catatan baris: Galaxy S24/S25 Ultra bawaan dirender 1080 px (DPR 2,8125), bukan 1440; iPhone mini dirender 1125x2436 (375x812 poin) di panel 1080x2340, DPR efektif 2,88; Pixel 9 menurut yesviz 360x808 bertentangan dengan terukur 412x924 (disimpan di `nilai_lain`).

## 6. Yang belum bisa dipastikan

- **OPPO A6 Pro milik Jo.** 1080x2372, 6,57 inci. DPR 3 memberi 360x791; pola OPPO FHD+ (Reno7 Pro terukur 393x873) memberi 393x863. **Ukur di Chrome HP** (buka halaman yang menampilkan `innerWidth`, `innerHeight`, `devicePixelRatio`), lalu perbarui baris ini.
- Pangsa per model di Indonesia tidak ditemukan; hanya pangsa merek dari trafik web dan pengiriman unit sekunder.
- StatCounter tidak menyebut model; pemetaan ukuran ke model adalah inferensi. 424x942 tidak teridentifikasi.
- Sekitar separuh baris HP potret masih perkiraan (Infinix, Tecno, vivo, OPPO/Realme 1080 px, Redmi 2023 ke atas): tidak ada sumber DPR bawaan per model.
- Agregator (yesviz, whatismyscreensize) bertentangan dengan data terukur untuk Samsung A/Ultra dan Pixel. Data terukur dipilih, nilai lain disimpan.
- Samsung S25+, S26+, S26 Ultra, A35, A55, A36: perkiraan dari pola terukur (A54, Ultra), belum diukur.
- Lipat: cover Z Fold6 hanya dihitung (369x905); cover Pixel 9 Pro Fold dua sumber bertentangan (412 dan 444); cover Flip, Honor Magic V5, OPPO Find N5, Xiaomi Mix Flip, Moto Razr tanpa angka CSS.
- Data DeviceAtlas bersifat global (Apple 30%, Samsung 25%), bukan Indonesia.
- Pengaruh Ukuran huruf pada `innerWidth` dan tinggi yang dimakan bilah alamat belum diuji.
- GSMArena memblokir (429) setelah puluhan halaman; sebagian spesifikasi dari telset.id, Wikipedia, halaman resmi vendor. Tidak ada blokir yang ditembus.

## Sumber utama

[StatCounter resolusi](https://gs.statcounter.com/screen-resolution-stats/mobile/indonesia), [StatCounter merek](https://gs.statcounter.com/vendor-market-share/mobile/indonesia), [DeviceAtlas Mei 2026](https://deviceatlas.com/blog/viewport-resolution-diagonal-screen-size-and-dpi-most-popular-smartphones), [yesviz](https://yesviz.com/viewport.php), [Use Your Loaf](https://useyourloaf.com/blog/iphone-17-screen-sizes/), [Counterpoint Q2 2025](https://counterpointresearch.com/en/insights/indonesia-smartphone-market-q2-2025), [Omdia via TelecomLead](https://telecomlead.com/smart-phone/indonesia-smartphone-market-share-2025-xiaomi-transsion-samsung-oppo-and-vivo-battle-in-value-first-era-amid-pricing-pressures-124688). Sumber per baris ada di JSON.
