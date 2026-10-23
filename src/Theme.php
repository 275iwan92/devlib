<?php

namespace Devlib;

// Sumber data tema (salinan system.sys_theme: 4 keluarga x 3 corak = 12 tema).
// Di aplikasi nanti dibaca dari tabel; di situs DevLib (tanpa DB) dari konstanta ini.
// Tema hanya MENIMPA token warna di :root (devlib.css) -- komponen tidak tahu tema.
final class Theme
{
    public const DEFAULT = 'navy';

    // code => [nama, keluarga, corak, warna utama, warna sidebar, latar isi]
    public const ALL = [
        'pastel_blue' => ['Pastel Biru', 'pastel', 'blue', '#6aa3e8', '#1f2742', '#f2f6fc'],
        'pastel_green' => ['Pastel Hijau', 'pastel', 'green', '#7fae8f', '#1c2e26', '#f2f7f3'],
        'pastel_red' => ['Pastel Merah', 'pastel', 'red', '#d98fa0', '#2f2028', '#faf3f5'],
        'navy' => ['Cerah Biru', 'cerah', 'blue', '#2f6fed', '#1f2742', null],
        'emerald' => ['Cerah Hijau', 'cerah', 'green', '#16a34a', '#123a26', null],
        'cerah_red' => ['Cerah Merah', 'cerah', 'red', '#e5484d', '#3a1414', null],
        'dark_blue' => ['Dark Biru', 'dark', 'blue', '#4c85ff', '#101b30', '#12182c'],
        'dark_green' => ['Dark Hijau', 'dark', 'green', '#34d399', '#0b1f16', '#0f1c17'],
        'dark_red' => ['Dark Merah', 'dark', 'red', '#ff6b6b', '#2a1416', '#1f1518'],
        'hitam_blue' => ['Hitam Biru', 'hitam', 'blue', '#5b9dff', '#0a0a0c', '#060608'],
        'hitam_green' => ['Hitam Hijau', 'hitam', 'green', '#2ee6a6', '#0a0a0c', '#060608'],
        'hitam_red' => ['Hitam Merah', 'hitam', 'red', '#ff5c5c', '#0a0a0c', '#060608'],
        // Soft = sidebar TERANG berwarna pastel (teks sidebar gelap lewat token side-ink / side-hot); keluarga baru, tema lama tidak berubah.
        'soft_blue' => ['Soft Biru', 'soft', 'blue', '#4a78c2', '#dde8f6', '#f3f6fb'],
        'soft_green' => ['Soft Hijau', 'soft', 'green', '#3f8a63', '#dcebe2', '#f4f8f5'],
        'soft_red' => ['Soft Merah', 'soft', 'red', '#c0586d', '#f4e1e5', '#fbf4f5'],
    ];

    public const FAMILIES = ['pastel' => 'Pastel', 'cerah' => 'Cerah', 'dark' => 'Dark', 'hitam' => 'Hitam', 'soft' => 'Soft'];

    public const HUES = ['blue' => 'Biru', 'green' => 'Hijau', 'red' => 'Merah'];

    // Permukaan gelap untuk keluarga dark/hitam (token netral ikut berubah).
    private const DARK = [
        'dark' => ['surface' => '#1a2238', 'surface-alt' => '#161d31', 'hover' => '#222b45', 'border' => '#2b3552', 'ink' => '#d6dcef', 'ink-strong' => '#f1f4ff', 'muted' => '#8a93b3'],
        'hitam' => ['surface' => '#111114', 'surface-alt' => '#0c0c0f', 'hover' => '#1a1a1f', 'border' => '#26262d', 'ink' => '#d4d4dc', 'ink-strong' => '#f4f4f8', 'muted' => '#8b8b96'],
    ];

    /** Token CSS milik satu tema. */
    public static function tokens(string $code): array
    {
        // Tema bawaan = nilai :root di devlib.css (persis mockup), tidak ditimpa.
        if ($code === self::DEFAULT) {
            return [];
        }
        [, $family, , $primary, $sidebar, $bg] = self::ALL[$code] ?? self::ALL[self::DEFAULT];
        $t = [
            'accent' => $primary,
            'accent-2' => "color-mix(in srgb, $primary 82%, #000)",
            'accent-soft' => "color-mix(in srgb, $primary 12%, var(--dl-surface))",
            'accent-soft-2' => "color-mix(in srgb, $primary 20%, var(--dl-surface))",
            'accent-ring' => "color-mix(in srgb, $primary 15%, transparent)",
            'side' => $sidebar, 'navy' => $sidebar, 'navy-2' => "color-mix(in srgb, $sidebar 80%, #fff)",
            // Baris/judul menu, hover dan scrollbar panel ikut warna sidebar tema (bukan biru bawaan), supaya tema hijau/merah tidak berbaur biru.
            'side-2' => "color-mix(in srgb, $sidebar 90%, #fff)", 'side-hover' => "color-mix(in srgb, $sidebar 80%, #fff)", 'side-scroll' => "color-mix(in srgb, $sidebar 65%, #fff)",
            'topbar' => "color-mix(in srgb, $sidebar 70%, #000)",
            'f-line' => "color-mix(in srgb, $primary 22%, #d1d5db)",
            'f-badge' => "color-mix(in srgb, $primary 14%, #fff)",
            'f-badge-ink' => "color-mix(in srgb, $primary 70%, #1f2937)",
            'f-ro' => "color-mix(in srgb, $primary 4%, #fff)",
        ];
        if ($bg) {
            $t['bg'] = $bg;
        }
        if ($family === 'soft') {
            // Sidebar terang: teks sidebar gelap; kartu "navy" (hero, bilah atas) tetap gelap supaya teks putihnya terbaca.
            $t = [
                'side-ink' => "color-mix(in srgb, $primary 15%, #0f1d16)", 'side-leaf' => "color-mix(in srgb, $primary 15%, #0f1d16)", 'side-hot' => '#0b1a12',
                'side-muted' => "color-mix(in srgb, $primary 40%, #2a3a31)", 'net-bad' => '#b3261e', 'net-warn' => '#8a5a00',
                'side-2' => "color-mix(in srgb, $sidebar 88%, $primary)", 'side-hover' => "color-mix(in srgb, $sidebar 75%, $primary)", 'side-scroll' => "color-mix(in srgb, $sidebar 70%, $primary)",
                'tabbar' => "color-mix(in srgb, $sidebar 85%, #fff)", 'tab' => "color-mix(in srgb, $sidebar 90%, $primary)", 'tab-hover' => "color-mix(in srgb, $sidebar 80%, $primary)",
                'navy' => "color-mix(in srgb, $primary 55%, #0b1a12)", 'navy-2' => "color-mix(in srgb, $primary 45%, #0b1a12)", 'topbar' => "color-mix(in srgb, $primary 60%, #0b1a12)",
            ] + $t;
        }
        if (isset(self::DARK[$family])) {
            $d = self::DARK[$family];
            $t['f-line'] = "color-mix(in srgb, $primary 30%, {$d['border']})";
            $t = [
                'surface' => $d['surface'], 'surface-alt' => $d['surface-alt'], 'surface-muted' => $d['hover'], 'hover' => $d['hover'],
                'border' => $d['border'], 'border-soft' => $d['border'], 'border-hover' => $d['muted'],
                'ink' => $d['ink'], 'ink-strong' => $d['ink-strong'], 'muted' => $d['muted'],
                'chip' => $d['hover'], 'tabbar' => $d['surface-alt'], 'tab' => $d['hover'], 'tab-hover' => $d['border'], 'tab-ink' => $d['muted'],
                'f-ink' => $d['ink-strong'], 'f-label' => $d['ink'], 'f-muted' => $d['muted'], 'f-line-soft' => $d['border'], 'f-bg-soft' => $d['hover'],
                'f-ro' => $d['hover'], 'f-badge' => "color-mix(in srgb, $primary 22%, {$d['surface']})", 'f-badge-ink' => $d['ink-strong'],
                'info-soft' => "color-mix(in srgb, $primary 14%, {$d['surface']})", 'info-line' => "color-mix(in srgb, $primary 35%, {$d['surface']})",
                'danger-soft' => '#3a1d20', 'danger-line' => '#5c2a2e', 'code-bg' => $d['border'],
            ] + $t;
        }

        return $t;
    }

    public static function css(): string
    {
        $out = '';
        foreach (array_keys(self::ALL) as $code) {
            $vars = '';
            foreach (self::tokens($code) as $k => $v) {
                $vars .= "--dl-$k:$v;";
            }
            $out .= "[data-dl-theme=\"$code\"]{{$vars}}\n";
        }

        return $out;
    }
}
