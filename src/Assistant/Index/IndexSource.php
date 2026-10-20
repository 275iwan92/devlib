<?php

namespace Devlib\Assistant\Index;

// Satu jenis sumber indeks, diikat program: menu, layar, Help, frasa Tool (hanya metadata, bukan baris data bisnis).
// Program mendaftarkannya dengan tag container `devlib.index-source`. id entri unik di dalam satu sumber.
interface IndexSource
{
    /** Nama sumber ('menu', 'help', ...): kunci kelompok di IndexStore. */
    public function key(): string;

    /** @return iterable<IndexEntry> */
    public function entries(): iterable;
}
