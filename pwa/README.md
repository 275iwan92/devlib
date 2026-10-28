# DevLib PWA (React + TypeScript)

Kerangka aplikasi web yang bisa dipasang di HP (PWA), opt-in dan terpisah dari komponen Blade `x-dl::*`. Belum berisi layar DevLib; hanya satu layar contoh.

```bash
cd pwa
npm install
npm run dev      # pengembangan
npm run build    # tsc --noEmit + vite build -> dist/ (manifest + service worker)
```

- Ikon: satu SVG (`public/icon.svg`). Safari iOS menuntut PNG 192/512 bila dipasang; tambahkan saat proyek pewaris membutuhkannya.
- Pasang di HP butuh HTTPS (Herd menyediakannya) atau `localhost`.
- Rencana layar berikutnya (satu per waktu) ditentukan Jo; menu HP tetap `x-dl::phone-shell` (Blade).
