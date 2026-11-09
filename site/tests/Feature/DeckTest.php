<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga x-dl::deck (slide, butir berurutan, tombol, interval, autoplay) dan CSS-nya tanpa warna literal.
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class DeckTest extends TestCase
{
    public function test_slides_render_first_is_on_with_controls(): void
    {
        $html = Blade::render('<x-dl::deck :slides="$s" :interval="1" autoplay />', ['s' => [['title' => 'One', 'lead' => 'Lead', 'items' => ['a', 'b']], ['title' => 'Two']]]);

        foreach (['data-dl-deck', 'data-interval="2"', 'data-autoplay="1"', 'dl-deck__slide is-on', '<h2>One</h2>', 'dl-deck__lead', '--i: 1', 'data-dk-play', 'data-dk-full', 'data-dk-prog'] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
        $this->assertSame(1, substr_count($html, 'dl-deck__slide is-on'));
    }

    public function test_empty_deck_shows_text(): void
    {
        $this->assertStringContainsString('dl-deck__empty', Blade::render('<x-dl::deck />'));
    }

    public function test_css_uses_tokens_only(): void
    {
        preg_match_all('/\.dl-deck[^{]*\{[^}]*\}/', file_get_contents(dirname(__DIR__, 3).'/resources/assets/devlib.css'), $m);
        $this->assertNotEmpty($m[0]);
        $this->assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{3,6}\b/', implode('', $m[0]));
    }
}
