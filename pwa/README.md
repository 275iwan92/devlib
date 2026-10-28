# DevLib PWA (React + TypeScript)

Kerangka aplikasi web yang bisa dipasang di HP (PWA), opt-in dan terpisah dari komponen Blade `x-dl::*`. Belum berisi layar DevLib; hanya satu layar contoh.

```bash
cd pwa
npm install
npm run build    # tsc --noEmit + vite build -> ../site/public/pwa (manifest + service worker; tidak di-commit)
npx vite build --watch   # pengembangan: bangun ulang saat berkas berubah
npm test         # uji platform.ts (node:test)
```

- Ikon: `public/icon-192.png` dan `icon-512.png` (dipakai manifest, juga sebagai maskable dan apple-touch-icon Safari iOS) dibuat dari rancangan `icon.svg` oleh `php scripts/make-icons.php <font-tebal.ttf>` (GD); jalankan ulang bila rancangan ikon berubah.
- Manifest bernama `manifest.json` (bukan `.webmanifest`): peladen Herd menyajikan `.webmanifest` sebagai `application/octet-stream`, `.json` sebagai `application/json`.
- Pasang di HP butuh HTTPS (Herd: `herd secure`, atau tunnel HTTPS) atau `localhost`; `http://devlib.test` di HP sungguhan tidak memenuhi syarat. Di HP: buka alamat HTTPS `/pwa/` di Chrome, menu titik tiga, "Instal aplikasi" (atau "Tambahkan ke layar utama"); ikon DL biru muncul di layar utama dan membuka layar login tanpa bilah alamat.
- Menu "Pasang aplikasi" (layar login): Android memanggil dialog pasang bawaan Chrome (`beforeinstallprompt`), iOS membuka petunjuk Bagikan > Tambah ke Layar Utama, hilang bila sudah terpasang. Uji tanpa perangkat: tambahkan `?install=android|ios|installed` ke alamat `/pwa/`.
- Rencana layar berikutnya (satu per waktu) ditentukan Jo; menu HP tetap `x-dl::phone-shell` (Blade).
- Dibuka dari situs Laravel di `/pwa/` (satu asal: cookie sesi dan CSRF berlaku). Server: `GET /pwa/session` dan `POST /login` (JSON) di `site/routes/web.php`.
