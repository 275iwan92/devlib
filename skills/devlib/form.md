# DevLib: aturan baku Form

Aturan penuh `x-dl::form`; ditegakkan kode oleh `Devlib\Lint\FormRules` (`php artisan devlib:lint`, Mode Developer). Siklus mode/tombol dokumen: `transaction-lifecycle.md`.

- **Tombol Back harus ke grid modulnya.** Isi `:back="route('x.index')"` di `x-dl::form` **dan** set `'back_history' => false` di `config/devlib.php`. Tanpa itu Back memakai `history.back()` → setelah Save, "layar sebelumnya" adalah form isian (risiko input ganda). Jangan pakai `history.back()` / `url()->previous()` di form.
- Alur lengkap grid → form → kembali (parameter, tujuan tiap tombol, dirty guard, galat): `grid-form-flow.md`; `:back` di sana = alamat grid asal beserta query-nya.
- Form dokumen: `kind="document"` (+ `:pipeline`, `:status`); tombol Save/Confirm/Delete/Cancel/Print/Back to Draft **datang dari status DevLib** — jangan dibuat manual. Controller membaca `_action` (save, confirm, delete, cancel, approve, reject). Pakai kata **Confirm**, bukan Submit. Form master: `kind="master"`.
- Isian wajib: `required`; error server: `:error="$errors->first('x')"`; nilai ulang: `old()`. Isian di dalam modal bersarang tidak ikut validasi form induk.
- Cegah kirim ganda: layout memuat penjaga `submit` (ada di `examples/layout.blade.php`) — jangan dibuang.
- Riwayat: tab History otomatis; isi `:created-at`, `:updated-at`, `:log`.
