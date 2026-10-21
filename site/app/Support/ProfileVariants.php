<?php

namespace App\Support;

// Varian panel Profil halaman p7-profile: ?variant=tmerp|sstock|devlib; tanpa parameter atau kunci tak dikenal = tmerp (isi halaman yang disetujui tidak berubah).
// Tiap varian = nilai `without` x-dl::layer-shell di aplikasi asalnya; panelnya dirender dari komponen yang sama (x-dl::layer-menu) oleh demo profile.
final class ProfileVariants
{
    public const DEFAULT = 'tmerp';

    public const ALL = [
        'tmerp' => [
            'label' => 'tmerp',
            'summary' => 'Full panel: every item is on, with chat, assistant, account and the wide Edit profile and Help drawers supplied by the application.',
            'pattern' => 'Layer Shell with drawers, chat, assistant and account, nothing hidden',
            'without' => [],
        ],
        'sstock' => [
            'label' => 'sstock',
            'summary' => 'Simple panel without Chat and AI Assistant; Setup offers Language only, Help opens a guide page.',
            'pattern' => 'Layer Shell with a help page and no chat, assistant or drawers',
            'without' => ['chat', 'ai', 'theme'],
        ],
        'devlib' => [
            'label' => 'devlib',
            'summary' => 'Minimal panel of the catalog site: header, Change password and Sign out only.',
            'pattern' => 'Layer Shell with every optional item hidden',
            'without' => ['notif', 'chat', 'ai', 'edit', 'help', 'setup'],
        ],
    ];

    public static function key(): string
    {
        $key = (string) request()->query('variant');

        return array_key_exists($key, self::ALL) ? $key : self::DEFAULT;
    }

    public static function current(): array
    {
        return self::ALL[self::key()] + ['key' => self::key()];
    }
}
