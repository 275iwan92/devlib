<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

// @devlib = satu panggilan di <head>: ikon, CSS, theme-boot, JS. Tanpa config devlib.cdn aset disajikan paket; dengan cdn dari situs aset (devlib:cdn-build).
class HeadDirectiveTest extends TestCase
{
    public function test_without_cdn_assets_come_from_the_package_route(): void
    {
        config(['devlib.cdn' => null]);
        $html = Blade::render('@devlib');

        $this->assertStringContainsString('/_devlib/devlib.css?v=', $html);
        $this->assertStringContainsString('/_devlib/devlib.js?v=', $html);
        $this->assertStringContainsString('font-awesome', $html);
    }

    public function test_cdn_build_writes_hashed_folder_and_latest_json_and_directive_points_to_it(): void
    {
        $out = sys_get_temp_dir().'/dl-cdn-'.uniqid();
        try {
            $this->artisan('devlib:cdn-build', ['--out' => $out])->assertSuccessful();
            $v = json_decode((string) file_get_contents($out.'/latest.json'), true)['version'];
            $this->assertFileExists("$out/$v/devlib.css");
            $this->assertFileExists("$out/$v/devlib.js");

            config(['devlib.cdn' => 'file:///'.strtr($out, [chr(92) => '/']), 'devlib.icons' => false]);
            $html = Blade::render('@devlib');
            $this->assertStringContainsString("/$v/devlib.css", $html);
            $this->assertStringContainsString("/$v/devlib.js", $html);
            $this->assertStringNotContainsString('font-awesome', $html);
        } finally {
            File::deleteDirectory($out);
        }
    }
}
