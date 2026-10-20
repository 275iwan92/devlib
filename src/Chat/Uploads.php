<?php

namespace Devlib\Chat;

// Penerima lampiran berpotong (tidak bergantung pada upload_max_filesize PHP). Diikat aplikasi (disk privat).
interface Uploads
{
    /** Terima satu potong; kembalian = total byte yang sudah diterima untuk $uid. */
    public function receive(string $uid, int $offset, string $chunk): int;

    /** Susun potongan jadi lampiran milik $owner; $uid dibuang. */
    public function complete(string $uid, string $name, string|int $owner): Attachment;
}
