<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga x-dl::countdown (kerangka, penanda JS, batas menit, preset) dan x-dl::stopwatch (tombol, daftar lap) serta skripnya (bunyi lewat Devlib.sound, waktu dari jam, CSS tanpa warna literal).
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class CountdownTest extends TestCase
{
    public function test_countdown_has_ring_controls_presets_and_form(): void
    {
        $html = Blade::render('<x-dl::countdown :presets="$p" :minutes="5" save-url="/tools/timer" csrf="tok" storage-key="k1" />', ['p' => [['id' => 't1', 'label' => 'Tea', 'minutes' => 3]]]);

        foreach (['data-dl-cd', 'data-key="k1"', 'data-minutes="5"', 'data-save="/tools/timer"', 'data-csrf="tok"', 'data-dl-cd-bar', 'data-dl-cd-time', 'data-dl-cd-minus', 'data-dl-cd-plus', 'data-dl-cd-minutes',
            'data-dl-cd-play', 'data-dl-cd-reset', 'data-dl-cd-presets', 'data-dl-cd-add', '&quot;label&quot;:&quot;Tea&quot;'] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
    }

    public function test_countdown_minutes_are_clamped(): void
    {
        $this->assertStringContainsString('data-minutes="600"', Blade::render('<x-dl::countdown :minutes="9999" />'));
        $this->assertStringContainsString('data-minutes="1"', Blade::render('<x-dl::countdown :minutes="0" />'));
    }

    public function test_stopwatch_has_buttons_and_lap_list(): void
    {
        $html = Blade::render('<x-dl::stopwatch storage-key="k2" />');

        foreach (['data-dl-sw', 'data-key="k2"', 'data-dl-sw-time', 'data-dl-sw-play', 'data-dl-sw-lap', 'data-dl-sw-reset', 'data-dl-sw-laps', '00:00:00.00'] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
    }

    public function test_scripts_use_the_sound_module_and_the_clock_and_css_has_no_literal_colors(): void
    {
        $dir = dirname(__DIR__, 3).'/resources/assets/';
        $cd = file_get_contents($dir.'js/components/countdown.js');

        $this->assertStringContainsString("Devlib.sound?.play('timer')", $cd);
        $this->assertStringNotContainsString('createOscillator', $cd);
        $this->assertStringContainsString('Date.now()', file_get_contents($dir.'js/components/stopwatch.js'));
        preg_match_all('/^\.dl-(cd|sw)[^{]*\{[^}]*\}/m', file_get_contents($dir.'devlib.css'), $m);
        $this->assertNotEmpty($m[0]);
        $this->assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{3,8}\b/', implode("\n", $m[0]));
    }
}
