<?php

namespace App\Console\Commands;

use App\Support\Projects;
use Illuminate\Console\Command;

// Daftar tahap fitur project.  php artisan stage:list [project]
class StageList extends Command
{
    protected $signature = 'stage:list {project=tmerp}';

    protected $description = 'Daftar fitur project dan tahapnya';

    public function handle(): int
    {
        if (! Projects::ready()) {
            $this->warn('Tabel project_stage belum ada: jalankan php artisan migrate.');

            return self::FAILURE;
        }
        $this->table(['Feature', 'Stage', 'Note'], array_map(fn ($f) => [$f['feature'], Projects::STAGES[$f['stage']] ?? 'Not recorded', $f['note']], Projects::features($this->argument('project'))));

        return self::SUCCESS;
    }
}
