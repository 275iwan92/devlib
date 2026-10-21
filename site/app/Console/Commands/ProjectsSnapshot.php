<?php

namespace App\Console\Commands;

use App\Support\Projects;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

// Executive Summary > All Projects. Dijalankan MANUAL dari folder site/:  php artisan projects:snapshot
// Menulis resources/data/projects.json (angka agregat saja). Git dibaca hanya lewat `git -C <path> log --all`; sesi Claude hanya
// dihitung dari berkas *.jsonl (jumlah + tanggal mulai dari properti timestamp); isi percakapan dan nama berkas sesi tidak dibaca/disimpan.
class ProjectsSnapshot extends Command
{
    protected $signature = 'projects:snapshot';

    protected $description = 'Hitung commit dan sesi Claude tiap project (config devlib.projects) ke projects.json';

    public function handle(): int
    {
        $out = [];
        foreach (config('devlib.projects') as $key => $p) {
            if (! is_dir($p['path'])) {
                $this->warn("{$p['label']}: jalur tidak ada, dilewati.");

                continue;
            }
            $git = Process::run(['git', '-C', $p['path'], 'log', '--all', '--format=%as|%an']);
            if (! $git->successful()) {
                $this->warn("{$p['label']}: git log gagal, dilewati.");

                continue;
            }
            $out[$key] = $this->git($p, $git->output()) + $this->sessions($p['sessions']);
        }
        if (! $out) {
            $this->error('Tidak ada project yang terbaca; projects.json tidak ditulis.');

            return self::FAILURE;
        }
        file_put_contents(Projects::file(), json_encode(['generated' => now()->toDateString(), 'projects' => $out], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
        foreach ($out as $p) {
            $this->line(sprintf('%-8s commits %5d  claude-authored %3d  active days %3d  sessions %4d  first %s', $p['label'], $p['commits'], $p['claude_commits'], $p['active_days'], $p['sessions'], $p['first']));
        }

        return self::SUCCESS;
    }

    private function git(array $p, string $log): array
    {
        $months = [];
        $days = [];
        $claude = 0;
        foreach (array_filter(explode("\n", trim($log))) as $line) {
            [$date, $author] = explode('|', $line, 2) + [1 => ''];
            $months[substr($date, 0, 7)] = ($months[substr($date, 0, 7)] ?? 0) + 1;
            $days[$date] = true;
            $claude += stripos($author, 'claude') !== false;
        }
        ksort($months);
        ksort($days);

        return ['label' => $p['label'], 'tone' => $p['tone'], 'position' => $p['position'] ?? '', 'commits' => array_sum($months), 'claude_commits' => $claude,
            'active_days' => count($days), 'first' => array_key_first($days), 'last' => array_key_last($days), 'months' => $months];
    }

    // Folder sesi = tepat awalan itu, atau awalan + "--claude-worktrees-*". Hanya properti timestamp dari 8 baris pertama yang dibaca.
    private function sessions(string $prefix): array
    {
        $count = 0;
        $first = null;
        foreach (glob(rtrim(config('devlib.claude_sessions_path'), '\\/').DIRECTORY_SEPARATOR.$prefix.'*', GLOB_ONLYDIR) ?: [] as $dir) {
            $name = basename($dir);
            if ($name !== $prefix && ! str_starts_with($name, $prefix.'--claude-worktrees-')) {
                continue;
            }
            foreach (glob($dir.DIRECTORY_SEPARATOR.'*.jsonl') ?: [] as $file) {
                $count++;
                $h = fopen($file, 'r');
                for ($i = 0; $h && $i < 8 && ($line = fgets($h)) !== false; $i++) {
                    if ($ts = json_decode($line, true)['timestamp'] ?? null) {
                        $first = min($first ?? substr($ts, 0, 10), substr($ts, 0, 10));
                        break;
                    }
                }
                $h && fclose($h);
            }
        }

        return ['sessions' => $count, 'sessions_first' => $first];
    }
}
