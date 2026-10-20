<?php

namespace Devlib\Chat;

// Kanal = tempat pesan berkumpul. Sekarang hanya langsung (dua orang); satu pasangan = satu kanal, kuncinya sama
// dari arah mana pun ("<id kecil>:<id besar>") sehingga penyimpanan bisa menjaganya UNIQUE.
final class Channel
{
    public static function key(string|int $a, string|int $b): string
    {
        $pair = [(string) $a, (string) $b];
        sort($pair, SORT_STRING);

        return implode(':', $pair);
    }
}
