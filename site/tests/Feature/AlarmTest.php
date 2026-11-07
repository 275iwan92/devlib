<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga x-dl::alarm-list (kerangka, penanda JS, hari, form Tambah), x-dl::alarm-runner (tak terlihat, penanda JS) dan skrip alarm.js (satu pembunyi di jendela paling atas, bunyi lewat Devlib.sound).
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class AlarmTest extends TestCase
{
    private const ITEMS = [['id' => 'a1', 'time' => '06:30', 'label' => 'Shift', 'days' => [1, 2], 'on' => true]];

    public function test_list_has_rows_data_form_and_seven_day_boxes(): void
    {
        $html = Blade::render('<x-dl::alarm-list :items="$i" save-url="/tools/alarm" csrf="tok" storage-key="k1" />', ['i' => self::ITEMS]);

        foreach (['data-dl-alarm', 'data-key="k1"', 'data-save="/tools/alarm"', 'data-csrf="tok"', 'data-dl-alarm-list', 'data-dl-alarm-empty', 'data-dl-alarm-add', 'data-dl-alarm-time', 'data-dl-alarm-label', '&quot;time&quot;:&quot;06:30&quot;'] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
        $this->assertSame(7, substr_count($html, 'data-dl-alarm-day'));
    }

    public function test_runner_is_hidden_and_carries_the_list(): void
    {
        $html = Blade::render('<x-dl::alarm-runner :items="$i" storage-key="k1" />', ['i' => self::ITEMS]);

        $this->assertStringContainsString('hidden', $html);
        $this->assertStringContainsString('data-dl-alarm-runner', $html);
        $this->assertStringContainsString('data-key="k1"', $html);
    }

    public function test_script_rings_through_the_sound_module_only_in_the_top_window(): void
    {
        $js = file_get_contents(dirname(__DIR__, 3).'/resources/assets/js/components/alarm.js');

        $this->assertStringContainsString("Devlib.sound?.play('alarm')", $js);
        $this->assertStringContainsString('window.top !== window', $js);
        $this->assertStringNotContainsString('createOscillator', $js);
    }
}
