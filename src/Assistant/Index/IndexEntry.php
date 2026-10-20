<?php

namespace Devlib\Assistant\Index;

use Devlib\Assistant\Sensitivity;

// Satu butir indeks: layar (kind 'screen', butuh url) atau topik bantuan (kind 'help'). Metadata saja. `hash` dihitung dari isi,
// dasar "hanya yang berubah yang diproses". Syarat hak (module, minLevel, sensitivity) disimpan bersama entri; penyaringan
// terjadi saat bertanya (IndexAccess::may). Entri Value hanya dibawa NAMA-nya (nameOnly()); entri Forbidden tidak pernah disimpan.
final class IndexEntry
{
    public const SCREEN = 'screen';

    public const HELP = 'help';

    public readonly string $hash;

    /** @param list<string> $steps langkah bantuan (kind 'help') */
    public function __construct(
        public readonly string $id,
        public readonly string $kind,
        public readonly string $title,
        public readonly string $words = '',
        public readonly string $text = '',
        public readonly array $steps = [],
        public readonly ?string $path = null,
        public readonly ?string $url = null,
        public readonly ?string $module = null,
        public readonly int $minLevel = 0,
        public readonly Sensitivity $sensitivity = Sensitivity::Quantity,
    ) {
        $this->hash = md5(json_encode([$id, $kind, $title, $words, $text, $steps, $path, $url, $module, $minLevel, $sensitivity->value]));
    }

    /** Hanya nama: kata cari, uraian, dan langkah dibuang; syarat `value` tetap ikut. */
    public function nameOnly(): self
    {
        return new self($this->id, $this->kind, $this->title, path: $this->path, url: $this->url, module: $this->module,
            minLevel: $this->minLevel, sensitivity: $this->sensitivity);
    }

    /** @return list<string> semua teks entri, untuk pemeriksaan DataPolicy */
    public function texts(): array
    {
        return [$this->id, $this->title, $this->words, $this->text, $this->path ?? '', $this->module ?? '', ...$this->steps];
    }

    public function searchable(): string
    {
        return trim($this->title.' '.$this->words.' '.$this->text.' '.($this->path ?? ''));
    }
}
