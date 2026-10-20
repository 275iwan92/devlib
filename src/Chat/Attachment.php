<?php

namespace Devlib\Chat;

// Lampiran satu pesan. File disimpan di penyimpanan privat aplikasi (bukan di database); url hanya boleh dibuka dua peserta.
final class Attachment
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly int $size,
        public readonly string $url,
        public readonly bool $image = false,
    ) {}
}
