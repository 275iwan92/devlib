<?php

namespace Devlib\Assistant;

// Satu kemampuan asisten = satu kelas. Angka dan hasil dihitung di sini oleh program (biasanya Devlib\Engine lewat
// EngineTool), BUKAN oleh model bahasa, supaya bisa diuji. Hak (module / minLevel) diperiksa Assistant sebelum answer().
interface Tool
{
    public function key(): string;

    public function title(): string;

    /** @return list<string> frasa pemicu huruf kecil ("volume discount", "diskon volume") */
    public function phrases(): array;

    /** Modul pemilik: penanya wajib boleh membukanya. null = terbuka untuk semua. */
    public function module(): ?string;

    /** Level keamanan minimum di atas hak modul (0 = cukup hak modul). */
    public function minLevel(): int;

    public function answer(Asker $asker, string $question): Reply;
}
