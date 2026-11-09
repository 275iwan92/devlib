<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga x-dl::alert-strip: kosong tidak dirender, satu notifikasi = teks diam (tanpa jalur ticker), banyak = ticker dua salinan (salinan kedua aria-hidden), warna strip = notifikasi paling genting, teks di-escape, CSS tanpa warna literal.
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class AlertStripTest extends TestCase
{
    public function test_empty_renders_nothing(): void
    {
        $this->assertStringNotContainsString('dl-astrip', Blade::render('<x-dl::alert-strip :items="[]" />'));
    }

    public function test_open_key_makes_the_item_open_a_modal(): void
    {
        $one = Blade::render('<x-dl::alert-strip :items="$i" />', ['i' => [['text' => 'T', 'tone' => 'warn', 'open' => 'm1', 'label' => 'Open']]]);
        $many = Blade::render('<x-dl::alert-strip :items="$i" />', ['i' => [['text' => 'A', 'open' => 'm1'], ['text' => 'B']]]);

        $this->assertStringContainsString('data-dl-open="m1"', $one);
        $this->assertStringContainsString('data-dl-open="m1"', $many);
    }

    public function test_single_notification_is_still_text_with_button(): void
    {
        $html = Blade::render('<x-dl::alert-strip :items="$i" />', ['i' => [['text' => 'Tasks: 4', 'tone' => 'warn', 'href' => '/t', 'label' => 'Open Task']]]);

        $this->assertStringContainsString('dl-astrip--warn', $html);
        $this->assertStringContainsString('dl-astrip__text', $html);
        $this->assertStringContainsString('href="/t"', $html);
        $this->assertStringNotContainsString('dl-astrip__track', $html);
        $this->assertStringNotContainsString('is-run', $html);
    }

    public function test_many_notifications_run_in_two_copies_and_strip_takes_most_urgent_tone(): void
    {
        $html = Blade::render('<x-dl::alert-strip :items="$i" />', ['i' => [
            ['text' => 'Leave 2', 'tone' => 'info', 'href' => '/l'],
            ['text' => 'Material 14', 'tone' => 'danger', 'href' => '/m'],
            ['text' => '<b>x</b>', 'tone' => 'warn'],
        ]]);

        $this->assertStringContainsString('is-run', $html);
        $this->assertStringContainsString('dl-astrip--danger', $html);
        $this->assertSame(2, substr_count($html, 'dl-astrip__set'), 'dua salinan daftar untuk loop tanpa celah');
        $this->assertSame(1, preg_match_all('/dl-astrip__set"\s+aria-hidden="true"/', $html), 'hanya salinan kedua aria-hidden');
        $this->assertStringContainsString('&lt;b&gt;x&lt;/b&gt;', $html, 'teks di-escape');
        $this->assertStringContainsString('<span class="dl-astrip__item dl-astrip__item--warn"', $html, 'tanpa href = span');
    }

    public function test_css_has_no_literal_color_in_strip_rules(): void
    {
        $css = file_get_contents(base_path('../resources/assets/devlib.css'));
        preg_match_all('/^\.dl-astrip[^\n]*$/m', $css, $m);

        $this->assertNotEmpty($m[0]);
        foreach ($m[0] as $line) {
            $this->assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{3,8}\b|rgba?\(/', $line, $line);
        }
    }
}
