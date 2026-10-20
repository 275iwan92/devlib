<?php

namespace Devlib\Engine;

use Closure;
use Illuminate\Support\Facades\DB;

// Alur baku CANCEL dokumen transaksional (aturan pemilik 2026-10-20): record menjadi CANCELLED dan TETAP tersimpan (bukan DELETE);
// bila dokumen Adjustable, record ADJUSTMENT pembalik dibuat OTOMATIS dalam transaksi yang sama. Pembalik gagal = pembatalan ikut batal,
// jadi data tidak pernah setengah jadi.
final class Cancellation
{
    /** @param Closure(object): mixed $markCancelled ubah status dokumen jadi CANCELLED (tanpa menghapus) */
    public static function run(object $document, Closure $markCancelled, ?string $connection = null): Result
    {
        $adjustment = null;
        DB::connection($connection)->transaction(function () use ($document, $markCancelled, &$adjustment): void {
            $markCancelled($document);
            $adjustment = $document instanceof Adjustable ? $document->createAdjustment() : null;
            if ($adjustment?->isRejected()) {
                throw new EngineException($document::class, 'Adjustment was rejected: '.implode(' ', $adjustment->reasons));
            }
        });
        event(new DocumentCancelled($document, $adjustment));

        return $adjustment ?? Result::applied([], ['Cancelled; no adjustment needed.']);
    }
}
