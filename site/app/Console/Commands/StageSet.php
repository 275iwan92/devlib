<?php

namespace App\Console\Commands;

use App\Support\Projects;
use Illuminate\Console\Command;

// Catat tahap satu fitur project (Executive Summary > All Projects).  php artisan stage:set tmerp Inventory tested --note="..."
// Tanpa {stage} = tahap dikosongkan (Not recorded).
class StageSet extends Command
{
    protected $signature = 'stage:set {project} {feature} {stage? : mockup | tested | compat | applied | uat} {--note=}';

    protected $description = 'Catat tahap satu fitur project (mockup, tested, compat, applied, uat)';

    public function handle(): int
    {
        if (! Projects::ready()) {
            $this->warn('Tabel project_stage belum ada: jalankan php artisan migrate.');

            return self::FAILURE;
        }
        try {
            Projects::setStage($this->argument('project'), $this->argument('feature'), $this->argument('stage'), $this->option('note'));
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
        $this->info($this->argument('feature').': '.(Projects::STAGES[$this->argument('stage')] ?? 'Not recorded'));

        return self::SUCCESS;
    }
}
