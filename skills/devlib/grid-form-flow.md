# DevLib: alur Grid → Form → kembali ke Grid

Satu alur lengkap per entri: **grid** (daftar) dan **form** (satu record/dokumen) adalah DUA halaman dengan alamat sendiri. Form dibuka dari grid; satu-satunya jalan kembali ke grid asal adalah tombol **Back** (dan **Delete**, karena barisnya hilang). Melengkapi `form.md` (tombol, `:back`, gaya baca-saja), `grid.md` (jenis grid), `transaction-lifecycle.md` (status dokumen, baris detail 2 state). Bila ada selisih, tiga berkas itu yang menang; berkas ini hanya merinci *urutan layar*.

**Siklus ketat** (aturan pemilik, 2026-10-21) = `x-dl::form :strict="true"`. Opt-in: tanpa `:strict` form berperilaku lama (New, Email, Back to Draft, dst.), supaya proyek yang sudah jalan tidak berubah. Berkas ini menjelaskan siklus ketat.

Demo hidup di menu **Grid and Form** (`/lib/p5-flow-document`, `-single`, `-approval`, `-master`; Wizard `/lib/p5-form-wizard`).

## 0. Pohon menu (acuan penamaan)

```
Grid and Form
├─ Grid Report (tanpa form): List · Group By · Summary → Details · Pivot
├─ Grid Pre-Entry
│   ├─ Transactional: Master-Detail · Single · Approval   (grid → form)
│   ├─ Master                                             (grid → Master Form)
│   └─ Lookup                                             (isi langsung di sel, tanpa form)
└─ Form Wizard                                            (tanpa grid)
```

**Single Form = transaksional** (Draft/Confirmed/Cancelled, ada nomor dokumen). **Master Form = data master** (hanya Active/Inactive). Dua istilah ini tidak boleh tertukar. Contoh baku: Master-Detail = Customer Order (Rupiah, pajak, biaya lain) lalu Purchase Order; Single = Cash Voucher; Master = Pegawai.

## 1. Prinsip

1. Grid dan form tidak digabung dalam satu komponen yang bertukar tampilan. Berpindah layar = pindah alamat.
2. Form selalu tahu grid asalnya lewat `:back` (alamat grid LENGKAP dengan query-nya). Tidak pernah `history.back()` / `url()->previous()`.
3. **Back** adalah tombol sendiri, tidak berhubungan dengan Save: selalu kembali ke grid dengan **tab, filter, urutan, kolom, baris per halaman** (tersimpan per grid di peramban) dan, dengan `restore`, **kata cari, halaman, kotak statistik** dipulihkan. Tanpa sorot baris.
4. **Save dan Confirm tetap di form** (toast, status baru). Hanya Back dan Delete yang pindah ke grid.
5. Pesan hasil aksi muncul sebagai **toast di halaman tujuan** (dari server, satu kali).
6. Tombol datang dari `x-dl::form` menurut `kind` + `status` (+ `approval`); layar tidak menyusun tombol sendiri.
7. **Record baru** = Draft (dokumen) atau Active (master), isian TIDAK kosong: nilai bawaan terisi (tanggal hari ini, pihak/lokasi/satuan bawaan); kosong hanya isian yang harus dipilih pengguna.

## 2. Parameter yang dibawa

| Parameter | Arah | Isi | Aturan |
|---|---|---|---|
| `{id}` di path form | grid → form | nilai `rowKey` baris; `new` = record baru | `openUrl` = `…/form/{id}`; `newUrl` = `…/form/new` |
| `from` | grid → form | alamat grid asal (path + query, mis. `/lib/p5-flow-document?doc=po`) | hanya path lokal `/…` (bukan `//` atau `http`); tak sah/kosong → grid bawaan modul. Menjadi `:back` dan tujuan setelah Delete |
| `copy` | form lama → form baru | `id` yang disalin (Duplicate, hanya dokumen REJECTED) | form baru tanpa nomor |
| `status`, `approval`, `as` | form ↔ form | hanya demo: status/approval yang ditampilkan dan peran simulasi. Di aplikasi nyata dibaca dari record dan login | |
| cari/halaman/statistik | grid → grid | disimpan grid ber-`restore` di `sessionStorage` saat form dibuka, dipulihkan SEKALI saat kembali | bukan parameter URL |

## 3. Aksi form dan tujuannya (dokumen transaksional, Single dan Master-Detail)

Toast dari server; galat = toast merah dan form TETAP terbuka. Isian terkunci = baca-saja berwarna (lihat `form.md`).

| Keadaan | Tombol (kiri → kanan) | Catatan |
|---|---|---|
| Baru (Draft, belum tersimpan) | Undo · Save · Back | Save: pindah ke `…/form/{id}` (status Draft), toast |
| Draft tersimpan | Delete · Edit · Print · Confirm · Back | isian terkunci; **Edit** membuka isian (Save + Undo); **Undo** = isi tersimpan dan terkunci lagi |
| Confirmed | Cancel · Print · Back | semua baca-saja, termasuk baris detail |
| Cancelled | Print · Back | record tetap tersimpan; tidak punya approval |

| Tombol | Perilaku | Tujuan sesudahnya |
|---|---|---|
| **Back** | bila ada isian belum disimpan: dialog **Save / Don't Save / Cancel** (Cancel = tetap di form) | Grid, keadaan dipulihkan |
| **Save** | validasi wajib (klien lalu server). Gagal: tab berisi galat dibuka, toast "N isian wajib". Berhasil: simpan, penanda berubah dibersihkan | tetap di form (record baru: pindah ke `…/form/{id}`), toast |
| **Undo** | baru: isian kembali ke nilai bawaan. Mode Edit: buang perubahan lalu terkunci lagi | tetap di form |
| **Delete** | hanya Draft; dialog Yes/No; Yes = record hilang total | Grid, toast "dihapus" |
| **Confirm** | dialog; hanya Confirm yang divalidasi penuh; Draft → Confirmed | tetap di form, toast |
| **Cancel** | dialog WAJIB alasan (masuk History); Confirmed → Cancelled; bila perlu ADJUSTMENT otomatis (`engine.md`); approval ikut hilang | tetap di form |
| **Print** | tab baru; cetakan SELALU memuat status dokumen (DRAFT/CONFIRMED/CANCELLED) dan, bila ada, status approval | tetap di form |

Tidak ada **New**, **Email**, **Back to Draft** pada siklus ketat. Tidak ada **Export** di form: Export ada di **toolbar grid** (satu tempat dengan Cari, Filter, Kolom; format CSV dan XLSX).

Baris detail (`x-dl::lines :row-state="true"`), mengikuti `transaction-lifecycle.md`:

| Keadaan baris | Tombol baris | Perilaku |
|---|---|---|
| baru diisi | **Save**, **Undo** | Save mengunci baris; Undo membuang baris baru |
| tersimpan | **Edit**, **Delete** | Edit membuka baris (Undo mengembalikan isi sebelum Edit); Delete menghapus baris dari form |
| dokumen Confirmed/Cancelled | tanpa tombol | baca-saja |

Save baris tidak menyimpan dokumen: semua baris terkirim bersama **Save** form.

## 4. Master Form (`kind="master"`)

Asal: grid `kind="master"` (`restore`, `openUrl`, `newUrl`). Master TIDAK punya Draft/Confirm/Cancel/Print: hanya **Active / Inactive**.

| Keadaan | Tombol |
|---|---|
| Baru (Active, belum tersimpan) | Undo · Save · Back |
| Active tersimpan | Edit · Deactivate · [Delete] · Back |
| Inactive | Activate · Back |

- **Deactivate** (dialog Yes/No) = disembunyikan dari pilihan, data tetap tersimpan. **Activate** membalikkannya.
- **Delete** hanya untuk **administrator** DAN data yang **belum dipakai transaksi** (`:deletable`). Server memeriksa ulang; bila ternyata dipakai: toast kuning "Sudah dipakai, nonaktifkan saja". Selain itu tombol tidak tampil. Dialog Yes/No; Yes → grid.
- Save tetap di form; record baru pindah ke `…/form/{id}` berstatus Active.

## 5. Approval: field terpisah dari status dokumen

Status dokumen (Draft/Confirmed/Cancelled) dan status approval (**APPROVED / HOLD / REJECTED**) adalah DUA field. Approval hanya ada saat dokumen **Confirmed** (`:approval="waiting|approved|hold|rejected"`; `waiting` = belum diputuskan, tampil sebagai cap putus-putus AWAITING, bukan status keempat). Draft dan Cancelled tidak punya approval. Dokumen masuk persetujuan selalu mulai dari Confirmed, jadi pasti baca-saja.

| Approval | Pembuat | Pemeriksa (`:can-approve`) |
|---|---|---|
| waiting | Cancel · Print · Back | Approve · Hold · Reject · Print · Back |
| hold | Print · Back | Approve · Reject · Print · Back |
| approved | Print · Back (tidak bisa Cancel) | Reject · Print · Back |
| rejected | Duplicate · Print · Back | Approve (dengan OTP) · Print · Back |

- **Hold** sementara; Reject dan Hold wajib alasan. APPROVED → REJECTED wajib alasan. Keputusan masuk log; setelah keputusan form tetap terbuka.
- **REJECTED**: tidak tampil di daftar biasa (Semua, daftar tugas), hanya di tab **Rejected**. Pembuat boleh **Duplicate** (Draft baru tanpa nomor) lalu memperbaikinya.
- **REJECTED → APPROVED** hanya oleh pemeriksa, dengan **OTP + alasan**. OTP diterbitkan **administrator** di luar sistem; administrator sendiri tidak bisa mengubahnya. DevLib hanya menyediakan dialog OTP (`x-dl::field.otp`) dan alamat verifikasi (`:otp-url`); penerbitan dan pemeriksaan kode adalah urusan proyek.
- Grid Approval (cabang di Transactional): kolom **Status** dan **Approval** (dua lencana), tab All (tanpa Rejected) · Awaiting · Hold · Approved · **Rejected**.

## 6. Wizard (tanpa grid)

Wizard berdiri sendiri, tidak punya grid dan tidak punya Back.

| Tombol | Perilaku |
|---|---|
| Next | validasi langkah ini; langkah berikut |
| Previous | langkah sebelumnya, isian tetap |
| Finish (label proyek) | validasi semua langkah, simpan; menampilkan layar **Done** (`:done="teks"`) dengan tombol **Start again** (isian dikosongkan, kembali ke langkah 1) |

## 7. Penjaga perubahan (dirty guard)

`x-dl::form` otomatis dijaga (`:guard="false"` mematikan). Form dianggap berubah begitu satu isian diubah.

| Kejadian | Hasil |
|---|---|
| Back / tautan di luar form / breadcrumb ke halaman lain | dialog **Save**, **Don't Save**, **Cancel** |
| tutup tab, muat ulang, Back peramban | peringatan peramban |
| Save berhasil, Undo, Delete | penanda berubah dihapus |
| form tidak berubah | Back langsung, tanpa dialog |

## 8. Kondisi galat

| Kondisi | Perilaku |
|---|---|
| isian wajib kosong | klien: tab berisi galat dibuka + angka di tab + fokus ke isian pertama, tidak kirim. Server (cadangan): kembali ke form, toast merah |
| `{id}` tidak ada | kembali ke grid asal, toast merah "Record not found" |
| tidak punya hak | tombol tidak ditampilkan; permintaan langsung ditolak server: kembali ke form, toast merah |
| aksi tak berlaku pada keadaan itu | ditolak server, toast kuning, status tidak berubah |
| OTP salah / alasan kosong | toast merah, approval tidak berubah |
| `from` tidak sah | diabaikan, grid bawaan modul dipakai |

## 9. `:back`, `back_history`, dan lint

- Setiap form panel memakai `:back="<alamat grid asal>"` (di alur ini = `from` yang sudah divalidasi, bawaan = grid modul).
- `config('devlib.back_history') = false`: tombol Back TIDAK memakai `history.back()`.
- **F101** (form dokumen tanpa `:back`) dan **F102** (`back_history` harus `false`) terpenuhi. **F110**: `:approval` hanya sah pada `:strict`, `kind="document"`, status `confirmed`.
- Grid yang membuka form memasang `restore`, `:open-url`, `:new-url` (alamat berisi `from`).

## 10. Pemakaian di proyek

```blade
<x-dl::grid id="po" kind="transactional" restore :rows="$rows" :columns="$cols"
    :open-url="route('po.form', ['id' => '{id}', 'from' => request()->getRequestUri()])"
    :new-url="route('po.form', ['id' => 'new', 'from' => request()->getRequestUri()])" />

<x-dl::form kind="document" panel="top" :strict="true" :status="$po->status" :approval="$po->approval"
    :can-approve="$canApprove" :otp-url="route('po.otp', $po)" :print-url="route('po.print', $po)"
    :back="$back" :action="route('po.save', $po)" … />
```

Controller: `from` divalidasi lokal. Save/Confirm/Cancel/Approve/… → `redirect()->route('po.form', …)` dengan toast; Save record baru → form record itu; Delete → `redirect($from)`. Pesan ditampilkan dengan `<div hidden data-dl-flash="success">teks</div>` (toast sekali muncul).
