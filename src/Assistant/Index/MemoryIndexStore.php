<?php

namespace Devlib\Assistant\Index;

use Devlib\Assistant\Asker;
use Devlib\Assistant\DataPolicy;

// IndexStore dalam memori untuk tes dan demo. Penyimpanan program (database) menyaring dengan IndexAccess::may yang sama.
final class MemoryIndexStore implements IndexStore
{
    /** @var array<string, array<string, IndexEntry>> */
    private array $rows = [];

    public function __construct(private ?DataPolicy $policy = null) {}

    public function hashes(string $source): array
    {
        return array_map(fn (IndexEntry $e) => $e->hash, $this->rows[$source] ?? []);
    }

    public function put(string $source, IndexEntry $entry): void
    {
        $this->rows[$source][$entry->id] = $entry;
    }

    public function forget(string $source, array $ids): void
    {
        foreach ($ids as $id) {
            unset($this->rows[$source][$id]);
        }
    }

    public function search(array $tokens, Asker $asker, int $limit = 10): array
    {
        $scored = [];
        foreach ($this->all() as [, $entry]) {
            $hay = $this->words($entry->searchable());
            $hit = count(array_filter($tokens, fn ($t) => in_array($t, $hay, true)));
            if ($hit > 0 && IndexAccess::may($asker, $entry, $this->policy)) {
                $scored[] = [$hit, $entry];
            }
        }
        usort($scored, fn ($a, $b) => $b[0] <=> $a[0]);  // usort stabil sejak PHP 8: urutan masuk menang bila seri

        return array_map(fn ($s) => $s[1], array_slice($scored, 0, $limit));
    }

    /** @return list<array{0: string, 1: IndexEntry}> [sumber, entri] seluruh isi, tanpa penyaringan hak (untuk tes / audit). */
    public function all(): array
    {
        $out = [];
        foreach ($this->rows as $source => $entries) {
            foreach ($entries as $entry) {
                $out[] = [(string) $source, $entry];
            }
        }

        return $out;
    }

    /** @return list<string> */
    private function words(string $text): array
    {
        preg_match_all('/[\p{L}\p{N}]+/u', mb_strtolower($text), $m);

        return $m[0];
    }
}
