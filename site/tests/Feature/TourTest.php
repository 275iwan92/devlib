<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga x-dl::tour (kartu misi per kelompok, titik dengan posisi persen, daftar langkah, skor) dan CSS-nya tanpa warna literal.
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class TourTest extends TestCase
{
    private array $m = [['id' => 'a', 'group' => 'Sales', 'title' => 'Read the list', 'summary' => 'S', 'image' => '/x.png', 'width' => 100, 'height' => 50, 'steps' => [[30, 11, 'Search', 'Type here.'], [89, 11, 'New', 'Press.']]], ['id' => 'b', 'title' => 'Two', 'image' => '/y.png', 'steps' => []]];

    public function test_missions_render_cards_hotspots_and_steps(): void
    {
        $html = Blade::render('<x-dl::tour :missions="$m" key="u1" />', ['m' => $this->m]);

        foreach (['data-dl-tour', 'data-key="u1"', 'data-total="2"', 'data-tr-open="a"', '<h4>Sales</h4>', 'left: 30%; top: 11%', 'data-t="Search"', 'data-d="Type here."', 'data-tr-li="1"', 'data-tr-win', '2 steps'] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
        $this->assertSame(2, substr_count($html, 'data-tr-step='));
    }

    public function test_empty_shows_text(): void
    {
        $this->assertStringContainsString('dl-tour__empty', Blade::render('<x-dl::tour />'));
    }

    public function test_css_uses_tokens_only(): void
    {
        preg_match_all('/\.dl-tour[^{]*\{[^}]*\}/', file_get_contents(dirname(__DIR__, 3).'/resources/assets/devlib.css'), $m);
        $this->assertNotEmpty($m[0]);
        $this->assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{3,6}\b/', implode('', $m[0]));
    }
}
