<?php

namespace Devlib\Console;

use Devlib\DevlibServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

// Pecah aset DevLib menjadi situs statis terpisah (seperti CDN): <out>/<hash isi>/devlib.css|devlib.js (+ aset CSS lain) dan <out>/latest.json.
// Aplikasi cukup menunjuk config devlib.cdn ke situs itu; @devlib memuat dari sana. Folder berversi hash isi = aman di-cache selamanya.
//   php artisan devlib:cdn-build --out=C:\Users\iwanp\Herd\devlib-cdn
class CdnBuild extends Command
{
    protected $signature = 'devlib:cdn-build {--out= : folder situs aset (dibuat bila belum ada)} {--keep=5 : jumlah versi lama yang dipertahankan}';

    protected $description = 'Bangun situs aset statis DevLib (CSS + JS berversi hash isi + latest.json)';

    public function handle(): int
    {
        $out = rtrim((string) $this->option('out'), '/\\');
        if ($out === '') {
            $this->error('--out wajib (folder situs aset).');

            return self::FAILURE;
        }
        $assets = __DIR__.'/../../resources/assets';
        $js = DevlibServiceProvider::bundle();
        $files = ['devlib.js' => $js];
        foreach (glob($assets.'/*.css') ?: [] as $css) {
            $files[basename($css)] = (string) file_get_contents($css);
        }
        ksort($files);
        $hash = substr(md5(implode('|', array_map(fn ($k, $v) => $k.md5($v), array_keys($files), $files))), 0, 12);
        $dir = $out.'/'.$hash;
        File::ensureDirectoryExists($dir);
        foreach ($files as $name => $body) {
            File::put($dir.'/'.$name, $body);
        }
        // latest.json ditulis terakhir: versi baru baru "terlihat" setelah semua berkasnya lengkap.
        File::put($out.'/latest.json', json_encode(['version' => $hash, 'built_at' => date('c'), 'files' => array_keys($files)], JSON_PRETTY_PRINT)."\n");
        // Tanpa .htaccess: CSS/JS lintas origin tidak butuh CORS; header cache diatur server situs aset (hash di jalur = aman immutable).

        $old = collect(File::directories($out))->sortByDesc(fn ($d) => filemtime($d))->slice((int) $this->option('keep'))->all();
        foreach ($old as $d) {
            File::deleteDirectory($d);
        }
        $this->info("Aset DevLib dibangun: {$out}/{$hash} (".count($files).' berkas, js '.round(strlen($js) / 1024).' KB).');

        return self::SUCCESS;
    }
}
