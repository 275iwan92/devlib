<?php

namespace Devlib\Assistant;

// Isi bantuan aplikasi (Help / UG) dalam bahasa si penanya. Asisten hanya MENGUTIP dari sini, tidak pernah mengarang.
interface Knowledge
{
    /**
     * Tiap topik: key, title, purpose?, words? (kata cari), steps? (daftar teks berurutan).
     *
     * @return list<array<string, mixed>>
     */
    public function topics(Asker $asker): array;
}
