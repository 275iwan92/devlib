<?php

namespace Devlib;

use Devlib\Components\DevlibComponent;
use ReflectionClass;
use ReflectionNamedType;

// Manifest komponen: menelusuri SEMUA kelas turunan DevlibComponent di src/Components lewat refleksi (tag, prop + tipe + nilai bawaan, tujuan dari
// komentar kepala, berkas) supaya skill mencari komponen dari skills/devlib/components.md, bukan membaca kelas satu per satu.
// Input: folder src/Components. Output: array rekaman / teks Markdown. Dipakai oleh: perintah devlib:manifest dan tes kontrak. Status: aktif.
final class Manifest
{
    /** @return list<array{class: string, tag: string, file: string, view: ?string, purpose: string, remark: bool, abstract: bool, props: list<array{name: string, type: string, default: string}>}> */
    public static function collect(): array
    {
        $root = str_replace('\\', '/', __DIR__).'/Components';
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        $rows = [];
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $rel = substr(str_replace('\\', '/', $file->getPathname()), strlen($root) + 1, -4);  // mis. Field/Text
            $class = 'Devlib\\Components\\'.str_replace('/', '\\', $rel);
            if (! class_exists($class) || ! is_subclass_of($class, DevlibComponent::class)) {
                continue;
            }
            $ref = new ReflectionClass($class);
            [$tag, $view] = DevlibComponent::describe($class);
            $src = (string) file_get_contents($file->getPathname());
            $rows[] = [
                'class' => $class,
                'tag' => $tag,
                'file' => 'src/Components/'.$rel.'.php',
                'view' => $view ? substr(str_replace('\\', '/', $view), strpos(str_replace('\\', '/', $view), 'resources/views')) : null,
                'purpose' => self::purpose($src),
                'remark' => (bool) preg_match('/^\s*\/\*\s*\R\s*\*\s*REMARK/m', $src),
                'abstract' => $ref->isAbstract(),
                'props' => self::props($ref),
            ];
        }
        usort($rows, fn ($a, $b) => strcmp($a['tag'], $b['tag']));

        return $rows;
    }

    private static function purpose(string $src): string
    {
        if (preg_match('/^\s*\*\s*Tujuan\s*:\s*(.+)$/m', $src, $m)) {
            return trim($m[1]);
        }
        // Komentar // tepat di atas deklarasi kelas, atau komentar // pertama di berkas.
        if (preg_match('/((?:^\/\/[^\n]*\n)+)\s*(?:abstract\s+|final\s+)?class\s/m', $src, $m) || preg_match('/^((?:\/\/[^\n]*\n)+)/m', $src, $m)) {
            return trim(preg_replace('/^\/\/\s?/', '', explode("\n", trim($m[1]))[0]));
        }

        return '';
    }

    private static function props(ReflectionClass $ref): array
    {
        $props = [];
        foreach ($ref->getConstructor()?->getParameters() ?? [] as $p) {
            $t = $p->getType();
            $type = $t instanceof ReflectionNamedType ? ($t->allowsNull() && $t->getName() !== 'mixed' ? '?' : '').$t->getName() : ($t ? (string) $t : 'untyped');
            if ($p->isDefaultValueAvailable()) {
                $d = $p->getDefaultValue();
                $default = is_array($d) ? ($d ? '[…]' : '[]') : (is_string($d) ? "'".(strlen($d) > 24 ? substr($d, 0, 24).'…' : $d)."'" : var_export($d, true));
            } else {
                $default = '(wajib)';
            }
            $props[] = ['name' => $p->getName(), 'type' => $type, 'default' => $default];
        }

        return $props;
    }

    public static function markdown(): string
    {
        $out = "# Manifest komponen DevLib\n\n";
        $out .= "Dibuat otomatis oleh `php artisan devlib:manifest` — JANGAN diedit tangan. Cari tag di sini; baca kelas/view hanya untuk komponen yang dipakai.\n";
        $out .= "Prop = parameter konstruktor `nama:tipe=bawaan` (di Blade: kebab-case, mis. `:open-url`).\n\n";
        foreach (self::collect() as $r) {
            if ($r['abstract']) {
                continue;
            }
            $props = implode(', ', array_map(fn ($p) => "{$p['name']}:{$p['type']}={$p['default']}", $r['props']));
            $out .= "## `<{$r['tag']}>`\n";
            $out .= ($r['purpose'] !== '' ? $r['purpose'] : '(tanpa keterangan)')."\n";
            $out .= '- Berkas: `'.$r['file'].'`'.($r['view'] ? ', tampilan `'.$r['view'].'`' : '')."\n";
            $out .= '- Prop: '.($props !== '' ? $props : '(tanpa prop)')."\n\n";
        }

        return $out;
    }
}
