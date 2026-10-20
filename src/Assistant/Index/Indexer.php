<?php

namespace Devlib\Assistant\Index;

use Devlib\Assistant\DataPolicy;
use Devlib\Assistant\Sensitivity;

// Satu potongan kerja pengindeks (docs/assistant-indexer.md). Idempoten: membandingkan hash, hanya yang berubah disimpan,
// yang hilang dari sumber dibuang (forget) bila seluruh sumber sudah terbaca. Anggaran habis = berhenti, lanjut giliran berikut.
// Entri yang dilarang DataPolicy::excludes() (gaji / payroll / formula HPP / data pribadi / orang terlindung; kunci, judul, kata
// cari, uraian, langkah, path, modul) atau berlabel Forbidden dilewati dan dicatat di Progress::$skipped, tidak pernah disimpan,
// dan bila sudah ada dari putaran lama dibuang. Entri Value disimpan sebagai NAMA saja.
// shortcut: membaca ulang sumber dari awal tiap potongan (entri tak berubah hanya dibandingkan hash); sumber sangat besar
// perlu kursor, naikkan bila waktu baca sumber melebihi anggaran.
final class Indexer
{
    public function __construct(private ?DataPolicy $policy = null) {}

    public function pass(IndexSource $source, IndexStore $store, Budget $budget = new Budget): Progress
    {
        $start = $budget->now();
        $key = $source->key();
        $stored = $store->hashes($key);
        $seen = [];
        $skipped = [];
        $put = 0;
        $same = 0;
        $complete = true;

        foreach ($source->entries() as $entry) {
            if ($entry->sensitivity === Sensitivity::Forbidden || $this->policy?->excludes(...$entry->texts())) {
                $skipped[] = $entry->id;

                continue;
            }
            if ($entry->sensitivity === Sensitivity::Value) {
                $entry = $entry->nameOnly();
            }
            $seen[$entry->id] = true;
            if (($stored[$entry->id] ?? null) === $entry->hash) {
                $same++;

                continue;
            }
            // sekurangnya satu entri per potongan supaya selalu ada kemajuan
            if ($put > 0 && ($put >= $budget->size || $budget->now() - $start >= $budget->ms)) {
                $complete = false;
                break;
            }
            $store->put($key, $entry);
            $put++;
        }

        $gone = [];
        if ($complete) {
            $gone = array_values(array_diff(array_map('strval', array_keys($stored)), array_map('strval', array_keys($seen))));
            $gone && $store->forget($key, $gone);
        }
        $ms = $budget->now() - $start;

        return new Progress($key, $put, $same, count($gone), $skipped, $complete, $ms, round($ms * $budget->pause, 3));
    }
}
