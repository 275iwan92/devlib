<?php

namespace Devlib\Assistant\Index;

use Devlib\Assistant\DataPolicy;
use Devlib\Assistant\Sensitivity;

// Alat bantu tes untuk program (keputusan Jo no. 6): membuktikan tidak ada sumber terlarang berlabel biasa yang lolos ke indeks.
// Sumber diindeks dengan kebijakan PRODUKSI program ($policy, boleh null), lalu isi indeks diperiksa terhadap daftar kata dan orang
// terlindung milik tes (boleh lebih lengkap dari produksi). Yang ditemukan = pelanggaran:
//   $this->assertSame([], IndexAudit::leaks($sources, $policy, ['gaji', 'bonus'], ['Nama Lengkap Orang Terlindung']));
// Hanya mencocokkan teks entri: sumber yang isinya terlarang tetapi tidak menyebut kata apa pun tidak terdeteksi (label jujur = tugas program).
final class IndexAudit
{
    /**
     * @param  iterable<IndexSource>  $sources
     * @param  list<string>  $words
     * @param  list<string>  $people
     * @return list<string> "sumber/id: alasan"
     */
    public static function leaks(iterable $sources, ?DataPolicy $policy = null, array $words = [], array $people = []): array
    {
        $store = new MemoryIndexStore;
        $everything = new Budget(ms: PHP_FLOAT_MAX, size: PHP_INT_MAX);
        foreach ($sources as $source) {
            (new Indexer($policy))->pass($source, $store, $everything);
        }
        $probe = new DataPolicy($words, $people);
        $out = [];
        foreach ($store->all() as [$source, $entry]) {
            if ($probe->excludes(...$entry->texts())) {
                $out[] = "{$source}/{$entry->id}: memuat kata atau orang terlarang";
            }
            if ($entry->sensitivity === Sensitivity::Value && ($entry->words !== '' || $entry->text !== '' || $entry->steps)) {
                $out[] = "{$source}/{$entry->id}: entri nilai membawa isi, bukan hanya nama";
            }
        }

        return $out;
    }
}
