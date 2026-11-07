<?php

namespace Tests\Feature;

use Devlib\Components\DocSections;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga x-dl::doc-sections: judul kapital jadi bagian, butir bernomor (baris lanjutan menempel), baris ringkas di atas (inline), teks tanpa judul tampil apa adanya, dan semua isi di-escape.
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class DocSectionsTest extends TestCase
{
    private const TEXT = "PURPOSE\nKeep it <b>right</b>.\nREPORTS TO\nThe manager.\nMAIN DUTIES\n1. First duty\nmore words\n2. Second duty";

    public function test_parse_splits_titles_paragraphs_and_numbered_items(): void
    {
        $parts = DocSections::parse(self::TEXT);

        $this->assertSame(['PURPOSE', 'REPORTS TO', 'MAIN DUTIES'], array_column($parts, 'title'));
        $this->assertSame('Keep it <b>right</b>.', $parts[0]['text']);
        $this->assertSame([['no' => 1, 'text' => 'First duty more words'], ['no' => 2, 'text' => 'Second duty']], $parts[2]['items']);
    }

    public function test_renders_sections_inline_rows_and_escapes_the_text(): void
    {
        $html = Blade::render('<x-dl::doc-sections :text="$t" :inline="[\'reports to\']" />', ['t' => self::TEXT]);

        $this->assertStringContainsString('<h4>Purpose</h4>', $html);
        $this->assertStringContainsString('<h4>Main Duties</h4>', $html);
        $this->assertStringContainsString('class="dl-docsec__meta"', $html);
        $this->assertStringContainsString('<dt>Reports To</dt>', $html);
        $this->assertStringNotContainsString('<h4>Reports To</h4>', $html);
        $this->assertStringContainsString('<li value="1">First duty more words</li>', $html);
        $this->assertStringNotContainsString('<b>right</b>', $html);
        $this->assertStringContainsString('&lt;b&gt;right&lt;/b&gt;', $html);
    }

    public function test_text_without_a_title_is_kept_as_it_is(): void
    {
        $html = Blade::render('<x-dl::doc-sections :text="$t" />', ['t' => "one line\ntwo line"]);

        $this->assertStringContainsString('dl-docsec__plain', $html);
        $this->assertStringContainsString("one line\ntwo line", $html);
        $this->assertStringNotContainsString('<h4>', $html);
    }

    public function test_css_uses_tokens_only(): void
    {
        $css = file_get_contents(dirname(__DIR__, 3).'/resources/assets/devlib.css');
        preg_match_all('/^\.dl-docsec[^{]*\{[^}]*\}/m', $css, $rules);

        $this->assertNotEmpty($rules[0]);
        foreach ($rules[0] as $rule) {
            $this->assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{3,8}\b/', $rule, 'Warna literal di '.$rule);
        }
    }
}
