<?php

namespace Devlib\Chat;

// Penyimpanan pesan, diikat aplikasi (tabel, koneksi, indeks = urusan aplikasi). Kanal pasangan dijaga UNIQUE lewat Channel::key().
interface ChatStore
{
    public function append(string|int $from, string|int $to, string $text, ?Attachment $attachment = null): Message;

    /** Satu halaman percakapan dua orang, terlama dulu; before = hanya id yang lebih kecil. */
    public function between(string|int $a, string|int $b, ?int $before, int $limit): array;

    /** Pesan yang melibatkan $user (masuk atau keluar) dengan id > $afterId, terlama dulu. */
    public function after(string|int $user, int $afterId): array;

    /** Ada pesan lebih lama dari $beforeId di percakapan ini? */
    public function hasOlder(string|int $a, string|int $b, int $beforeId): bool;

    /** $reader sudah membaca pesan $peer sampai id ini (hanya maju). */
    public function markRead(string|int $reader, string|int $peer, int $upToId): void;

    /** Id pesan terakhir milik $reader yang sudah dibaca $peer (untuk centang dibaca). */
    public function readBy(string|int $peer, string|int $reader): int;

    /** Jumlah pesan dari $peer yang belum dibaca $me. */
    public function unread(string|int $me, string|int $peer): int;
}
