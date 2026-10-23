<?php

namespace Tests\Feature;

use App\Support\PhoneRef;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

// Halaman peraga "Phone shell" (p7-phone): layar contoh netral, galeri acuan, x-dl::image-gallery, panel dan aksi layar sendiri di phone-shell.
class PhoneRefTest extends TestCase
{
    private array $user = ['login' => 'x', 'name' => 'Sample', 'role' => 'tester'];

    public function test_every_sample_screen_opens_and_has_no_project_names(): void
    {
        foreach (array_keys(PhoneRef::SCREENS) as $name) {
            $html = $this->withSession(['devlib_user' => $this->user])->get('/ref/phone/screen/'.$name)->assertOk()->getContent();
            $this->assertStringNotContainsStringIgnoringCase('stoku', $html, $name);
            $this->assertStringNotContainsStringIgnoringCase('sstock', $html, $name);
            $this->assertStringNotContainsStringIgnoringCase('tmerp', $html, $name);
        }
        foreach (['form', 'grid', 'report'] as $name) {
            $this->withSession(['devlib_user' => $this->user])->get('/ref/phone/inner/'.$name)->assertOk();
        }
        $this->withSession(['devlib_user' => $this->user])->get('/ref/phone/screen/nothing')->assertNotFound();
        $this->withSession(['devlib_user' => $this->user])->get('/ref/phone-shell')->assertRedirect(route('lib', 'p7-phone'));
    }

    public function test_shell_screens_carry_the_bar_the_panel_and_the_labels(): void
    {
        $html = $this->withSession(['devlib_user' => $this->user])->get('/ref/phone/screen/grid')->assertOk()->getContent();
        $this->assertStringContainsString('data-dl-phone-bar', $html);
        $this->assertStringContainsString('data-dl-phone-panel', $html);
        $this->assertStringContainsString('data-dl-phone-sheet', $html);
        $ui = json_decode(html_entity_decode((string) preg_replace('/^.*data-l-ui="([^"]*)".*$/s', '$1', $html)), true);
        $this->assertSame(['filter', 'export', 'paper', 'group', 'ink', 'view', 'cols', 'apply'], array_keys($ui));
        $this->assertSame('Saring', $ui['filter']);
    }

    public function test_the_framed_pages_expose_the_controls_the_bar_reads(): void
    {
        $grid = $this->withSession(['devlib_user' => $this->user])->get('/ref/phone/inner/grid')->getContent();
        foreach (['data-g-search', 'data-g-new', 'data-g-expmenu', 'data-g-filter-fields'] as $hook) {
            $this->assertStringContainsString($hook, $grid, $hook);
        }
        $report = $this->withSession(['devlib_user' => $this->user])->get('/ref/phone/inner/report')->getContent();
        foreach (['data-p-print', 'data-p-paper', 'data-p-group', 'data-p-bw'] as $hook) {
            $this->assertStringContainsString($hook, $report, $hook);
        }
        $form = $this->withSession(['devlib_user' => $this->user])->get('/ref/phone/inner/form')->getContent();
        foreach (['save', 'undo', 'back', 'new'] as $act) {
            $this->assertStringContainsString('data-f-act="'.$act.'"', $form, $act);
        }
    }

    public function test_image_gallery_renders_items_and_every_reference_picture_exists(): void
    {
        $html = Blade::render('<x-dl::image-gallery title="Ref" note="360 x 790" :items="[[\'src\' => \'/a.png\', \'caption\' => \'One\'], [\'src\' => \'/b.png\']]" :columns="40" />');
        $this->assertStringContainsString('dl-gallery', $html);
        $this->assertSame(2, substr_count($html, 'dl-gallery__item"'));
        $this->assertStringContainsString('One', $html);
        $this->assertStringContainsString('--dl-gal-min:80px', $html, 'kolom minimum 80 px');

        $demo = file_get_contents(resource_path('views/demos/phone-gallery.blade.php'));
        preg_match_all("~'src' => '(/ref/phone/[^']+)'~", $demo, $m);
        $this->assertCount(15, $m[1]);
        foreach ($m[1] as $src) {
            $this->assertFileExists(public_path($src), $src);
            $this->assertLessThan(150 * 1024, filesize(public_path($src)), $src.' harus kecil');
        }
    }

    public function test_own_screen_actions_are_hidden_in_the_phone_context_only(): void
    {
        $css = file_get_contents(base_path('../resources/assets/devlib.css'));
        $this->assertMatchesRegularExpression('/^body\.dl-phone-shelled \[data-phone-act\],\n\.dl-phone \[data-phone-act\],/m', str_replace("\r\n", "\n", $css));
        $js = file_get_contents(base_path('../resources/assets/js/components/phone-shell.js'));
        $this->assertStringContainsString("[data-phone-act]", $js);
    }
}
