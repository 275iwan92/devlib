<?php

namespace Devlib\Lint;

// Kolektor penegak aturan baku (skills/devlib): opt-in, mati secara bawaan (nol biaya). Saat dinyalakan, komponen yang dirender
// (lewat DevlibComponent::dl()) mencatat Finding dari GridRules / FormRules; tes dan perintah devlib:lint --render membacanya.
final class Lint
{
    private static bool $on = false;

    /** @var list<Finding> */
    private static array $found = [];

    public static function enable(bool $on = true): void
    {
        self::$on = $on;
        self::$found = [];
    }

    public static function on(): bool
    {
        return self::$on;
    }

    /** @param list<Finding> $findings */
    public static function record(array $findings): void
    {
        array_push(self::$found, ...$findings);
    }

    /** @return list<Finding> */
    public static function findings(?string $level = null): array
    {
        return array_values(array_filter(self::$found, fn (Finding $f) => $level === null || $f->level === $level));
    }
}
