<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga x-dl::calculator (tombol ikon + modal tiga tab, data satuan/kurs di atribut, label), pemasangannya di x-dl::greeting-card (board['calculator']), dan CSS tanpa warna literal.
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class CalculatorTest extends TestCase
{
    private const Q = [['key' => 'LENGTH', 'label' => 'Length', 'units' => [['c' => 'mm', 'n' => 'millimetre', 'f' => 0.001, 'o' => 0]]]];

    private const C = [['c' => 'USD', 'n' => 'US Dollar', 'r' => 15800], ['c' => 'EUR', 'n' => 'Euro', 'r' => null]];

    public function test_button_opens_a_modal_with_three_tabs_and_the_data(): void
    {
        $html = Blade::render('<x-dl::calculator id="c1" :quantities="$q" :currencies="$c" :featured="[\'LENGTH\']" rate-url="/rates" />', ['q' => self::Q, 'c' => self::C]);

        $this->assertStringContainsString('data-dl-open="c1"', $html);
        $this->assertStringContainsString('data-dl-modal-id="c1"', $html);
        $this->assertStringContainsString('aria-label="Calculator"', $html);
        foreach (['calc', 'conv', 'hist'] as $tab) {
            $this->assertStringContainsString('data-wc-tab="'.$tab.'"', $html);
        }
        $this->assertStringContainsString('data-wc-k="="', $html);
        $this->assertStringContainsString('data-rate-url="/rates"', $html);
        $this->assertStringContainsString('&quot;c&quot;:&quot;EUR&quot;,&quot;n&quot;:&quot;Euro&quot;,&quot;r&quot;:null', $html, 'Kurs kosong dikirim sebagai null, bukan 0.');
        $this->assertStringContainsString('&quot;key&quot;:&quot;LENGTH&quot;', $html);
    }

    public function test_greeting_card_shows_the_calculator_only_when_given(): void
    {
        $with = Blade::render('<x-dl::greeting-card :board="$b" />', ['b' => ['user' => ['first' => 'Jo'], 'calculator' => ['quantities' => self::Q, 'currencies' => self::C]]]);
        $without = Blade::render('<x-dl::greeting-card :board="$b" />', ['b' => ['user' => ['first' => 'Jo']]]);

        $this->assertStringContainsString('dl-wcalc-btn', $with);
        $this->assertStringNotContainsString('dl-wcalc-btn', $without);
    }

    public function test_greeting_card_button_order_is_calendar_calculator_play_and_calendar_without_url_is_disabled(): void
    {
        $b = ['user' => ['first' => 'Jo'], 'calendar' => ['url' => null], 'calculator' => ['quantities' => self::Q, 'currencies' => self::C], 'music' => ['tracks' => [['file' => 'a.mp3', 'title' => 'A', 'author' => 'B']]]];
        $html = Blade::render('<x-dl::greeting-card :board="$b" />', ['b' => $b]);

        $this->assertTrue(strpos($html, 'data-ic="calendar"') < strpos($html, 'data-ic="calculator"') && strpos($html, 'data-ic="calculator"') < strpos($html, 'data-dl-music-play'));
        $this->assertMatchesRegularExpression('/<button[^>]*dl-wcalc-btn[^>]*disabled[^>]*Coming soon/', $html);
        $this->assertStringContainsString('href="/cal"', Blade::render('<x-dl::greeting-card :board="$b" />', ['b' => ['user' => ['first' => 'Jo'], 'calendar' => ['url' => '/cal']]]));
    }

    public function test_script_follows_the_windows_rules_and_the_css_has_no_literal_colors(): void
    {
        $root = dirname(__DIR__, 3);
        $js = file_get_contents($root.'/resources/assets/js/components/calculator.js');
        $css = file_get_contents($root.'/resources/assets/devlib.css');

        // Eksekusi langsung (2 + 3 x 4 = 20), % = a * b / 100, kurs kosong tidak dihitung, riwayat per perangkat.
        $this->assertStringContainsString("if (this.op) { v = this.apply(this.acc, this.op, v);", $js);
        $this->assertStringContainsString('this.acc * parseFloat(c) / 100', $js);
        $this->assertStringContainsString('a.r == null || b.r == null', $js);
        $this->assertStringContainsString('localStorage.setItem(this.key', $js);

        preg_match_all('/^\.dl-wcalc[^{]*\{[^}]*\}/m', $css, $rules);
        $this->assertNotEmpty($rules[0]);
        foreach ($rules[0] as $rule) {
            $this->assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{3,8}\b/', $rule, 'Warna lewat token --dl-*: '.$rule);
        }
    }
}
