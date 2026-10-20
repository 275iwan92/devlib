<?php

namespace Devlib\Assistant\Index;

use Closure;

// Anggaran kerja pengindeks. ANGGARAN, bukan jaminan CPU: PHP tidak bisa menjanjikan persentase prosesor. Yang dijanjikan:
// satu potongan kerja berhenti setelah `ms` milidetik atau `size` entri tersimpan (mana yang lebih dulu), lalu menunggu
// `pause` x lama kerjanya (9 = sekitar 10% waktu dinding), paling banyak `chunks` potongan per satu kali jalan.
// `clock` (mengembalikan milidetik) hanya untuk tes.
final class Budget
{
    public function __construct(
        public readonly float $ms = 200,
        public readonly int $size = 200,
        public readonly float $pause = 9,
        public readonly int $chunks = 20,
        private readonly ?Closure $clock = null,
    ) {}

    public function now(): float
    {
        return $this->clock ? (float) ($this->clock)() : hrtime(true) / 1e6;
    }
}
