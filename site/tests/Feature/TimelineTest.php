<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga x-dl::timeline (titik bernada, judul bertaut, keterangan, teks kosong) dan CSS-nya tanpa warna literal.
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class TimelineTest extends TestCase
{
    public function test_items_render_with_tone_link_and_body(): void
    {
        $html = Blade::render('<x-dl::timeline :items="$i" />', ['i' => [['title' => 'Order CO1', 'meta' => '07-Sep', 'body' => 'Done', 'tone' => 'success', 'icon' => 'fa-bag-shopping', 'url' => '/o/1']]]);

        foreach (['dl-timeline__item--success', 'fa-bag-shopping', '<a href="/o/1">Order CO1</a>', 'dl-timeline__meta', '07-Sep', 'dl-timeline__body'] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
    }

    public function test_empty_list_shows_text(): void
    {
        $this->assertStringContainsString('dl-timeline__empty', Blade::render('<x-dl::timeline />'));
    }

    public function test_css_uses_tokens_only(): void
    {
        preg_match_all('/\.dl-timeline[^{]*\{[^}]*\}/', file_get_contents(dirname(__DIR__, 3).'/resources/assets/devlib.css'), $m);
        $this->assertNotEmpty($m[0]);
        $this->assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{3,6}\b/', implode('', $m[0]));
    }
}
