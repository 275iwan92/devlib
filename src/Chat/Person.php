<?php

namespace Devlib\Chat;

// Satu orang yang bisa di-chat. id = kunci user aplikasi (string atau int); photo = url / data-uri, kosong = inisial.
final class Person
{
    public function __construct(
        public readonly string|int $id,
        public readonly string $name,
        public readonly string $role = '',
        public readonly ?string $photo = null,
    ) {}
}
