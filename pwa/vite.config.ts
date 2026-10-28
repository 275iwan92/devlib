import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { VitePWA } from 'vite-plugin-pwa';

// Disajikan situs Laravel di /pwa/ (satu asal: cookie sesi + CSRF berlaku); keluaran build = site/public/pwa (tidak di-commit).
export default defineConfig({
    base: '/pwa/',
    build: { outDir: '../site/public/pwa', emptyOutDir: true },
    plugins: [
        react(),
        VitePWA({
            registerType: 'autoUpdate',
            // .json dikenal semua peladen (application/json); .webmanifest jatuh ke application/octet-stream di Herd
            manifestFilename: 'manifest.json',
            manifest: {
                name: 'DevLib',
                short_name: 'DevLib',
                lang: 'id',
                display: 'standalone',
                orientation: 'portrait',
                start_url: '.',
                scope: '.',
                background_color: '#ffffff',
                theme_color: '#1f6feb',
                // PNG dibuat scripts/make-icons.php dari rancangan icon.svg (Chrome Android menuntut 192 + 512; Safari iOS memakai apple-touch-icon di index.html)
                icons: [
                    { src: 'icon-192.png', sizes: '192x192', type: 'image/png', purpose: 'any' },
                    { src: 'icon-512.png', sizes: '512x512', type: 'image/png', purpose: 'any' },
                    { src: 'icon-512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
                ],
            },
            workbox: { globPatterns: ['**/*.{js,css,html,svg,png}'] },
        }),
    ],
});
