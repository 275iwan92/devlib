# Catatan perubahan

Format mengikuti [Keep a Changelog](https://keepachangelog.com/id-ID/1.1.0/);
versi mengikuti [kebijakan versi](docs/versioning.md) (SemVer, tahap `0.y.z`).
Selama `0.x`, kenaikan minor boleh merusak antarmuka; perubahan seperti itu
ditandai **Merusak:**. Hash di kurung menunjuk commit asal.

## [Belum dirilis]

### Ditambahkan

- **`x-dl::sheet` / `x-dl::lookup-grid` prop `row-tone` (opt-in, bawaan `null`)** (permintaan Jo 2026-10-29, Stoku: baris selisih besar di layar tinjau Stok Opname harus berwarna): nama kolom baris yang nilainya (`danger` | `warn` | `success`) mewarnai seluruh baris lewat kelas `is-tone-*` pada `<tr>` (CSS `.dl-sheet__table tr.is-tone-*`, token tema; sel terpilih / aktif / salah tetap warnanya sendiri). Tanpa prop, markup `<tr>` dan tampilan identik dengan sebelumnya. Panduan `sheet.md`, `lookup-grid.md`; manifest; tes `SheetRowToneTest`.

- **`x-dl::side-screen.layout` di tampilan HP: daftar bagian, lalu satu bagian dengan tombol kembali** (permintaan Jo 2026-10-29, layar Edit profile; data contoh fiktif): di konteks phone (`body.dl-on-phone` / `.dl-phone`) rel bagian menjadi baris selebar layar (ikon, nama, lencana, panah, caption grup) dan bagian disembunyikan; mengetuk baris membuka bagian itu saja dengan tombol kembali (juga tombol kembali Android; halaman yang dibuka dengan `?tab=` langsung ke bagian); judul tidak lengket, `x-dl::side-screen.foot` menjadi blok biasa di akhir bagian (tombol selebar layar, tidak ada yang menempel di bawah). Opt-in murni konteks: markup dan perilaku desktop tidak berubah (atribut baru `data-l-back` di akar saja; tombol kembali dan keadaan `data-ss-view` dibuat JS hanya di HP). Peraga: katalog Phone shell, layar "Edit profile" (`/ref/phone/screen/profile`). Panduan `side-screen/layout.md`; tes `PhoneShellTest`.
- **`x-dl::phone-shell` prop `logout-top` (opt-in): ikon keluar (power) di baris nama, bagian atas lembar Menu HP** (disetujui Jo 2026-10-29, Stoku: tombol Sign out di dasar lembar harus menggulir daftar modul yang panjang): `true` = tombol ikon `fa-power-off` bergaris merah 44 px di kanan baris nama; ketukan pertama meminta konfirmasi (`data-dl-confirm`), tombol "Sign out" di dasar tidak digambar. Bawaan `false` (tidak ada yang berubah); aturan CSS hanya di konteks phone; panduan, manifest, tes.
- **Form di tampilan HP: pelengkap audit layar formulir master (opt-in konteks phone) dan `x-dl::table` prop `stack`** (permintaan Jo 2026-10-28, Stoku): blok baru di akhir `devlib.css` (hanya `body.dl-on-phone` / `.dl-phone`; desktop tidak cocok): item dropdown / baris lookup / kolom cari dropdown >= 44px dan 16px, tab form 44px, pesan galat 14px dan hint 13px (11px terlalu kecil), pilihan radio / checkbox 44px, area ketuk tombol huruf `aa` dan format angka `,00` diperluas 44px (tampilan kecil tetap), tombol naik/turun angka disembunyikan, dialog (`dl-modal`: tombol tutup 44x44, kaki tombol 48px selebar dialog, isian 16px), textarea >= 96px. Prop baru `stack` (bawaan `false`) pada `x-dl::table`: tiap baris menjadi kartu di phone (`data-label` per sel); markup desktop identik bila tidak dipakai. Panduan `table.md`; tes `PhoneShellTest`.
- **Situs demo: rute `ui.set` (POST `/ui`)** (permintaan Jo 2026-10-28): menyimpan pilihan tampilan phone/desktop di cookie `ui` terenkripsi (bentuk baku `skills/devlib/phone.md`), nilai tidak sah ditolak. Tes `UiSetRouteTest`.
- **`x-dl::phone-offer` prop `auto` (opt-in, bawaan `false`)** (permintaan Jo 2026-10-28, Stoku: layar desktop yang menyempit harus langsung memakai tampilan phone): bila popup diminta (belum ada cookie `ui`, tebakan desktop) dan layar memenuhi aturan phone (`Devlib.phoneScreen()`, juga saat jendela dipersempit setelah halaman dimuat), tombol "phone" ditekan sendiri lewat satu kelas JS kecil `PhoneOffer` (`phone-offer.js`; pengaman satu kiriman per 10 detik agar browser yang menolak cookie tidak berputar). Tanpa `auto` perilaku lama: hanya bertanya, tanpa JS. Pilihan pengguna (cookie `ui`) tetap menang; panduan, tes. **Dua arah (opt-in)**: `auto` mengirim juga `auto=1`; rute proyek menyimpan `Device::AUTO_COOKIE` (`ui_auto`, helper `Device::autoChosen()`), `x-dl::phone-offer` menganggap `ui` hasil otomatis sebagai belum dipilih pengguna, dan `x-dl::phone-shell` prop baru `autoReturn` (bawaan `false`) mengembalikan ke desktop saat layar melebar lagi (`ui=desktop` + `auto=1` lewat `deviceAction`, satu kiriman per 10 detik); pilihan pengguna (tanpa `auto`) membuang penanda dan tetap menang. `autoReturn` juga berlaku bila halaman phone dibuka langsung sementara `Device::view` = desktop (ponsel sungguhan dan `ui=phone` pilihan pengguna tidak disentuh).

- **`x-dl::pick-queue`: antrean pilih dokumen sumber menjadi SATU dokumen turunan** (permintaan Jo 2026-10-23, preview disetujui; halaman peraga `p5-pick-queue` status Review). Chip filter status dengan jumlah, bilah aksi di ATAS daftar (muncul bila ada yang dicentang: `N dipilih - pihak - boleh digabung`, satu tombol utama; pilihan beda `group` = alasan merah dan tombol mati), baris dengan lencana kesiapan + batang kemajuan + `siap / dipesan` + jatuh tempo; klik di mana saja pada baris memilihnya; baris belum siap tidak bisa dicentang. HP: baris menjadi kartu, tanpa kontrol di bawah layar. Opt-in, netral; prop `rows`, `states`, `filters`, `action`, teks tombol. Berkas: `src/Components/PickQueue.php`, `resources/views/components/pick-queue.blade.php`, `pick-queue.js`, CSS `.dl-pq*`, `resources/guide/pick-queue.md`, tes `PickQueueTest`.
- **`x-dl::screen-info`: halaman diagnostik layar (murni sisi klien)** (permintaan Jo 2026-10-23; dasar: laporan breakpoint, mengukur HP sungguhan sebelum angka breakpoint dikunci): kotak ringkasan (`360 x 791 - DPR 3 - potret - phone`), baris lebar x DPR / perkiraan lebar fisik, `innerWidth` / `clientWidth`, `svh` / `lvh` / `dvh`, `screen`, `visualViewport` (lebar, tinggi, skala), `pointer` / `any-pointer` / `hover`, area aman (`env(safe-area-inset-*)` lewat elemen ukur), aturan layar phone (600 + HP landscape) beserta alasannya, view yang dirender server dan asalnya (`Device::viewSource`: cookie, client hint, User-Agent, override), OS server / browser, breakpoint `max-width` aktif, User-Agent, `userAgentData` (opsional, termasuk model bila diizinkan browser), secure context, waktu. Tombol besar "Salin hasil" menyalin satu blok teks polos (baris pertama = ringkasan); cadangan bila bukan secure context (`http://192.168.x.x` tanpa `navigator.clipboard`): textarea terpilih + `execCommand('copy')`, lalu teks terpilih untuk disalin manual. Diperbarui otomatis saat resize, rotasi, keyboard, atau zoom. Tidak mengirim atau menyimpan apa pun, tanpa pustaka luar; terang / gelap, kontrol >= 16 px. Prop `title`, `note`. `Devlib\Device::viewSource()` baru. Halaman peraga katalog `p7-screen` (Review), panduan `resources/guide/screen-info.md`, tes `ScreenInfoTest`.

- **Merusak (kecil): `x-dl::weather` mengikuti POSISI PERANGKAT secara bawaan** (keputusan Jo 2026-10-23: di DevLib tidak ada pengaturan kota; kota selalu tetap sebelumnya). Prop baru `locate` (bawaan `true`; `:locate="false"` memaksa tempat tetap). `city` / `lat` / `lon` kini nullable dan menjadi tempat CADANGAN bila izin lokasi ditolak, tidak tersedia, halaman bukan HTTPS / localhost, atau timeout; tanpa cadangan dan lokasi gagal kartu tidak tampil (`<x-dl::weather />` tanpa prop tidak lagi menampilkan Surabaya). Rantai: cache `localStorage` 1 jam (`dl-wx`, koordinat dibulatkan 2 desimal) -> Geolocation (`maximumAge` 6 jam, timeout 8 detik, tanpa akurasi tinggi; ditolak = diingat 7 hari di `dl-wx-no`) -> cadangan props -> tersembunyi. Nama kota dari BigDataCloud `reverse-geocode-client` (tanpa kunci, kebijakan pemakaian wajar memang untuk dipanggil dari browser pengguna); gagal = cuaca tanpa nama kota. Geolocation hanya jalan di HTTPS / localhost: di `http://` biasa (IP LAN, host `.test`, server tanpa sertifikat) cadangan yang dipakai. `x-dl::frame` (halaman login) tetap tempat tetap (`:locate="false"`, HTML identik); `x-dl::greeting-card` meneruskan `board['weather']['locate']` (opsional) dan kuncinya `city` / `lat` / `lon` kini opsional. Teks privasi: browser mengirim koordinat (dibulatkan) ke Open-Meteo dan BigDataCloud (lihat `weather.md`). Rantai diuji murni di `WeatherTest` (node).
- **Kecil, opt-in (permintaan Jo 2026-10-23, Stoku)**: `x-dl::clock mode="line"` (satu baris kecil `Jumat, 23 Oktober 2026 · 15:13`, tanpa detik, mewarisi warna teks; di HP `body.dl-on-phone` tanggal singkat `Jum, 23 Okt 2026 · 15:13`); `x-dl::weather` prop `variant` (`glass` bawaan = tampilan lama, `card` = kartu biru tua kecil untuk halaman terang, `chip` = pil kecil ikon + suhu + kota untuk HP); `x-dl::phone-shell` menggambar `x-dl::net-line` di bawah nama pada daftar Menu bila aplikasi mengikat `devlib.net` (tanpa binding HTML sama seperti sebelumnya); token `--dl-net-warn-ink`.
- **`Devlib\AppVersion` + `x-dl::env-badge part="version"`: versi tampil satu bentuk** (keputusan Jo 2026-10-23): aplikasi (mockup, Stoku) `ver 0.<jumlah commit git>` + satu huruf tahap kecil dan pudar (`A` alpha, `B` beta, `RC`; rilis `ver 1.0.0` tanpa huruf) dengan tooltip kata lengkap; pustaka DevLib `v0.6.0` (tag rilis terakhir; jumlah commit sejak tag di tooltip). Tahap = `APP_STAGE` lewat `config('devlib.stage')`. Jumlah commit di-cache per commit baru (kunci waktu ubah `.git/logs/HEAD`); tanpa git = berkas `VERSION` lalu `0.0`. `x-dl::layer-menu` menaruhnya di judul logo; opt-in lewat binding `devlib.version` (tanpa binding HTML sama). Aturan di `docs/versioning.md`.
- **`x-dl::layer-menu` prop `foot-role` (dan `user['foot_role']` di `x-dl::layer-shell`)** (koreksi Jo 2026-10-23, mockup): baris kecil di bawah nama pada footer pengguna (jabatan) tanpa mengubah header panel Profile (bentuk disetujui: nama + e-mail); null = memakai `role` seperti sebelumnya.
- **`x-dl::net-line` + `Devlib\NetworkInfo` + rute `GET /devlib/ping`** (permintaan Jo 2026-10-23, mockup; preview disetujui): satu baris kecil dan pudar `IP client → IP server` di bawah nama pengguna (footer `x-dl::layer-menu`), nama host client dan server lewat tooltip. Status koneksi: ping ringan berkala dari browser (bawaan 10 detik, `fetch` no-store, timeout 5 detik, tanpa menumpuk, berhenti saat tab tersembunyi); dua kegagalan berturut-turut = IP server berkedip merah muda dan tulisan 'Server tidak terjangkau'; `offline` browser = IP client berkedip kuning dan 'Perangkat offline'; kedipan mati bila `prefers-reduced-motion`. Batas: browser tidak bisa membedakan LAN putus dari internet putus, hanya apakah server kita menjawab. Opt-in: footer Layer Menu memakai baris ini bila aplikasi mengikat `devlib.net` (mis. `NetworkInfo::of(request())`), selain itu tetap `x-dl::env-badge` (HTML sama seperti sebelumnya). `NetworkInfo::of($request)` memberi `client_ip`, `client_host` (DNS balik, hanya IP pribadi, di-cache sejam), `server_ip`, `server_host`. Rute `devlib.ping` = 204 tanpa sesi, `no-store`, throttle 120/menit; prop `ping-url` menimpanya. Token `--dl-net-bad` / `--dl-net-warn` (nilai lebih gelap untuk keluarga tema sidebar terang), kunci Lang `net_server_down` / `net_offline` / `net_simulate`, halaman peraga `p7-netline` (tombol Simulasi), tes `NetLineTest` / `NetworkInfoTest`, Technical Guide `net-line.md`. Baris IP di bawah kartu cuaca kepala dashboard (kunci `network` pada `x-dl::greeting-card`) DIHAPUS TOTAL sebelum pernah dirilis (keputusan Jo), tanpa tag riwayat.

### Diubah

- **Hasil uji interaksi HP (Stoku 2026-10-29; konteks phone saja)**: popup hasil pencarian `x-dl::lines` (`.dl-lines__pop`) selebar layar dan hasilnya bertumpuk sebagai kartu dua baris (sebelumnya tabel 422 px yang membuat halaman melebar dan nama barang terpecah empat baris); penomoran halaman, pilihan jumlah baris, dan chip filter `x-dl::grid` min 44 px tinggi; kartu baris `x-dl::lines` tanpa tombol (mis. Hak akses Grup) tidak menyisakan baris tombol kosong. Sel hasil pencarian `x-dl::lines` kini membawa `data-label` (judul kolom) dan di HP tampil sebagai "Code: ... · Stock: ...".

- **Pilihan bahasa tampil sebagai EN | ID di semua tempat** (permintaan Jo 2026-10-28): `x-dl::lang-switch` bawaan memakai kode huruf besar, bukan emoji bendera (Windows menggambar 🇬🇧 sebagai "GB"). Prop `langs` tetap bisa memberi label sendiri. Proyek pewaris yang memakai komponen bawaan ikut berubah tampilannya saat memperbarui DevLib.
- **Sasaran ketuk HP diperbesar (hasil uji layar satu per satu, Stoku 2026-10-28; konteks phone saja)**: tombol kalender / pilih `.dl-pick > .dl-field__trigger` min 44 px lebar, segmen bilah cetak (`.dl-print__bar .dl-segment__opt`) min 44 px tinggi, tombol ikon baris (`.dl-lines__act .dl-iconbtn`) 44 x 44 dan tombol huruf (`.dl-lines__case`) 40 x 36 di kartu `x-dl::lines`. Desktop tidak berubah.

- **`x-dl::report` / `x-dl::print-out` prop `phone-list` (opt-in, bawaan `false`)** (permintaan Stoku 2026-10-28: lembar A4 diperkecil ke 33% di HP, huruf tak terbaca): di konteks phone lembar diganti daftar baca berupa kartu dari data yang sama (`print.js` `list()`: kotak kriteria dan KPI, satu kartu per baris dengan kolom pertama sebagai judul, kolom teks kedua sebagai subjudul, lencana dan nada baris, judul kelompok dengan subtotal, total); bilah pratinjau tetap, tombol Grup membangun ulang daftar, saat cetak lembar tetap dipakai. Tanpa prop perilaku lama; panduan, tes.

- **`x-dl::lines` di tampilan HP: tiap baris menjadi satu kartu** (permintaan Stoku 2026-10-28: tabel barang di formulir Penerimaan / Pengeluaran 908 px, kolom jumlah tersembunyi): di konteks phone (`body.dl-on-phone` / `.dl-phone`) tabel baris tampil sebagai kartu: nomor kiri atas, tombol baris kanan atas, tiap kolom berlabel (`data-label` dipasang `lines.js`), kolom pendek dua-tiga per baris, isian 16 px / >= 44 px, tanpa gulir ke samping; baris jumlah footer disembunyikan, Summary tetap. Tanpa prop baru; desktop tidak berubah (semua aturan dibatasi konteks phone); panduan, tes.

- **Ikon tepuk tangan dihapus dari sapaan** (permintaan Jo 2026-10-23: kurang profesional): `x-dl::greeting` (`greeting.js`) dan judul sambutan `shell/work` kini hanya teks (`Good afternoon, Johannes`). Tidak ada prop yang berubah; panduan `greeting.md` dan `greeting-card.md` diperbarui.

- **Aturan layar phone: batas 760 px diganti 600 px + aturan tinggi untuk HP landscape (sementara)** (keputusan Jo 2026-10-23, dasar: riset "Halaman ukur viewport dan breakpoint HP"): layar = phone bila lebar CSS < 600, ATAU (tinggi < 480 DAN `pointer: coarse` DAN lebar < 1000); selain itu desktop (tablet, iPad mini 744, lipat dalam 690-755, laptop). Alasan: 600 = batas "compact" Android (satu-satunya angka berdasar data) dan 760 / 768 salah menggolongkan iPad mini dan ponsel lipat sebagai HP; lebar saja tidak memisahkan HP landscape (800-997 px) dari tablet, tinggi memisahkan. Baru: `Device::PHONE_MEDIA`, `PHONE_MAX_WIDTH`, `LANDSCAPE_MAX_HEIGHT`, `LANDSCAPE_MAX_WIDTH`, `Device::screenIsPhone($w, $h, $coarse)`; `Devlib.phoneScreen()` dan `html[data-dl-screen="phone|desktop"]` di `view.js`. Popup `x-dl::phone-offer` kini muncul pada layar yang memenuhi aturan itu (sebelumnya `<= 760 px`) bila view desktop yang aktif. **Perilaku berubah (kecil):** layar 600-760 px tegak (tablet kecil, jendela desktop sempit) tidak lagi ditawari tampilan phone; HP landscape sentuh kini ditawari. `Device::view()` tidak berubah (tebakan header, cookie, dan pilihan pengguna tetap; pilihan pengguna menang). Media query komponen lain (760 / 640 / 900 dst.) tidak diubah. Tes: 599 / 600 / 761, 800x360 sentuh, iPad 820x1180 dan 744, lipat 690-755, dan kesamaan PHP-CSS-JS.

### Diperbaiki

- **`x-dl::phone-shell`: kolom `x-dl::field.phone` di halaman ber-shell tidak lagi merusak pemasangan komponen** (ditemukan 2026-10-29, layar Edit profile HP): kolom telepon membawa atribut `data-dl-phone` yang sama dengan penanda shell, jadi dianggap shell dan `init()` gagal (tombol menu null) sehingga komponen sesudahnya tidak terpasang; selector shell kini `.dl-phone[data-dl-phone]`.
- **`x-dl::table` prop `stack` / `dl-table--stack` (konteks phone): sel tabel kembali berupa baris label + isi** (ditemukan Jo 2026-10-29, layar Akun UAT Stoku di HP): aturan `display:block` untuk `td` lebih spesifik daripada `display:flex` sel bertumpuk, sehingga label dan isi menempel (`Usernameadmin`) dan rata kanan semua. `td` dikeluarkan dari aturan block; sel tetap `display:flex` (label kiri, isi kanan). Hanya konteks phone.

## [0.6.0] - 2026-10-23

Rilis keenam. **Multi view: smartphone (Android dan iOS) dan desktop/laptop/tablet** sebagai kemampuan dan aturan baku semua proyek: `Devlib\Device` (`view()`, `os()`, cookie `ui` = `phone`|`desktop`), tampilan HP lengkap (`x-dl::phone-shell` dengan satu tombol hamburger melayang dan bilah menu atas keadaan A/B/C, `x-dl::phone-login`, `x-dl::phone-offer`, `x-dl::device-pick`, form satu kolom, grid `phoneCards`, bilah aksi grid dan report, `x-dl::image-gallery`), pratinjau 'Lihat sebagai' di katalog, serta perbaikan cetak (tabel lebar diperkecil sesuai lembar, kolom `wrap`, halaman cetak bisa digulir). Semua opt-in; tampilan desktop dan halaman DIKUNCI tidak berubah. Tidak ada tag komponen atau prop 0.5.0 yang dihapus atau diganti nama. Bentuk lama yang dikubur: bilah tab bawah `x-dl::phone-shell` (tag `history/phone-shell-bottom-tabs-v1`; tidak pernah dirilis di 0.5.0).

### Ditambahkan

- **Multi view baku: `phone` (smartphone Android dan iOS) dan `desktop` (laptop, desktop, tablet)** (permintaan Jo 2026-10-23): `Devlib\Device` kini memakai dua view kanonik `Device::PHONE` / `Device::DESKTOP`; `Device::view($request)` ('phone' | 'desktop'), `Device::os($request)` ('android' | 'ios' | 'other'), `Device::detectOs`, `Device::normalize`, `Device::viewport($request)` (viewport-fit=cover hanya di phone), `Device::override` (per permintaan), `Device::bodyClass` memakai tambahan `dl-os-android` / `dl-os-ios`. Cookie `ui` kanonik `phone` | `desktop`; nilai lama `hp` tetap terbaca sebagai `phone` (alias, `Device::ALIASES`); `Device::HP` = `PHONE` dan `fromRequest` = `view` tetap ada. Tebakan: `Sec-CH-UA-Mobile`, lalu User-Agent (Safari iOS tidak mengirim client hints; iPhone = phone, iPad dan iPadOS bermode Mac = desktop, tablet Android tanpa "Mobile" = desktop). **Merusak (kecil):** nilai yang dikembalikan `Device::detect/resolve/view/fromRequest` dan nilai radio `x-dl::device-pick` / `x-dl::phone-offer` / aksi `x-dl::layer-shell` kini `phone` (bukan `hp`); proyek yang membandingkan dengan `Device::HP` tidak berubah, yang membandingkan dengan literal `'hp'` harus diganti; validasi POST `ui` dengan `Device::CHOICES` kini hanya `phone`, `desktop` (tambahkan `array_keys(Device::ALIASES)` bila form lama masih beredar). Aturan per OS di `devlib.css` ("Multi view", hanya `body.dl-on-phone`): safe-area iOS, isian >= 16 px, `text-size-adjust`, sasaran >= 44 px, `overscroll-behavior-y: contain` (pull-to-refresh Android); `view.js`: `Devlib.os` dan keyboard HP (`visualViewport`, `--dl-vvh`, isian digulir ke tengah); class `dl-touch` (opt-in) memperbesar sasaran sentuh di layar sentuh non-phone. Aturan baku `skills/devlib/views.md` (tertaut dari `SKILL.md`, `object.md`, `phone.md`; halaman Aturan katalog).
- **`x-dl::device-frame` mode `src` (props `src`, `preset`, `back`) + `x-dl::view-as` + `Devlib\ViewOverride`** (permintaan Jo 2026-10-23): pratinjau halaman nyata di `<iframe>` berukuran perangkat (Android phone 360x790, iPhone 393x852, Tablet 820x1180, Laptop 1366x768, Desktop 1920x1080), diperkecil agar muat; `?dl-view=` / `?dl-os=` membuat server merender view yang benar (middleware `ViewOverride` opt-in, per permintaan, tanpa cookie). `x-dl::view-as` = pil "Lihat sebagai" dengan menu perangkat. Katalog: pil di setiap halaman (`/lib/{slug}/as?as=`); isi halaman tanpa pil identik (tes). Mode slot lama `x-dl::device-frame` tidak berubah.

- **Baru: isi bilah aksi `x-dl::phone-shell` untuk GRID dan REPORT, panel dari atas, layar sendiri, `x-dl::image-gallery`, halaman peraga hidup** (disetujui Jo 2026-10-23): grid = Cari, Baru (utama), Saring, Ekspor; report = Cetak (utama), Kertas, Grup, Hemat tinta; form = Simpan, Urungkan, Kembali, Baru (urutan tetap). Saring (Tampilan bila ada `cards`, satu pilihan per saring grid, Atur kolom, Terapkan), Ekspor (daftar format), Kertas, dan Grup membuka PANEL dari atas di bawah bilah dengan latar digelapkan; tidak ada lembar dari bawah, kolom tidak punya tombol bilah. Layar sendiri: tombol bertanda `data-phone-act` (`primary` = utama) menjadi aksi bilah. Lembar cetak report diperkecil agar pas lebar layar (variabel `--dl-phone-fit`, hanya di layar). Objek baru `x-dl::image-gallery` (kisi gambar acuan + keterangan, tanpa JS). Halaman katalog `p7-phone` (Review): shell hidup di `x-dl::device-frame` (layar Home, Form, Grid, Report, Login, popup Offer) dan galeri 15 tangkapan layar acuan di `site/public/ref/phone/`; layar contoh `/ref/phone/screen/{nama}` dengan data fiktif (`App\Support\PhoneRef`), alamat pendek `/ref/phone-shell`. Kunci Lang `phone_filter` / `phone_export` / `phone_paper` / `phone_group` / `phone_ink` / `phone_view` / `phone_cols` / `phone_apply`; `phone_options` dihapus.
- **Diubah: `x-dl::phone-shell` = SATU tombol hamburger MELAYANG + bilah menu di atas, tanpa bilah permanen dan tanpa bilah bawah (keputusan final Jo 2026-10-23; sebagian aplikasi bermasalah bila bilah bawah bertabrakan dengan tombol navigasi Android)**: bilah tab bawah DIKUBUR (tag `history/phone-shell-bottom-tabs-v1`, tanpa saklar). Tombol ≡ kotak membulat (46 px, radius 13, opasitas 0,9, `position: fixed`) di kiri atas; baris judul + tombol kembali ada di isi halaman. Diketuk, tombol HILANG dan BILAH selebar layar meluncur di atas (62 px, lima item sama lebar, ikon + label, item Menu ≡ paling kanan dipisah garis tipis): B = aksi layar (form: Simpan utama berwarna aksen, Urungkan, Kembali; grid: Cari, Data Baru, Saring, Kolom; report / cetak: Cetak, Pengaturan; sisanya / jarang dipakai masuk 'Aksi lain' di daftar Menu; hanya bila layar punya aksi; aksi dijalankan lewat click() pada tombol asli halaman, yang disembunyikan), C = lima menu utama (prop `tabs`, empat + Menu); 'Menu' di C membuka daftar semua modul di bawah bilah dengan latar digelapkan, 'Desktop view', Sign out. Layar tanpa aksi: A, C, A. Bilah dan daftar menyembunyi sendiri (memilih, ketuk di luar, menggulir, diam 6 detik) kecuali bilah aksi form yang kotor; titik kuning di tombol dan item Menu menandai form kotor, pindah layar saat kotor meminta konfirmasi. `aria-label` / `aria-expanded` / `aria-live`, Esc, `prefers-reduced-motion`; tanpa gestur geser dari tepi atas (bentrok tarik-untuk-muat-ulang Chrome). Bilah tombol form bawah DIHAPUS; `x-dl::phone-bar` mengikuti alur isi. Halaman desktop di `frame` diberi kelas `dl-phone-shelled` (tombol form / toolbar grid / bilah cetak disembunyikan, aksinya pindah ke bilah). Prop `burger` tidak ada. Guide `phone-shell.md`, `form.md`, `grid.md`, `phone-bar.md`, `phone.md`, tes `PhoneShellTest`.

- **`x-dl::grid` prop `phoneCards` (daftar kartu di tampilan HP)** (permintaan Jo 2026-10-23): bentuk sama dengan `cards` tetapi tanpa tombol Tabel/Kartu; di konteks HP (`body.dl-on-phone` atau di dalam `x-dl::phone-shell`) grid dibuka sebagai daftar kartu (nama + kode, lencana, nilai `meta`; nilai kosong disembunyikan), ketuk kartu = buka baris (`open-url`, event `dl:grid-open`), cari selebar layar di atas, Filters / Columns / Export menjadi lembar dari bawah, kontrol kerapatan dan tampilan disembunyikan, `row-tone` mewarnai kartu. Opt-in: kunci `phoneCards` ditulis ke konfigurasi grid hanya bila diisi; tanpa konteks HP tidak ada yang berubah (dijaga tes `PhoneShellTest`). Technical Guide `grid.md`.

- **Form satu kolom di tampilan HP** (permintaan Jo 2026-10-23): CSS di konteks HP (`body.dl-on-phone` atau di dalam `x-dl::phone-shell`): baris form dan grid body jadi satu kolom, label di atas, isian 16 px dan >= 44 px, tombol plus tambah-cepat tetap sebaris, panel statistik dua kolom, tombol panel (Simpan, Urungkan, Kembali, Nonaktifkan) menjadi bilah menempel di bawah layar. Baru: pembungkus `div.dl-form__acts` di panel `x-dl::form` (`display: contents` di desktop, tata letak desktop identik, terbukti dengan 220 elemen berposisi sama) dan `Devlib\Device::bodyClass()` untuk `<body>` layout proyek. Opt-in; aturan HP hanya cocok di konteks HP (dijaga tes `PhoneShellTest`). Technical Guide `form.md`, `skills/devlib/phone.md`.

- **Bagian HP generik naik ke DevLib** (permintaan Jo 2026-10-23): `x-dl::phone-login` (login HP fluid 320-440 px, tautan kembali ke desktop), `x-dl::phone-tiles` + `x-dl::phone-tile` (kartu angka), `x-dl::phone-link` (baris tautan besar dengan lencana dan tone), `x-dl::phone-bar` (bilah tombol menempel di bawah, prop `columns`). Semua opt-in; Technical Guide `phone-*.md`, peraga `p7-phone`, tes `PhoneShellTest`. Panduan adopsi `skills/devlib/phone.md` (ditautkan dari `SKILL.md`, terdaftar di katalog Rules).

- **`x-dl::phone-offer`** (permintaan Jo 2026-10-23, halaman login): popup "Ingin tampilan yang lebih nyaman di smartphone? Ketuk di sini" + tombol kecil "Tetap desktop" (kunci Lang `phone_offer*`, ID/EN). Ditulis hanya bila belum ada cookie `ui` dan perangkat ditebak desktop; CSS menampilkannya hanya pada lebar <= 760 px; tanpa JavaScript (dua form POST `ui=hp|desktop` ke rute proyek). Opt-in. Guide `phone-offer.md`, peraga di halaman `p7-phone`, tes `PhoneShellTest`.

- **Tampilan HP (smartphone potret): `x-dl::phone-shell`, `x-dl::device-pick`, `Devlib\Device`** (permintaan Jo 2026-10-23, tahap 1; pemakai pertama sstock). Tampilan HP dan desktop adalah dua varian PERANGKAT (bukan versi lama / baru). `Devlib\Device::detect/resolve/fromRequest`: tebakan `hp` hanya untuk ponsel (`Sec-CH-UA-Mobile`, lalu User-Agent; tablet dan "situs desktop" = `desktop`), cookie `ui` (`hp` | `desktop`) yang sah selalu menang. `x-dl::phone-shell`: bilah atas kecil (judul, kembali), isi selebar layar (slot, atau `frame` = halaman desktop di dalam iframe untuk layar yang belum punya tampilan HP), bilah tab bawah (tab tautan + Menu), lembar Menu dari bawah berisi semua modul (bentuk `modules` sama dengan `x-dl::layer-shell`), pilihan tampilan dan Keluar; sasaran sentuh >= 44 px, isian 16 px, `env(safe-area-inset-*)`. `x-dl::device-pick`: dua radio `ui` (Phone view / Desktop view) terpilih awal menurut cookie / tebakan; bagian baru `device` pada `x-dl::login-box` (opt-in, tidak ada di `show` bawaan). `x-dl::layer-shell` prop baru `deviceAction` (opt-in, bawaan `null`): menambah aksi "Phone view" di menu profil desktop; tanpa prop markup identik dengan sebelumnya. Halaman peraga `p7-phone` (Review), Technical Guide `phone-shell.md`, `device-pick.md`, `login-box.md`, `layer-shell.md`, tes `PhoneShellTest`.

- **`x-dl::print-out` / `x-dl::report` kolom `wrap`** (permintaan Jo 2026-10-22, Ringkasan Stok sstock terpotong di kanan): `'wrap' => true` pada definisi kolom membolehkan judul kolom turun baris (bawaan satu baris). Opt-in; tanpa `wrap` HTML identik. Guide `print-out.md`.

### Diperbaiki

- **Tabel `x-dl::print-out` / `x-dl::report` yang lebih lebar dari lembar terpotong di kanan** (permintaan Jo 2026-10-22: "ALWAYS FIX IT"): `print.js` kini memperkecil tabel (CSS `zoom`) sampai muat lebar lembar; tabel yang sudah muat tidak berubah. Halaman tidak dihitung ulang, jadi lembar sedikit longgar.
- **Halaman cetak/laporan yang menjadi anak langsung `body` tidak bisa digulir** (`body.dl-body` mengunci `overflow:hidden`): `body.dl-body:has(> .dl-print){overflow-y:auto}`. Halaman yang membungkus `.dl-print` di wadah gulir sendiri (situs acuan) tidak terpengaruh.

## [0.5.0] - 2026-10-22

Rilis kelima. Dropdown dengan tombol plus (Add New, `x-dl::field.select` prop `add`), gaya kop dan nama badan hukum pada semua cetakan (proporsional di A4), dan perbaikan baris NPWP pada kop PPN. Semua opt-in: tanpa prop/kunci baru, HTML lama identik. Tidak ada tag komponen atau prop yang dihapus atau diganti nama; tidak ada perilaku lama yang berubah selain perbaikan NPWP (kop PPN `x-dl::print-out` / `x-dl::report` kini mencetak baris NPWP).

### Ditambahkan

- **`x-dl::field.select` prop `add` (Add New)** (permintaan Jo 2026-10-22; untuk pewaris sstock dan tmerp): dropdown dengan tombol plus di kanannya; klik membuka popup `x-dl::modal` berisi `fields`, simpan lewat POST JSON ke `add.url` (same-origin, CSRF), lalu item baru disisipkan ke daftar dan langsung terpilih (event `change`, tanpa duplikat). 422 Laravel ditampilkan di bawah isian; galat lain = pesan umum. Opt-in: tanpa `add` markup identik dengan sebelumnya; select `readonly`/`disabled` tanpa tombol. Kunci `add`: `url`, `fields`, `title`, `icon`, `label`, `note`, `value-key`, `label-key`. Halaman peraga `p2-select-add` (Review), rute demo `/demo/select-add/*`, kunci Lang `sa_add`/`sa_saving`/`sa_failed`, Technical Guide `field/select.md`.
- **Gaya kop dan baris nama badan hukum pada semua cetakan** (permintaan Jo 2026-10-22; modul Perusahaan sstock): kunci opsional baru pada array `company` (sumber sama: `Document::companyUsing()` / `companyOf()`, prop `company` / `company-ppn`): `style` = `logo_name_address` (logo + nama + nama PT + alamat) | `logo_name` (logo + nama + nama PT) | `name` (nama + nama PT saja) dan `legal_name` (nama PT, baris kecil hanya bila berbeda dari `name`, tanpa beda huruf besar/kecil dan spasi). Gaya berlogo tanpa `logo` jatuh ke nama saja (tanpa lencana inisial). Satu pembantu `Devlib\PrintDoc\Kop::of()` dipakai `x-dl::print-doc` (+ lembar lampiran), `x-dl::print-sheet`, `x-dl::print-out` dan `x-dl::report`; blok perusahaan print-doc/print-sheet dipindah ke partial `print-doc-co`. Opt-in: tanpa `style` dan `legal_name` HTML identik dengan sebelumnya (dibuktikan tes `CompanyKopStyleTest` terhadap fixture HTML lama, termasuk halaman `p5-print`, `p5-print-doc`, `p5-report`); tinggi kop dan jumlah baris per lembar tidak berubah (kop bergaya memadatkan teks di tinggi 14,5 mm). Halaman peraga `p5-kop-style` (Review, tidak masuk `APPROVED`), logo contoh fiktif `site/public/kop-logo-contoh*.svg`, Technical Guide `print-doc.md`, `print-sheet.md`, `print-out.md`, `report.md`. Temuan (tidak diubah): baris `NPWP` kop PPN pada `x-dl::print-out` / `x-dl::report` tidak pernah tercetak (`$isPpn === '1'` vs kunci integer); lihat guide.

### Diubah

- **Kop bergaya `x-dl::print-doc` proporsional pada A4** (permintaan Jo 2026-10-22: "CHECK YANG PROPORSIONAL DI A4"): ukuran kop bergaya (`style`) kini mengikuti kertas lewat variabel CSS `--co-*`. A4 dan `x-dl::print-sheet`: kop 20 mm, nama 12 pt, nama PT / alamat / NPWP 7,5 pt, logo maks. 16 x 26 mm, kolom perusahaan mengambil sisa lebar antara judul dan QR; tinggi kop yang bertambah (+5,5 mm) dibayar 2 mm margin atas sehingga kapasitas 22 baris dan `--pd-info-rows` / `--pd-rh` / `--pd-tail` tetap berlaku (sisa ruang A4 terukur 6,4 mm, 1,8 mm dengan 7 baris info). A5 dan kop tanpa `style` tidak berubah (14,5 mm, ukuran lama; HTML identik). `x-dl::print-out` / `x-dl::report` sudah proporsional dan tidak diubah. Tes: `CompanyKopStyleTest`; guide `print-doc`.

### Diperbaiki

- **Baris NPWP pada kop PPN `x-dl::print-out` dan `x-dl::report` tidak pernah tercetak** (permintaan Jo 2026-10-22: "ALWAYS FIX IT"): perbandingan `$isPpn === '1'` selalu salah karena kunci larik `'1'` menjadi integer `1` di PHP; kini `(int) $isPpn === 1`. Satu-satunya perubahan keluaran: kop PPN (kop `data-p-kop="1"`) memuat `NPWP <nomor>` bila `npwp` terisi; tanpa `company-ppn`, kop "1" memakai `company` sehingga `npwp`-nya ikut tercetak pada tampilan PPN. Halaman katalog p5-print berubah tepat sebatas itu (p5-print-doc dan p5-report tidak berubah). Tes: `CompanyKopStyleTest::test_vat_header_prints_the_npwp_line_on_print_out_and_report`.

## [0.4.0] - 2026-10-22

Rilis keempat. Technical Guide lengkap (semua object punya rincian tulisan tangan dan kontrak tes); engine `LeadTime`; komponen `idle-logout` (logout otomatis setelah tidak ada aktivitas, aman untuk layar iframe); halaman All Projects di Executive Summary; varian panel Profil; dan perbaikan menyeluruh kejanggalan field (crash, `disabled`, Undo, tanggal, dll.). Tidak ada tag komponen atau prop yang dihapus atau diganti nama.
**Perilaku berubah (bukan antarmuka; pewaris perlu tahu):** field `disabled` tidak lagi ikut terkirim ke server pada banyak field (multi-select, tags, date, date-time, slider, rating, otp, rich-text, signature, toggle-group, money, lookup, dan lain-lain; `select` sengaja dipertahankan tetap terkirim karena dipakai pewaris); `color` dengan nilai yang ditolak browser jatuh ke biru bawaan; `display` kosong untuk `money`/`number` tampil "—" (bukan "IDR 0"); tanggal awal `date` dan `date-time` dibaca sebagai tanggal lokal.

### Ditambahkan

- **`x-dl::alert` menerima `items`** (permintaan Jo 2026-10-21, portal karir tmerp): daftar butir di bawah pesan (`ul.dl-alert__list`), untuk semua galat validasi form panjang sekaligus. Tanpa `items` markup sama seperti sebelumnya. Panduan dan manifest diperbarui.
- **Halaman tamu dari database** (permintaan Jo 2026-10-21, tmerp mewarisi): `x-dl::frame` menerima `image-list` dan `quote-list` (gambar latar dan kutipan dari aplikasi, bukan folder media); `x-dl::login-box` menerima `uat-accounts`; `x-dl::uat-accounts` menerima baris `label => ['login' =>, 'password' =>]` yang bisa diklik untuk mengisi kolom login dan password. Panduan dan manifest diperbarui.

- **Engine `LeadTime`** (permintaan Jo 2026-10-21; untuk sstock dan modul pembelian ERP): `Devlib\Engine\LeadTime` mencatat lama dari DIPESAN sampai MASUK dari data nyata (hitungan murni atas array, tanpa database), memberi `count/average/min/max/median/last`, `effective()` (rata-rata `samples` terakhir dibulatkan ke atas bila data >= `min_samples`, kalau tidak `fallback_days`, beserta `source` dan `basis`), `expected()`, `status()` (on_time / late / unknown dengan toleransi), `suggestReorderPoint()` (hanya saran) dan kalimat `describe*()` dengan kunci Lang `leadtime_*` (ID/EN). Hari = tanggal kalender, tidak bergeser oleh jam/zona. `LeadTimeEngine` (key `lead-time`, Calc) membungkusnya untuk `EngineRegistry` dan terdaftar di Engine Catalog situs. Opt-in. Skill `engine.md`, tes `LeadTimeTest`.
- **`x-dl::idle-logout`** (permintaan Jo 2026-10-21; untuk pewaris sstock dan tmerp): logout otomatis setelah tidak ada aktivitas, objek sisi layar yang dipasang satu baris di layout. Props: `minutes` (30), `warn` (2), `logout-url` (POST + CSRF), `ping-url` (GET, 204), `ping-every` (5), `login-url`, `preview` (peraga katalog, tidak mengirim apa pun). Aktivitas = mouse, klik, ketikan, scroll, sentuhan (form yang sedang diisi dihitung); peringatan non-modal `role="alertdialog"` dengan hitung mundur, tombol Tetap masuk (ping) dan Keluar sekarang; sinkron antar tab (localStorage + BroadcastChannel, tahan bila diblokir). Halaman peraga baru `p3-idle-logout` (status Review, tidak masuk `APPROVED`), Technical Guide `idle-logout.md`, kunci bahasa `idle_*`. Opt-in: tanpa tag ini tidak ada yang berubah.
- **Technical Guide lengkap: 138 object terakhir** (permintaan Jo 2026-10-21): rincian tulisan tangan (`resources/guide/<tag>.md`, English, tujuh judul, semua prop) untuk keluarga tata letak, tampilan dan umpan balik, navigasi dan shell, data dan board, cetak dan label, serta akun dan wizard. `resources/guide/PENDING.txt` kini kosong; `GuideContractTest` mewajibkan object baru langsung punya rincian. Kejanggalan kode yang ditemukan penulis tercatat sebagai "Code oddity (not fixed)" di tiap entri.
- **Varian panel Profil di halaman `p7-profile`** (permintaan Jo 2026-10-21): pemilih `?variant=tmerp|sstock|devlib`, bawaan **tmerp** (tanpa parameter isi halaman yang disetujui tidak berubah, dibuktikan perbandingan HTML). tmerp = tanpa `without`; sstock = `without ['chat','ai','theme']` (Setup hanya Bahasa); devlib = `without ['notif','chat','ai','edit','help','setup']` (hanya Change password dan Sign out). Tiap varian: panel hidup, tabel item (tampil / nilai `without` / yang disediakan aplikasi), cuplikan pemakaian. Tanpa komponen baru; `App\Support\ProfileVariants`. Tes `ProfilePageTest`.

- **Executive Summary > All Projects** (permintaan Jo 2026-10-21, item pertama menu): commit, sesi Claude, hari kerja pertama, commit per bulan (batang tumpuk), tabel per project (DevLib, tmerp, sstock), dan kartu Mockup lane / Applied lane untuk tahap fitur tmerp. Data: `php artisan projects:snapshot` (manual; hanya `git log --all` dan hitungan berkas sesi Claude, hanya angka) menulis `site/resources/data/projects.json`; daftar project di `config/devlib.php` kunci `projects`. Tahap fitur tmerp: tabel `project_stage` (11 modul menu tmerp, semua belum dicatat), `stage:set {project} {feature} {stage?} {--note=}` dan `stage:list`. Halaman lain tidak berubah. Tes `ProjectsSummaryTest`.

### Diperbaiki

- **`x-dl::idle-logout` di layar iframe** (temuan pemasangan di sstock, 2026-10-21): bila layout yang sama memuat komponen di dalam frame same-origin, frame tidak lagi punya pencacah, peringatan, ping, atau logout sendiri; ia hanya meneruskan aktivitas (`postMessage`, hanya ke origin yang sama, dibatasi 1x per detik) ke jendela paling atas, yang tetap satu-satunya yang menampilkan peringatan, ping, dan logout (logout memuat jendela atas, bukan frame). Saat peringatan terbuka pesan dari frame tidak memperpanjang; hanya Tetap masuk. Jendela tunggal tidak berubah. Batasan: komponen harus ada di jendela atas (guide, Rules and pitfalls).
- **Field: empat masukan yang membuat halaman crash atau macet** (disetujui Jo 2026-10-21). `field.date-time`: `minute-step` ≤ 0 membuat loop di view tanpa akhir dan ≥ 60 membuat `field.js` error; kini dijepit ke 1..30. `field.slider`: `value`/`value-to` string kosong atau non-angka melempar `TypeError`; kini dianggap kosong (min/max), string angka tetap bekerja. `field.number-words`: kode `currency` huruf kecil atau tak dikenal membuat skrip error; kini dijadikan huruf besar, kode tak dikenal = tanpa mata uang. `Lookup`: `rows` berupa Collection/iterable kini dikonversi benar (sebelumnya properti internal Collection). Masukan sah tidak berubah. Tes `FieldCrashFixesTest`.
- **Field: kejanggalan sisi PHP dan Blade** (izin berdiri Jo 2026-10-21; keluaran masukan yang sah tidak berubah). `checkbox` readonly yang dicentang kini terkirim lewat cermin hidden (seperti `toggle`). `radio`, `checkboxes`, `toggle-group`, `key-value`, `rating`, `display`: label tidak lagi memakai `for` menggantung; label diberi id `<uid>-lbl` dan kelompoknya `role` + `aria-labelledby` (`Field::labelable()`). `disabled` kini juga mematikan input hidden/cermin sehingga tidak terkirim di `multi-select`, `tags`, `date`, `rating`, `otp`, `rich-text`, `signature`, `toggle-group`, `money` (mata uang), `key-value`, `phone`, `credit-card`; tombol pendamping ikut mati (kalender `date`, jam `time`, panah `number`, mata `password` saat disabled, `x` pada `tags`, mata uang `money`, huruf holder `credit-card`); sel dan tombol hapus `key-value` mengikuti `disabled`/`readonly`. `multi-select`: `data-label` berisi label, bukan kunci. `otp`: `value` kini dirender (hidden + kotak). `display`: `required` diabaikan; `money`/`number` kosong tampil tanda pisah, bukan `IDR 0`. `color`: `#rgb` diperluas dan nilai yang ditolak browser jatuh ke biru bawaan, label HEX sama dengan nilai terkirim. `date-slider`: `until` bawaan tanpa limpahan tanggal 29-31 dan zona `Locale::timezone()`; komentar REMARK dibetulkan. Tooltip demo `field-text` soal `code`. Tes `FieldPhpFixesTest`; butir yang butuh persetujuan tetap tercatat "Code oddity (not fixed)" di guide.
- **Field: kejanggalan sisi JavaScript** (izin berdiri Jo 2026-10-21; keluaran penggunaan yang sudah benar tidak berubah). Mode `Aa` mengapitalisasi huruf aksen dengan benar (`étienne` -> `Étienne`). Undo / `Devlib.fill` kini memulihkan multi-select, checkboxes, tags, key-value, negara telepon, mata uang money dan berkas upload, serta menyegarkan toggle-group, rating, tanda tangan, OTP dan area rich-text; nilai kanonik yang ditulis ke decimal / money menjadi tampilan (`1.250` tidak lagi terbaca ribuan; money bulat tidak lagi menjadi `125000050`). Perubahan lewat tombol badge huruf, tags, key-value, upload (pilih/tarik/hapus), rich-text, Apply/Clear jam, pilihan mata uang dan negara, OTP, serta nomor telepon kini menandai form berubah (dirty guard); pesan "Required" ikut hilang untuk isian yang hanya menembak `change` (select, lookup, toggle-group, tags, tanda tangan) dan rich-text tidak lagi telat satu ketikan. Pesan `error` dari server pada masked, phone dan credit-card bertahan sampai isian diubah (blur tidak menimpanya); `placeholder` phone kini tampil. Mask menerima digit literal (`+62 999-999`); credit-card tidak lagi menghitung `_brand` sebagai isian. Tanda tangan tidak menembak `change` saat dimuat dan tidak menimpa nilai tersimpan dengan `data:,` pada kanvas tersembunyi. Kalender date pindah bulan dari tanggal 29-31 tanpa melompat dan membaca tanggal lokal; date-range mengosongkan durasi saat salah satu tanggal dikosongkan dan tidak bergeser di zona barat UTC; number membolehkan `-` bila `min` negatif; popover jam membulatkan 58/59 ke jam berikutnya; tombol kalender/jam, spin number, mata password (disabled) dan tombol huruf credit-card mati bila isiannya disabled/readonly.
- **Field: sisa kejanggalan `date-time`, `slider`, `number-words`, `lookup`** (izin berdiri Jo 2026-10-21; keluaran penggunaan yang sudah benar tidak berubah). `disabled` kini mematikan input hidden keempatnya (nilai tidak terkirim), juga tombol pilih mata uang `number-words`. `date-time`: pindah bulan dari tanggal 29-31 tidak meloncat, tanggal awal dibaca sebagai tanggal lokal, `Apply` tanpa hari memakai hari ini menurut zona aplikasi, `Clear` menembak `change`, tampilan mengikuti hidden saat `Devlib.fill`/Undo. `slider` dan `number-words`: menggeser / mengetik menandai form berubah, `Devlib.fill`/Undo menyegarkan gagang, kotak, dan terbilang; `number-words` tidak lagi mengubah `12.5` menjadi `125` di bahasa Inggris dan kode mata uang tampil sekali. `lookup`: klik `create` menembak `change`, `setField` memakai `placeholder` field. Komentar `DateTime.php` disesuaikan. Tes `FieldRestFixesTest`; guide empat object disesuaikan. Menunggu persetujuan Jo: menit non-kelipatan di `date-time` (tampil dibulatkan, nilai tidak), label lookup remote saat `Devlib.fill`.

## [0.3.0] - 2026-10-21

Rilis ketiga. Satu default = versi terakhir (versi lama dikubur sebagai kode sejarah bertag `history/…`); alur Approval
dengan Form Approval baku; Nested Grid; halaman OO Methods and Properties (Grid, Form, Kanban); halaman Profile
(Special Screen); menu profil bisa diringkas lewat `without`. **Merusak:** Menu Classic (`x-dl::shell`, `Shell`,
cookie `dl_menu`, prop `classic`), Dieline v1/v2, dan prop `:approval`/`:otp-url` pada `x-dl::form` dihapus.

### Ditambahkan

- **Halaman katalog `p7-profile` (Special Screen)**: merekam panel Profil Layer Menu (header + aksi akun) sebagai contoh, bentuk bawaan penuh, data fiktif; tabel item dibaca dari `LayerShell::actions()`. Belum APPROVED; tanpa perubahan komponen.
- **`x-dl::layer-shell :without` mengenali `notif`, `edit`, dan `setup`** (permintaan Jo 2026-10-21: menu profil aplikasi cukup Ubah password):
  `notif` = aksi Notifikasi + laci-nya, `edit` = aksi Edit profile + modal / laci-nya, `setup` = halaman Setup (Tema, Bahasa) beserta entrinya.
  Dengan `['notif','chat','ai','edit','help','setup']` menu profil tinggal header, Ubah password, dan Sign out. Opt-in: tanpa `without`
  keluaran tidak berubah (dibuktikan perbandingan HTML); nilai `chat`, `ai`, `help`, `theme` tidak berubah. Tes `LayerShellWithoutTest`.

### Diubah

- **Halaman katalog `p5-override` (OO Methods and Properties) kini menceritakan satu object, contoh kerja Kanban** (permintaan Jo
  2026-10-21), enam bagian: cerita object, tabel properti, tabel method (PHP dan JS), cara membuat turunan (kode asli
  `KanbanProduksi`), demo hidup, aturan terkait (`object.md`, `inheritance.md`, entri Technical Guide). Tabel dibaca dari
  kode lewat `App\Support\ObjectDoc` (refleksi konstruktor dan method, header REMARK, berkas kelas JS); arti singkat di
  `ObjectNotes` dicocokkan dengan kode oleh `ObjectDocTest`. Status Review (belum APPROVED); `x-dl::kanban` tidak diubah.
- **Halaman `p5-override` kini punya tiga contoh object: Grid, Form, Kanban** (permintaan Jo 2026-10-21): pemilih di atas halaman
  (`?object=grid|form|kanban`, tanpa parameter = Kanban), enam bagian yang sama untuk tiap object. Tabel properti Grid (28) dan Form
  (34) dibaca dari konstruktor dan dikelompokkan; method PHP/JS dibaca dari kode dan dicocokkan dengan catatan di `ObjectNotes`.
  Dua turunan contoh baru di `site/app/View/Components/Demo/`: `GridStockMovement` (properti ditimpa + method JS `fmtCell`) dan
  `FormStockAdjustment` (properti ditimpa + method JS `act`). Bagian Kanban yang disetujui tidak berubah; `x-dl::grid`, `x-dl::form`
  dan `x-dl::kanban` tidak diubah.
- **Alur Approval (Grid and Form, `p5-flow-approval`) memakai Form Approval baku** (keputusan Jo 2026-10-21): grid
  awal tetap; klik baris membuka `x-dl::approval` untuk SATU dokumen (hanya-baca, Level n of N, Riwayat, Reject / Hold /
  Setujui), Back kembali ke grid. `x-dl::approval` mendapat prop opt-in `single` (tanpa daftar kiri; dokumen
  `'readonly' => true` = sudah diputuskan, tanpa tombol keputusan). Keluaran halaman terkunci `p5-form-approval` tidak berubah.

### Dihapus

- **Merusak:** **Form Approval versi 2** (ditolak Jo, belum pernah disetujui) dihapus total, tanpa riwayat/tag: prop `approval` dan `otp-url` pada
  `x-dl::form` (cap status approval, tombol Approve/Hold/Reject/Duplicate menurut `approval`, dialog OTP `data-f-otp`),
  aturan lint **F110**, opsi `otp` pada `Devlib.confirm()`, teks `cf_otp`/`otp_required`/`f_ask_approve_otp`, dan di demo
  login simulasi Creator/Approver/Administrator + kode OTP. `:can-approve` dan `:approval-levels/-at` tetap. Prop yang
  masih dikirim pemakai menjadi atribut HTML biasa (tidak galat); tmerp dan sstock tidak memakainya.
- **Merusak:** **Menu Classic** dikubur sebagai versi lama (tag `history/menu-classic-v1`): komponen `x-dl::shell`
  (`Devlib\Components\Shell`, `Shell::preferred()`), `shell.js`, pilihan "tukar ke Menu Classic" di menu foto profil,
  cookie `dl_menu`, dan prop `classic` pada `x-dl::layer-shell`. **Layer Menu (`x-dl::layer-shell`) satu-satunya menu
  dan menjadi default.** Prop `classic` yang masih dikirim pemakai tidak menimbulkan galat (menjadi atribut HTML biasa).
- **Dieline v1 dan v2** dikubur (tag `history/dieline-v1`, `history/dieline-v2`); `Dieline` kini = versi terakhir
  (v3), tanpa kelas `DielineV2`/`DielineV3`.
- Aturan baru: **satu default = versi terakhir; versi lama = kode sejarah bertag `history/…`**
  (`skills/devlib/object.md` bagian 6, `docs/versioning.md`). Rilis berikutnya sebaiknya minor `0.3.0`.

## [0.2.0] - 2026-10-21

Rilis kedua. Menu katalog 5 bagian dengan halaman Aturan yang membaca `skills/devlib/*.md`; menu "Grid and Form"
(alur Grid -> Form, form siklus ketat, Nested Grid, Export CSV dan XLSX); Layer Menu accordion dan tema Soft;
Technical Guide semua object; kebijakan data dan pengindeks Assistant. **Merusak:** komponen `x-dl::master`
dan Mode Developer dihapus (tidak dipakai sstock maupun proyek ERP); layar Special Forms dipindah ke aplikasi pemakai.

### Ditambahkan

- **Technical Guide semua object** (pengganti Mode Developer): `Devlib\Guide` menyusun satu halaman HTML English berisi SEMUA object `x-dl::*` (tag, kelas, berkas, prop + tipe + bawaan otomatis dari `Manifest`, ditambah rincian tulisan tangan `resources/guide/<tag>.md` dengan tujuh bagian baku dan semua prop dijelaskan). Halaman = route `devlib.guide` (`/_devlib-guide`, opt-in lewat `config('devlib.guide_middleware')`; `config('devlib.guide_url')` untuk halaman sendiri), dibuka dari menu Help. Judul form/kartu/wizard/flow dan lain-lain, ikon info di toolbar grid, serta nama object di halaman katalog menautkan entri object itu (`guide-link.js`, `window.DL_GUIDE` dari `x-dl::theme-boot`; tanpa URL = tanpa link, proyek lama tidak berubah). Rincian tahap 1: `grid` dan `form`; object lain tercatat di `resources/guide/PENDING.txt` (hanya boleh menyusut; `GuideContractTest` mewajibkan rincian untuk object baru).
- **Menu katalog 5 bagian** (situs acuan; slug lama utuh, tanpa halaman lama baru di `APPROVED`). Bagian tampil sebagai judul kelompok di akar Layer Menu:
  Foundations & Rules (Basics & Themes, Global Behaviors, Rules), Components (Inputs & Buttons, Display & Feedback, Widgets & Utilities),
  Screen (Grid and Form, Charts & Dashboards, Schedule, Print & Report, Special Screen [Warehouse, Chat], Shell, Account & Security), Flow, Engine.
  Penempatan halaman satu tempat: `Catalog::layout()`. Halaman Aturan = tampilan langsung `skills/devlib/*.md` (`App\Support\RulesDocs`, view `lib-doc`,
  slug `rules-<berkas>`; tanpa salinan, judul dari dokumen, tidak dihitung sebagai objek QC). Bagian Flow: `p10-system-flow`, `p10-data-flow`,
  `p10-work-flow` (contoh fiktif `x-dl::flow`, belum ditinjau). Library: modul Shell boleh memuat `section` (judul kelompok di Layer Menu; Shell biasa
  mengabaikannya); tanpa `section` perilaku lama.
  `x-dl::layer-menu` menerima `fold-groups` (opt-in): judul kelompok di layer akar jadi tombol lipat, anak menunya menggulung ke atas di belakang judul,
  keadaannya diingat per browser (mode mini selalu terbuka). `'1'` = tiap judul sendiri-sendiri, `'accordion'` = satu kelompok terbuka sekali waktu (awal:
  kelompok layar aktif) dengan jumlah halaman di tiap judul; `x-dl::layer-shell` memakai `accordion` otomatis hanya bila ada modul ber-`section`.
  Tema: token `side-2`, `side-hover`, `side-scroll` kini diturunkan dari warna sidebar tiap tema (sebelumnya biru bawaan, tampak biru di tema hijau/merah);
  tema bawaan `navy` tidak berubah. Keluarga tema baru **Soft** (`soft_blue`, `soft_green`, `soft_red`): sidebar terang berwarna pastel dengan teks gelap
  (token baru `--dl-side-hot`, bawaan putih sehingga tema lama tidak berubah; tema Soft juga menyetel `tabbar`/`tab`); tema kini 15. Situs acuan memakai
  `config('devlib.theme') = 'soft_green'` (hijau pastel); pilihan pengguna tetap menang. Menu klasik (`x-dl::shell`) belum diperiksa pada tema Soft.

- **Aturan membangun object** (`skills/devlib/object.md`, dokumen; tanpa perubahan kode): daftar periksa membuat komponen (kelas PHP + JS berpasangan,
  header REMARK, CSS token, manifest, tes kontrak, halaman katalog), aturan baku, cara mewariskan, uji layak jadi object, dan versi. Butir yang belum
  punya aturan tertulis ditandai **BELUM ADA ATURAN**. Ditautkan dari `SKILL.md`.

- **Nested Grid** (opt-in, aturan pemilik 2026-10-21; `skills/devlib/grid.md`). Kolom teks / daftar `x-dl::grid` dengan `'url'` kini mengisi `{kolom}` (nilai kolom lain di baris itu), tidak hanya `{id}`,
  dan `'ref' => true` memberi gaya tautan rujukan (aksen, garis bawah, panah ↗). Dengan itu grid bisa saling menaut tanpa form (grid ke grid, tingkat tidak dibatasi).
  Situs acuan: halaman `p5-grid-nested` (Grid Report): Customer Order → Customer / Order → item → Product → Order …, jejak di breadcrumb dan `?t=`, Back naik satu tingkat
  (`App\Support\NestedDemo`, tes `NestedGridTest`). Kolom tanpa `url` / `ref` tidak berubah.

- **Nested Grid: penyempurnaan dan tiga kemampuan baru** (opt-in, izin pemilik 2026-10-21; hanya `p5-grid-nested` dan `grid.js`, tanpa perubahan pada halaman APPROVED lain):
  - *Jejak sejak tingkat pertama*: grid awal menaut dengan `?t=home:<daftar>`, sehingga breadcrumb, `?t=` dan Back konsisten dari tingkat pertama; Back dari tingkat dua kembali ke grid awal.
  - *Grid `restore` memulihkan keadaan saat tautan sel diikuti* (bukan hanya saat membuka form): kata cari, halaman, kotak statistik, dan kini juga saringan per kolom (`colf`). Grid tanpa `restore` tidak berubah.
  - *Jejak rusak dibakukan*: segmen tak sah dibuang, `t` bukan teks tidak lagi menjadi galat, alamat dialihkan ke bentuk baku.
  - *Contoh kedua, data lain*: Supplier → Purchase Order → Item → semua PO berisi item itu → Supplier … pada halaman yang sama (`grid-nested-purchase`), bukti Nested Grid bersifat generik.
  - *Back dan Start*: Back dengan pintasan Alt+↑ (`data-dl-key` baku), dan tombol Start (ke grid pertama) muncul bila jejak panjang.
  - *Jejak panjang dan berulang*: breadcrumb dipendekkan menjadi pertama, "…", tiga terakhir; tingkat yang sama muncul lagi = kembali ke sana (jejak dipotong, tidak menumpuk).

- **Export grid: CSV dan XLSX.** Tombol Export di toolbar `x-dl::grid` kini membuka menu dua pilihan (CSV, XLSX). XLSX dibuat di peramban tanpa pustaka
  (`Devlib.h.xlsxBlob` / `downloadXlsx`: satu lembar, ZIP tanpa kompresi, angka tetap angka). Berlaku untuk semua grid, termasuk halaman APPROVED
  (hanya tombolnya: bertambah panah dan menu). Isi export tidak berubah (kolom yang tampil, urutan dan saringan saat itu).

- **Menu "Grid and Form" dan siklus form ketat** (opt-in, aturan pemilik 2026-10-21; rinci `skills/devlib/grid-form-flow.md`).
  `x-dl::form` menerima `:strict`: record baru = Undo + Save (Draft, atau Active untuk master, isian bawaan terisi); tersimpan = terkunci, **Edit** membuka
  isian (Save + Undo); Save dan Confirm tetap di form; Delete (Yes/No) dan Back kembali ke grid; tanpa New / Email / Back to Draft; cap status dokumen
  dan approval di pojok kanan atas; isian terkunci berwarna dan bergaya (angka dan total tebal + lebih besar, terbilang bergaris bawah, tanggal miring).
  `:approval` = field TERPISAH dari status (`waiting|approved|hold|rejected`, hanya saat Confirmed): Cancel pembuat hanya sebelum diputuskan, APPROVED hanya
  bisa menjadi REJECTED, REJECTED hanya tampil di tab Rejected, Duplicate oleh pembuat, REJECTED → APPROVED oleh pemeriksa dengan OTP (`:otp-url`; dialog OTP
  lewat `Devlib.confirm({otp:true})`; penerbitan OTP urusan proyek). Master: `:deletable` (Delete hanya administrator untuk data yang belum dipakai). Aturan lint F110.
  `x-dl::lines` di dalam form `:strict` tidak baca-saja saat Draft tersimpan (tombol baris selalu ada); aksi atau perubahan baris membuka mode Edit (Save + Undo), menandai form berubah, dan ikut Undo (isi baris dikembalikan).
  Print di form membuka cetakan contoh (`/lib/{slug}/print/{id}`) yang memuat status dokumen dan approval ("CONFIRMED · REJECTED", tanda air untuk Draft / Hold / Rejected / Cancelled).
  `x-dl::wizard` menerima `:done` (layar Done + Start again, tanpa Back). Tanpa `:strict` / `:approval` / `:done` perilaku lama tidak berubah.
  Situs acuan: grup menu "Grid and Form" (pohon dua tingkat: Grid Report | Grid Pre-Entry > Transactional (Master-Detail, Single, Approval), Master, Lookup | Form
  Wizard), halaman baru `p5-flow-single` (Cash Voucher) dan `p5-flow-approval`, contoh Master-Detail Customer Order lalu Purchase Order. Halaman APPROVED lama
  (`p5-grid-tx`, `p5-grid-master`, `p5-form-single`, `p5-form-md`, `p5-form-approval`) tidak diubah dan hanya keluar dari menu.

- **Alur Grid → Form → kembali** (opt-in). `x-dl::grid` menerima `restore`: cari, halaman, dan kotak statistik disimpan saat form dibuka lewat
  `openUrl` / `newUrl` dan dipulihkan sekali saat kembali; `?hl=<id>` menyorot baris yang baru disimpan (halaman diloncati). `boot.js` menampilkan
  `<div hidden data-dl-flash="success|danger|warn|info">` sebagai toast sekali muncul. Tanpa `restore` dan tanpa elemen itu perilaku lama tidak berubah.
  Aturan baku urutan layar, tombol, tujuan, dirty guard dan galat: `skills/devlib/grid-form-flow.md`.
  Situs acuan: alur `p5-flow-master` (grid pegawai → Master Form) dan `p5-flow-document` (grid CO / PO → Form
  Master-Detail), rute nyata `/lib/{slug}/form/{id}` (belum ditinjau, bukan `APPROVED`).

- **Asisten: pengindeks latar belakang, tahap 1** (opt-in). `Devlib\Assistant\Index`: `IndexSource`, `IndexEntry`, `IndexStore`
  (+ `MemoryIndexStore`), `Indexer` (hanya yang berubah; yang hilang dibuang; entri terlarang `DataPolicy` dilewati; entri nilai
  hanya namanya), `Budget` (anggaran waktu + jeda, bukan jaminan CPU), `IndexRunner` + `IndexThrottle` (sekali per jam),
  `IndexAudit` (alat tes wajib tiap program) dan perintah `devlib:index` (selalu terdaftar; tanpa `IndexStore` terikat ia mencetak petunjuk dan selesai tanpa galat).
  `Assistant` menerima `index:` sebagai calon tambahan layar / topik, tersaring hak; tanpa indeks perilaku lama tidak berubah.
  Rancangan: `docs/assistant-indexer.md`.
- **Asisten: data pribadi terlarang total dan orang terlindung** (opt-in, lewat `DataPolicy`). Frasa data pribadi
  (KTP / NIK, alamat rumah, tanggal lahir, kata sandi, dsb.; kata umum "alamat" tidak termasuk) tidak dibacakan siapa pun
  (tanpa Tool dan tanpa model bahasa; layar pengelolaan data sendiri tetap boleh ditunjukkan), dan `protected:` berisi
  orang yang ditunjuk program: pertanyaan yang menyebutnya `DENIED` tanpa menyentuh Tool / model, tidak pernah diindeks.
  `DataPolicy::personal()`, `protects()`, `excludes()`. Tanpa `DataPolicy` perilaku lama tidak berubah.
- **Asisten: hak nilai rupiah per Tool** (opt-in). `ScopedValueAsker::canSeeValueOf($kunciTool)`:
  program dapat memberi sales hanya hasil HPP sementara keuangan melihat semua nilai rupiah. Penanya
  `ValueAsker` biasa tetap memakai `canSeeValue()` global (perilaku sebelumnya tidak berubah).
- **Asisten: cakupan data dan sensitivitas** (opt-in). `Sensitivity` + `Classified` (Tool menyatakan
  qty / nilai / terlarang), `ValueAsker` (hanya orang keuangan melihat nilai rupiah; `Asker` biasa
  ditolak), dan `DataPolicy` (kata terlarang gaji / payroll / formula HPP: Tool ditolak saat dibuat, layar dan
  topik tidak ditawarkan, pertanyaannya `DENIED`). Absensi tetap boleh. Tanpa itu perilaku lama
  tidak berubah. Aturan: `skills/devlib/assistant.md`.
- **Asisten: jawaban "di luar program"** (opt-in). `Assistant` menerima `outOfScope`
  (kalimat tetap milik aplikasi); bila diisi dan tidak ada kata pertanyaan yang cocok
  dengan layar, bantuan, atau Tool aplikasi, jawabannya `Reply::OUT_OF_SCOPE`. Pertanyaan
  dalam lingkup yang belum ada jawabannya tetap `UNKNOWN`; tanpa `outOfScope` perilaku
  lama tidak berubah. Halaman katalog `p7-assistant` memakai contoh pengguna aplikasi
  (order hari ini, letak menu PO, di luar program) dan judul kolom satu bahasa.

### Diubah

- Situs acuan: Back pada form contoh (`p5-form-single`, `p5-form-md`) kini ke grid alurnya lewat `:back`, `back_history = false` di config situs, dan
  pengecualian lint F101 untuk tiga form itu dihapus (disetujui pemilik 2026-10-20). Tampilan dan fungsi lain form tidak berubah.

### Dihapus

- **Merusak:** **Mode Developer** (F10, saklar `x-dl::dev-toggle`, kotak asal-usul objek, garis merah objek non-library, tampilan temuan lint di layar) dihapus beserta prop `developer` di `x-dl::shell`/`x-dl::layer-shell`, prop `table`/`record-id` di `x-dl::meta`, dan atribut `data-dl-lint`. Gantinya Technical Guide (lihat Ditambahkan). Temuan aturan tetap lewat `php artisan devlib:lint`.
- **Halaman katalog Grid Variant** (`p5-grid-variant`, 2026-10-21, atas permintaan pemilik; akan diganti Nested Grid) beserta enam demonya (`grid-master-detail`, `grid-status`, `grid-photos`,
  `grid-mix`, `grid-sheet`, `grid-coverage`). Komponen `x-dl::sheet`, `x-dl::coverage`, dan `x-dl::grid` tidak berubah; hanya halaman demonya yang hilang.

- **Merusak: komponen `x-dl::master`** (grid + form edit dalam satu layar): `Devlib\Components\Master`, `master.js` (`data-dl-master`, event
  `dl:master-save`), CSS `.dl-master*`, dan di situs acuan halaman `p5-master`, `p5-master-detail` (generasi lama, disetujui 2026-09-26) beserta demo
  `master-employee` dan `master-detail-po`. Penggantinya alur grid → form di halaman terpisah (`grid-form-flow.md`, `x-dl::grid restore` + `x-dl::form :back`).
  Sebelum dihapus dipastikan tmerp dan sstock tidak memakainya (tmerp: hanya teks di `docs/`; sstock: nol). Ini menghapus komponen publik,
  jadi rilis berikutnya sebaiknya naik minor (`0.2.0`) sesuai `docs/versioning.md`.
- **Grup Special Forms (10 layar `sf-*`) dari situs acuan**: layar Customer Plan Delivery, Edit Profile, Master Product,
  Customer 360, Petty Cash, Label Printing, Pallet Label, Track via QR / Barcode, Material Planning and Booking dan Assign Crew
  dipindah ke aplikasi pemakai (tmerp) sebagai layar pembanding. Ikut dihapus 10 demo, 12 komponen `x-mp::*` yang hanya
  dipakai layar itu di `packages/mockup-parts` (PettyCash, SelfService, LabelPrint, PalletLabel, ScanTrack, MaterialPlan,
  MaterialBooking, CrewForm, CustomerCard, ProductMaster, ProductTree, PromiseBook) dan CSS-nya. Bundel `mockup-parts.js/css`
  tidak lagi dimuat di tata letak situs (tidak ada halaman katalog yang memakai `x-mp::*`); folder paketnya tetap ada.
  Library `jo/devlib` (`src/`, `resources/`) tidak berubah.

## [0.1.0] - 2026-10-20

Rilis pertama: DevLib sebagai repositori mandiri (`jo/devlib`) beserta situs
acuan `site/`. Riwayat sebelum 2026-10-19 berasal dari pengembangan bersama
proyek induk lama; ringkasannya dimasukkan di sini.

### Ditambahkan

- **Komponen `x-dl::*`** (PHP + JS berpasangan, tema, bahasa), dibangun bertahap
  mulai 2026-09-23: komponen dasar, field, login (`375811d`, `befff0f`, `0806c50`),
  grid, master, master-detail, approval, wizard, kanban, gantt, calendar,
  print-out, report, import, org-chart (`173b06b`..`a53dc75`), dieline,
  imposition, chart/dashboard, QR/barcode, id-card, roster, formation
  (`833c60c`..`f5e8bff`), pivot, sheet, drilldown (`422dd33`, `b93449f`, `acc9154`),
  chat gaya WhatsApp (`0e15535`), menu Ribbon/Mega/Side Panel (`f247465`).
- Form dengan siklus dokumen Draft/Confirmed/Cancelled/Closed, Approval Center
  dan log keputusan (`d8e60f4`, `5b05737`, `4bc9c91`), `x-dl::doc-footer` (`a81649c`),
  `x-dl::doc-share` (cetak PDF/JPEG/HTML/e-mail, `46dba36`).
- Objek lanjutan: `x-dl::side-screen` (`4729326`), `x-dl::blueprint` (`940101b`),
  `x-dl::code-generator` (`6df223f`), `x-dl::statement` (`be78acf`), `x-dl::coverage`
  (`0e55e65`), `x-dl::warehouse-desk` (`5075487`), `x-dl::delivery-track`
  (`909ded7`), `x-dl::feed` (`7156262`), `x-dl::timer` (`ea74c05`),
  `x-dl::member-list` (`b515eba`), `x-dl::login-card`, `x-dl::photo-box`,
  `x-dl::profile-banner` (`eda1dad`, `37deb64`), `x-dl::strip-kv`, `x-dl::kpi-row`,
  `x-dl::box`, `x-dl::media` (`43a070e`, `986b289`, `fedf8c1`).
- Papan widget pembuka dan dashboard per posisi (`723b683`, `5529309`).
- Terbilang PHP+JS dan field `number-words` (`8cc7325`); angka, tanggal
  `dd-Mmm-yyyy`, dan kode mata uang mengikuti bahasa tampilan (`b50028e`, `3de372b`).
- Multi-bahasa dengan kamus; teks JS dipindah ke kunci terjemahan (`4b0f378`,
  `df52a93`..`2377bea`).
- **Engine**: kontrak `Devlib\Engine\*` (mode Suggest/Auto/Calc/Flow, `Result`,
  `EngineRegistry`, `EngineException`, alur Cancel + Adjustment) dan skill
  `engine.md` (`26930e5`); katalog engine (`e16a2e2`); tes kontrak engine.
- **Assistant AI dan Chat** sebagai kontrak opt-in `Devlib\Assistant` dan
  `Devlib\Chat` dengan adaptor diikat aplikasi (`55e975f`), halaman katalog
  berdata fiktif dan aturan `assistant.md`/`chat.md` (`5a6314f`). Halaman
  katalog belum disetujui (`286ae1e`).
- **PrintDoc**: objek `print-doc` (`9f0a695`), watermark DRAFT (`7efca34`),
  `x-dl::print-sheet` A4 berbagian (`42b56f0`), lembar lampiran A4
  `Document::appendices` (`952381c`).
- **Lint**: `php artisan devlib:lint` (GridRules, FormRules, ViewLinter),
  tampil di Mode Developer (`fbced97`).
- **Manifest**: `php artisan devlib:manifest` menulis
  `skills/devlib/components.md`, tes kontrak, header REMARK baku di 147 komponen
  (`5004222`).
- `php artisan qc:reviews` dan bilah QC (QC > UAT petugas > UAT supervisor)
  (`beaf2ce`, `2c3d8b0`).
- **Skill** `devlib` modular: `SKILL.md` inti + `install`, `grid`, `form`,
  `transaction-lifecycle`, `help`, `inheritance`, `engine` (`465e1d4`..`53208e9`);
  skill `inventory` (`bdaf6ca`).
- **Situs acuan** `site/` mandiri: katalog komponen, contoh engine, login contoh
  `admin`/`admin`, data milik sendiri (`3719286`, `09735ab`, `ca8a116`, `1204477`).
- Opsi opt-in: identitas grid `hue`/`box` (`513a8b7`), kamus istilah tombol/status
  (`6ca238c`), `without` dan `pin` pada Layer Shell (`753d5c9`, `aa7c0ca`),
  `config devlib.back_history` (`d76f429`), prop `row-state` pada Lines
  (`573fd41`), sel tautan pada Table (`af5e508`).

### Diubah

- Grid dipisah dua jenis: `kind="master"` (kotak warna) dan
  `kind="transactional"` (kolom pertama polos tebal) (`9c7202f`, `3307beb`).
  **Merusak:** `type doc` tidak lagi berkotak warna.
- Label aksi form dan grid selalu English; tanggal baku `dd-Mmm-yyyy`;
  jumlah memakai kode mata uang (`43edb84`, `b50028e`, `b50cff0`).
- JS komponen jadi berbasis kelas dengan induk `DlComponent` dan registry
  (`d180e70`, `3d7ad0d`).
- Markup tulisan tangan di layar diganti objek DevLib (`c1ea102`, `fedf8c1`);
  penghitung markup mentah diturunkan ke lantai terdokumentasi.
- Repo dibuat mandiri: README pasang, paket `jo/devlib`, tanpa rujukan ke
  `packages/devlib` (`b095b3a`, `26930e5`).
- Pengenal berawalan nama proyek induk di situs diganti `devlib`
  (`DevlibDieline`, `devlibDirty`, kunci `postMessage`) (`fa44629`, `1d33d99`).
- Wallpaper login semua halaman satu folder `bg/devlib` (`8a54fdc`).

### Dihapus

- Form Variant (Form Filter dan Quick-add) beserta `x-dl::filter-form`
  (`f5ec3fc`); `x-dl::approval-bar` (`8ec0629`).
- Menu Setup Language/Localization lama (`652c79d`).
- Ketergantungan situs acuan pada paket dan data proyek induk lama; sebutan
  nama proyek itu dibersihkan (`2d1cde5`, `1204477`). Paket `mockup-parts`
  dan grup Special Forms dipulihkan karena 10 layar masih memakainya (`013ae98`).
- `tmdieline.js` lama (`1d33d99`).

### Diperbaiki

- Empat bug angka: Lines desimal, persen footer Lines, field Money, presisi
  kalkulator Sheet (`28fe5ff`).
- Dropdown terpotong card ber-overflow dan tinggi daftar (`e9f9d8d`, `c6e60de`).
- Halaman awal terkunci di balik popup kekurangan bahan (`3e11d54`).
- Lembar cetak meluber ke footer halaman (`4bc1e8a`).
- Tombol hapus baris terakhir pada Lines, validasi isian di modal bersarang
  (`f023b46`).
- Tes bawaan situs diperbaiki dan login contoh dibuat bypass (`09735ab`).
