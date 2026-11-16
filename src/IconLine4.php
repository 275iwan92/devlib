<?php

namespace Devlib;

// Set ikon garis 4px (opt-in, x-dl::icon set="line4"): garis seragam, ujung dan sudut membulat,
// kanvas 48x48, tebal garis 4. Kunci = ARTI ikon (sama dengan Devlib\Icon::MAP bila ada padanannya).
// Arti yang tidak ada di sini jatuh ke Font Awesome, jadi aman dipakai berdampingan.
final class IconLine4
{
    public const PATHS = [
        'settings' => '<circle cx="24" cy="24" r="6"/><path d="M24 6v5M24 37v5M6 24h5M37 24h5M11.3 11.3l3.5 3.5M33.2 33.2l3.5 3.5M11.3 36.7l3.5-3.5M33.2 14.8l3.5-3.5"/>',
        'percent' => '<path d="M38 10L10 38"/><circle cx="14" cy="14" r="5"/><circle cx="34" cy="34" r="5"/>',
        'qr' => '<rect x="7" y="7" width="13" height="13" rx="3"/><rect x="28" y="7" width="13" height="13" rx="3"/><rect x="7" y="28" width="13" height="13" rx="3"/><rect x="28" y="28" width="13" height="13" rx="3"/>',
        'info' => '<circle cx="24" cy="24" r="17"/><path d="M24 22v10"/><path d="M24 15.5v.5"/>',
        'list' => '<path d="M16 12h26M16 24h26M16 36h26"/><path d="M6 12h.5M6 24h.5M6 36h.5"/>',
        'power' => '<path d="M24 7v15"/><path d="M33 12.5a16 16 0 11-18 0"/>',
        'email' => '<rect x="6" y="11" width="36" height="26" rx="5"/><path d="M8 15l16 12 16-12"/>',
        'user' => '<circle cx="24" cy="16" r="8"/><path d="M8 42c0-9 7-14 16-14s16 5 16 14"/>',
        'warning' => '<circle cx="24" cy="24" r="17"/><path d="M24 14v13"/><path d="M24 33.5v.5"/>',
        'attachment' => '<path d="M36 22L22 36a7 7 0 01-10-10L27 11a5 5 0 017 7L19 33a3 3 0 01-4-4l12-12"/>',
        'notification' => '<path d="M12 33V22a12 12 0 0124 0v11l3 4H9z"/><path d="M20 42h8"/>',
        'hash' => '<path d="M18 8l-4 32M34 8l-4 32M8 18h32M6 30h32"/>',
        'arrow' => '<path d="M8 24h32M28 12l12 12-12 12"/>',
        'time' => '<circle cx="24" cy="24" r="17"/><path d="M24 14v10l7 5"/>',
        'more' => '<path d="M11 24h.5M24 24h.5M37 24h.5"/>',
        'open' => '<path d="M20 9H12a4 4 0 00-4 4v23a4 4 0 004 4h23a4 4 0 004-4v-8"/><path d="M28 8h12v12M40 8L22 26"/>',
        'cloud' => '<path d="M14 36a9 9 0 01-1-17.9A12 12 0 0136 20a8 8 0 01-1 16z"/>',
        'wifi' => '<path d="M5 19a28 28 0 0138 0M12 27a18 18 0 0124 0M19 34a8 8 0 0110 0"/><path d="M24 40.5v.5"/>',
        'image' => '<rect x="6" y="8" width="36" height="32" rx="5"/><circle cx="17" cy="19" r="3"/><path d="M7 35l11-10 8 7 6-5 10 9"/>',
        'refresh' => '<path d="M40 24a16 16 0 11-5-11.6"/><path d="M36 5v8.5h-8.5"/>',
        // I2: 20 ikon berikutnya
        'target' => '<circle cx="24" cy="24" r="17"/><circle cx="24" cy="24" r="7"/><path d="M24 3v6M24 39v6M3 24h6M39 24h6"/>',
        'delete' => '<path d="M8 12h32M18 12V8h12v4"/><path d="M12 12l2 28h20l2-28M20 20v12M28 20v12"/>',
        'download' => '<path d="M24 6v26M13 22l11 11 11-11"/><path d="M8 40h32"/>',
        'music' => '<path d="M18 36V10l22-4v26"/><circle cx="12" cy="36" r="6"/><circle cx="34" cy="32" r="6"/>',
        'send' => '<path d="M42 6L6 21l14 6 6 15z"/><path d="M42 6L20 27"/>',
        'shield' => '<path d="M24 6l15 5v12c0 10-7 16-15 19C16 39 9 33 9 23V11z"/><path d="M17 24l5 5 9-10"/>',
        'camera' => '<path d="M6 17a4 4 0 014-4h5l3-5h12l3 5h5a4 4 0 014 4v18a4 4 0 01-4 4H10a4 4 0 01-4-4z"/><circle cx="24" cy="25" r="7"/>',
        'search' => '<circle cx="21" cy="21" r="13"/><path d="M31 31l11 11"/>',
        'home' => '<path d="M6 23L24 7l18 16"/><path d="M11 20v18a2 2 0 002 2h22a2 2 0 002-2V20M20 40V28h8v12"/>',
        'location' => '<path d="M24 43S10 31 10 20a14 14 0 0128 0c0 11-14 23-14 23z"/><circle cx="24" cy="20" r="5"/>',
        'users' => '<circle cx="18" cy="15" r="7"/><path d="M4 40c0-8 6-13 14-13s14 5 14 13"/><path d="M32 8a7 7 0 010 14M36 28c5 1 8 5 8 12"/>',
        'star' => '<path d="M24 6l5.4 11.4 12.6 1.6-9.2 8.7 2.3 12.4L24 33.9 12.9 40.1l2.3-12.4L6 19l12.6-1.6z"/>',
        'tag' => '<path d="M6 8h18l18 18-14 14L6 22z"/><path d="M15 16h.5"/>',
        'chat' => '<path d="M8 8h32a2 2 0 012 2v22a2 2 0 01-2 2H22l-9 8v-8H8a2 2 0 01-2-2V10a2 2 0 012-2z"/>',
        'at' => '<circle cx="24" cy="24" r="8"/><path d="M32 16v12a5 5 0 0010 0v-4a18 18 0 10-7 14"/>',
        'lock' => '<rect x="8" y="21" width="32" height="21" rx="5"/><path d="M15 21v-6a9 9 0 0118 0v6M24 29v6"/>',
        'close' => '<path d="M10 10l28 28M38 10L10 38"/>',
        'success' => '<path d="M8 25l11 11 21-23"/>',
        'new' => '<path d="M24 8v32M8 24h32"/>',
        'bag' => '<path d="M10 14h28l3 26H7z"/><path d="M17 20v-4a7 7 0 0114 0v4"/>',
    ];

    public static function has(string $name): bool
    {
        return isset(self::PATHS[$name]);
    }

    public static function svg(string $name): string
    {
        return '<svg class="dl-icon dl-icon--line4" viewBox="0 0 48 48" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" focusable="false">'.self::PATHS[$name].'</svg>';
    }
}
