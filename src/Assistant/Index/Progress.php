<?php

namespace Devlib\Assistant\Index;

// Hasil satu potongan kerja Indexer::pass(). `skipped` = id entri yang dilewati kebijakan data (bukan galat).
// `complete` false = anggaran habis, sisanya dikerjakan pada giliran berikut. `pauseMs` = jeda sebelum potongan berikut.
final class Progress
{
    /** @param list<string> $skipped */
    public function __construct(
        public readonly string $source,
        public readonly int $stored = 0,
        public readonly int $unchanged = 0,
        public readonly int $removed = 0,
        public readonly array $skipped = [],
        public readonly bool $complete = true,
        public readonly float $ms = 0,
        public readonly float $pauseMs = 0,
    ) {}
}
