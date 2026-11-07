<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga x-dl::slideshow: kerangka modal (id, role dialog), daftar slide sebagai JSON data-items, tombol Sebelumnya / Berikutnya, penanda JS, judul bawaan "Guide", dan CSS tanpa warna literal.
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class SlideshowTest extends TestCase
{
    private const ITEMS = [['src' => '/guide/a/01.png', 'caption' => 'Press Deactivate'], ['src' => '/guide/a/02.png', 'caption' => 'Choose a reason', 'alt' => 'Reason list']];

    private function show(string $attrs = ''): string
    {
        return Blade::render('<x-dl::slideshow id="s1" :items="$items" '.$attrs.' />', ['items' => self::ITEMS]);
    }

    public function test_is_a_modal_with_the_slide_list_as_json(): void
    {
        $html = $this->show('title="Employee guide"');

        $this->assertStringContainsString('data-dl-modal-id="s1"', $html);
        $this->assertStringContainsString('data-dl-slideshow', $html);
        $this->assertStringContainsString('role="dialog"', $html);
        $this->assertStringContainsString('Employee guide', $html);
        $this->assertStringContainsString('&quot;caption&quot;:&quot;Press Deactivate&quot;', $html);
        $this->assertStringContainsString('&quot;alt&quot;:&quot;Reason list&quot;', $html);
        $this->assertStringNotContainsString('is-on', $html, 'tertutup sampai tombol data-dl-open menekannya');
    }

    public function test_has_previous_next_dots_and_default_title(): void
    {
        $html = $this->show();

        foreach (['data-sl-prev', 'data-sl-next', 'data-sl-dots', 'data-sl-img', 'data-sl-cap', 'data-sl-n'] as $hook) {
            $this->assertStringContainsString($hook, $html);
        }
        $this->assertStringContainsString('Guide', $html);
    }

    public function test_open_prop_shows_it_on_load(): void
    {
        $this->assertStringContainsString('is-on', $this->show('open'));
    }

    public function test_grid_guide_prop_adds_a_toolbar_button_that_opens_the_slideshow(): void
    {
        $cols = [['key' => 'name', 'label' => 'Name']];
        $with = Blade::render('<x-dl::grid id="g" :rows="[]" :columns="$c" guide="s1" />', ['c' => $cols]);
        $plain = Blade::render('<x-dl::grid id="g" :rows="[]" :columns="$c" />', ['c' => $cols]);

        $this->assertStringContainsString('data-dl-open="s1"', $with);
        $this->assertStringNotContainsString('data-dl-open="s1"', $plain, 'tanpa prop guide toolbar tidak berubah');
    }

    public function test_more_url_adds_a_link_to_the_full_guide(): void
    {
        $with = $this->show('more-url="/guides?doc=7" more-label="Full guide"');

        $this->assertStringContainsString('href="/guides?doc=7"', $with);
        $this->assertStringContainsString('target="_blank"', $with);
        $this->assertStringNotContainsString('dl-slides__more', $this->show(), 'tanpa moreUrl tidak ada tautan');
    }

    public function test_css_uses_tokens_only(): void
    {
        $css = file_get_contents(dirname(__DIR__, 3).'/resources/assets/devlib.css');
        preg_match_all('/\.dl-slides[^{]*\{[^}]*\}/', $css, $rules);

        $this->assertNotEmpty($rules[0]);
        foreach ($rules[0] as $r) {
            $this->assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{3,8}\b|rgba?\(/', $r, 'warna literal di '.$r);
        }
    }
}
