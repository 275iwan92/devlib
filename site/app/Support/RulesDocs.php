<?php

namespace App\Support;

use Illuminate\Support\Str;

// Halaman Aturan katalog = tampilan langsung berkas skills/devlib/*.md (sumber tunggal, tanpa salinan, dibaca saat halaman dibuka).
// Yang ditulis di sini hanya urutan dan folder menu (nama berkas); judul halaman diturunkan dari baris "# ..." pertama dokumen.
final class RulesDocs
{
    // folder menu => berkas (tanpa .md), mengikuti pembagian SKILL.md.
    public const FOLDERS = [
        'Develop & Coding' => ['SKILL', 'inheritance', 'object', 'install', 'phone', 'views'],
        'Screens' => ['grid', 'form', 'grid-form-flow', 'transaction-lifecycle', 'help'],
        'Models' => ['engine', 'assistant', 'chat'],
        'Reference' => ['components'],
    ];

    public static function dir(): string
    {
        return base_path('../skills/devlib');
    }

    public static function slug(string $name): string
    {
        return 'rules-'.$name;
    }

    /** @return list<string> nama berkas (tanpa .md) menurut urutan menu */
    public static function names(): array
    {
        return array_merge(...array_values(self::FOLDERS));
    }

    public static function path(string $name): ?string
    {
        return in_array($name, self::names(), true) && is_file($f = self::dir().'/'.$name.'.md') ? $f : null;
    }

    // Isi dokumen tanpa front matter (--- ... --- di awal, dipakai SKILL.md).
    public static function markdown(string $name): string
    {
        $text = str_replace("\r\n", "\n", (string) file_get_contents(self::path($name) ?? abort(404)));

        return ltrim(preg_replace('/\A---\n.*?\n---\n/s', '', $text));
    }

    // Baris "# ..." pertama; awalan "DevLib: " dibuang.
    public static function title(string $name): string
    {
        preg_match('/^#\s+(.+)$/m', self::markdown($name), $m);
        $title = preg_replace('/^DevLib:\s*/', '', trim($m[1] ?? $name));

        return Str::ucfirst($title);
    }

    // HTML mentah di dokumen di-escape, tautan tidak aman ditolak (sama dengan halaman Historical).
    public static function html(string $name): string
    {
        return Str::markdown(self::markdown($name), ['html_input' => 'escape', 'allow_unsafe_links' => false]);
    }

    // Entri halaman katalog (bentuk Catalog::groups()) untuk semua dokumen: slug => halaman.
    public static function pages(): array
    {
        $pages = [];
        foreach (self::FOLDERS as $folder => $names) {
            foreach ($names as $name) {
                $pages[self::slug($name)] = ['label' => self::title($name), 'icon' => 'fa-solid fa-book', 'folder' => $folder, 'doc' => $name, 'classes' => [], 'mockup' => null];
            }
        }

        return $pages;
    }
}
