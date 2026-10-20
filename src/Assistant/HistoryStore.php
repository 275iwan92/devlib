<?php

namespace Devlib\Assistant;

// Penyimpanan riwayat percakapan, diikat aplikasi (tabel, koneksi, masa simpan = urusan aplikasi).
// Satu entri = satu pertanyaan + jawabannya, milik satu user. Juga bahan admin: jawaban UNKNOWN / jempol turun
// = bahan menambah Help atau frasa Tool.
interface HistoryStore
{
    /** @return int id entri */
    public function store(Asker $asker, string $question, Reply $reply): int;

    /** @return list<array{id: int, q: string, reply: array<string, mixed>, feedback: ?int}> terlama dulu */
    public function recent(Asker $asker, int $limit = 30): array;

    /** Jempol naik (1) / turun (-1) / dicabut (0) pada entri milik si penanya sendiri. */
    public function feedback(Asker $asker, int $id, int $value): bool;
}
