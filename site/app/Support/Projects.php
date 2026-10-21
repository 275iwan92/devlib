<?php

namespace App\Support;

use Devlib\Locale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Executive Summary > All Projects: angka semua project Jo (DevLib, tmerp, sstock). Halaman hanya membaca projects.json
// (ditulis `php artisan projects:snapshot`, angka agregat saja) dan tabel project_stage (tahap fitur tmerp, `stage:set`).
final class Projects
{
    // kunci => label. Mockup lane = mockup, tested; Applied lane = compat, applied, uat.
    public const STAGES = ['mockup' => 'In mockup', 'tested' => 'Tested OK', 'compat' => 'Compatibility made', 'applied' => 'Applied to tmerp', 'uat' => 'UAT'];

    public const LANES = ['mockup' => ['mockup', 'tested'], 'applied' => ['compat', 'applied', 'uat']];

    // Proyek yang ikut jalur mockup -> applied (dan tabel project_stage).
    public const LANE_PROJECT = 'tmerp';

    public static function file(): string
    {
        return config('devlib.projects_json');
    }

    public static function load(): ?array
    {
        $raw = is_file(self::file()) ? json_decode((string) file_get_contents(self::file()), true) : null;

        return is_array($raw) && ! empty($raw['projects']) ? $raw : null;
    }

    public static function ready(): bool
    {
        try {
            return Schema::hasTable('project_stage');
        } catch (\Throwable) {
            return false;
        }
    }

    public static function features(string $project = self::LANE_PROJECT): array
    {
        return self::ready() ? DB::table('project_stage')->where('project', $project)->orderBy('id')->get()->map(fn ($r) => (array) $r)->all() : [];
    }

    public static function setStage(string $project, string $feature, ?string $stage, ?string $note = null): void
    {
        if ($stage !== null && ! isset(self::STAGES[$stage])) {
            throw new \InvalidArgumentException('Tahap tidak sah: '.$stage.' (pilih: '.implode(', ', array_keys(self::STAGES)).')');
        }
        $values = ['stage' => $stage, 'updated_at' => now()] + ($note !== null ? ['note' => mb_substr($note, 0, 300)] : []);
        $row = DB::table('project_stage')->where('project', $project)->where('feature', $feature);
        $row->exists() ? $row->update($values) : DB::table('project_stage')->insert($values + ['project' => $project, 'feature' => $feature, 'note' => null, 'created_at' => now()]);
    }

    // Data tampilan; null = projects.json belum ada (empty state).
    public static function page(): ?array
    {
        if (! $data = self::load()) {
            return null;
        }
        $projects = $data['projects'];
        $months = array_values(array_unique(array_merge(...array_values(array_map(fn ($p) => array_keys($p['months'] ?? []), $projects)))));
        sort($months);
        $firsts = array_filter(array_column($projects, 'first'));
        $edge = strtotime(($data['generated'] ?? 'now').' -30 days');

        return [
            'generated' => $data['generated'] ?? null,
            'projects' => $projects,
            'commits' => array_sum(array_column($projects, 'commits')),
            'sessions' => array_sum(array_column($projects, 'sessions')),
            'first' => $firsts ? min($firsts) : null,
            'active' => count(array_filter($projects, fn ($p) => ! empty($p['last']) && strtotime($p['last']) >= $edge)),
            'months' => $months,
            'series' => array_values(array_map(fn ($p) => ['name' => $p['label'], 'tone' => $p['tone'] ?? 'accent',
                'data' => array_map(fn ($m) => $p['months'][$m] ?? 0, $months)], $projects)),
            'lanes' => self::lanes(),
        ];
    }

    // Hitungan fitur tmerp per tahap untuk dua kartu; belum ada tahap tercatat = null ("Not recorded yet").
    public static function lanes(): array
    {
        $features = self::features();
        $recorded = array_filter($features, fn ($f) => $f['stage'] !== null);
        $out = [];
        foreach (self::LANES as $lane => $stages) {
            $out[$lane] = $recorded ? array_map(fn ($s) => ['stage' => self::STAGES[$s], 'count' => count(array_filter($recorded, fn ($f) => $f['stage'] === $s))], $stages) : null;
        }
        $out['unrecorded'] = count($features) - count($recorded);

        return $out;
    }

    public static function rows(array $page): array
    {
        $n = fn ($v) => Locale::number((float) $v, 0);
        $d = fn ($v) => $v ? Locale::date($v) : '-';

        return array_values(array_map(fn ($p) => [
            'label' => $p['label'], 'commits' => $n($p['commits']), 'sessions' => $n($p['sessions']), 'days' => $n($p['active_days']),
            'started' => $d($p['first']), 'position' => $p['position'] ?? '',
        ], $page['projects']));
    }
}
