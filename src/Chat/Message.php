<?php

namespace Devlib\Chat;

// Satu pesan. id naik terus (urutan + penanda "sudah dibaca"); at = epoch detik.
final class Message
{
    public function __construct(
        public readonly int $id,
        public readonly string|int $from,
        public readonly string|int $to,
        public readonly string $text,
        public readonly int $at,
        public readonly ?Attachment $attachment = null,
    ) {}

    /** Bentuk MSG yang dibaca x-dl::chat (lihat kepala Devlib\Components\Chat). */
    public function toArray(string|int $viewer): array
    {
        return array_filter(['id' => $this->id, 'me' => (string) $this->from === (string) $viewer, 't' => $this->text, 'ts' => $this->at,
            'file' => $this->attachment ? ['name' => $this->attachment->name, 'size' => $this->attachment->size,
                'url' => $this->attachment->url, 'image' => $this->attachment->image] : null], fn ($v) => $v !== null);
    }
}
