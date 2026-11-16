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
