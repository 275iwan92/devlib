# DevLib: alur Grid → Form → kembali ke Grid

Satu alur lengkap per entri: **grid** (daftar) dan **form** (satu record/dokumen) adalah DUA halaman dengan alamat sendiri. Form dibuka dari grid, dan semua jalan keluar form berujung di grid asalnya. Melengkapi `form.md` (tombol, `:back`), `grid.md` (jenis grid), `transaction-lifecycle.md` (status dokumen, baris detail 2 state). Bila ada selisih, tiga berkas itu yang menang; berkas ini hanya merinci *urutan layar*.

Demo hidup: `/lib/p5-flow-master` (master), `/lib/p5-flow-document` (dokumen master-detail), menu **Grid + Form**.

## 1. Prinsip

1. Grid dan form tidak digabung dalam satu komponen yang bertukar tampilan (komponen `x-dl::master` lama dihapus). Berpindah layar = pindah alamat.
2. Form selalu tahu grid asalnya lewat `:back` (alamat grid LENGKAP dengan query-nya). Tidak pernah `history.back()` / `url()->previous()`.
3. Keadaan grid yang dipulihkan saat kembali: **tab, filter, urutan, kolom, baris per halaman** (sudah tersimpan per grid di peramban) dan, dengan `restore`, **kata cari, halaman, kotak statistik**. Keadaan di server (mis. `?doc=po`) ikut di `from`.
4. Baris yang baru disimpan **disorot** dan halaman grid meloncat ke baris itu (`?hl=<id>`).
5. Pesan hasil aksi muncul sebagai **toast di halaman tujuan** (dari server, satu kali).
6. Keputusan tampilan memakai keadaan record (status), bukan layar: tombol datang dari `x-dl::form` menurut `kind` + `status`; layar tidak menyusun tombol sendiri.

## 2. Parameter yang dibawa

| Parameter | Arah | Isi | Aturan |
|---|---|---|---|
| `{id}` di path form | grid → form | nilai `rowKey` baris; `new` = record baru | `openUrl` = `…/form/{id}`; `newUrl` = `…/form/new` |
| `from` | grid → form | alamat grid asal (path + query, mis. `/lib/p5-flow-document?doc=po`) | hanya path lokal `/…` (bukan `//` atau `http`); tak sah/kosong → grid bawaan modul. Menjadi `:back` dan tujuan setelah Simpan/Hapus |
| `hl` | form → grid | `id` baris yang baru disimpan | dibaca grid ber-`restore`; dipakai sekali |
| `status` | form ↔ form | status yang ditampilkan (hanya demo; di aplikasi nyata status dibaca dari record) | |
| `copy` | form lama → form baru | `id` yang disalin (Duplicate) | form baru tanpa nomor |
| cari/halaman/statistik | grid → grid | disimpan grid ber-`restore` di `sessionStorage` saat form dibuka, dipulihkan SEKALI saat kembali | bukan parameter URL; tab terbuka lain tidak terpengaruh |

## 3. Aksi form dan tujuannya

Berlaku untuk semua alur. "Grid" = alamat `from` (atau grid bawaan). Toast dari server; galat = toast merah dan form TETAP terbuka.

| Tombol | Kapan ada | Perilaku | Tujuan sesudahnya |
|---|---|---|---|
| **Back** | semua status | bila ada isian belum disimpan: dialog **Save / Don't Save / Cancel** (Cancel = tetap di form); selain itu langsung | Grid, keadaan dipulihkan, tanpa sorot |
| **Save** | `new`, `draft`, master | validasi wajib (klien lalu server). Gagal: tab berisi galat dibuka, toast "N isian wajib". Berhasil: simpan | Grid, baris tersorot (`hl`), toast "… tersimpan" |
| **Undo** | `new`, `draft`, master; mati bila form tidak berubah | dialog "Buang perubahan?"; Ya = isian kembali ke saat form dibuka, tanpa kirim ke server | tetap di form |
| **Delete** | `draft` | dialog konfirmasi; record hilang total (hanya Draft) | Grid, toast "… dihapus", tanpa sorot |
| **New** | `draft`, `confirmed`, `cancelled`, `closed`, master | pindah ke form kosong (`…/form/new`), `from` yang sama diteruskan. Tautan di dalam form tidak melewati penjaga perubahan, hanya peringatan peramban; Save dulu bila perlu | form baru |
| **Confirm** | `draft` | dialog; hanya Confirm yang divalidasi penuh; Draft → Confirmed, isian terkunci; baris detail: tombol baris HIDDEN | tetap di form, status baru, toast |
| **Cancel** | `confirmed`, `approved`, approval bagi pembuat | dialog WAJIB alasan (masuk History); Confirmed → Cancelled; record tetap tersimpan; bila perlu ADJUSTMENT otomatis (`engine.md`) | tetap di form, status Cancelled |
| **Back to Draft** | `confirmed`, hanya `:can-reopen` | dialog WAJIB alasan; isian terbuka lagi, harus Confirm ulang; server cek hak yang sama | tetap di form, status Draft |
| **Duplicate** | `cancelled`, `rejected` | salinan isi sebagai Draft baru tanpa nomor (nomor dibuat saat Save) | form baru (`copy={id}`) |
| **Print / Email** | semua status dokumen | tab baru (cetakan membawa MODE record); Email membuka dialog di halaman cetak. Tidak mengubah dokumen | tetap di form |
| **Approve / Reject / Hold** | `approval`, `hold`, hanya pemeriksa tingkat berjalan | dialog; Reject/Hold wajib alasan; Approve/Reject final; Hold sementara | Grid (daftar tugas pemeriksa), toast |
| **Activate / Deactivate** | master | dialog; data nonaktif tidak muncul di pilihan | tetap di form |

Catatan: **Save** pada dokumen juga kembali ke grid (aturan alur ini); langkah lanjut (Confirm dst.) dilakukan dengan membuka barisnya lagi. Bila sebuah proyek ingin dokumen tetap di form setelah Save, ia mencatat selisihnya di skill proyek itu sendiri.

## 4. Alur Master Data (`kind="master"` atau Single Form)

Asal: grid `kind="master"` (`restore`, `openUrl`, `newUrl`).

| Pemicu di grid | Form terbuka | Status awal |
|---|---|---|
| klik 2× baris / ikon Edit | `…/form/{id}?from=<grid>` | dari record: `active`/`inactive` (master) atau status dokumen (Single Form) |
| **+ New Record** | `…/form/new?from=<grid>` | `new` (Undo + Save saja) |

| Keadaan | Tombol (kiri → kanan) |
|---|---|
| `new` | Undo · Save · Back |
| master `active` | Deactivate · New · Undo · Save · Back |
| master `inactive` | Activate · New · Undo · Save · Back |
| Single Form `draft` | Delete · New · Undo · Print · Confirm · Save · Back |
| Single Form `confirmed` | Cancel · [Back to Draft] · New · Email · Print · Back |
| Single Form `cancelled` | New · Duplicate · Print · Back |
| Single Form `closed` | New · Print · Back |

Tujuan: Save → grid + sorot (data baru: halaman meloncat ke barisnya, saringan sisa dilepas bila menyembunyikannya). Delete → grid. Back → grid.

## 5. Alur Dokumen Master-Detail (Customer Order, Purchase Order)

Asal: grid `kind="transactional"` (zebra, kolom Status berwarna, aksi **Buka** saja; Confirm/Approve/Cancel/Delete TIDAK di grid). Dua dokumen dipilih lewat `?doc=co|po` pada grid; `from` membawa pilihan itu sehingga Back kembali ke dokumen yang sama.

| Pemicu | Form terbuka | Status awal |
|---|---|---|
| klik 2× baris / ikon Buka | `…/form/{id}?doc=po&from=<grid>` | status record (Draft/Confirmed/Cancelled/Closed) |
| **+ New Record** | `…/form/new?doc=po&from=<grid>` | `new` → Undo + Save; baris detail 1 baris kosong |

| Status | Isian | Tombol |
|---|---|---|
| `new` | terbuka; default tidak kosong (tanggal hari ini, pihak/satuan bawaan) | Undo · Save · Back |
| `draft` | terbuka | Delete · New · Undo · Print · Confirm · Save · Back |
| `confirmed` | terkunci | Cancel · [Back to Draft] · New · Email · Print · Back |
| `cancelled` | terkunci | New · Duplicate · Print · Back |
| `closed` (otomatis oleh proses lanjutan, bukan tombol) | terkunci | New · Print · Back |

Baris detail (`x-dl::lines :row-state="true"`), mengikuti `transaction-lifecycle.md`:

| Keadaan baris | Tombol baris | Perilaku |
|---|---|---|
| baru diisi | **SAVE**, **UNDO** | SAVE mengunci baris (pindah ke state tertulis); UNDO membuang baris baru |
| telah ditulis | **EDIT**, **DELETE** | EDIT membuka baris (UNDO lalu mengembalikan isi sebelum EDIT); DELETE menghapus baris dari form |
| dokumen `confirmed`/`cancelled`/`closed` | HIDDEN | readonly |

Save baris tidak menyimpan dokumen: semua baris tetap terkirim bersama **Save** form. Total (Subtotal/Diskon/DPP/PPN/Total) dan Grand Total di ringkasan dihitung ulang di layar.

Tujuan: Save → grid (`?doc=…`) + sorot barisnya. Confirm/Cancel/Back to Draft → tetap di form. Delete → grid. Back → grid.

## 6. Alur Approval

Dua bentuk, tujuan sama (daftar tugas pemeriksa):

| Bentuk | Asal | Perilaku |
|---|---|---|
| **Approval Center** (`x-dl::approval`) | daftar dokumen menunggu di sisi kiri komponen (satu halaman) | memilih dokumen mengisi detail di kanan (hanya-baca); Approve / Reject / Hold; dokumen yang diputuskan keluar dari daftar, dokumen berikutnya terpilih; tidak ada Back karena daftar selalu tampak |
| **Form dokumen ber-approval** (`:approval-levels` 1–3, `status` = `approval`/`hold`) | grid transaksional (tab/filter "Menunggu approval") → form | pemeriksa tingkat berjalan: Print · Reject · Hold · Approve · Back; pembuat: Cancel · New · Print · Back. Approve/Reject final, Hold sementara; keputusan masuk Log Keputusan. Sesudah keputusan: kembali ke grid + toast (tugas pemeriksa selesai). Tanpa Back to Draft setelah masuk approval; Rejected → Duplicate |

Pemeriksa tidak boleh input (`:can-input="false"`): isian terkunci, tombol Save/Delete/Confirm/New tidak ada.

## 7. Alur Wizard

Asal: grid master (mis. Customer) → **+ New Record** → wizard (halaman sendiri).

| Tombol | Perilaku | Tujuan |
|---|---|---|
| Next | validasi langkah ini; langkah berikut | tetap di wizard |
| Previous | langkah sebelumnya, isian tetap | tetap di wizard |
| Finish (label proyek, mis. Daftarkan) | validasi semua langkah, simpan | Grid, baris tersorot, toast |
| Back | isian belum disimpan → dialog Save / Don't Save / Cancel | Grid |

## 8. Penjaga perubahan (dirty guard)

`x-dl::form` otomatis dijaga (`:guard="false"` mematikan). Form dianggap berubah begitu satu isian diubah.

| Kejadian | Hasil |
|---|---|
| Back / tautan di luar form / breadcrumb ke halaman lain | dialog **Save** (kirim form; Save ber-tujuan grid), **Don't Save** (buang lalu lanjut ke tujuan klik), **Cancel** (tetap) |
| tutup tab, muat ulang, Back peramban | peringatan peramban |
| Save berhasil, Undo, Delete | penanda berubah dihapus; tidak ditanya lagi |
| form tidak berubah | Back langsung, tanpa dialog |

## 9. Kondisi galat

| Kondisi | Perilaku |
|---|---|
| isian wajib kosong | klien: tab berisi galat dibuka + angka di tab + fokus ke isian pertama, tidak kirim. Server (defense): kembali ke form, toast merah; aplikasi nyata mengisi ulang dengan `old()` dan `:error="$errors->first('x')"` |
| `{id}` tidak ada | kembali ke grid asal, toast merah "Data tidak ditemukan" (bukan halaman 404) |
| tidak punya hak (`can`) | tombol tidak ditampilkan; permintaan langsung ditolak server: kembali ke form, toast merah "Tidak punya hak" |
| aksi tak berlaku di status itu (mis. Confirm pada Cancelled) | ditolak server, toast kuning, status tidak berubah |
| `from` tidak sah | diabaikan, grid bawaan modul dipakai |

## 10. `:back`, `back_history`, dan lint

- Setiap form panel memakai `:back="<alamat grid asal>"` (di alur ini = `from` yang sudah divalidasi, bawaan = grid modul). Tombol Back menjadi tautan biasa ke alamat itu.
- `config('devlib.back_history') = false`: tombol Back TIDAK memakai `history.back()`. Tanpa itu, setelah Save "halaman sebelumnya" adalah form isian (risiko input ganda).
- **F101** (form dokumen tanpa `:back` ke grid modul) terpenuhi karena setiap form di alur membawa `:back`; **F102** (`back_history` harus `false`) terpenuhi karena config situs menyetelnya. Tidak ada lagi `lint_except` untuk form dokumen.
- Grid yang membuka form memasang `restore`, `:open-url`, `:new-url` (alamat berisi `from`); tanpa `restore` keadaan cari/halaman tidak dipulihkan, tetapi Back tetap ke grid.

## 11. Pemakaian di proyek

```blade
<x-dl::grid id="po" kind="transactional" restore :rows="$rows" :columns="$cols"
    :open-url="route('po.form', ['id' => '{id}', 'from' => request()->getRequestUri()])"
    :new-url="route('po.form', ['id' => 'new', 'from' => request()->getRequestUri()])" />

<x-dl::form kind="document" panel="top" :status="$po->status" :back="$back" :action="route('po.save', $po)" … />
```

Controller: `from` divalidasi lokal; Save/Delete → `redirect($from)->with('flash', …)` dengan `hl=<id>` di query; Confirm/Cancel/dst. → `redirect()->route('po.form', …)`. Pesan ditampilkan dengan `<div hidden data-dl-flash="success">teks</div>` (toast sekali muncul).
