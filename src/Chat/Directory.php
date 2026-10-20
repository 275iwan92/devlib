<?php

namespace Devlib\Chat;

// Siapa saja yang boleh di-chat (semua user aktif, satu cabang, satu jabatan, ...). Diikat aplikasi.
interface Directory
{
    /** @return list<Person> semua orang yang bisa di-chat, termasuk si pemanggil */
    public function people(): array;

    public function find(string|int $id): ?Person;
}
