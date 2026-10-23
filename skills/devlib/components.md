# Manifest komponen DevLib

Dibuat otomatis oleh `php artisan devlib:manifest` — JANGAN diedit tangan. Cari tag di sini; baca kelas/view hanya untuk komponen yang dipakai.
Prop = parameter konstruktor `nama:tipe=bawaan` (di Blade: kebab-case, mis. `:open-url`).

## `<x-dl::accordion>`
Panel lipat DevLib dengan ringkasan di header (summary), lencana berwarna (tone), key dan maxHeight.
- Berkas: `src/Components/Accordion.php`
- Prop: title:string='', open:bool=false, icon:?string=NULL, badge:?string=NULL, summary:?string=NULL, tone:string='info', key:?string=NULL, maxHeight:?string=NULL

## `<x-dl::accordion-group>`
Wadah beberapa accordion; arah vertikal/horizontal, multiple, dan ingatan panel terbuka (remember).
- Berkas: `src/Components/AccordionGroup.php`
- Prop: direction:string='vertical', multiple:bool=false, height:string='300px', remember:?string=NULL

## `<x-dl::action-bar>`
Baris tombol di kaki form/kartu. align: end (default) | start | between.
- Berkas: `src/Components/ActionBar.php`
- Prop: align:string='end'

## `<x-dl::action-form>`
Form aksi kecil: pembungkus <form> TANPA judul / panel / tombol sendiri -- untuk tombol aksi (Post, Cancel, Create),.
- Berkas: `src/Components/ActionForm.php`
- Prop: method:string='post', action:?string=NULL

## `<x-dl::alert>`
Kotak pesan sebaris. Kosong = tersembunyi (bisa diisi lewat JS). Slot = isi kaya (teks tebal + tombol aksi).
- Berkas: `src/Components/Alert.php`
- Prop: tone:string='danger', message:?string=NULL, items:array=[]

## `<x-dl::announce>`
Pengumuman bergantian (dari file teks lewat x-dl::frame announce="...").
- Berkas: `src/Components/Announce.php`
- Prop: lines:array=[], interval:int=6

## `<x-dl::approval>`
Approval Center (mockup pr/po_approval_center): daftar dokumen menunggu (kiri) + detail (kanan):.
- Berkas: `src/Components/Approval.php`
- Prop: docs:array=[], lineColumns:array=[], title:string='Waiting for My Approval', approveLabel:string='Approve', holdLabel:?string='Hold', rejectLabel:string='Reject', me:string='Me', source:?string=NULL, action:?string=NULL, log:array=[], single:bool=false, back:?string=NULL

## `<x-dl::avatar>`
Foto orang/objek; tanpa foto = inisial dengan warna tetap per nama.
- Berkas: `src/Components/Avatar.php`
- Prop: name:string='', photo:?string=NULL, size:string='md', sub:?string=NULL

## `<x-dl::back-button>`
Tombol Back baku (label SELALU "Back", LOCKED): kembali ke layar sebelumnya; fallback = alamat bila tak ada riwayat.
- Berkas: `src/Components/BackButton.php`
- Prop: fallback:string='/'

## `<x-dl::badge>`
Label status kecil. tone: neutral|accent|success|warn|danger|info|violet. Status dokumen -> Badge::forStatus("APPROVED").
- Berkas: `src/Components/Badge.php`
- Prop: label:string='', tone:string='neutral', pill:bool=true, icon:?string=NULL

## `<x-dl::barcode>`
Barcode 1D (JsBarcode dari cdnjs): format CODE128 (default) | EAN13 | EAN8 | CODE39 | ITF14 | UPC.
- Berkas: `src/Components/Barcode.php`
- Prop: value:string='', format:string='CODE128', height:int=50, width:float=1.6, text:bool=true

## `<x-dl::block>`
Kotak berjudul (fieldset + legend) untuk mengelompokkan field yang bisa dikunci bersama (mis. blok revisi spesifikasi).
- Berkas: `src/Components/Block.php`
- Prop: legend:string=''

## `<x-dl::blueprint>`
Kanvas CAD / blueprint: grid minor+mayor, zoom-pan (viewBox), crosshair, HUD koordinat, garis dimensi.
- Berkas: `src/Components/Blueprint.php`
- Prop: width:float=400.0, height:float=300.0, minor:float=10.0, major:float=50.0, unit:string='mm', dims:array=[], heightCss:string='420px'

## `<x-dl::box>`
Wadah netral (div, p, span, article, ...) yang meneruskan semua atribut dan kelas; dipakai bila layar butuh pembungkus
- Berkas: `src/Components/Box.php`
- Prop: as:string='div'

## `<x-dl::brand>`
lencana logo + nama + tagline aplikasi. tone: light (di atas gelap) | dark | auto.
- Berkas: `src/Components/Brand.php`
- Prop: name:?string=NULL, tagline:?string=NULL, tone:string='light', logo:?string=NULL

## `<x-dl::breadcrumb>`
Jejak lokasi halaman: Modul › Grup › Layar [› current].
- Berkas: `src/Components/Breadcrumb.php`
- Prop: items:array=[], menu:bool=false, current:?string=NULL

## `<x-dl::build-board>`
Papan Build Progress: berapa objek, modul, layar, form, grid, laporan dan engine yang sudah dibangun, modul mana yang.
- Berkas: `src/Components/BuildBoard.php`
- Prop: build:array=[], library:array=[], enginesUrl:?string=NULL, source:?string=NULL

## `<x-dl::bullet>`
Bullet chart (Stephen Few): rentang kualitatif (buruk/cukup/baik), batang nilai, garis target.
- Berkas: `src/Components/Bullet.php`
- Prop: label:string='', value:float=0.0, target:float=0.0, ranges:array=[], max:float=0.0, format:string='number', sub:?string=NULL

## `<x-dl::button>`
Tombol baku. icon = arti baku (save, delete, print... lihat Devlib\Icon) atau emoji. href terisi = dirender <a>. variant link = tampil sebagai tautan.
- Berkas: `src/Components/Button.php`
- Prop: label:string='', variant:string='primary', size:string='md', type:?string=NULL, href:?string=NULL, icon:?string=NULL, block:bool=false, i18n:?string=NULL, preset:?string=NULL

## `<x-dl::calendar>`
Kalender acara (mockup_UIUX_calendar): kiri = Create Event + kalender mini + cari; tengah = Day/Week/Month;.
- Berkas: `src/Components/Calendar.php`
- Prop: events:array=[], display:string='week', date:?string=NULL, weekStart:int=0, scrollHour:int=7, sidebar:bool=true, source:?string=NULL

## `<x-dl::card>`
Kotak berjudul untuk mengelompokkan isi (bagian form, panel info). Slot "actions" = tombol di kanan judul.
- Berkas: `src/Components/Card.php`
- Prop: title:?string=NULL, description:?string=NULL, variant:string='section', icon:?string=NULL

## `<x-dl::chart>`
Grafik SVG tanpa library luar. Satu format data untuk semua tipe:.
- Berkas: `src/Components/Chart.php`
- Prop: type:string='bar', labels:array=[], series:array=[], title:?string=NULL, subtitle:?string=NULL, format:string='number', height:string='280px', legend:bool=true, source:?string=NULL

## `<x-dl::chat>`
Chat ala WhatsApp. Daftar kiri = orang yang SEDANG LOGIN (avatar berwarna cerah + titik hijau) + percakapan lain yang.
- Berkas: `src/Components/Chat.php`
- Prop: me:array=[], people:array=[], convos:array=[], api:array=[], compact:bool=false, height:string='560px', source:?string=NULL

## `<x-dl::chips>`
Baris chip / badge yang membungkus ke baris berikutnya (tag proses khusus, label jenis, status kecil).
- Berkas: `src/Components/Chips.php`
- Prop: as:string='div'

## `<x-dl::clock>`
Jam + tanggal berjalan menurut zona waktu SISTEM (default Asia/Jakarta = WIB), ikut bahasa aktif. mode: clock (jam+tanggal) | date (tanggal saja).
- Berkas: `src/Components/Clock.php`
- Prop: mode:string='clock', timezone:?string=NULL, tzLabel:?string=NULL

## `<x-dl::code-button>`
Ikon kecil </> di pojok layar: klik -> jendela berisi.
- Berkas: `src/Components/CodeButton.php`, tampilan `resources/views/components/code-button.blade.php`
- Prop: code:string='', file:string='', classes:array=[], mockupUrl:?string=NULL

## `<x-dl::code-chain>`
Dropdown yang saling terkait dan MENYUSUN KODE (Material Category -> kode item, user 2026-10-03; pola yang sama untuk Product).
- Berkas: `src/Components/CodeChain.php`
- Prop: nodes:array=[], levels:array=[], value:mixed=NULL, name:string='chain', codeLabel:string='Code', navigate:?string=NULL, addUrl:?string=NULL, inactive:bool=false, disabled:bool=false, source:?string=NULL

## `<x-dl::code-generator>`
Pembuat kode QR dan barcode: pilih jenis, ketik isinya, hasil tampil langsung, lalu unduh PNG / SVG atau cetak.
- Berkas: `src/Components/CodeGenerator.php`
- Prop: type:string='QR', value:?string=NULL, title:?string=NULL, hint:?string=NULL

## `<x-dl::cols>`
Dua (atau lebih) blok berdampingan yang otomatis bertumpuk di layar sempit (grid auto-fit 320px, jarak 12px).
- Berkas: `src/Components/Cols.php`
- Prop: (tanpa prop)

## `<x-dl::coverage>`
Matriks kelengkapan: satu baris per kelompok (mis. set mesin), kolom = jenis dokumen / item, tiap sel = chip status yang.
- Berkas: `src/Components/Coverage.php`
- Prop: columns:array=[], rows:array=[], filters:array=[], head:string='Item', subHead:string='', search:string='Search…', empty:string='Nothing matches.', source:?string=NULL

## `<x-dl::dashboard>`
Opening screen setelah login (Beranda): salam + jam + cuaca, tombol Job Description & SOP jabatan,.
- Berkas: `src/Components/Dashboard.php`, tampilan `resources/views/components/dashboard.blade.php`
- Prop: board:array=[], source:?string=NULL

## `<x-dl::delivery-track>`
Jejak pengiriman skematis (SVG, tanpa peta): gudang -> pelanggan, truk bergerak di sepanjang rute dan berhenti di titik.
- Berkas: `src/Components/DeliveryTrack.php`
- Prop: stage:string='draft', from:string='Warehouse', to:string='Customer', position:?float=NULL, times:array=[], title:string='', subtitle:string=''

## `<x-dl::device-frame>`
Bingkai pratinjau Laptop / Tablet (768px) / Smartphone (390px) dari mockup form.
- Berkas: `src/Components/DeviceFrame.php`
- Prop: mode:string='laptop'

## `<x-dl::device-pick>`
Pilihan tampilan perangkat (Phone view / Desktop view): dua pilihan radio besar bernama `ui`, terpilih awal menurut cookie atau tebakan perangkat.
- Berkas: `src/Components/DevicePick.php`
- Prop: name:string='ui', value:?string=NULL, label:string='Display'

## `<x-dl::divider>`
Garis pemisah dengan teks di tengah (mis. "atau").
- Berkas: `src/Components/Divider.php`
- Prop: label:string='', i18n:?string=NULL

## `<x-dl::doc-footer>`
Footer dokumen Master-Detail untuk layar yang totalnya dihitung SERVER (mis. CoOrder::totals()) -- tampilan sama dengan.
- Berkas: `src/Components/DocFooter.php`
- Prop: tabs:array=[], summary:array=[], words:?string=NULL

## `<x-dl::doc-share>`
Print + Email untuk SETIAP form/dokumen (user 2026-09-29). Layar cukup menaruh 1 tag dan menunjuk area dokumennya:.
- Berkas: `src/Components/DocShare.php`, tampilan `resources/views/components/doc-share.blade.php`
- Prop: target:string='', type:string='doc', docId:string|int|null=NULL, number:?string=NULL, title:string='Document', emailTo:?string=NULL, subject:?string=NULL, message:?string=NULL, endpoint:?string=NULL, orientation:string='portrait', email:bool=true, size:string='md', contacts:array=[], contactsUrl:?string=NULL

## `<x-dl::drawer>`
Panel geser dari samping (kanan/kiri). Dibuka data-dl-open="<id>" atau Devlib.drawer.open(id).
- Berkas: `src/Components/Drawer.php`
- Prop: id:string='drawer', title:?string=NULL, side:string='right', width:string='390px', icon:?string=NULL, maximize:bool=false

## `<x-dl::drilldown>`
Grid Summary -> Detail (drill-down): slot "summary" = grid ringkasan, slot bawaan = grid detail.
- Berkas: `src/Components/Drilldown.php`
- Prop: link:string='', labelKey:string='name', title:string='Summary'

## `<x-dl::empty-state>`
Tampilan saat data kosong: ikon, judul, keterangan, dan tombol aksi (slot).
- Berkas: `src/Components/EmptyState.php`
- Prop: title:string='No data yet', message:?string=NULL, icon:string='file'

## `<x-dl::engine-catalog>`
Daftar ENGINE (mesin hitung / aturan bisnis) sebuah aplikasi: untuk tiap engine tampil kegunaan, cara memanggil.
- Berkas: `src/Components/EngineCatalog.php`
- Prop: engines:array=[], open:bool=false, source:?string=NULL

## `<x-dl::env-badge>`
Teks lingkungan: akhiran judul logo ('- mockup ver 0.20', part title) dan baris host/IP kecil (part where, di bawah nama pengguna); tidak tampil di situs live.
- Berkas: `src/Components/EnvBadge.php`
- Prop: part:string='where', variant:string='side', label:?string=NULL

## `<x-dl::error-page>`
Halaman kesalahan baku (403, 404, 419, 500, 503, offline): kode, judul, penjelasan, tombol Back & Beranda. full=false untuk pratinjau di dalam halaman.
- Berkas: `src/Components/ErrorPage.php`
- Prop: code:string='404', title:?string=NULL, message:?string=NULL, home:string='/', full:bool=true

## `<x-dl::feed>`
Umpan gaya blog / berita (user 2026-10-07): banyak narasi dibaca berurutan di satu halaman, tanpa membuka satu per satu.
- Berkas: `src/Components/Feed.php`
- Prop: posts:array=[], empty:string='Nothing to read yet.'

## `<x-dl::field.checkbox>`
Satu kotak centang dengan teksnya sendiri (mis. "Ingat saya", "Setuju syarat").
- Berkas: `src/Components/Field/Checkbox.php`, tampilan `resources/views/components/field/checkbox.blade.php`
- Prop: text:string='', name:string='', label:string='', tooltip:?string=NULL, hint:?string=NULL, error:?string=NULL, value:mixed=NULL, placeholder:?string=NULL, required:bool=false, disabled:bool=false, readonly:bool=false, icon:?string=NULL, size:string='sm', i18n:?string=NULL, source:?string=NULL

## `<x-dl::field.checkboxes>`
Pilihan ganda (checkbox group).
- Berkas: `src/Components/Field/Checkboxes.php`, tampilan `resources/views/components/field/checkboxes.blade.php`
- Prop: options:array=[], name:string='', label:string='', tooltip:?string=NULL, hint:?string=NULL, error:?string=NULL, value:mixed=NULL, placeholder:?string=NULL, required:bool=false, disabled:bool=false, readonly:bool=false, icon:?string=NULL, size:string='sm', i18n:?string=NULL, source:?string=NULL

## `<x-dl::field.color>`
Pemilih warna + kode HEX.
- Berkas: `src/Components/Field/Color.php`, tampilan `resources/views/components/field/color.blade.php`
- Prop: name:string='', label:string='', tooltip:?string=NULL, hint:?string=NULL, error:?string=NULL, value:mixed=NULL, placeholder:?string=NULL, required:bool=false, disabled:bool=false, readonly:bool=false, icon:?string=NULL, size:string='sm', i18n:?string=NULL, source:?string=NULL

## `<x-dl::field.credit-card>`
Kartu kredit/debit: nomor ber-mask per jenis (Visa/Mastercard/Amex/JCB/Discover/Diners/UnionPay),.
- Berkas: `src/Components/Field/CreditCard.php`, tampilan `resources/views/components/field/credit-card.blade.php`
- Prop: holder:bool=true, preview:bool=true, name:string='', label:string='', tooltip:?string=NULL, hint:?string=NULL, error:?string=NULL, value:mixed=NULL, placeholder:?string=NULL, required:bool=false, disabled:bool=false, readonly:bool=false, icon:?string=NULL, size:string='sm', i18n:?string=NULL, source:?string=NULL

## `<x-dl::field.date>`
Tanggal dengan kalender popover.
- Berkas: `src/Components/Field/Date.php`, tampilan `resources/views/components/field/date.blade.php`
- Prop: name:string='', label:string='', tooltip:?string=NULL, hint:?string=NULL, error:?string=NULL, value:mixed=NULL, placeholder:?string=NULL, required:bool=false, disabled:bool=false, readonly:bool=false, icon:?string=NULL, size:string='sm', i18n:?string=NULL, source:?string=NULL

## `<x-dl::field.date-range>`
Rentang tanggal awal s.d. akhir + durasi otomatis.
- Berkas: `src/Components/Field/DateRange.php`, tampilan `resources/views/components/field/date-range.blade.php`
- Prop: nameEnd:?string=NULL, valueEnd:mixed=NULL, duration:bool=true, name:string='', label:string='', tooltip:?string=NULL, hint:?string=NULL, error:?string=NULL, value:mixed=NULL, placeholder:?string=NULL, required:bool=false, disabled:bool=false, readonly:bool=false, icon:?string=NULL, size:string='sm', i18n:?string=NULL, source:?string=NULL

## `<x-dl::field.date-slider>`
Geser tanggal (satu tanggal atau rentang). Nilai dikirim ISO (YYYY-MM-DD), tampil dalam format lokal WIB;.
- Berkas: `src/Components/Field/DateSlider.php`
- Prop: from:string='', until:string='', range:bool=false, valueTo:mixed=NULL, nameTo:?string=NULL, orientation:string='horizontal', height:string='220px', name:string='', label:string='', tooltip:?string=NULL, hint:?string=NULL, error:?string=NULL, value:mixed=NULL, placeholder:?string=NULL, required:bool=false, disabled:bool=false, readonly:bool=false, icon:?string=NULL, size:string='sm', i18n:?string=NULL, source:?string=NULL

## `<x-dl::field.date-time>`
Tanggal + jam dalam satu popover (mockup form: "Select Date & Time").
- Berkas: `src/Components/Field/DateTime.php`, tampilan `resources/views/components/field/date-time.blade.php`
- Prop: minuteStep:int=5, name:string='', label:string='', tooltip:?string=NULL, hint:?string=NULL, error:?string=NULL, value:mixed=NULL, placeholder:?string=NULL, required:bool=false, disabled:bool=false, readonly:bool=false, icon:?string=NULL, size:string='sm', i18n:?string=NULL, source:?string=NULL

## `<x-dl::field.decimal>`
Angka desimal (otomatis .00 saat keluar) + satuan.
- Berkas: `src/Components/Field/Decimal.php`, tampilan `resources/views/components/field/decimal.blade.php`
- Prop: decimals:int=2, unit:?string=NULL, toggle:bool=true, name:string='', label:string='', tooltip:?string=NULL, hint:?string=NULL, error:?string=NULL, value:mixed=NULL, placeholder:?string=NULL, required:bool=false, disabled:bool=false, readonly:bool=false, icon:?string=NULL, size:string='sm', i18n:?string=NULL, source:?string=NULL

## `<x-dl::field.display>`
Tampilan nilai read-only (bukan kotak isian): label + nilai. format: text|money|number|date|mono.
- Berkas: `src/Components/Field/Display.php`, tampilan `resources/views/components/field/display.blade.php`
- Prop: format:string='text', name:string='', label:string='', tooltip:?string=NULL, hint:?string=NULL, error:?string=NULL, value:mixed=NULL, placeholder:?string=NULL, required:bool=false, disabled:bool=false, readonly:bool=false, icon:?string=NULL, size:string='sm', i18n:?string=NULL, source:?string=NULL

## `<x-dl::field.key-value>`
Daftar pasangan nama - nilai (mis. spesifikasi barang) + tambah/hapus baris.
- Berkas: `src/Components/Field/KeyValue.php`, tampilan `resources/views/components/field/key-value.blade.php`
- Prop: keyLabel:string='Name', valueLabel:string='Value', name:string='', label:string='', tooltip:?string=NULL, hint:?string=NULL, error:?string=NULL, value:mixed=NULL, placeholder:?string=NULL, required:bool=false, disabled:bool=false, readonly:bool=false, icon:?string=NULL, size:string='sm', i18n:?string=NULL, source:?string=NULL

## `<x-dl::field.lookup>`
Pilih dari data master (bukan ketik bebas) + isi otomatis field lain.
- Berkas: `src/Components/Field/Lookup.php`, tampilan `resources/views/components/field/lookup.blade.php`
- Prop: lookup:string='', fill:array=[], create:bool=false, params:array=[], name:string='', label:string='', tooltip:?string=NULL, hint:?string=NULL, error:?string=NULL, value:mixed=NULL, placeholder:?string=NULL, required:bool=false, disabled:bool=false, readonly:bool=false, icon:?string=NULL, size:string='sm', i18n:?string=NULL, source:?string=NULL

## `<x-dl::field.masked>`
Input bermask angka, mis. NIK 6.6.4 digit.
- Berkas: `src/Components/Field/Masked.php`, tampilan `resources/views/components/field/masked.blade.php`
- Prop: mask:string='999999.999999.9999', invalidText:?string=NULL, name:string='', label:string='', tooltip:?string=NULL, hint:?string=NULL, error:?string=NULL, value:mixed=NULL, placeholder:?string=NULL, required:bool=false, disabled:bool=false, readonly:bool=false, icon:?string=NULL, size:string='sm', i18n:?string=NULL, source:?string=NULL

## `<x-dl::field.money>`
Nominal uang + pilihan mata uang. words = tampilkan terbilang di bawahnya, mengikuti mata uang terpilih.
- Berkas: `src/Components/Field/Money.php`, tampilan `resources/views/components/field/money.blade.php`
- Prop: currencies:array=[], currency:string='IDR', words:bool=false, decimals:int=2, toggle:bool=true, name:string='', label:string='', tooltip:?string=NULL, hint:?string=NULL, error:?string=NULL, value:mixed=NULL, placeholder:?string=NULL, required:bool=false, disabled:bool=false, readonly:bool=false, icon:?string=NULL, size:string='sm', i18n:?string=NULL, source:?string=NULL

## `<x-dl::field.multi-select>`
Dropdown pilihan ganda bertanda centang.
- Berkas: `src/Components/Field/MultiSelect.php`, tampilan `resources/views/components/field/multi-select.blade.php`
- Prop: options:array=[], emptyText:string='Choose (0 Selected)', noun:string='Selected', name:string='', label:string='', tooltip:?string=NULL, hint:?string=NULL, error:?string=NULL, value:mixed=NULL, placeholder:?string=NULL, required:bool=false, disabled:bool=false, readonly:bool=false, icon:?string=NULL, size:string='sm', i18n:?string=NULL, source:?string=NULL

## `<x-dl::field.number>`
Angka bulat + tombol naik/turun.
- Berkas: `src/Components/Field/Number.php`, tampilan `resources/views/components/field/number.blade.php`
- Prop: min:int=0, max:?int=NULL, step:int=1, name:string='', label:string='', tooltip:?string=NULL, hint:?string=NULL, error:?string=NULL, value:mixed=NULL, placeholder:?string=NULL, required:bool=false, disabled:bool=false, readonly:bool=false, icon:?string=NULL, size:string='sm', i18n:?string=NULL, source:?string=NULL

## `<x-dl::field.number-words>`
Angka + terbilang langsung saat diketik (Devlib\Terbilang, ikut bahasa user).
- Berkas: `src/Components/Field/NumberWords.php`, tampilan `resources/views/components/field/number-words.blade.php`
- Prop: currency:?string=NULL, picker:bool=false, decimals:int=0, name:string='', label:string='', tooltip:?string=NULL, hint:?string=NULL, error:?string=NULL, value:mixed=NULL, placeholder:?string=NULL, required:bool=false, disabled:bool=false, readonly:bool=false, icon:?string=NULL, size:string='sm', i18n:?string=NULL, source:?string=NULL

## `<x-dl::field.otp>`
Kode OTP per digit, fokus pindah otomatis.
- Berkas: `src/Components/Field/Otp.php`, tampilan `resources/views/components/field/otp.blade.php`
- Prop: length:int=6, name:string='', label:string='', tooltip:?string=NULL, hint:?string=NULL, error:?string=NULL, value:mixed=NULL, placeholder:?string=NULL, required:bool=false, disabled:bool=false, readonly:bool=false, icon:?string=NULL, size:string='sm', i18n:?string=NULL, source:?string=NULL

## `<x-dl::field.password>`
Password dengan tombol lihat/sembunyi.
- Berkas: `src/Components/Field/Password.php`, tampilan `resources/views/components/field/password.blade.php`
- Prop: autocomplete:string='current-password', name:string='', label:string='', tooltip:?string=NULL, hint:?string=NULL, error:?string=NULL, value:mixed=NULL, placeholder:?string=NULL, required:bool=false, disabled:bool=false, readonly:bool=false, icon:?string=NULL, size:string='sm', i18n:?string=NULL, source:?string=NULL

## `<x-dl::field.phone>`
Telepon per negara (Devlib\PhoneFormat): mask otomatis, buang 0 / +kode di depan, cek pola & panjang.
- Berkas: `src/Components/Field/Phone.php`, tampilan `resources/views/components/field/phone.blade.php`
- Prop: countries:array=[], country:string='ID', kind:string='mobile', name:string='', label:string='', tooltip:?string=NULL, hint:?string=NULL, error:?string=NULL, value:mixed=NULL, placeholder:?string=NULL, required:bool=false, disabled:bool=false, readonly:bool=false, icon:?string=NULL, size:string='sm', i18n:?string=NULL, source:?string=NULL

## `<x-dl::field.radio>`
Pilihan tunggal (radio).
- Berkas: `src/Components/Field/Radio.php`, tampilan `resources/views/components/field/radio.blade.php`
- Prop: options:array=[], name:string='', label:string='', tooltip:?string=NULL, hint:?string=NULL, error:?string=NULL, value:mixed=NULL, placeholder:?string=NULL, required:bool=false, disabled:bool=false, readonly:bool=false, icon:?string=NULL, size:string='sm', i18n:?string=NULL, source:?string=NULL

## `<x-dl::field.rating>`
Rating bintang. half = boleh setengah bintang (klik sisi kiri bintang). readonly = tampil saja.
- Berkas: `src/Components/Field/Rating.php`, tampilan `resources/views/components/field/rating.blade.php`
- Prop: max:int=5, half:bool=false, name:string='', label:string='', tooltip:?string=NULL, hint:?string=NULL, error:?string=NULL, value:mixed=NULL, placeholder:?string=NULL, required:bool=false, disabled:bool=false, readonly:bool=false, icon:?string=NULL, size:string='sm', i18n:?string=NULL, source:?string=NULL

## `<x-dl::field.rich-text>`
Catatan berformat (tebal, miring, garis bawah, daftar). Disimpan sebagai HTML --.
- Berkas: `src/Components/Field/RichText.php`, tampilan `resources/views/components/field/rich-text.blade.php`
- Prop: rows:int=5, name:string='', label:string='', tooltip:?string=NULL, hint:?string=NULL, error:?string=NULL, value:mixed=NULL, placeholder:?string=NULL, required:bool=false, disabled:bool=false, readonly:bool=false, icon:?string=NULL, size:string='sm', i18n:?string=NULL, source:?string=NULL

## `<x-dl::field.select>`
Pilihan tunggal dengan kotak cari (mis. lokasi Kota, Provinsi, Negara).
- Berkas: `src/Components/Field/Select.php`, tampilan `resources/views/components/field/select.blade.php`
- Prop: options:array=[], searchPlaceholder:string='Type to search...', name:string='', label:string='', tooltip:?string=NULL, hint:?string=NULL, error:?string=NULL, value:mixed=NULL, placeholder:?string=NULL, required:bool=false, disabled:bool=false, readonly:bool=false, icon:?string=NULL, size:string='sm', i18n:?string=NULL, source:?string=NULL, add:array=[]

## `<x-dl::field.signature>`
Tanda tangan: kanvas mouse / pena / jari. Nilai = gambar PNG (data URL) di input tersembunyi.
- Berkas: `src/Components/Field/Signature.php`, tampilan `resources/views/components/field/signature.blade.php`
- Prop: height:int=170, name:string='', label:string='', tooltip:?string=NULL, hint:?string=NULL, error:?string=NULL, value:mixed=NULL, placeholder:?string=NULL, required:bool=false, disabled:bool=false, readonly:bool=false, icon:?string=NULL, size:string='sm', i18n:?string=NULL, source:?string=NULL

## `<x-dl::field.slider>`
Geser nilai. range = dua gagang (min-max; nilai kedua dikirim sebagai name_to / nameTo).
- Berkas: `src/Components/Field/Slider.php`, tampilan `resources/views/components/field/slider.blade.php`
- Prop: min:int|float=0, max:int|float=100, step:int|float=1, format:string='{v}', range:bool=false, valueTo:mixed=NULL, nameTo:?string=NULL, orientation:string='horizontal', height:string='180px', ticks:int=0, name:string='', label:string='', tooltip:?string=NULL, hint:?string=NULL, error:?string=NULL, value:mixed=NULL, placeholder:?string=NULL, required:bool=false, disabled:bool=false, readonly:bool=false, icon:?string=NULL, size:string='sm', i18n:?string=NULL, source:?string=NULL

## `<x-dl::field.tags>`
Isian label berganda: ketik lalu Enter/koma = jadi label; klik x untuk hapus.
- Berkas: `src/Components/Field/Tags.php`, tampilan `resources/views/components/field/tags.blade.php`
- Prop: suggestions:array=[], name:string='', label:string='', tooltip:?string=NULL, hint:?string=NULL, error:?string=NULL, value:mixed=NULL, placeholder:?string=NULL, required:bool=false, disabled:bool=false, readonly:bool=false, icon:?string=NULL, size:string='sm', i18n:?string=NULL, source:?string=NULL

## `<x-dl::field.text>`
Teks satu baris / banyak baris (auto-tinggi) + tombol huruf aa/Aa/AA.
- Berkas: `src/Components/Field/Text.php`, tampilan `resources/views/components/field/text.blade.php`
- Prop: multiline:bool=false, caseToggle:bool=true, type:string='text', autocomplete:?string=NULL, code:bool=false, prefix:?string=NULL, suffix:?string=NULL, maxlength:?int=NULL, counter:bool=false, name:string='', label:string='', tooltip:?string=NULL, hint:?string=NULL, error:?string=NULL, value:mixed=NULL, placeholder:?string=NULL, required:bool=false, disabled:bool=false, readonly:bool=false, icon:?string=NULL, size:string='sm', i18n:?string=NULL, source:?string=NULL

## `<x-dl::field.time>`
Jam dengan pemilih jam:menit.
- Berkas: `src/Components/Field/Time.php`, tampilan `resources/views/components/field/time.blade.php`
- Prop: name:string='', label:string='', tooltip:?string=NULL, hint:?string=NULL, error:?string=NULL, value:mixed=NULL, placeholder:?string=NULL, required:bool=false, disabled:bool=false, readonly:bool=false, icon:?string=NULL, size:string='sm', i18n:?string=NULL, source:?string=NULL

## `<x-dl::field.toggle>`
Saklar aktif/nonaktif.
- Berkas: `src/Components/Field/Toggle.php`, tampilan `resources/views/components/field/toggle.blade.php`
- Prop: onLabel:string='Active', offLabel:string='Non-Active', name:string='', label:string='', tooltip:?string=NULL, hint:?string=NULL, error:?string=NULL, value:mixed=NULL, placeholder:?string=NULL, required:bool=false, disabled:bool=false, readonly:bool=false, icon:?string=NULL, size:string='sm', i18n:?string=NULL, source:?string=NULL

## `<x-dl::field.toggle-group>`
Pilihan tombol berderet (segmented), satu yang aktif. Mis. Harian / Mingguan / Bulanan.
- Berkas: `src/Components/Field/ToggleGroup.php`, tampilan `resources/views/components/field/toggle-group.blade.php`
- Prop: options:array=[], name:string='', label:string='', tooltip:?string=NULL, hint:?string=NULL, error:?string=NULL, value:mixed=NULL, placeholder:?string=NULL, required:bool=false, disabled:bool=false, readonly:bool=false, icon:?string=NULL, size:string='sm', i18n:?string=NULL, source:?string=NULL

## `<x-dl::field.upload>`
Unggah file (tarik-lepas / pilih) + pratinjau nama file.
- Berkas: `src/Components/Field/Upload.php`, tampilan `resources/views/components/field/upload.blade.php`
- Prop: accept:?string=NULL, multiple:bool=false, preview:bool=true, name:string='', label:string='', tooltip:?string=NULL, hint:?string=NULL, error:?string=NULL, value:mixed=NULL, placeholder:?string=NULL, required:bool=false, disabled:bool=false, readonly:bool=false, icon:?string=NULL, size:string='sm', i18n:?string=NULL, source:?string=NULL

## `<x-dl::flow>`
Diagram alur proses berupa blok bersambung (gaya pipeline approval), untuk menjelaskan alur antar modul/dokumen.
- Berkas: `src/Components/Flow.php`
- Prop: steps:array=[], title:string='', vertical:bool=false, source:?string=NULL

## `<x-dl::forgot-box>`
Isi kotak Lupa Password. mode="email" (mockup forgot-password.html): isi email -> pesan selalu sama.
- Berkas: `src/Components/ForgotBox.php`, tampilan `resources/views/components/forgot-box.blade.php`
- Prop: mode:string='email', action:string='', loginUrl:string='#', question:string='What was the name of you…'

## `<x-dl::form>`
Induk form (mockup UIUX_components_part1.html): judul + isi (kolom/baris field) + kaki berisi tombol tambahan opsional dan tombol split aksi.
- Berkas: `src/Components/Form.php`
- Prop: title:string='', action:?string=NULL, method:string='POST', actions:array=[…], extraAction:?string=NULL, extraIcon:string='📱', footer:bool=true, guard:bool=true, mode:string='edit', panel:?string=NULL, back:?string=NULL, newUrl:?string=NULL, kind:string='master', status:string='active', tabs:array=[], createdBy:?string=NULL, createdAt:?string=NULL, updatedBy:?string=NULL, updatedAt:?string=NULL, canReopen:bool=false, canInput:bool=true, subtitle:?string=NULL, pipeline:?bool=NULL, log:array=[], approvalLevels:int=0, approvalAt:int=0, canApprove:bool=false, actionField:string='_action', hide:array=[], hidden:array=[], stage:?string=NULL, printUrl:?string=NULL, strict:bool=false, deletable:bool=false

## `<x-dl::form.col>`
Satu kolom form: field disusun ke bawah.
- Berkas: `src/Components/Form/Col.php`
- Prop: (tanpa prop)

## `<x-dl::form.row>`
Satu baris form: field berdampingan (default 2 sama lebar).
- Berkas: `src/Components/Form/Row.php`
- Prop: cols:int=2

## `<x-dl::frame>`
bingkai layar penuh (gambar latar + widget + kotak isi) untuk HP/tablet/laptop/desktop di resolusi apa pun.
- Berkas: `src/Components/Frame.php`, tampilan `resources/views/components/frame.blade.php`
- Prop: bg:string='devlib', bgOrder:string='random', bgInterval:int=0, overlay:string='dark', box:string='right', boxStyle:?string=NULL, color:string='light', boxWidth:?string=NULL, resizable:bool=false, widgets:string='', quotes:string='motivasi.txt', quoteOrder:string='random', quoteInterval:int=0, announce:string='pengumuman.txt', city:string='Surabaya', lat:float=-7.2575, lon:float=112.7521, brand:?string=NULL, tagline:?string=NULL, logo:?string=NULL, imageList:?array=NULL, quoteList:?array=NULL

## `<x-dl::gantt>`
Papan Gantt resource x waktu; mode linked = dropdown Process lalu Machine (hanya mesin mampu), sumbu dipas ke bar.
- Berkas: `src/Components/Gantt.php`
- Prop: rows:array=[], tasks:array=[], categories:array=[], start:int=8, end:int=20, pxPerHour:int=70, snap:int=15, idleHours:float=2.0, unscheduled:int=0, simulation:bool=true, submitLabel:?string='Submit to Master Schedul…', resourceLabel:string='Machine / Crew', source:?string=NULL, originDate:?string=NULL, lockUntil:string|int|float|null=NULL, nowAt:?float=NULL, impactUrl:?string=NULL, moveUrl:?string=NULL, draftLabel:?string=NULL, reasonRequired:bool=false, moveLabel:?string=NULL, bulkLabel:?string=NULL, bulkUrl:?string=NULL, emptyText:?string=NULL, modeSwitch:bool=true, extras:bool=true, linked:?array=NULL

## `<x-dl::gauge>`
Gauge setengah lingkaran: zona warna dari bands [[batas atas, tone]] + jarum + nilai.
- Berkas: `src/Components/Gauge.php`
- Prop: value:float=0.0, min:float=0.0, max:float=100.0, bands:array=[…], label:string='', unit:string='%', target:?float=NULL

## `<x-dl::geo-clock>`
Absen berbasis lokasi (GPS geofencing, dipakai ponsel): membaca posisi perangkat, menanyakan jarak ke lokasi kerja ke server.
- Berkas: `src/Components/GeoClock.php`
- Prop: checkUrl:string='', clockUrl:string='', radius:int=200, history:array=[], title:string='', subtitle:string='', source:?string=NULL

## `<x-dl::greeting>`
Salam sesuai waktu: Selamat pagi / siang / sore / malam (ikut bahasa aktif).
- Berkas: `src/Components/Greeting.php`
- Prop: name:string='', timezone:?string=NULL

## `<x-dl::greeting-card>`
Widget sapaan berdiri sendiri (user 2026-10-07, tampilan disetujui): salam + jabatan + ringkasan hari ini,.
- Berkas: `src/Components/GreetingCard.php`, tampilan `resources/views/components/greeting-card.blade.php`
- Prop: board:array=[], source:?string=NULL

## `<x-dl::grid>`
Grid induk (griduistandard + perilaku tabel Filament/PowerGrid yang dipilih user).
- Berkas: `src/Components/Grid.php`, tampilan `resources/views/components/grid.blade.php`
- Prop: id:string='grid', columns:array=[], rows:array=[], rowKey:string='id', stats:array=[], tabs:array=[], filters:array=[], groups:array=[], bulk:array=[], expand:array=[], actions:string='master', activeKey:?string='active', newRecord:?string='+ New Record', perPage:?int=NULL, detail:array=[], cards:array=[], phoneCards:array=[], quickAdd:array=[], groupBy:?string=NULL, search:string='Search...', source:?string=NULL, openUrl:?string=NULL, newUrl:?string=NULL, newModal:?string=NULL, toggleUrl:?string=NULL, mode:string='entry', rowTone:?string=NULL, kind:?string=NULL, restore:bool=false

## `<x-dl::headline>`
Judul besar nama dokumen / produk di dalam isi form (22px tebal), dengan sub-teks kecil opsional.
- Berkas: `src/Components/Headline.php`
- Prop: (tanpa prop)

## `<x-dl::help-map>`
Peta bantuan = pohon Modul > Grup > Layar di kiri, detail panduan layar terpilih di kanan, dengan filter
- Berkas: `src/Components/HelpMap.php`
- Prop: tree:array=[], topics:array=[], current:?string=NULL, copyUrl:string='', requestTab:string='tickets'

## `<x-dl::hint>`
Teks petunjuk satu paragraf (penjelasan kecil di bawah judul / field).
- Berkas: `src/Components/Hint.php`
- Prop: note:bool=false, as:string='p', italic:bool=false

## `<x-dl::icon>`
Ikon baku: sebut ARTINYA (save, delete, print, ...), bukan nama Font Awesome.
- Berkas: `src/Components/Icon.php`
- Prop: name:string='info', label:?string=NULL

## `<x-dl::icon-button>`
Tombol ikon persegi (kolom Aksi grid, toolbar). icon = arti baku (edit, delete, open, ...).
- Berkas: `src/Components/IconButton.php`
- Prop: icon:string='edit', title:string='', variant:string='default', href:?string=NULL, confirm:?string=NULL

## `<x-dl::idle-logout>`
Logout otomatis setelah tidak ada aktivitas: hitung mundur di browser, peringatan dengan tombol Tetap masuk, lalu logout dan pindah ke halaman login.
- Berkas: `src/Components/IdleLogout.php`
- Prop: minutes:float=30.0, warn:float=2.0, logoutUrl:string='', pingUrl:string='', pingEvery:float=5.0, loginUrl:string='', preview:bool=false

## `<x-dl::import>`
Import data dari CSV (perilaku ImportAction Filament): 1 Upload (+ template) -> 2 Cocokkan kolom.
- Berkas: `src/Components/Import.php`
- Prop: title:string='Import Data', columns:array=[], template:string='template-import', source:?string=NULL

## `<x-dl::issue-modal>`
Popup peringatan yang harus terlihat: judul + penjelasan + tabel masalah (WO, proses, alasan), terbuka saat halaman dimuat.
- Berkas: `src/Components/IssueModal.php`
- Prop: id:string='issues', title:?string=NULL, size:string='lg', open:bool=true, icon:?string='fa-triangle-exclamation', tone:?string='danger', message:?string=NULL, columns:array=[], rows:array=[]

## `<x-dl::kanban>`
Papan Kanban (mockup_UIUX_kanban, kanbanboardstandard). Layar cukup deklarasi kolom + kartu.
- Berkas: `src/Components/Kanban.php`
- Prop: columns:array=[], cards:array=[], transitions:array=[], members:array=[], addColumn:bool=true, addCard:bool=true, me:string='Me', source:?string=NULL, moveUrl:?string=NULL, readonly:bool=false

## `<x-dl::kpi>`
Kartu KPI dashboard: label, nilai, perubahan vs periode lalu (▲▼, warna sesuai arah yang BAIK),.
- Berkas: `src/Components/Kpi.php`
- Prop: label:string='', value:string|int|float=0, format:string='number', previous:?float=NULL, goodWhen:string='up', spark:array=[], target:?float=NULL, hint:?string=NULL, icon:string='fa-chart-line', tone:string='accent', note:?string=NULL

## `<x-dl::kpi-row>`
Baris pembungkus kartu KPI (x-dl::kpi) di atas laporan / dashboard: satu objek, jarak dan kolom diatur satu tempat.
- Berkas: `src/Components/KpiRow.php`
- Prop: (tanpa prop)

## `<x-dl::label>`
Label tempel gudang. Tiga jenis yang SENGAJA tampak berbeda (kind): location (bin / rak), item (isi satu bin),.
- Berkas: `src/Components/Label.php`
- Prop: code:string='', title:?string=NULL, name:?string=NULL, lines:array=[], qr:?string=NULL, barcode:?string=NULL, size:string='md', tone:?string=NULL, footer:?string=NULL, kind:?string=NULL, color:?string=NULL, mark:?string=NULL, highlight:?string=NULL, tags:array=[]

## `<x-dl::label-guide>`
Panduan ukuran label + cara cetak (user 2026-10-02: "jelaskan ukuran label agar mudah di cetak"): tabel tiga ukuran.
- Berkas: `src/Components/LabelGuide.php`
- Prop: size:?string=NULL, colors:array=[], open:bool=false

## `<x-dl::label-sheet>`
Lembar cetak label: isi slot = deretan x-dl::label. Tombol Print mencetak labelnya saja (bilah dan layar lain tidak.
- Berkas: `src/Components/LabelSheet.php`
- Prop: title:string='Labels', back:?string=NULL, count:?int=NULL, size:string='md', paper:string='a4'

## `<x-dl::lang-switch>`
Pilihan bahasa (bendera). Daftar bahasa = Locale::languages() (aplikasi: sys_languages yang aktif), jadi bahasa baru.
- Berkas: `src/Components/LangSwitch.php`
- Prop: langs:array=[], active:?string=NULL, reload:?bool=NULL

## `<x-dl::lang-text>`
Teks antarmuka dari kamus Devlib\Lang (ID/EN); ikut berganti saat bahasa diganti tanpa muat ulang.
- Berkas: `src/Components/LangText.php`
- Prop: key:string=''

## `<x-dl::layer-menu>`
Menu panel kiri berlapis (layer 1 modul -> layer 2 menu -> layer 3 sub -> layer 4 ...), SEMUA di panel yang sama:.
- Berkas: `src/Components/LayerMenu.php`
- Prop: items:array=[], title:?string=NULL, logo:?string=NULL, tagline:?string=NULL, id:string='layer', active:?string=NULL, user:?string=NULL, role:?string=NULL, login:?string=NULL, counts:bool=true, rootCaption:string='Modules', dock:?string=NULL, actions:array=[], views:array=[], initialView:?string=NULL, search:bool=false, collapsible:bool=true, home:bool=false, foldGroups:string=''

## `<x-dl::layer-shell>`
Kerangka aplikasi setelah login: navigasi x-dl::layer-menu di kiri, TANPA top bar; tab MDI, Ctrl+K, laci, modal. Satu-satunya menu utama.
- Berkas: `src/Components/LayerShell.php`, tampilan `resources/views/components/layer-shell.blade.php`
- Prop: modules:array=[], user:array=[…], brand:?string=NULL, tagline:?string=NULL, logo:?string=NULL, logoutAction:string='', menuSource:string='sys_app_menu', chat:array=[], open:?string=NULL, helpUrl:?string=NULL, profilePage:?string=NULL, assistant:array=[], account:array=[], fresh:bool=false, drawers:array=[], without:array=[], deviceAction:?string=NULL

## `<x-dl::lines>`
Tabel baris item dokumen (detail dari Master-Detail: PO, PR, CO, Stock In, ...).
- Berkas: `src/Components/Lines.php`
- Prop: name:string='items', columns:array=[], rows:array=[], title:string='Item', addLabel:string='+ Add Row', minRows:int=1, totals:array=[], totalKey:?string=NULL, countKey:?string=NULL, readonly:bool=false, source:?string=NULL, keyField:?string=NULL, removeField:?string=NULL, addable:bool=true, duplicable:bool=true, rowLock:?string=NULL, rowState:bool=false

## `<x-dl::lock-screen>`
Layar kunci: menutup aplikasi setelah tidak ada aktivitas `idle` menit (0 = hanya manual), atau lewat tombol.
- Berkas: `src/Components/LockScreen.php`
- Prop: user:array=[…], idle:int=0, logoutAction:string='', id:string='lock'

## `<x-dl::login-box>`
Isi kotak login. Cukup SEBUT bagian yang tampil, berurutan, dipisah koma:.
- Berkas: `src/Components/LoginBox.php`, tampilan `resources/views/components/login-box.blade.php`
- Prop: show:string='lang, title, username, p…', action:string='', button:string='Sign in', error:?string=NULL, username:string='', forgotUrl:string='#', registerUrl:string='#', brand:?string=NULL, tagline:?string=NULL, uatAccounts:?array=NULL

## `<x-dl::login-card>`
Kartu login karyawan baru yang bisa dicetak: nama, kode, username, password awal. Dipakai di dalam x-dl::modal; tombol Print hanya mencetak kartunya.
- Berkas: `src/Components/LoginCard.php`
- Prop: name:string='', code:string='', login:string='', password:string='', title:string='Login card', note:?string=NULL

## `<x-dl::lookup-grid>`
Grid lookup kecil = Sheet mode lookup; meneruskan saveUrl, reorderUrl, reasonOnSave, links, autoSave, addLabel, deleteUrl/deleteLabel, resetUrl/resetLabel, searchable, showCount.
- Berkas: `src/Components/LookupGrid.php`
- Prop: columns:array=[], rows:array=[], rowKey:string='id', title:string='', source:?string=NULL, saveUrl:?string=NULL, stats:array=[], addRows:bool=true, reorderUrl:?string=NULL, reasonOnSave:?string=NULL, links:array=[], autoSave:bool=false, addLabel:?string=NULL, deleteUrl:?string=NULL, searchable:bool=true, showCount:bool=true, deleteLabel:?string=NULL, resetUrl:?string=NULL, resetLabel:?string=NULL

## `<x-dl::media>`
Gambar atau frame dokumen (img / iframe) sebagai objek, supaya layar tidak menulis tag media mentah.
- Berkas: `src/Components/Media.php`
- Prop: src:string='', alt:string='', as:string='img'

## `<x-dl::mega-menu>`
Menu atas ala ScriptCase: bilah berwarna berisi item; item bisa tautan, dropdown biasa (bisa bertingkat), atau.
- Berkas: `src/Components/MegaMenu.php`
- Prop: items:array=[], brand:string='', tone:string='green', active:?string=NULL

## `<x-dl::member-list>`
Daftar peserta ber-batas (maks N baris): nomor, nama, peran, tombol Remove (dengan konfirmasi); pilih dari daftar lalu Add; penghitung "n of N filled" + bar.
- Berkas: `src/Components/MemberList.php`
- Prop: name:string='members', rows:array=[], options:array=[], roles:array=[], max:int=10, title:string=''

## `<x-dl::menu-button>`
Tombol + daftar pilihan (dropdown) -- mis. Print ▾ / Email ▾ bila satu form punya lebih dari satu dokumen cetak.
- Berkas: `src/Components/MenuButton.php`
- Prop: items:array=[], preset:?string=NULL, label:string='', icon:?string=NULL, variant:string='secondary', size:string='md'

## `<x-dl::meta>`
Info rekaman: dibuat/diubah oleh siapa & kapan (WIB), asal data.
- Berkas: `src/Components/Meta.php`
- Prop: createdBy:?string=NULL, createdAt:mixed=NULL, updatedBy:?string=NULL, updatedAt:mixed=NULL, origin:?string=NULL

## `<x-dl::modal>`
Jendela modal. Dibuka tombol ber-atribut data-dl-open="<id>" atau Devlib.modal.open(id). Slot "footer" = tombol bawah.
- Berkas: `src/Components/Modal.php`
- Prop: id:string='modal', title:?string=NULL, size:string='md', open:bool=false, icon:?string=NULL, tone:?string=NULL

## `<x-dl::notification-list>`
Daftar notifikasi (mockup_notification): ikon berwarna, judul, titik belum dibaca, keterangan, waktu relatif.
- Berkas: `src/Components/NotificationList.php`
- Prop: items:array=[], title:string='All Notifications', markAll:bool=true

## `<x-dl::org-chart>`
Struktur organisasi (Organization-Structure-Mockup + org chart aplikasi): pohon bergaris, kartu jabatan.
- Berkas: `src/Components/OrgChart.php`
- Prop: nodes:array=[], editable:bool=true, title:string='Organization Structure', source:?string=NULL, departments:array=[]

## `<x-dl::pagination>`
Kaki halaman ala ScriptCase: Go to + baris/halaman | navigasi | info total.
- Berkas: `src/Components/Pagination.php`
- Prop: total:int=0, perPage:int=25, page:int=1, sizes:array=[…]

## `<x-dl::permission-matrix>`
Hak akses grup per objek (mockup security.html "Hak Akses Grup"): pilih grup -> tiap objek Form/Grid diberi.
- Berkas: `src/Components/PermissionMatrix.php`
- Prop: objects:array=[], groups:array=[], perms:array=[], levels:array=[…], default:int=1, source:?string=NULL, saveUrl:?string=NULL

## `<x-dl::phone-bar>`
Bilah tombol yang menempel di bawah area isi layar HP (di atas bilah tab): dua atau tiga tombol besar sebaris, selalu terlihat saat isi digulir.
- Berkas: `src/Components/PhoneBar.php`
- Prop: columns:string=''

## `<x-dl::phone-link>`
Baris kartu besar layar HP: ikon, nama, lencana kecil; seluruh baris adalah tautan (tinggi >= 64 px). Untuk daftar modul / menu pada layar HP.
- Berkas: `src/Components/PhoneLink.php`
- Prop: label:string='', icon:string='fa-solid fa-circle', url:string='#', badge:?string=NULL, tone:string=''

## `<x-dl::phone-login>`
Halaman login smartphone potret: satu kolom mengalir (320 sampai sekitar 440 px), isian 52 px dan teks 16 px, tombol Masuk besar, tepi aman, dan tautan kecil kembali ke tampilan desktop.
- Berkas: `src/Components/PhoneLogin.php`, tampilan `resources/views/components/phone-login.blade.php`
- Prop: action:string='', error:?string=NULL, username:string='', forgotUrl:string='#', brand:?string=NULL, tagline:?string=NULL, show:string='brand, title, username, …', deviceAction:?string=NULL, contained:bool=false

## `<x-dl::phone-offer>`
Popup kecil di layar desktop yang sempit: "Ingin tampilan yang lebih nyaman di smartphone? Ketuk di sini" dan tombol kecil "Tetap desktop"; keduanya menyimpan pilihan tampilan (cookie ui) lewat POST.
- Berkas: `src/Components/PhoneOffer.php`
- Prop: action:string='', force:bool=false

## `<x-dl::phone-shell>`
Kerangka aplikasi untuk smartphone potret: bilah atas kecil (satu tombol hamburger, tombol kembali, judul), isi selebar layar penuh, dan panel Menu dari atas (menu utama besar + semua modul).
- Berkas: `src/Components/PhoneShell.php`, tampilan `resources/views/components/phone-shell.blade.php`
- Prop: title:string='', back:?string=NULL, tabs:array=[], active:string='', modules:array=[], user:array=[…], brand:?string=NULL, logoutAction:string='', deviceAction:?string=NULL, links:array=[], frame:?string=NULL, contained:bool=false

## `<x-dl::phone-tile>`
Kartu ringkasan layar HP: ikon, angka besar, dan nama; seluruh kartu adalah tautan (sasaran sentuh besar). Dua kartu per baris di dalam x-dl::phone-tiles.
- Berkas: `src/Components/PhoneTile.php`
- Prop: label:string='', value:string|int|float|null=NULL, icon:string='fa-solid fa-circle', url:string='#', tone:string=''

## `<x-dl::phone-tiles>`
Pembungkus grid dua kolom untuk x-dl::phone-tile di layar HP.
- Berkas: `src/Components/PhoneTiles.php`
- Prop: (tanpa prop)

## `<x-dl::photo-box>`
Kotak pas foto 3 x 4 yang bisa diklik: membuka dialog crop (bingkai tetap 3:4, geser dan zoom) lalu menaruh hasil JPEG 600 x 800 px ke input file milik form.
- Berkas: `src/Components/PhotoBox.php`
- Prop: name:string='photo', photo:?string=NULL, label:string='', caption:?string=NULL, outW:int=600, outH:int=800, size:string='md'

## `<x-dl::pick-matrix>`
Matriks pilih pemenang: baris = kebutuhan (mis. baris PR), kolom = penawar (mis. supplier), tiap sel = satu radio.
- Berkas: `src/Components/PickMatrix.php`
- Prop: name:string='pick', columns:array=[], rows:array=[], empty:string='There are no quotations …', unit:string='PO', source:?string=NULL

## `<x-dl::pivot>`
Grid Pivot: data datar -> tabel silang yang bisa diatur user di layar.
- Berkas: `src/Components/Pivot.php`
- Prop: rows:array=[], fields:array=[], measures:array=[], rowsBy:array=[], colBy:?string=NULL, value:?string=NULL, agg:string='sum', formats:array=[], title:string='', source:?string=NULL

## `<x-dl::print-doc>`
Cetakan dokumen transaksi (user 2026-10-01): A5 landscape untuk 1-5 baris, A4 portrait di atas itu, satu tata letak.
- Berkas: `src/Components/PrintDoc.php`, tampilan `resources/views/components/print-doc.blade.php`
- Prop: doc:Devlib\PrintDoc\Document=(wajib)

## `<x-dl::print-out>`
Dokumen cetak (nota_co_mockup, nota_pv_spb, printnotastandard). Layar cukup deklarasi isi dokumen.
- Berkas: `src/Components/PrintOut.php`, tampilan `resources/views/components/print-out.blade.php`
- Prop: title:string='', subtitle:?string=NULL, number:?string=NULL, date:?string=NULL, status:?string=NULL, company:array=[], companyPpn:array=[], ppn:bool=false, parties:array=[], columns:array=[], rows:array=[], totals:array=[], currency:string='IDR', signatures:array=[], paper:string='a4', prices:bool=true, printedBy:?string=NULL, criteria:array=[], summary:array=[], groups:array=[], groupBy:?string=NULL, opening:?float=NULL, source:?string=NULL

## `<x-dl::print-sheet>`
Cetakan lembar berbagian (laporan cetak / formulir lapangan): kop perusahaan, judul + kode formulir, baris info, bagian bernomor berisi tabel / kotak pihak / catatan, tanda tangan, kaki. Melengkapi x-dl::print-doc (dokumen transaksi satu tabel).
- Berkas: `src/Components/PrintSheet.php`, tampilan `resources/views/components/print-sheet.blade.php`
- Prop: title:string='', code:?string=NULL, info:array=[], sections:array=[], signatures:array=[], note:?string=NULL, landscape:bool=false, footer:?string=NULL

## `<x-dl::profile-banner>`
Banner profil orang / objek: foto di kiri, nama besar + baris keterangan + chip berkelompok (berlabel) + batang kelengkapan di sampingnya, tile KPI selebar penuh di bawahnya, lalu satu baris pemberitahuan.
- Berkas: `src/Components/ProfileBanner.php`
- Prop: name:string='', subtitle:?string=NULL, groups:array=[], progress:?float=NULL, progressText:?string=NULL

## `<x-dl::progress>`
Batang kemajuan; tone otomatis dari nilai bila tone="auto" (>=80 hijau, >=50 kuning, sisanya merah).
- Berkas: `src/Components/Progress.php`
- Prop: value:float=0.0, tone:string='accent', label:?string=NULL, text:?string=NULL

## `<x-dl::progress-ring>`
Cincin progres (persentase tercapai), warna otomatis dari ambang atau tone tetap.
- Berkas: `src/Components/ProgressRing.php`
- Prop: value:float=0.0, label:string='', tone:?string=NULL, size:int=96, caption:?string=NULL

## `<x-dl::qc-bar>`
Bilah QC cepat di tepi bawah halaman: status tahap ini + dua tombol, Approved (satu klik, langsung tersimpan beserta.
- Berkas: `src/Components/QcBar.php`
- Prop: key:string='', name:string='', stage:string='QC', status:?string=NULL, note:?string=NULL, by:?string=NULL, at:?string=NULL, action:?string=NULL, csrf:?string=NULL, canReview:bool=true, chain:array=[], release:bool=false

## `<x-dl::qr>`
Kode QR (qrcodejs, dimuat sekali dari cdnjs saat dibutuhkan). Isi = teks/URL/ID.
- Berkas: `src/Components/Qr.php`
- Prop: text:string='', size:int=120, caption:?string=NULL

## `<x-dl::queue-board>`
Antrean per proses per mesin: dropdown Process bertaut dropdown Machine, daftar item hanya-baca dengan status, progres persen, start, end, dan jeda (idle) sebelum job beserta alasannya.
- Berkas: `src/Components/QueueBoard.php`
- Prop: processes:array=[], machines:array=[], rows:array=[], process:?string=NULL, machine:string='', empty:string='Nothing is queued on thi…', suggested:bool=false

## `<x-dl::quote>`
Kata motivasi dari daftar (biasanya dari file teks lewat x-dl::frame quotes="...").
- Berkas: `src/Components/Quote.php`
- Prop: quotes:array=[], order:string='random', interval:int=0

## `<x-dl::rate-list>`
Daftar hasil kerja yang dinilai: satu baris per item (form / grid / report / engine / objek / fitur) dengan pembuat,.
- Berkas: `src/Components/RateList.php`
- Prop: items:array=[], types:array=[], qc:array=[], agents:array=[], action:?string=NULL, csrf:?string=NULL, canRate:bool=true, only:?string=NULL, source:?string=NULL

## `<x-dl::register-box>`
Isi kotak Daftar Akun (mockup register.html), dipasang di x-dl::frame seperti login.
- Berkas: `src/Components/RegisterBox.php`, tampilan `resources/views/components/register-box.blade.php`
- Prop: action:string='', loginUrl:string='#', types:array=[…]

## `<x-dl::report>`
Laporan cetak (mockup_print_stock_card, mockup_print_master_stock): sama dengan x-dl::print-out.
- Berkas: `src/Components/Report.php`
- Prop: title:string='', subtitle:?string=NULL, number:?string=NULL, date:?string=NULL, status:?string=NULL, company:array=[], companyPpn:array=[], ppn:bool=false, parties:array=[], columns:array=[], rows:array=[], totals:array=[], currency:string='IDR', signatures:array=[], paper:string='a4', prices:bool=true, printedBy:?string=NULL, criteria:array=[], summary:array=[], groups:array=[], groupBy:?string=NULL, opening:?float=NULL, source:?string=NULL

## `<x-dl::review-feed>`
Umpan review gaya blog (user 2026-10-13): satu kartu per butir, huruf besar seperti WordPress, teks hitam, lalu tombol keputusan besar yang langsung tersimpan.
- Berkas: `src/Components/ReviewFeed.php`
- Prop: posts:array=[], empty:string='Nothing to review.', choices:array=[], choiceTones:array=[], filters:array=[], saveUrl:?string=NULL, csrf:?string=NULL, field:string='decision', source:?string=NULL, search:bool=true, quick:bool=true

## `<x-dl::ribbon>`
Ribbon ala Microsoft Excel: tab -> grup berlabel -> tombol besar (ikon di atas teks) / kecil (3 bertumpuk per kolom).
- Berkas: `src/Components/Ribbon.php`
- Prop: tabs:array=[], file:array=[], quick:array=[], title:string='', active:?string=NULL

## `<x-dl::scan>`
Kotak pindai QR / barcode. Scanner USB / Bluetooth bekerja seperti keyboard: kode masuk ke kotak lalu Enter. Di HP.
- Berkas: `src/Components/Scan.php`
- Prop: name:string='code', label:?string=NULL, placeholder:string='Scan or type a code, the…', value:?string=NULL, action:?string=NULL, hint:?string=NULL, autofocus:bool=true

## `<x-dl::sheet>`
Grid sel mirip spreadsheet; mendukung links (sel terhitung), reasonOnSave, reorderUrl.
- Berkas: `src/Components/Sheet.php`
- Prop: columns:array=[], rows:array=[], rowKey:string='id', title:string='', source:?string=NULL, lookup:bool=false, saveUrl:?string=NULL, stats:array=[], addRows:bool=true, reorderUrl:?string=NULL, reasonOnSave:?string=NULL, links:array=[], autoSave:bool=false, addLabel:?string=NULL, deleteUrl:?string=NULL, searchable:bool=true, showCount:bool=true, deleteLabel:?string=NULL, resetUrl:?string=NULL, resetLabel:?string=NULL

## `<x-dl::side-menu>`
Menu panel samping (kiri ATAU kanan). Mode lebar: akordeon bertingkat (grup -> sub -> item), cari (buka otomatis.
- Berkas: `src/Components/SideMenu.php`
- Prop: items:array=[], side:string='left', title:string='Menu', id:string='side', collapsed:bool=false, search:bool=true, favorites:bool=true, active:?string=NULL

## `<x-dl::side-screen>`
Side Screen (user 2026-10-04): jendela layar PENUH untuk layar akun yang "banyak aspek" (Help & support, Edit profile).
- Berkas: `src/Components/SideScreen.php`
- Prop: id:string='side', title:?string=NULL, subtitle:?string=NULL, icon:?string=NULL, url:?string=NULL, search:bool=true, width:string='1140px', height:string='680px'

## `<x-dl::side-screen.foot>`
Baris aksi LENGKET di dasar bagian Side Screen (user 2026-10-04): tombol form (Request Correction, Send ticket, Save).
- Berkas: `src/Components/SideScreen/Foot.php`
- Prop: hint:?string=NULL, align:string='start'

## `<x-dl::side-screen.layout>`
Isi Side Screen (user 2026-10-04): rel bagian VERTIKAL di kiri (menggantikan tab mendatar; 4 bagian Help / 11 bagian.
- Berkas: `src/Components/SideScreen/Layout.php`
- Prop: sections:array=[], active:?string=NULL, groups:array=[], hints:array=[], links:array=[], crumbs:array=[], param:string='tab'

## `<x-dl::skeleton>`
Kerangka abu-abu berdenyut selama data dimuat. type: text | table | card | form.
- Berkas: `src/Components/Skeleton.php`
- Prop: type:string='text', lines:int=3, rows:int=5, cols:int=4

## `<x-dl::spinner>`
Tanda sedang memproses. overlay=true menutupi induknya (mis. kartu/grid) selama proses.
- Berkas: `src/Components/Spinner.php`
- Prop: label:?string=NULL, size:string='md', overlay:bool=false

## `<x-dl::split>`
Tata letak dokumen: isi utama (lebar) + panel samping (sempit) mis. ringkasan & pajak, atau pohon kelompok di kiri.
- Berkas: `src/Components/Split.php`
- Prop: aside:string='320px', side:string='right'

## `<x-dl::split-button>`
Tombol utama + panah pilihan aksi (mockup form: Save / Download / Export). Aksi terpilih dikirim sebagai input _action.
- Berkas: `src/Components/SplitButton.php`
- Prop: actions:array=[…], name:string='_action'

## `<x-dl::stat-box>`
Kotak statistik grid; filter=true -> bisa diklik sebagai penyaring data (klik lagi = lepas).
- Berkas: `src/Components/StatBox.php`
- Prop: label:string='', value:string='0', tone:string='accent', sub:?string=NULL, filter:bool=false, active:bool=false

## `<x-dl::statement>`
Laporan keuangan baris demi baris (Neraca, Laba Rugi, Arus Kas): judul kelompok, baris biasa, subtotal, total akhir. Objek ini tidak tahu database:.
- Berkas: `src/Components/Statement.php`
- Prop: rows:array=[], columns:array=[…], title:string='', subtitle:string='', currency:string='IDR', source:?string=NULL

## `<x-dl::status-card>`
Kartu status (mesin, dokumen, layanan): ikon + judul + badge status + nilai + keterangan + waktu.
- Berkas: `src/Components/StatusCard.php`
- Prop: title:string='', status:string='', tone:string='accent', icon:string='info', value:?string=NULL, desc:?string=NULL, meta:?string=NULL, filter:bool=false, active:bool=false

## `<x-dl::strip-kv>`
Satu kotak "label kecil + nilai" di strip ringkasan Form dokumen (no. dokumen, total, status angka).
- Berkas: `src/Components/StripKv.php`
- Prop: label:string='', value:?string=NULL, kind:string='', valueAttrs:array=[]

## `<x-dl::suggestion-strip>`
Strip usulan machine learning di atas Gantt: lencana ML, usulan urutan (chip berurutan) + tombol Preview / Use / Dismiss, atau keadaan Learning n / 30 + bar kemajuan.
- Berkas: `src/Components/SuggestionStrip.php`
- Prop: mode:string='ready', title:string='', subtitle:string='', items:array=[], note:string='', value:float=0.0, needed:float=0.0, previewUrl:?string=NULL, useUrl:?string=NULL, dismissUrl:?string=NULL, previewing:bool=false

## `<x-dl::switch-case>`
Satu panel milik x-dl::switcher; tampil hanya bila pilihan dropdown sama dengan value-nya.
- Berkas: `src/Components/SwitchCase.php`
- Prop: value:string=''

## `<x-dl::switcher>`
Dropdown yang menukar panel di bawahnya tanpa memuat ulang (mis. peran / jabatan -> job description dan KPI jabatan itu).
- Berkas: `src/Components/Switcher.php`
- Prop: label:string='', options:array=[], value:?string=NULL

## `<x-dl::table>`
Tabel sederhana (di dalam form/kartu). columns: key => label, atau key => [label, num=>true]. Angka rata kanan.
- Berkas: `src/Components/Table.php`
- Prop: columns:array=[], rows:array=[], empty:string='No data yet.', striped:bool=true, totals:array=[], source:?string=NULL

## `<x-dl::tabs>`
Tab: judul di atas (vertical = daftar di kiri, seperti halaman Setup & Security), isi berganti.
- Berkas: `src/Components/Tabs.php`
- Prop: tabs:array=[], active:?string=NULL, vertical:bool=false

## `<x-dl::theme-boot>`
Dipasang SEKALI di <head>: CSS 12 tema (dari Devlib\Theme) + skrip kecil yang.
- Berkas: `src/Components/ThemeBoot.php`
- Prop: theme:?string=NULL

## `<x-dl::theme-picker>`
Pemilih tampilan: 12 tema (keluarga x corak) + density + skala huruf.
- Berkas: `src/Components/ThemePicker.php`
- Prop: (tanpa prop)

## `<x-dl::timer>`
Stopwatch display (hh:mm:ss). The server gives the seconds already elapsed when the page was made; the browser keeps counting while running.
- Berkas: `src/Components/Timer.php`
- Prop: seconds:int=0, running:bool=true, label:?string=NULL, tone:string='accent'

## `<x-dl::token-table>`
Tabel dokumentasi token Basic (DevLib): nama token, nilai terkini, contoh tampilan.
- Berkas: `src/Components/TokenTable.php`
- Prop: tokens:array=[], kind:string='color'

## `<x-dl::tooltip>`
Keterangan saat kursor diarahkan ke isi slot.
- Berkas: `src/Components/Tooltip.php`
- Prop: text:string='', position:string='top'

## `<x-dl::traffic-light>`
Lampu status (scorecard): green | amber | red (+ label & keterangan). Dipakai di baris KPI / scorecard.
- Berkas: `src/Components/TrafficLight.php`
- Prop: status:string='green', label:string='', note:?string=NULL

## `<x-dl::uat-accounts>`
Ikon support kecil; diklik -> popup daftar akun uji coba (UAT). HAPUS dari login sebelum rilis produksi.
- Berkas: `src/Components/UatAccounts.php`
- Prop: accounts:array=[…], password:string='contoh!23'

## `<x-dl::warehouse-desk>`
Form khusus Warehouse Map (user 2026-10-02, docs/warehouselayout_tier1.md): pemilih gedung + pintasan ke Buildings / Storage.
- Berkas: `src/Components/WarehouseDesk.php`, tampilan `resources/views/components/warehouse-desk.blade.php`
- Prop: desk:array=[]

## `<x-dl::weather>`
Cuaca terkini dari internet (Open-Meteo, gratis tanpa kunci API), diambil oleh browser.
- Berkas: `src/Components/Weather.php`
- Prop: city:string='Surabaya', lat:float=-7.2575, lon:float=112.7521

## `<x-dl::wh-map>`
Peta gudang (warehouse map): denah satu gedung per lantai -- garis luar gedung (kotak, huruf L, atau bentuk apa pun),.
- Berkas: `src/Components/WhMap.php`, tampilan `resources/views/components/wh-map.blade.php`
- Prop: id:string='whmap', title:?string=NULL, width:float=0.0, height:float=0.0, outline:array=[], floors:array=[], bins:array=[], actions:array=[], floor:?int=NULL, selected:string|int|null=NULL, search:string='Search bin, item, lot...', empty:string='Click a bin on the map t…', saveUrl:?string=NULL, restackUrl:?string=NULL, source:?string=NULL, layer:array=[]

## `<x-dl::widget-board>`
Papan widget (opening screen yang bisa diatur pemiliknya): kisi 12 kolom berisi widget dari katalog aplikasi.
- Berkas: `src/Components/WidgetBoard.php`, tampilan `resources/views/components/widget-board.blade.php`
- Prop: widgets:array=[], catalog:array=[], actionUrl:?string=NULL, editable:bool=true, editing:bool=false, title:?string=NULL, id:string='wboard', source:?string=NULL, periods:array=[], period:?string=NULL, dashboards:array=[], dashboard:?string=NULL

## `<x-dl::wizard>`
Form bertahap. Isi = beberapa x-dl::wizard.step. Next mengecek isian wajib langkah itu;.
- Berkas: `src/Components/Wizard.php`
- Prop: title:string='', action:?string=NULL, review:bool=true, finishLabel:string='Finish', done:?string=NULL

## `<x-dl::wizard-option>`
Satu baris pilihan di wizard dalam modal (emoji + judul + penjelasan + tombol aksi), mis. langkah jenis / sumber data.
- Berkas: `src/Components/WizardOption.php`
- Prop: emoji:string='', title:string='', description:string='', badge:?string=NULL, badgeTone:string='neutral', note:?string=NULL, kind:string='', off:bool=false

## `<x-dl::wizard-options>`
Pembungkus daftar pilihan wizard (x-dl::wizard-option) di dalam modal.
- Berkas: `src/Components/WizardOptions.php`
- Prop: (tanpa prop)

## `<x-dl::wizard.step>`
Satu langkah di dalam x-dl::wizard. icon = arti baku (Devlib\Icon) atau kelas fa-*.
- Berkas: `src/Components/Wizard/Step.php`
- Prop: title:string='', icon:?string=NULL, hint:?string=NULL

## `<x-dl::year-calendar>`
Kalender setahun (12 bulan sekaligus) untuk hari libur / hari kerja: tanggal bertanda diwarnai per tone,.
- Berkas: `src/Components/YearCalendar.php`
- Prop: year:int=(wajib), marks:array=[], legend:array=[], offDays:array=[…], newUrl:?string=NULL, source:?string=NULL

