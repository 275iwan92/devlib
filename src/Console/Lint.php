<?php

namespace Devlib\Console;

use Devlib\Lint\Finding;
use Devlib\Lint\ViewLinter;
use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

// php artisan devlib:lint [path...] [--json] [--strict]
// Memeriksa berkas Blade (bawaan: resources/views proyek) terhadap aturan baku DevLib. Keluar 1 bila ada error (--strict: juga warn). Hanya membaca.
// Opt-in: config('devlib.lint_except') = ['jalur/berkas.blade.php' => ['L003', ...]] mengecualikan kode tertentu pada berkas tertentu (mis. demo yang
// sengaja memperlihatkan HTML mentah). Kosong/tidak diisi = perilaku lama; jalur relatif terhadap base_path(); kecualinya dihitung di ringkasan.
class Lint extends Command
{
    protected $signature = 'devlib:lint {paths?* : folder/berkas Blade (bawaan resources/views)} {--json : keluaran JSON} {--strict : warn dianggap gagal} {--level=warn : tampilkan minimal info|warn|error}';

    protected $description = 'Periksa view terhadap aturan baku DevLib (grid kind, form back, HTML mentah, hex, script inline, isian awal)';

    public function handle(): int
    {
        $paths = $this->argument('paths') ?: [resource_path('views')];
        $rank = [Finding::INFO => 0, Finding::WARN => 1, Finding::ERROR => 2];
        $min = $rank[$this->option('level')] ?? 1;

        $except = (array) config('devlib.lint_except', []);
        $all = [];
        $excepted = 0;
        foreach ($paths as $path) {
            $files = is_file($path) ? [$path] : array_map(fn ($f) => $f->getPathname(), iterator_to_array(Finder::create()->files()->in($path)->name('*.blade.php')->sortByName(), false));
            foreach ($files as $file) {
                $rel = ltrim(str_replace([base_path(), '\\'], ['', '/'], $file), '/');
                $skip = (array) ($except[$rel] ?? []);
                foreach (ViewLinter::lint((string) file_get_contents($file), $rel) as $f) {
                    in_array($f->code, $skip, true) ? $excepted++ : $all[] = $f;
                }
            }
        }
        $shown = array_values(array_filter($all, fn (Finding $f) => $rank[$f->level] >= $min));

        if ($this->option('json')) {
            $this->line(json_encode(array_map(fn (Finding $f) => $f->toArray(), $shown), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        } else {
            foreach ($shown as $f) {
                $this->line(strtoupper($f->level).' '.$f);
            }
        }
        $errors = count(array_filter($all, fn (Finding $f) => $f->level === Finding::ERROR));
        $warns = count(array_filter($all, fn (Finding $f) => $f->level === Finding::WARN));
        $this->info("devlib:lint: {$errors} error, {$warns} warn, ".(count($all) - $errors - $warns).' info'.($excepted ? ", {$excepted} dikecualikan (config devlib.lint_except)" : ''));

        return $errors || ($this->option('strict') && $warns) ? self::FAILURE : self::SUCCESS;
    }
}
