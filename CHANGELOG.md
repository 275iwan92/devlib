# Catatan perubahan

Format mengikuti [Keep a Changelog](https://keepachangelog.com/id-ID/1.1.0/);
versi mengikuti [kebijakan versi](docs/versioning.md) (SemVer, tahap `0.y.z`).
Selama `0.x`, kenaikan minor boleh merusak antarmuka; perubahan seperti itu
ditandai **Merusak:**. Hash di kurung menunjuk commit asal.

## [Belum dirilis]

### Diubah

- **Halaman katalog `p5-override` (OO Methods and Properties) kini menceritakan satu object, contoh kerja Kanban** (permintaan Jo
  2026-10-21), enam bagian: cerita object, tabel properti, tabel method (PHP dan JS), cara membuat turunan (kode asli
  `KanbanProduksi`), demo hidup, aturan terkait (`object.md`, `inheritance.md`, entri Technical Guide). Tabel dibaca dari
  kode lewat `App\Support\ObjectDoc` (refleksi konstruktor dan method, header REMARK, berkas kelas JS); arti singkat di
  `ObjectNotes` dicocokkan dengan kode oleh `ObjectDocTest`. Status Review (belum APPROVED); `x-dl::kanban` tidak diubah.
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
