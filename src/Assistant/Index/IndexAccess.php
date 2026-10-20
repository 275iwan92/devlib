<?php

namespace Devlib\Assistant\Index;

use Devlib\Assistant\Asker;
use Devlib\Assistant\DataPolicy;
use Devlib\Assistant\ScopedValueAsker;
use Devlib\Assistant\Sensitivity;
use Devlib\Assistant\ValueAsker;

// Satu-satunya aturan "penanya ini boleh melihat entri ini?". Dipakai IndexStore::search, dan dipakai lagi oleh Assistant
// (sabuk pengaman bila penyimpanan program menyaring kurang).
final class IndexAccess
{
    // Hak nilai rupiah: ScopedValueAsker menentukan per kunci (id entri / kunci Tool); ValueAsker global; Asker biasa = tidak boleh.
    public static function seesValue(Asker $asker, string $key): bool
    {
        return match (true) {
            $asker instanceof ScopedValueAsker => $asker->canSeeValueOf($key),
            $asker instanceof ValueAsker => $asker->canSeeValue(),
            default => false,
        };
    }

    public static function may(Asker $asker, IndexEntry $entry, ?DataPolicy $policy = null): bool
    {
        return match (true) {
            $entry->sensitivity === Sensitivity::Forbidden => false,
            (bool) $policy?->excludes(...$entry->texts()) => false,
            $entry->module !== null && ! $asker->can($entry->module) => false,
            $asker->level() < $entry->minLevel => false,
            $entry->sensitivity === Sensitivity::Value => self::seesValue($asker, $entry->id),
            default => true,
        };
    }
}
