<?php

namespace Devlib\Assistant;

// Orang yang bertanya, dilihat dari keamanan aplikasi pemakai. Asisten tidak punya aturan hak sendiri:
// semua keputusan "boleh / tidak" bertanya ke sini. Diikat aplikasi.
interface Asker
{
    /** Kunci user untuk riwayat percakapan. */
    public function id(): string;

    public function name(): string;

    /** Level keamanan (angka; makin besar makin tinggi). */
    public function level(): int;

    /** Boleh membuka modul ini menurut keamanan aplikasi. */
    public function can(string $module): bool;
}
