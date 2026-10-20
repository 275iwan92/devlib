<?php

namespace Devlib\Console;

use Illuminate\Console\Command;

// php artisan devlib:manifest [--check] — menulis skills/devlib/components.md dari refleksi semua komponen. --check: gagal bila berkas usang (untuk tes/CI).
class Manifest extends Command
{
    protected $signature = 'devlib:manifest {--check : hanya bandingkan, jangan menulis}';

    protected $description = 'Hasilkan skills/devlib/components.md (tag, prop, tujuan, berkas semua komponen)';

    public function handle(): int
    {
        $path = dirname(__DIR__, 2).'/skills/devlib/components.md';
        $md = \Devlib\Manifest::markdown();
        if ($this->option('check')) {
            $same = is_file($path) && str_replace("\r\n", "\n", (string) file_get_contents($path)) === $md;
            $same ? $this->info('components.md terbaru') : $this->error('components.md usang: jalankan php artisan devlib:manifest');

            return $same ? self::SUCCESS : self::FAILURE;
        }
        file_put_contents($path, $md);
        $this->info('Ditulis: '.$path.' ('.substr_count($md, "\n## ").' komponen)');

        return self::SUCCESS;
    }
}
