<?php

namespace Devlib\Assistant;

// Daftar layar yang BOLEH dibuka si penanya. Diikat aplikasi; Assistant yang mencocokkan pertanyaan dengan daftar ini.
interface Navigator
{
    /**
     * Tiap layar: key (kode unik = kode tab), label, url, path? (letak di menu), words? (kata cari tambahan),
     * about? (satu kalimat kegunaan), help? (kunci topik bantuan layar itu), icon?.
     *
     * @return list<array<string, ?string>>
     */
    public function screens(Asker $asker): array;
}
