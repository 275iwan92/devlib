<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga x-dl::habit-tracker, x-dl::sticky-notes, x-dl::eisenhower dan x-dl::world-clock (kerangka, penanda JS, data, empat kuadran, daftar zona) serta skripnya (teks tidak lewat innerHTML) dan CSS tanpa warna literal.
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class ToolsListsTest extends TestCase
{
    public function test_habit_tracker_markup(): void
    {
        $html = Blade::render('<x-dl::habit-tracker :items="$i" save-url="/t/habit" csrf="tok" />', ['i' => [['id' => 'h1', 'name' => 'Walk', 'log' => ['2026-11-08']]]]);

        foreach (['data-dl-habit', 'data-save="/t/habit"', 'data-csrf="tok"', 'data-dl-habit-list', 'data-dl-habit-add', 'data-dl-habit-name', '&quot;name&quot;:&quot;Walk&quot;'] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
    }

    public function test_sticky_notes_markup_lists_the_five_colors(): void
    {
        $html = Blade::render('<x-dl::sticky-notes :items="$i" />', ['i' => [['id' => 'n1', 'text' => 'Call', 'color' => 'green']]]);

        foreach (['data-dl-notes', 'data-dl-notes-add', 'data-dl-notes-wall', '[&quot;yellow&quot;,&quot;green&quot;,&quot;blue&quot;,&quot;pink&quot;,&quot;orange&quot;]'] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
    }

    public function test_eisenhower_has_four_boxes_and_a_clear_button(): void
    {
        $html = Blade::render('<x-dl::eisenhower :items="$i" />', ['i' => [['id' => 'k1', 'text' => 'Report', 'q' => 1, 'done' => false]]]);

        $this->assertSame(4, substr_count($html, 'data-dl-eis-add'));
        foreach (['data-dl-eis-q="1"', 'data-dl-eis-q="4"', 'data-dl-eis-clear', 'Do first', 'Schedule', 'Delegate', 'Eliminate'] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
    }

    public function test_world_clock_offers_zones_and_keeps_the_given_cities(): void
    {
        $html = Blade::render('<x-dl::world-clock :items="$i" />', ['i' => [['id' => 'c1', 'tz' => 'Asia/Tokyo', 'label' => 'Tokyo']]]);

        foreach (['data-dl-wclock', 'data-dl-wclock-add', 'data-dl-wclock-zone', '<option value="Asia/Jakarta">Jakarta</option>', '<option value="UTC">UTC</option>', '&quot;tz&quot;:&quot;Asia\/Tokyo&quot;'] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
        $custom = Blade::render('<x-dl::world-clock :zones="$z" />', ['z' => ['Asia/Seoul' => 'Seoul']]);
        $this->assertStringContainsString('<option value="Asia/Seoul">Seoul</option>', $custom);
        $this->assertStringNotContainsString('Asia/Jakarta', $custom);
    }

    public function test_scripts_never_write_user_text_with_innerhtml_and_css_has_no_literal_colors(): void
    {
        $dir = dirname(__DIR__, 3).'/resources/assets/';
        foreach (['habit-tracker', 'sticky-notes', 'eisenhower', 'world-clock'] as $js) {
            $src = file_get_contents($dir.'js/components/'.$js.'.js');
            $this->assertStringContainsString('textContent', $src, $js);
            // innerHTML hanya untuk ikon tetap (string literal), tidak pernah variabel
            preg_match_all('/innerHTML\s*=\s*([^;]+);/', $src, $m);
            foreach ($m[1] as $rhs) {
                $this->assertMatchesRegularExpression("/^'[^']*'$/", trim($rhs), $js.': '.$rhs);
            }
        }
        preg_match_all('/^\.dl-(habit|notes|eis|wclock)[^{]*\{[^}]*\}/m', file_get_contents($dir.'devlib.css'), $m);
        $this->assertNotEmpty($m[0]);
        $this->assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{3,8}\b/', implode("\n", $m[0]));
    }
}
