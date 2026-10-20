<?php

namespace Devlib\Assistant\Index;

use Devlib\Assistant\Asker;

// Penyimpanan indeks, diikat program (tabelnya milik program). Indeks bersama satu per program, hasil per penanya.
interface IndexStore
{
    /** @return array<string, string> id => hash tersimpan untuk satu sumber */
    public function hashes(string $source): array;

    public function put(string $source, IndexEntry $entry): void;

    /** @param list<string> $ids */
    public function forget(string $source, array $ids): void;

    /**
     * Calon terbaik dulu. WAJIB sudah tersaring hak penanya: setiap hasil lolos IndexAccess::may($asker, $entry).
     *
     * @param  list<string>  $tokens  kata pertanyaan (huruf kecil)
     * @return list<IndexEntry>
     */
    public function search(array $tokens, Asker $asker, int $limit = 10): array;
}
