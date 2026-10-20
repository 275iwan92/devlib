<?php

namespace Devlib\Chat;

// Kehadiran. Online = sedang login dan denyut terakhir masih dalam batas waktu (batas = urusan implementasi).
// touch() dipanggil tiap poll; signOut() saat logout supaya langsung offline.
interface Presence
{
    public function touch(string|int $user): void;

    public function signOut(string|int $user): void;

    public function isOnline(string|int $user): bool;

    /** Epoch detik terakhir terlihat, null = belum pernah. */
    public function lastSeen(string|int $user): ?int;
}
