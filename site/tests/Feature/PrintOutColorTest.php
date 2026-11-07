<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

// x-dl::print-out prop color: segmen Color | B&W di bilah (data-p-color), mode awal lewat data-color, pilihan diingat (localStorage), badge tetap terbaca di B&W (token, tanpa hex).
class PrintOutColorTest extends TestCase
{
    public function test_bar_has_color_segment_and_initial_mode(): void
    {
        $def = Blade::render('<x-dl::print-out title="T" />');
        $bw = Blade::render('<x-dl::print-out title="T" color="bw" />');

        $this->assertStringContainsString('data-p-color', $def);
        $this->assertStringContainsString('data-value="color"', $def);
        $this->assertStringContainsString('data-value="bw"', $def);
        $this->assertStringContainsString('data-color="color"', $def, 'bawaan = berwarna');
        $this->assertStringContainsString('data-color="bw"', $bw);
        $this->assertStringNotContainsString('data-p-bw', $def, 'tombol Hemat Tinta lama diganti segmen');
    }

    public function test_js_applies_remembers_and_css_keeps_badges_legible(): void
    {
        $js = file_get_contents(base_path('../resources/assets/js/components/print.js'));
        $css = file_get_contents(base_path('../resources/assets/devlib.css'));

        $this->assertStringContainsString("localStorage.getItem('dl-print-color')", $js);
        $this->assertStringContainsString("localStorage.setItem('dl-print-color'", $js);
        $this->assertStringContainsString('.dl-print.is-bw .dl-print__sheet{filter:grayscale(1)}', $css);
        $this->assertStringContainsString('.dl-print.is-bw .dl-print__badge{background:var(--dl-pr-paper);color:var(--dl-pr-strong)', $css, 'badge berbingkai, token');
        $this->assertStringContainsString('print-color-adjust:exact', $css);
    }
}
