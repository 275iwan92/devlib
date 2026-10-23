<?php

namespace Tests\Feature;

use Devlib\AppVersion;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga Devlib\AppVersion (ver 0.<commit> + huruf tahap A / B / RC, rilis tanpa huruf, nilai tahap tak dikenal dianggap kosong, cadangan berkas VERSION) dan x-dl::env-badge part=version.
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class AppVersionTest extends TestCase
{
    private function noGit(string $version = ''): string
    {
        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'appver-'.bin2hex(random_bytes(4));
        mkdir($dir);
        if ($version !== '') {
            file_put_contents($dir.DIRECTORY_SEPARATOR.'VERSION', $version."\n");
        }

        return $dir;
    }

    public function test_commit_count_comes_from_git_and_stage_adds_one_letter_with_a_full_word_tooltip(): void
    {
        $root = dirname(base_path());
        $expected = trim((string) shell_exec('git -C '.escapeshellarg($root).' rev-list --count HEAD'));
        $this->assertMatchesRegularExpression('/^\d+$/', $expected);

        $a = AppVersion::of($root, 'alpha');
        $this->assertSame(['ver 0.'.$expected, 'A', 'alpha'], [$a['text'], $a['letter'], $a['stage']]);
        $this->assertSame('alpha', $a['title']);
        $this->assertSame('B', AppVersion::of($root, 'beta')['letter']);
        $rc = AppVersion::of($root, 'RC');
        $this->assertSame(['RC', 'release candidate'], [$rc['letter'], $rc['title']]);
    }

    public function test_unknown_stage_is_ignored_and_the_stage_is_read_from_config(): void
    {
        $this->assertSame('', AppVersion::stage('gamma'));
        config(['devlib.stage' => 'beta']);
        $this->assertSame('beta', AppVersion::stage());
        config(['devlib.stage' => 'nonsense']);
        $this->assertSame('', AppVersion::stage());
    }

    public function test_without_git_the_version_file_then_zero_is_used(): void
    {
        $this->assertSame('ver 0.133', AppVersion::of($this->noGit('0.133'), 'alpha')['text']);
        $this->assertSame('ver 0.0', AppVersion::of($this->noGit(), 'alpha')['text']);
        $this->assertSame('ver 0.0', AppVersion::of($this->noGit('rubbish'), 'alpha')['text']);
    }

    public function test_release_without_a_tag_falls_back_to_the_commit_number_without_a_letter(): void
    {
        $v = AppVersion::of($this->noGit('0.7'), '');

        $this->assertSame(['ver 0.7', ''], [$v['text'], $v['letter']]);
    }

    public function test_the_library_shows_its_release_tag(): void
    {
        $lib = AppVersion::library(dirname(base_path()));

        $this->assertMatchesRegularExpression('/^v\d+\.\d+\.\d+$/', $lib['text']);
        $this->assertSame('', $lib['letter']);
        $this->assertStringStartsWith($lib['text'], $lib['title']);
    }

    public function test_the_badge_draws_the_version_only_when_bound(): void
    {
        app()->bind('devlib.version', fn () => []);
        $this->assertSame('', trim(Blade::render('<x-dl::env-badge part="version" variant="version" />')));

        app()->bind('devlib.version', fn () => ['text' => 'ver 0.433', 'letter' => 'A', 'title' => 'alpha', 'stage' => 'alpha']);
        $html = Blade::render('<x-dl::env-badge part="version" variant="version" />');

        $this->assertStringContainsString('dl-envbadge--version', $html);
        $this->assertStringContainsString('title="alpha"', $html);
        $this->assertMatchesRegularExpression('/ver 0\.433\s*<span class="dl-envbadge__stage">A<\/span>/', $html);
    }

    public function test_the_layer_menu_title_has_the_version_next_to_the_logo_name(): void
    {
        app()->bind('devlib.version', fn () => ['text' => 'v0.6.0', 'letter' => '', 'title' => 'v0.6.0 + 3 commits', 'stage' => '']);
        $html = Blade::render('<x-dl::layer-menu id="m" title="Demo" :items="$i" />', ['i' => [['label' => 'A', 'url' => '#']]]);

        $this->assertMatchesRegularExpression('/<b>Demo.*?dl-envbadge--version.*?v0\.6\.0/s', $html);
    }
}
