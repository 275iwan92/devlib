<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga x-dl::pomodoro (kerangka, penanda JS, batas menit 1-90, label), halaman Sound di x-dl::setup-media (6 baris, saklar, volume, Test), widget-board type pomodoro, dan modul bunyi (bawaan mati, tanpa warna literal di CSS pomodoro).
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class PomodoroTest extends TestCase
{
    public function test_card_has_ring_play_minus_plus_segment_count_and_menu(): void
    {
        $html = Blade::render('<x-dl::pomodoro :focus="25" :break="5" :sessions="3" save-url="/tools/pomodoro" csrf="tok" storage-key="k9" />');

        foreach (['data-dl-pomo', 'data-key="k9"', 'data-focus="25"', 'data-break="5"', 'data-sessions="3"', 'data-save="/tools/pomodoro"', 'data-csrf="tok"', 'data-dl-pomo-play', 'data-dl-pomo-minus',
            'data-dl-pomo-plus', 'data-dl-pomo-time', 'data-dl-pomo-bar', 'data-dl-pomo-reset', 'data-dl-pomo-auto', 'data-dl-pomo-notify', 'value="focus"', 'value="break"', 'Sessions today'] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
        $this->assertStringContainsString('25:00', $html);
    }

    public function test_minutes_are_clamped_to_1_to_90(): void
    {
        $html = Blade::render('<x-dl::pomodoro :focus="500" :break="0" />');

        $this->assertStringContainsString('data-focus="90"', $html);
        $this->assertStringContainsString('data-break="1"', $html);
    }

    public function test_setup_media_has_a_sound_page_with_six_rows(): void
    {
        $html = Blade::render('<x-dl::setup-media />');

        $this->assertStringContainsString('data-dl-setup-sound', $html);
        $this->assertSame(6, substr_count($html, 'data-dl-sound-test'));
        foreach (['pomodoro', 'alarm', 'timer', 'notif', 'chat', 'facility'] as $kind) {
            $this->assertStringContainsString('data-kind="'.$kind.'"', $html, $kind);
        }
        $this->assertStringContainsString('data-dl-sound-on', $html);
        $this->assertStringContainsString('data-dl-sound-vol', $html);
    }

    public function test_sound_module_is_off_by_default_and_chat_uses_it(): void
    {
        $dir = dirname(__DIR__, 3).'/resources/assets/js/components/';
        $sound = file_get_contents($dir.'sound.js');

        $this->assertStringContainsString('s.on === true', $sound);   // bawaan mati: hanya true yang tersimpan menyalakan
        $this->assertStringContainsString("Devlib.sound?.play('chat')", file_get_contents($dir.'chat.js'));
        $this->assertStringNotContainsString('createOscillator', file_get_contents($dir.'chat.js'));
    }

    public function test_widget_board_renders_a_pomodoro_widget(): void
    {
        $html = Blade::render('<x-dl::widget-board :widgets="$w" :catalog="[]" />', ['w' => [['key' => 'pomodoro', 'type' => 'pomodoro', 'title' => 'Pomodoro', 'size' => 4,
            'data' => ['focus' => 30, 'break' => 5, 'auto' => true, 'sessions' => 2, 'save_url' => '/v2/tools/pomodoro', 'csrf' => 't', 'key' => 'pomo-1']]]]);

        $this->assertStringContainsString('data-dl-pomo', $html);
        $this->assertStringContainsString('data-focus="30"', $html);
        $this->assertStringContainsString('data-key="pomo-1"', $html);
        $this->assertStringContainsString('dl-pomo--compact', $html);
    }

    public function test_pomodoro_css_uses_tokens_only(): void
    {
        $css = file_get_contents(dirname(__DIR__, 3).'/resources/assets/devlib.css');
        preg_match_all('/^\.dl-pomo[^{]*\{[^}]*\}/m', $css, $m);

        $this->assertNotEmpty($m[0]);
        $this->assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{3,8}\b/', implode("\n", $m[0]));
    }
}
