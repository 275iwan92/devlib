# DevLib: aturan baku Form

Aturan penuh `x-dl::form`; ditegakkan kode oleh `Devlib\Lint\FormRules` (`php artisan devlib:lint`). Siklus mode/tombol dokumen: `transaction-lifecycle.md`.

- **Tombol Back harus ke grid modulnya.** Isi `:back="route('x.index')"` di `x-dl::form` **dan** set `'back_history' => false` di `config/devlib.php`. Tanpa itu Back memakai `history.back()` → setelah Save, "layar sebelumnya" adalah form isian (risiko input ganda). Jangan pakai `history.back()` / `url()->previous()` di form.
- Alur lengkap grid → form → kembali (parameter, tujuan tiap tombol, dirty guard, galat): `grid-form-flow.md`; `:back` di sana = alamat grid asal beserta query-nya.
- Form dokumen: `kind="document"` (+ `:pipeline`, `:status`); tombol Save/Confirm/Delete/Cancel/Print/Back to Draft **datang dari status DevLib** — jangan dibuat manual. Controller membaca `_action` (save, confirm, delete, cancel, approve, reject). Pakai kata **Confirm**, bukan Submit. Form master: `kind="master"`.
- Isian wajib: `required`; error server: `:error="$errors->first('x')"`; nilai ulang: `old()`. Isian di dalam modal bersarang tidak ikut validasi form induk.
- Cegah kirim ganda: layout memuat penjaga `submit` (ada di `examples/layout.blade.php`) — jangan dibuang.
- Riwayat: tab History otomatis; isi `:created-at`, `:updated-at`, `:log`.
- **Siklus ketat** (`:strict="true"`, opt-in; aturan pemilik 2026-10-21): record baru = Draft/Active dengan isian bawaan terisi (Undo + Save); tersimpan = terkunci, **Edit** membuka isian (Save + Undo); Save dan Confirm tetap di form; Delete (Yes/No) dan Back kembali ke grid; tidak ada New, Email, Back to Draft, Export di form. Rinci: `grid-form-flow.md`.
- **Single Form ≠ Master Form.** Single Form = transaksional satu record tanpa baris (Draft → Confirmed → Cancelled, ada nomor, ada Print). Master Form = data master (`kind="master"`): hanya Active/Inactive (Edit, Deactivate/Activate, Delete hanya administrator untuk data yang belum dipakai: `:deletable`), tanpa Confirm/Cancel/Print.
- **Form Approval baku (Jo, 2026-10-21; terkunci):** bentuk Approval Center PO (`p5-form-approval`, `x-dl::approval`): detail dokumen **hanya-baca**, alur bertingkat **Level n of N**, riwayat dokumen, tombol Reject / Hold / Setujui. Bentuk ini tidak diubah tanpa perintah Jo. Dalam alur Grid and Form, Approval punya **grid sendiri** (daftar menunggu) dan **form sendiri** (bentuk baku ini), terpisah dari form dokumen biasa.
- **Status dokumen dan approval = dua field** (`:status`, `:approval="waiting|approved|hold|rejected"`); approval hanya pada dokumen Confirmed. Rejected → Approved hanya oleh pemeriksa dengan OTP (`:otp-url`).
- **Gaya baca-saja** (terkunci pada siklus ketat; Confirmed hijau, Cancelled merah, Inactive abu, Draft netral): angka dan total (Qty, Rupiah) **tebal dan sedikit lebih besar**; terbilang **bergaris bawah**; tanggal **miring**; teks lain berwarna menurut status; baris detail ikut baca-saja; **cap status** (dan approval) di pojok kanan atas. Otomatis dari `:strict`, jangan diatur per layar.
- **Print** selalu memuat status dokumen (DRAFT/CONFIRMED/CANCELLED) dan status approval bila ada. Export ada di toolbar grid, bukan di form.
