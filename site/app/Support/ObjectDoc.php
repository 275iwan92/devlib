<?php

namespace App\Support;

use Devlib\Components\DevlibComponent;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

// Cerita satu object DevLib untuk halaman katalog "OO Methods and Properties": semua yang bisa dibaca dari kode DIBACA dari kode
// (header REMARK, komentar kelas, konstruktor lewat refleksi, method PHP lewat refleksi, method JS dari berkas kelas JS); hanya arti
// singkat tiap butir ditulis tangan di ObjectNotes dan dicocokkan dengan kode oleh tes (butir baru tanpa catatan = tes gagal).
// Input: nama kelas object (+ nama kelas turunan contoh dan berkas demo JS-nya). Output: array untuk tabel / blok kode. Dipakai oleh: demo oo-*.
final class ObjectDoc
{
    public function __construct(public readonly string $class, public readonly ?string $child = null, public readonly ?string $childJs = null) {}

    // Object kerja halaman p5-override: Kanban, turunan contoh KanbanProduksi, JS turunannya ada di demo oo-override.
    public static function kanban(): self
    {
        return new self(\Devlib\Components\Kanban::class, \App\View\Components\Demo\KanbanProduksi::class, resource_path('views/demos/oo-override.blade.php'));
    }

    public function file(): string
    {
        return (string) (new ReflectionClass($this->class))->getFileName();
    }

    public function tag(): string
    {
        return DevlibComponent::describe($this->class)[0];
    }

    public function notes(): array
    {
        return ObjectNotes::for($this->class);
    }

    /** @return array<string, string> bidang header REMARK (Tujuan, Sumber hulu, Input, ...) apa adanya */
    public function remark(): array
    {
        preg_match('~/\*\s*\n\s*\*\s*REMARK\b(.*?)\*/~s', (string) file_get_contents($this->file()), $m);
        $out = [];
        foreach (preg_split('/\R/', $m[1] ?? '') as $line) {
            if (preg_match('/^\s*\*\s*([^:]+?)\s*:\s*(.+?)\s*$/', $line, $f)) {
                $out[$f[1]] = $f[2];
            }
        }

        return $out;
    }

    // Komentar // tepat di atas deklarasi kelas (tujuan + bentuk data array), tanpa awalan.
    public function classComment(): string
    {
        $short = (new ReflectionClass($this->class))->getShortName();
        preg_match('~((?:^//[^\n]*\n)+)(?:final |abstract )?class '.$short.'\b~m', str_replace("\r\n", "\n", (string) file_get_contents($this->file())), $m);

        return trim(preg_replace('~^// ?~m', '', $m[1] ?? ''));
    }

    /** @return list<array{name: string, attr: string, type: string, default: string, meaning: string, example: string, child: string}> */
    public function props(): array
    {
        $ref = new ReflectionClass($this->class);
        $notes = $this->notes()['props'] ?? [];
        $child = $this->child ? new ($this->child)() : null;
        $rows = [];
        foreach ($ref->getConstructor()?->getParameters() ?? [] as $p) {
            $default = $p->isDefaultValueAvailable() ? $p->getDefaultValue() : null;
            $value = $child?->{$p->getName()};
            $rows[] = [
                'name' => $p->getName(),
                'attr' => \Illuminate\Support\Str::kebab($p->getName()),
                'type' => ($t = $p->getType()) instanceof ReflectionNamedType ? ($t->allowsNull() && $t->getName() !== 'mixed' ? '?' : '').$t->getName() : 'mixed',
                'default' => self::short($default),
                'meaning' => $notes[$p->getName()][0] ?? '',
                'example' => $notes[$p->getName()][1] ?? '',
                'child' => $child === null ? '' : ($value == $default ? 'inherited' : 'overridden: '.self::short($value)),
            ];
        }

        return $rows;
    }

    /** @return list<array{name: string, signature: string, from: string, can: string, use: string, child: string}> */
    public function phpMethods(): array
    {
        $ref = new ReflectionClass($this->class);
        $chain = [$ref->getName(), ...array_filter(class_parents($this->class), fn ($c) => is_a($c, DevlibComponent::class, true))];
        $notes = $this->notes()['php'] ?? [];
        $rows = [];
        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED) as $m) {
            if ($m->isStatic() || $m->isConstructor() || ! in_array($m->getDeclaringClass()->getName(), $chain, true)) {
                continue;
            }
            $params = implode(', ', array_map(fn ($p) => '$'.$p->getName(), $m->getParameters()));
            $rows[] = [
                'name' => $m->getName(),
                'signature' => $m->getName().'('.$params.')'.($m->hasReturnType() ? ': '.$m->getReturnType() : ''),
                'from' => $m->getDeclaringClass()->getShortName(),
                'can' => $notes[$m->getName()][0] ?? '',
                'use' => $notes[$m->getName()][1] ?? '',
                'child' => $this->child ? (((new ReflectionMethod($this->child, $m->getName()))->getDeclaringClass()->getName() === $this->child) ? 'overrides' : 'inherits') : '',
            ];
        }

        return $rows;
    }

    public function jsFile(): ?string
    {
        $rel = 'resources/assets/js/components/'.\Illuminate\Support\Str::kebab((new ReflectionClass($this->class))->getShortName()).'.js';

        return is_file(base_path('../'.$rel)) ? $rel : null;
    }

    /** @return list<array{name: string, signature: string, from: string, can: string, use: string, child: string}> */
    public function jsMethods(): array
    {
        if (! $this->jsFile()) {
            return [];
        }
        $short = (new ReflectionClass($this->class))->getShortName();
        $notes = $this->notes()['js'] ?? [];
        $own = self::jsClassMethods((string) file_get_contents(base_path('../'.$this->jsFile())), $short);
        $base = self::jsClassMethods((string) file_get_contents(base_path('../resources/assets/js/core.js')), 'DlComponent');
        $overridden = $this->childJs ? array_keys(self::jsClassMethods($this->childJsBody(), (new ReflectionClass((string) $this->child))->getShortName())) : [];
        $rows = [];
        foreach ([$short => $own, 'Devlib.Component' => array_diff_key($base, $own)] as $from => $methods) {
            foreach ($methods as $name => $args) {
                $rows[] = [
                    'name' => $name,
                    'signature' => $name.'('.$args.')',
                    'from' => $from,
                    'can' => $notes[$name][0] ?? '',
                    'use' => $notes[$name][1] ?? '',
                    'child' => $this->childJs ? (in_array($name, $overridden, true) ? 'overrides' : 'inherits') : '',
                ];
            }
        }

        return $rows;
    }

    // Isi <script> demo turunan JS (contoh asli, dibaca dari berkas, tidak disalin).
    public function childJsBody(): string
    {
        preg_match('~<script>\s*\n(.*?)</script>~s', str_replace("\r\n", "\n", (string) file_get_contents((string) $this->childJs)), $m);

        return rtrim($m[1] ?? '');
    }

    // Baris tabel "From the source": header REMARK apa adanya + tag + kelas PHP + berkas JS.
    /** @return list<array{field: string, value: string}> */
    public function sourceRows(): array
    {
        $rows = array_map(fn ($k, $v) => ['field' => $k, 'value' => $v], array_keys($this->remark()), $this->remark());

        return [...$rows, ['field' => 'Tag', 'value' => $this->tag()], ['field' => 'PHP class', 'value' => $this->class], ['field' => 'JavaScript file', 'value' => (string) $this->jsFile()]];
    }

    public function childPhpPath(): string
    {
        return self::relative((string) (new ReflectionClass((string) $this->child))->getFileName());
    }

    public function childJsPath(): string
    {
        return self::relative((string) $this->childJs);
    }

    private static function relative(string $path): string
    {
        $root = str_replace('\\', '/', dirname(base_path()));

        return ltrim(str_replace($root, '', str_replace('\\', '/', $path)), '/');
    }

    public function childPhpSource(): string
    {
        return rtrim(str_replace("\r\n", "\n", (string) file_get_contents((string) (new ReflectionClass((string) $this->child))->getFileName())));
    }

    // Nama method sebuah kelas JS => daftar argumen, dibaca dari berkas (method = sejajar baris pertama isi kelas; kelas ditutup sejajar deklarasinya).
    /** @return array<string, string> */
    public static function jsClassMethods(string $js, string $class): array
    {
        $js = str_replace("\r\n", "\n", $js);
        if (! preg_match('/^( *)class '.preg_quote($class, '/').'\b[^\n]*\{\n(.*?)\n\1\}\n/ms', $js, $body)) {
            return [];
        }
        preg_match('/^( *)\S/', $body[2], $member);   // indentasi anggota = baris pertama isi kelas
        preg_match_all('/^'.$member[1].'(?:async +)?(?:static +)?([\w$]+)\(([^)]*)\)\s*\{/m', $body[2], $m, PREG_SET_ORDER);
        $out = [];
        foreach ($m as [, $name, $args]) {
            $name === 'constructor' || $out[$name] = trim($args);
        }

        return $out;
    }

    private static function short(mixed $v): string
    {
        $s = json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return mb_strlen((string) $s) > 64 ? mb_substr((string) $s, 0, 61).'...' : (string) $s;
    }
}
