<?php

namespace Devlib\Engine;

// Engine yang menjelaskan dirinya sendiri untuk x-dl::engine-catalog: penjelasan hidup bersama kodenya, jadi tidak basi.
// details() mengembalikan kunci opsional katalog: 'inputs' => [[nama, arti]], 'outputs' => [[nama, arti]], 'steps' => [teks],
// 'reads' / 'writes' / 'settings' / 'used' / 'tests' / 'docs' => [..] (lihat EngineCatalog).
interface Described
{
    public function details(): array;
}
