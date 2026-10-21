<?php

namespace Devlib;

use Devlib\Components\DevlibComponent;
use Illuminate\Support\Str;

// Technical Guide (TG) semua object DevLib, English (skills/devlib/help.md). Kerangka tiap object dibuat otomatis dari Manifest
// (tag, kelas, berkas, prop + tipe + bawaan, tujuan dari REMARK); rincian tulisan tangan = resources/guide/<tag tanpa "x-dl::">.md
// (titik di tag jadi "/", mis. field/text.md). Object tanpa berkas rincian tetap tampil dengan kerangkanya dan tercatat di
// resources/guide/PENDING.txt (tes kontrak: object baru wajib punya rincian; daftar itu hanya boleh menyusut).
// Input: Manifest::collect(), resources/guide. Output: array entri / HTML. Dipakai oleh: route devlib.guide, JS link judul (window.DL_GUIDE), tes.
final class Guide
{
    public const FAMILIES = [
        'grid' => 'Grid & tables',
        'form' => 'Form & fields',
        'buttons' => 'Buttons & actions',
        'print' => 'Print & documents',
        'messages' => 'Messages & feedback',
        'menu' => 'Menu & navigation',
        'other' => 'Other objects',
    ];

    // Bagian wajib pada rincian tulisan tangan (tes kontrak).
    public const SECTIONS = ['Overview', 'Props', 'Behavior', 'Data and events', 'Rules and pitfalls', 'Example', 'Related'];

    public static function dir(): string
    {
        return dirname(__DIR__).'/resources/guide';
    }

    // Alamat halaman TG untuk link judul: config devlib.guide_url, atau route bawaan paket bila diaktifkan (guide_middleware); kosong = tanpa link.
    public static function url(): ?string
    {
        if ($url = config('devlib.guide_url')) {
            return (string) $url;
        }

        return config('devlib.guide_middleware') !== null && \Illuminate\Support\Facades\Route::has('devlib.guide') ? route('devlib.guide') : null;
    }

    // Id anchor satu object: sama dengan yang dihitung JS (devlib.js, link judul).
    public static function anchor(string $tag): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/i', '-', $tag), '-');
    }

    // Berkas rincian untuk satu tag, mis. x-dl::field.text => resources/guide/field/text.md.
    public static function detailPath(string $tag): string
    {
        $name = preg_replace('/^[a-z0-9-]+::/i', '', $tag);

        return self::dir().'/'.str_replace('.', '/', $name).'.md';
    }

    /** @return list<array{tag: string, anchor: string, family: string, class: string, file: string, view: ?string, js: ?string, purpose: string, props: list<array{name: string, type: string, default: string}>, detail: ?string}> */
    public static function entries(): array
    {
        $rows = [];
        foreach (Manifest::collect() as $r) {
            if ($r['abstract']) {
                continue;
            }
            $path = self::detailPath($r['tag']);
            $js = 'resources/assets/js/components/'.Str::kebab(class_basename($r['class'])).'.js';
            $rows[] = [
                'tag' => $r['tag'],
                'anchor' => self::anchor($r['tag']),
                'family' => DevlibComponent::groupOf($r['class']) ?? 'other',
                'class' => $r['class'],
                'file' => $r['file'],
                'view' => $r['view'],
                'js' => is_file(dirname(__DIR__).'/'.$js) ? $js : null,
                'purpose' => $r['purpose'],
                'props' => $r['props'],
                'detail' => is_file($path) ? str_replace("\r\n", "\n", (string) file_get_contents($path)) : null,
            ];
        }

        return $rows;
    }

    /** @return array<string, list<array>> kelompok => entri, urutan menurut FAMILIES */
    public static function byFamily(): array
    {
        $out = array_fill_keys(array_keys(self::FAMILIES), []);
        foreach (self::entries() as $e) {
            $out[$e['family']][] = $e;
        }

        return array_filter($out);
    }

    // Tag yang belum punya rincian tulisan tangan.
    /** @return list<string> */
    public static function pending(): array
    {
        return array_values(array_map(fn ($e) => $e['tag'], array_filter(self::entries(), fn ($e) => $e['detail'] === null)));
    }

    // Isi PENDING.txt (satu tag per baris; baris "#" = komentar).
    /** @return list<string> */
    public static function pendingList(): array
    {
        $f = self::dir().'/PENDING.txt';
        $lines = is_file($f) ? preg_split('/\R/', (string) file_get_contents($f)) : [];

        return array_values(array_filter(array_map('trim', $lines), fn ($l) => $l !== '' && $l[0] !== '#'));
    }

    // Markdown rincian -> HTML: HTML mentah di-escape, tautan tidak aman ditolak (Str::markdown sudah memuat GFM: tabel).
    public static function html(string $markdown): string
    {
        return Str::markdown($markdown, ['html_input' => 'escape', 'allow_unsafe_links' => false]);
    }
}
