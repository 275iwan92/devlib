# DevLib PWA (React + TypeScript)

Kerangka aplikasi web yang bisa dipasang di HP (PWA), opt-in dan terpisah dari komponen Blade `x-dl::*`. Belum berisi layar DevLib; hanya satu layar contoh.

```bash
cd pwa
npm install
npm run build    # tsc --noEmit + vite build -> ../site/public/pwa (manifest + service worker; tidak di-commit)
npx vite build --watch   # pengembangan: bangun ulang saat berkas berubah
```

- Ikon: satu SVG (`public/icon.svg`). Safari iOS menuntut PNG 192/512 bila dipasang; tambahkan saat proyek pewaris membutuhkannya.
- Pasang di HP butuh HTTPS (Herd menyediakannya) atau `localhost`.
- Rencana layar berikutnya (satu per waktu) ditentukan Jo; menu HP tetap `x-dl::phone-shell` (Blade).
- Dibuka dari situs Laravel di `/pwa/` (satu asal: cookie sesi dan CSRF berlaku). Server: `GET /pwa/session` dan `POST /login` (JSON) di `site/routes/web.php`.
