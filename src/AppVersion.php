<?php

namespace Devlib;

/* REMARK
 * Tujuan       : Versi tampil satu bentuk untuk DevLib dan aplikasi pewaris (keputusan Jo 2026-10-23).
 *                Aplikasi: `ver 0.<jumlah commit git>` + SATU HURUF TAHAP kecil dan pudar: A (alpha, dibangun dan dicoba tim sendiri), B (beta, UAT berjalan, fitur dibekukan), RC (calon rilis); rilis (tahap kosong) = tag rilis terakhir tanpa huruf, mis. `ver 1.0.0`.
 *                Pustaka DevLib: versi RILIS-nya apa adanya (`v0.6.0`, tag terakhir `v*`), jumlah commit sejak tag hanya di tooltip.
 *                Tahap dibaca dari config('devlib.stage') (aplikasi mengisi dari APP_STAGE di config/devlib.php; nilai selain alpha | beta | rc | kosong dianggap kosong). Tidak membaca env() langsung.
 *                Jumlah commit = `git rev-list --count HEAD` pada jalur TETAP yang diberikan aplikasi (bukan input pengguna), disimpan di storage/framework/cache dengan kunci waktu ubah `.git/logs/HEAD` (naik tiap commit),
 *                jadi git hanya dijalankan sekali per commit baru. Git tidak ada / gagal = berkas VERSION di jalur itu = '0.0'.
 * Output       : of($basePath) dan library($basePath) = ['text', 'letter', 'title', 'stage'], dipakai x-dl::env-badge part=version lewat binding 'devlib.version'.
 * Dipakai oleh : aplikasi pewaris dan situs katalog (binding devlib.version di AppServiceProvider).
 * Status       : Aktif.
 */
final class AppVersion
{
    public const STAGES = ['alpha' => ['A', 'alpha'], 'beta' => ['B', 'beta'], 'rc' => ['RC', 'release candidate']];

    /** Versi aplikasi untuk ditampilkan. @return array{text: string, letter: string, title: string, stage: string} */
    public static function of(string $basePath, ?string $stage = null): array
    {
        $stage = self::stage($stage);
        if ($stage === '' && ($tag = self::tag($basePath, ['v[0-9]*', '[0-9]*'])) !== null) {
            return ['text' => 'ver '.ltrim($tag, 'v'), 'letter' => '', 'title' => 'release '.$tag, 'stage' => ''];
        }
        $number = '0.'.self::commits($basePath);
        [$letter, $word] = self::STAGES[$stage] ?? ['', ''];

        return ['text' => 'ver '.$number, 'letter' => $letter, 'title' => $word !== '' ? $word : 'ver '.$number, 'stage' => $stage];
    }

    /** Versi pustaka DevLib: tag rilis terakhir; jumlah commit sejak tag di tooltip. */
    public static function library(string $basePath): array
    {
        $tag = self::tag($basePath, ['v[0-9]*']);
        if ($tag === null) {
            return ['text' => 'v0.0', 'letter' => '', 'title' => 'no release tag', 'stage' => ''];
        }
        $since = self::git($basePath, 'rev-list --count '.escapeshellarg($tag).'..HEAD');

        return ['text' => $tag, 'letter' => '', 'title' => $tag.($since !== null && (int) $since > 0 ? ' + '.(int) $since.' commits' : ''), 'stage' => ''];
    }

    /** Tahap dari parameter atau config('devlib.stage'): alpha | beta | rc, selain itu ''. */
    public static function stage(?string $value = null): string
    {
        $value = strtolower(trim((string) ($value ?? config('devlib.stage', ''))));

        return isset(self::STAGES[$value]) ? $value : '';
    }

    /** Jumlah commit; cache per commit baru, cadangan berkas VERSION, lalu '0'. */
    public static function commits(string $basePath): string
    {
        $key = self::stamp($basePath);
        $cache = storage_path('framework/cache/devlib-appver-'.md5($basePath).'.json');
        if ($key !== null && is_file($cache)) {
            $saved = json_decode((string) @file_get_contents($cache), true);
            if (is_array($saved) && ($saved['key'] ?? null) === $key && isset($saved['count'])) {
                return (string) $saved['count'];
            }
        }
        $count = self::git($basePath, 'rev-list --count HEAD');
        if ($count !== null && ctype_digit($count)) {
            if ($key !== null) {
                @file_put_contents($cache, json_encode(['key' => $key, 'count' => $count]));   // shortcut: gagal menulis = hitung lagi tiap permintaan (git cepat), tidak mengganggu
            }

            return $count;
        }
        $file = trim((string) @file_get_contents(rtrim($basePath, '/\\').DIRECTORY_SEPARATOR.'VERSION'));

        return preg_match('/^0\.(\d+)$/', $file, $m) ? $m[1] : '0';
    }

    /** Waktu ubah log HEAD (berubah tiap commit); null bila bukan folder .git biasa. */
    private static function stamp(string $basePath): ?string
    {
        $git = rtrim($basePath, '/\\').DIRECTORY_SEPARATOR.'.git';
        if (! is_dir($git)) {
            return null;
        }
        $t = @filemtime($git.DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR.'HEAD') ?: @filemtime($git.DIRECTORY_SEPARATOR.'HEAD');

        return $t ? (string) $t : null;
    }

    private static function tag(string $basePath, array $matches): ?string
    {
        $arg = collect($matches)->map(fn (string $m) => ' --match '.escapeshellarg($m))->implode('');
        $tag = self::git($basePath, 'describe --tags --abbrev=0'.$arg);

        return $tag !== null && $tag !== '' ? $tag : null;
    }

    /** Menjalankan git pada jalur tetap; hasil kosong / gagal = null. $args disusun kode ini, tidak pernah dari input pengguna. */
    private static function git(string $basePath, string $args): ?string
    {
        if (! function_exists('exec') || ! is_dir($basePath)) {
            return null;
        }
        $out = [];
        $code = 1;
        @exec('git -C '.escapeshellarg($basePath).' '.$args.' 2>&1', $out, $code);

        return $code === 0 && isset($out[0]) ? trim($out[0]) : null;
    }
}
