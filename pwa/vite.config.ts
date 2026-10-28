import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        react(),
        VitePWA({
            registerType: 'autoUpdate',
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
                // shortcut: ikon SVG satu berkas, ganti PNG 192/512 bila proyek pewaris menuntutnya (Safari iOS)
                icons: [{ src: 'icon.svg', sizes: 'any', type: 'image/svg+xml', purpose: 'any maskable' }],
            },
            workbox: { globPatterns: ['**/*.{js,css,html,svg}'] },
        }),
    ],
});
