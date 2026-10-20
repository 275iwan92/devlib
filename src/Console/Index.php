<?php

namespace Devlib\Console;

use Devlib\Assistant\DataPolicy;
use Devlib\Assistant\Index\Budget;
use Devlib\Assistant\Index\CacheThrottle;
use Devlib\Assistant\Index\IndexRunner;
use Devlib\Assistant\Index\Indexer;
use Devlib\Assistant\Index\IndexStore;
use Devlib\Assistant\Index\IndexThrottle;
use Illuminate\Console\Command;
use Illuminate\Contracts\Foundation\Application;

// Pengindeks latar belakang Assistant (docs/assistant-indexer.md). Dipanggil scheduler program, mis. ->hourly(); pemicu dibatasi
// sekali per jam (IndexThrottle) dan tiap potongan dibatasi Budget, jadi aman dipanggil lebih sering. Hanya terdaftar bila program
// mengikat IndexStore; sumbernya = tag container `devlib.index-source`. Jalankan:  php artisan devlib:index   |   --force
class Index extends Command
{
    protected $signature = 'devlib:index {--force : abaikan pembatas sekali per jam}';

    protected $description = 'Perbarui indeks Assistant (menu, layar, Help) dengan anggaran waktu dan jeda';

    public function handle(Application $app): int
    {
        $sources = iterator_to_array($app->tagged('devlib.index-source'), false);
        if (! $sources) {
            $this->warn('Tidak ada sumber indeks: tag IndexSource dengan `devlib.index-source` di container.');

            return self::SUCCESS;
        }
        $runner = new IndexRunner(
            new Indexer($app->bound(DataPolicy::class) ? $app->make(DataPolicy::class) : null),
            $app->make(IndexStore::class),
            $app->bound(IndexThrottle::class) ? $app->make(IndexThrottle::class) : new CacheThrottle,
        );
        $done = $runner->run($sources, $app->bound(Budget::class) ? $app->make(Budget::class) : new Budget, (bool) $this->option('force'));
        if ($done === null) {
            $this->line('Dilewati: sudah berjalan dalam satu jam terakhir (--force untuk memaksa).');

            return self::SUCCESS;
        }
        foreach ($done as $p) {
            $this->line(sprintf('%s: disimpan %d, tak berubah %d, dibuang %d, dilewati kebijakan %d, %s (%.0f ms)',
                $p->source, $p->stored, $p->unchanged, $p->removed, count($p->skipped), $p->complete ? 'selesai' : 'belum selesai, lanjut giliran berikut', $p->ms));
        }

        return self::SUCCESS;
    }
}
