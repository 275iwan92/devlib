<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

// x-dl::print-out prop watermark: atribut data-watermark hanya bila diisi; print.js menyalinnya ke tiap lembar (data-wm); CSS menggambarnya di belakang isi.
class PrintOutWatermarkTest extends TestCase
{
    public function test_watermark_attribute_only_when_set(): void
    {
        $on = Blade::render('<x-dl::print-out title="T" watermark="DRAFT" />');
        $off = Blade::render('<x-dl::print-out title="T" />');

        $this->assertStringContainsString('data-watermark="DRAFT"', $on);
        $this->assertStringNotContainsString('data-watermark', $off, 'dokumen Confirmed: tanpa cap air');
    }

    public function test_js_copies_watermark_to_each_sheet_and_css_draws_it_behind_text(): void
    {
        $js = file_get_contents(base_path('../resources/assets/js/components/print.js'));
        $css = file_get_contents(base_path('../resources/assets/devlib.css'));

        $this->assertStringContainsString('s.dataset.wm = this.el.dataset.watermark', $js);
        $this->assertStringContainsString('.dl-print__sheet[data-wm]::before{content:attr(data-wm)', $css);
        $this->assertStringContainsString('.dl-print__sheet[data-wm] .dl-print__inner{position:relative;z-index:1}', $css, 'isi di atas cap air');
        $this->assertStringContainsString('color:var(--dl-pr-neg)', $css, 'warna lewat token, bukan hex');
    }
}
