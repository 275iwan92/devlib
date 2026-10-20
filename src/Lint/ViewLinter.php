<?php

namespace Devlib\Lint;

// Pemeriksa statis berkas Blade layar (demo katalog / view proyek): HTML mentah, hex warna, <script> inline, grid tanpa kind, form dokumen tanpa back,
// isian form dokumen tanpa nilai awal. Input: isi berkas. Output: Finding[]. Dipakai oleh: devlib:lint. Status: opt-in, hanya membaca.
final class ViewLinter
{
    private const RAW_TAGS = 'table|button|input|select|textarea|form';

    private const FIELDS = ['text', 'select', 'date', 'money', 'number', 'lookup'];

    /** @return list<Finding> */
    public static function lint(string $source, string $file): array
    {
        $out = [];
        // Komentar Blade dibuang tapi panjangnya dijaga (spasi/baris baru) supaya nomor baris tetap benar.
        $src = preg_replace_callback('/\{\{--.*?--\}\}|<!--.*?-->/s', fn ($m) => preg_replace('/[^\n]/', ' ', $m[0]), $source);
        $add = function (string $code, string $msg, string $level, int $offset) use (&$out, $src, $file) {
            $out[] = new Finding($code, $msg, $level, $file.':'.(substr_count($src, "\n", 0, $offset) + 1));
        };

        $layout = (bool) preg_match('/<!doctype|<html\b/i', $src);  // berkas layout memang memuat <script>/<link> aset

        if (! $layout) {
            if (preg_match_all('/<script\b(?![^>]*\bsrc=)/i', $src, $m, PREG_OFFSET_CAPTURE)) {
                foreach ($m[0] as [, $o]) {
                    $add('L002', '<script> inline: perilaku harus jadi komponen/JS DevLib.', Finding::ERROR, $o);
                }
            }
            if (preg_match_all('/<('.self::RAW_TAGS.')\b/i', $src, $m, PREG_OFFSET_CAPTURE)) {
                foreach ($m[0] as [$t, $o]) {
                    $add('L001', 'HTML mentah '.$t.': pakai x-dl::*.', Finding::ERROR, $o);
                }
            }
        }
        if (preg_match_all('/(?<![&\w\/])#(?:[0-9a-fA-F]{8}|[0-9a-fA-F]{6})\b|:\s*#[0-9a-fA-F]{3}\b/', $src, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[0] as [$t, $o]) {
                $add('L003', 'Hex warna '.trim($t, ': ').': pakai token tema.', Finding::ERROR, $o);
            }
        }

        foreach (self::tags($src, 'x-dl::grid') as [$attrs, $o]) {
            $mode = self::attr($attrs, 'mode');
            $kind = self::attr($attrs, 'kind');
            if ($mode !== 'report' && $kind === null) {
                $add('G101', 'Grid entry tanpa kind="master|transactional" (perilaku lama).', Finding::INFO, $o);
            }
            if ($kind === 'master' && ! preg_match('/(^|\s):?toggle-url=/', $attrs)) {
                $add('G110', 'Grid master tanpa :toggle-url (Aktif/Nonaktif hanya pratinjau).', Finding::ERROR, $o);
            }
            if ($kind === 'transactional' && ! preg_match("/['\"]type['\"]\s*=>\s*['\"]badge['\"]/", $attrs)) {
                $add('G120', 'Grid transaksional tanpa kolom Status type=badge.', Finding::WARN, $o);
            }
            if ($kind === 'transactional' && preg_match('/(^|\s):?row-tone=/', $attrs)) {
                $add('G122', 'Grid transaksional: row-tone tidak dipakai.', Finding::ERROR, $o);
            }
        }

        $closing = [];
        preg_match_all('/<\/x-dl::form>/', $src, $closing, PREG_OFFSET_CAPTURE);
        foreach (self::tags($src, 'x-dl::form') as [$attrs, $o, $end]) {
            if (self::attr($attrs, 'kind') !== 'document') {
                continue;
            }
            if (! preg_match('/(^|\s):back=|(^|\s)back=/', $attrs)) {
                $add('F101', 'Form dokumen tanpa :back ke grid modul.', Finding::ERROR, $o);
            }
            $stop = strlen($src);
            foreach ($closing[0] as [, $co]) {
                if ($co > $end) {
                    $stop = $co;
                    break;
                }
            }
            $body = substr($src, $end, $stop - $end);
            foreach (self::FIELDS as $ft) {
                foreach (self::tags($body, 'x-dl::field.'.$ft) as [$fa, $fo]) {
                    if (! preg_match('/(^|\s):?(value|selected|default)=|\breadonly\b|:?options-default=/', $fa)) {
                        $add('F201', 'Isian field.'.$ft.' ('.(self::attr($fa, 'name') ?? '?').') tanpa nilai awal: isian default tidak kosong.', Finding::WARN, $end + $fo);
                    }
                }
            }
        }

        return $out;
    }

    /** @return list<array{0: string, 1: int, 2: int}> [atribut, offset awal, offset akhir tag] */
    private static function tags(string $src, string $tag): array
    {
        $found = [];
        $n = strlen($src);
        $needle = '<'.$tag;
        for ($p = 0; ($p = strpos($src, $needle, $p)) !== false; $p += strlen($needle)) {
            $after = $src[$p + strlen($needle)] ?? '';
            if (! ctype_space($after) && $after !== '/' && $after !== '>') {
                continue;  // x-dl::grid-foo bukan x-dl::grid
            }
            $q = null;
            for ($i = $p + strlen($needle); $i < $n; $i++) {
                $c = $src[$i];
                if ($q) {
                    $c === $q && $q = null;
                } elseif ($c === '"' || $c === "'") {
                    $q = $c;
                } elseif ($c === '>' && $src[$i - 1] !== '=') {
                    break;
                }
            }
            $found[] = [substr($src, $p + strlen($needle), $i - $p - strlen($needle)), $p, $i + 1];
        }

        return $found;
    }

    private static function attr(string $attrs, string $name): ?string
    {
        return preg_match('/(?:^|\s)'.preg_quote($name, '/').'=(?:"([^"]*)"|\'([^\']*)\')/', $attrs, $m) ? ($m[1] !== '' ? $m[1] : ($m[2] ?? '')) : null;
    }
}
