<?php

namespace App\Console\Commands;

use App\Support\Catalog;
use Devlib\Engine\EngineRegistry;
use Illuminate\Console\Command;

// Menyusun papan Build Progress dari katalog DevLib SENDIRI (Catalog::groups(), demo, engine terdaftar), bukan dari proyek lain.
// Hasil: resources/data/build.json (dibaca Catalog::build() dan Summary). Jalankan ulang setelah katalog atau engine berubah.
class BuildCatalog extends Command
{
    protected $signature = 'build:catalog';

    protected $description = 'Build the Build Progress data from the DevLib catalog and the registered engines';

    public function handle(EngineRegistry $registry): int
    {
        $approved = Catalog::APPROVED;
        $modules = [];
        $items = [];
        foreach (Catalog::groups() as $g) {
            $g['pages'] = array_filter($g['pages'], fn ($p) => ! isset($p['doc'])); // halaman dokumen Aturan (skills/devlib/*.md) bukan objek yang ditinjau
            $slugs = array_keys($g['pages']);
            $done = count(array_intersect($slugs, array_keys($approved)));
            $with = fn (string $word) => count(array_filter($slugs, fn ($s) => str_contains($s, $word)));
            $modules[] = ['label' => $g['label'], 'total' => count($slugs), 'v2' => count($slugs), 'v1' => 0, 'not_built' => 0,
                'waiting_qc' => count($slugs) - $done, 'locked' => $done, 'form' => $with('form'), 'grid' => $with('grid'), 'report' => $with('report')];
            foreach ($g['pages'] as $slug => $page) {
                $items[] = ['type' => 'OBJECT', 'key' => 'page:'.$slug, 'name' => $page['label'], 'module' => $g['label'],
                    'qc' => isset($approved[$slug]) ? 'LOCKED' : 'READY_FOR_QC', 'built_at' => $approved[$slug] ?? null, 'built_by' => null,
                    'changed_at' => $approved[$slug] ?? null, 'changed_by' => null];
            }
        }
        $engines = [];
        foreach ($registry->catalog() as $e) {
            $engines[$e['group']][] = ['key' => $e['key'], 'name' => $e['name'], 'purpose' => $e['purpose'], 'mode' => $e['mode'], 'used' => 0, 'tests' => 0];
            $items[] = ['type' => 'ENGINE', 'key' => 'engine:'.$e['key'], 'name' => $e['name'], 'module' => 'Engines', 'qc' => 'READY_FOR_QC',
                'built_at' => null, 'built_by' => null, 'changed_at' => null, 'changed_by' => null];
        }
        $sum = fn (string $k) => array_sum(array_column($modules, $k));
        $demos = count(glob(resource_path('views/demos/*.blade.php')));
        $git = fn (string $args) => trim((string) @shell_exec('git -C '.escapeshellarg(base_path('..')).' '.$args.(PHP_OS_FAMILY === 'Windows' ? ' 2>NUL' : ' 2>/dev/null')));

        $build = [
            'generated' => now()->format('Y-m-d H:i'),
            'database' => (string) config('database.connections.'.config('database.default').'.database'),
            'totals' => [
                'objects' => $sum('total'), 'modules' => count($modules), 'modules_done' => count(array_filter($modules, fn ($m) => $m['waiting_qc'] === 0)),
                'screens' => $demos, 'v2' => $demos, 'v1' => 0, 'not_built' => 0, 'waiting_qc' => $sum('waiting_qc'), 'locked' => $sum('locked'),
                'forms' => $sum('form'), 'grids' => $sum('grid'), 'reports' => $sum('report'),
                'engines' => count($registry->all()), 'engine_groups' => count($engines),
                'migrations' => count(glob(database_path('migrations/*.php'))),
                'tests' => count(glob(base_path('tests/*/*Test.php'))),
                'commits' => (int) $git('rev-list --count HEAD'), 'since' => substr($git('log --max-parents=0 --format=%ad --date=short'), 0, 10) ?: null,
            ],
            'modules' => $modules,
            'engines' => array_map(fn ($group, $list) => ['group' => $group, 'items' => $list], array_keys($engines), $engines),
            'features' => [
                ['fa-language', 'Multi-language + dictionary'],
                ['fa-palette', '12 themes (Pastel, Bright, Dark, Black x Blue, Green, Red)'],
                ['fa-table', 'Grid: master, transactional and report (zebra rows)'],
                ['fa-gears', 'Engine contract (Devlib\\Engine): modes, results, registry, cancel + adjustment'],
                ['fa-print', 'Print documents that carry the record mode'],
                ['fa-user-shield', 'Login and security model for other projects'],
            ],
            'notes' => [],
            'items' => $items,
        ];
        file_put_contents(base_path('resources/data/build.json'), json_encode($build, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
        $this->info('resources/data/build.json: '.count($items).' items, '.count($modules).' modules, '.count($registry->all()).' engines.');

        return self::SUCCESS;
    }
}
