<?php

namespace Devlib\Assistant\Index;

use Closure;

// Pelari beranggaran: memicu paling sering sekali per `every` detik (IndexThrottle), menjalankan potongan Indexer::pass
// berurutan dengan jeda = lama kerja potongan sebelumnya x Budget::$pause, dan berhenti setelah Budget::$chunks potongan
// (sisanya pada jalan berikut). Dipanggil perintah `devlib:index` dari scheduler program. `sleep` (milidetik) hanya untuk tes.
final class IndexRunner
{
    public function __construct(
        private readonly Indexer $indexer,
        private readonly IndexStore $store,
        private readonly IndexThrottle $throttle,
        private readonly ?Closure $sleep = null,
        private readonly int $every = 3600,
        private readonly string $key = 'index',
    ) {}

    /**
     * @param  iterable<IndexSource>  $sources
     * @return list<Progress>|null  null = dibatasi pemicu (sudah jalan dalam jangka ini)
     */
    public function run(iterable $sources, Budget $budget = new Budget, bool $force = false): ?array
    {
        if (! $force && ! $this->throttle->claim($this->key, $this->every)) {
            return null;
        }
        $out = [];
        foreach ($sources as $source) {
            do {
                if (count($out) >= $budget->chunks) {
                    return $out;
                }
                if ($out) {
                    $this->wait(end($out)->pauseMs);
                }
                $out[] = $progress = $this->indexer->pass($source, $this->store, $budget);
            } while (! $progress->complete);
        }

        return $out;
    }

    private function wait(float $ms): void
    {
        $this->sleep ? ($this->sleep)($ms) : usleep((int) ($ms * 1000));
    }
}
