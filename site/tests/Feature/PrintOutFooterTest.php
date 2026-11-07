<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

// x-dl::print-out prop footer: token {no} {status} dari prop (di-escape), {page} {pages} = span yang diisi print.js per lembar; tanpa footer = kaki bawaan.
class PrintOutFooterTest extends TestCase
{
    public function test_footer_tokens_are_filled_and_page_tokens_become_script_spans(): void
    {
        $html = Blade::render('<x-dl::print-out title="T" number="CO1" status="Confirmed <b>" footer="{no} · {status} · Hal. {page} / {pages}" />');

        $this->assertStringContainsString('<footer class="dl-print__foot is-fmt"><span>CO1 · Confirmed &lt;b&gt; · Hal. <span data-p-pn></span> / <span data-p-pt></span></span></footer>', $html);
        $this->assertStringNotContainsString('Printed from DevLib', $html);
    }

    public function test_default_footer_is_kept_without_prop_and_js_fills_page_numbers(): void
    {
        $off = Blade::render('<x-dl::print-out title="T" number="CO1" />');
        $js = file_get_contents(base_path('../resources/assets/js/components/print.js'));

        $this->assertStringNotContainsString('is-fmt', $off);
        $this->assertStringContainsString('data-p-pg', $off);
        $this->assertStringContainsString("'[data-p-pn]'", $js);
        $this->assertStringContainsString('x.textContent = all.length', $js, 'total halaman = jumlah lembar terukur');
    }
}
