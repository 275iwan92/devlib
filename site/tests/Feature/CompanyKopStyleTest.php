<?php

namespace Tests\Feature;

use App\Support\DemoPrintDocs;
use Devlib\PrintDoc\Document;
use Devlib\PrintDoc\Kop;
use Illuminate\Support\Facades\Blade;
use Tests\Support\RendersKopCases;
use Tests\TestCase;

// Gaya kop (style) dan baris nama badan hukum (legal_name) pada semua cetakan; tanpa kunci baru, keluaran harus sama dengan sebelum fitur ada.
class CompanyKopStyleTest extends TestCase
{
    use RendersKopCases;

    private const CO = ['name' => 'Toko Maju', 'initials' => 'TM', 'legal_name' => 'PT Maju Jaya Abadi', 'lines' => ['Jl. Melati 5', 'Telp 021-777'],
        'npwp' => '01.234.567.8-901.000', 'logo' => '/kop-logo-contoh.svg'];

    protected function tearDown(): void
    {
        Document::companyUsing(null);
        parent::tearDown();
    }

    private function doc(array $co): string
    {
        $doc = DemoPrintDocs::deliveryNote();
        Document::companyUsing(fn () => $co);

        return $this->kopNormalize(Blade::render('<x-dl::print-doc :doc="$doc" />', ['doc' => $doc]));
    }

    private function kop(string $html): string
    {
        preg_match('/<header class="dl-pdoc__kop">.*?<h1/s', $html, $m);

        return $m[0] ?? '';
    }

    public function test_without_new_keys_output_is_identical_to_the_old_html(): void
    {
        foreach ($this->kopCases() as $name => $html) {
            $this->assertSame(file_get_contents(base_path("tests/fixtures/kop/{$name}.html")), $html, "Keluaran {$name} berubah");
        }
    }

    public function test_style_logo_name_address_shows_everything(): void
    {
        $kop = $this->kop($this->doc(self::CO + ['style' => 'logo_name_address']));

        $this->assertStringContainsString('<img class="dl-pdoc__brand"', $kop);
        $this->assertStringContainsString('<b>Toko Maju</b>', $kop);
        $this->assertStringContainsString('<small class="dl-pdoc__legal">PT Maju Jaya Abadi</small>', $kop);
        $this->assertStringContainsString('<small>Jl. Melati 5</small>', $kop);
        $this->assertStringContainsString('NPWP 01.234.567.8-901.000', $kop);
        $this->assertStringContainsString('dl-pdoc__co is-side', $kop);
        $this->assertStringNotContainsString('dl-pdoc__logo', $kop, 'lencana inisial tidak tampil bila nama ada');
    }

    public function test_style_logo_name_has_no_address(): void
    {
        $kop = $this->kop($this->doc(self::CO + ['style' => 'logo_name']));

        $this->assertStringContainsString('<img class="dl-pdoc__brand"', $kop);
        $this->assertStringContainsString('<b>Toko Maju</b>', $kop);
        $this->assertStringContainsString('PT Maju Jaya Abadi', $kop);
        $this->assertStringNotContainsString('Jl. Melati 5', $kop);
    }

    public function test_style_name_has_no_logo_and_no_address(): void
    {
        $kop = $this->kop($this->doc(self::CO + ['style' => 'name']));

        $this->assertStringNotContainsString('<img', $kop);
        $this->assertStringNotContainsString('dl-pdoc__logo', $kop);
        $this->assertStringContainsString('<b>Toko Maju</b>', $kop);
        $this->assertStringContainsString('PT Maju Jaya Abadi', $kop);
        $this->assertStringNotContainsString('Jl. Melati 5', $kop);
    }

    public function test_legal_name_equal_to_name_is_not_repeated(): void
    {
        foreach (['PT  MAJU  jaya', 'pt maju jaya'] as $legal) {
            $kop = $this->kop($this->doc(['name' => 'PT Maju Jaya', 'legal_name' => $legal, 'style' => 'name']));
            $this->assertStringNotContainsString('dl-pdoc__legal', $kop);
            $this->assertSame(1, substr_count(strtolower($kop), 'maju'));
        }
    }

    public function test_logo_style_without_a_logo_falls_back_to_name_only(): void
    {
        foreach (['logo_name_address', 'logo_name'] as $style) {
            $kop = $this->kop($this->doc(['logo' => null] + self::CO + ['style' => $style]));

            $this->assertStringNotContainsString('<img', $kop);
            $this->assertStringNotContainsString('dl-pdoc__logo', $kop);
            $this->assertStringNotContainsString('is-side', $kop);
            $this->assertStringContainsString('<b>Toko Maju</b>', $kop);
        }
    }

    public function test_initials_badge_only_when_there_is_no_name_to_show(): void
    {
        $this->assertTrue(Kop::of(['name' => '', 'initials' => 'XY', 'style' => 'name'])['badge']);
        $this->assertFalse(Kop::of(['name' => 'A', 'style' => 'name'])['badge']);
        $this->assertFalse(Kop::of(['name' => 'A', 'style' => 'tidak-dikenal'])['styled'], 'gaya tak dikenal = perilaku lama');
    }

    public function test_legal_name_without_style_only_adds_a_small_line(): void
    {
        $old = $this->kop($this->doc(['name' => 'CV Contoh', 'initials' => 'CC', 'lines' => ['Jl. A']]));
        $new = $this->kop($this->doc(['name' => 'CV Contoh', 'initials' => 'CC', 'lines' => ['Jl. A'], 'legal_name' => 'PT Contoh Sejahtera']));

        $this->assertSame($old, str_replace('<small class="dl-pdoc__legal">PT Contoh Sejahtera</small>', '', $new));
        $this->assertStringContainsString('dl-pdoc__logo', $new);
    }

    public function test_print_sheet_uses_the_style_but_still_has_no_npwp(): void
    {
        Document::companyUsing(fn () => self::CO + ['style' => 'logo_name']);
        $html = Blade::render('<x-dl::print-sheet title="Form" code="F-1" />');

        $this->assertStringContainsString('<b>Toko Maju</b>', $html);
        $this->assertStringContainsString('dl-pdoc__legal', $html);
        $this->assertStringNotContainsString('Jl. Melati 5', $html);
        $this->assertStringNotContainsString('NPWP', $html);
    }

    public function test_print_out_and_report_follow_the_style_and_keep_the_vat_switch(): void
    {
        $non = ['name' => 'Toko Maju', 'legal_name' => 'PT Maju Jaya Abadi', 'address' => 'Jl. Melati 5', 'contact' => 'Telp 021-777', 'logo' => '/kop-logo-contoh.svg',
            'logoBw' => '/kop-logo-contoh-bw.svg', 'style' => 'logo_name_address'];
        $ppn = ['name' => 'Toko Maju', 'legal_name' => 'PT Maju Jaya Abadi', 'address' => 'Jl. Pajak 1', 'npwp' => '99.999', 'style' => 'name'];

        foreach (['print-out', 'report'] as $tag) {
            $html = $this->kopNormalize(Blade::render("<x-dl::{$tag} title=\"T\" :company=\"\$a\" :company-ppn=\"\$b\" :columns=\"[['key' => 'n', 'label' => 'N']]\" :rows=\"[['n' => 'x']]\" />", ['a' => $non, 'b' => $ppn]));
            preg_match_all('/<div class="dl-print__co[^"]*" data-p-kop="(\d)">(.*?)<\/div><\/div>/s', $html, $m, PREG_SET_ORDER);

            $this->assertCount(2, $m, $tag);
            [$n, $p] = $m[0][1] === '0' ? [$m[0][2], $m[1][2]] : [$m[1][2], $m[0][2]];
            $this->assertStringContainsString('dl-print__brand is-bw', $n);
            $this->assertStringContainsString('<b>Toko Maju</b>', $n);
            $this->assertStringContainsString('<small class="dl-print__legal">PT Maju Jaya Abadi</small>', $n);
            $this->assertStringContainsString('<span>Jl. Melati 5</span>', $n);
            $this->assertStringNotContainsString('<img', $p);
            $this->assertStringNotContainsString('Jl. Pajak 1', $p, 'gaya name = tanpa alamat');
            $this->assertStringContainsString('data-config=', $html);
        }
    }

    public function test_every_company_header_goes_through_the_one_helper(): void
    {
        $root = dirname(base_path());
        foreach (['partials/print-doc-co', 'components/print-out'] as $view) {
            $this->assertStringContainsString('PrintDoc\Kop::of', file_get_contents("{$root}/resources/views/{$view}.blade.php"), "{$view} tidak memakai Kop");
        }
        foreach (['components/print-sheet', 'partials/print-doc-kop'] as $view) {
            $this->assertStringContainsString('partials.print-doc-co', file_get_contents("{$root}/resources/views/{$view}.blade.php"), "{$view} tidak memakai blok kop bersama");
        }
        $this->assertStringContainsString('class Report extends PrintOut', file_get_contents("{$root}/src/Components/Report.php"));
    }

    public function test_vat_header_prints_the_npwp_line_on_print_out_and_report(): void
    {
        $non = ['name' => 'Toko Maju', 'address' => 'Jl. Melati 5', 'npwp' => '11.111'];
        $ppn = ['name' => 'Toko Maju', 'address' => 'Jl. Pajak 1', 'npwp' => '99.999.999'];

        foreach (['print-out', 'report'] as $tag) {
            $html = $this->kopNormalize(Blade::render("<x-dl::{$tag} title=\"T\" :company=\"\$a\" :company-ppn=\"\$b\" :columns=\"[['key' => 'n', 'label' => 'N']]\" :rows=\"[['n' => 'x']]\" />", ['a' => $non, 'b' => $ppn]));
            preg_match_all('/data-p-kop="(\d)">(.*?)<\/div><\/div>/s', $html, $m, PREG_SET_ORDER);
            $kop = array_column($m, 2, 1);

            $this->assertStringContainsString('<span class="dl-print__npwp">NPWP 99.999.999</span>', $kop['1'], "{$tag}: kop PPN harus memuat NPWP");
            $this->assertStringNotContainsString('NPWP', $kop['0'], "{$tag}: kop Non PPN tetap tanpa NPWP");
        }
    }

    // Ukuran kop bergaya ikut kertas lewat variabel CSS: bawaan = A4, .is-a5 = rapat. Tes ini menjaga rasio dan batas pagination.
    public function test_styled_kop_is_proportional_on_a4_and_a5(): void
    {
        $css = file_get_contents(dirname(base_path()).'/resources/assets/devlib.css');
        $vars = function (string $selector) use ($css): array {
            preg_match('/'.preg_quote($selector, '/').'\{([^}]*)\}/', $css, $m);
            preg_match_all('/--co-([a-z-]+):([\d.]+)(pt|mm)?/', $m[1] ?? '', $v, PREG_SET_ORDER);

            return array_column(array_map(fn ($x) => [$x[1], (float) $x[2]], $v), 1, 0);
        };
        $a4 = $vars('.dl-pdoc__co.is-styled');
        $a5 = $vars('.is-a5 .dl-pdoc__co.is-styled') + $a4;

        foreach (['name', 'sub', 'logo-h', 'logo-w', 'badge', 'max'] as $k) {
            $this->assertGreaterThan($a5[$k], $a4[$k], "A4 harus lebih besar dari A5 untuk {$k}");
        }
        foreach ([$a4, $a5] as $label => $v) {
            $this->assertEqualsWithDelta(1.6, $v['name'] / $v['sub'], 0.1, 'rasio nama : baris lain di kertas '.($label ? 'A5' : 'A4'));
        }
        $this->assertGreaterThanOrEqual(12, $a4['name']);
        $this->assertGreaterThanOrEqual(7.5, $a4['sub']);
        $this->assertGreaterThanOrEqual(16, $a4['logo-h']);
        $this->assertSame([10.0, 6.0, 12.0, 18.0], [$a5['name'], $a5['sub'], $a5['logo-h'], $a5['logo-w']], 'A5 tetap rapat seperti semula');
        $this->assertStringContainsString('.dl-pdoc__kop{display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:4mm;height:14.5mm', $css, 'kop tanpa style (dan A5) tetap 14,5 mm');

        // Pagination A4: kop 20 mm (+5,5) dibayar 2 mm margin atas = +3,5 mm; sisa ruang A4 terkecil (infoRows 7) 5,3 mm.
        preg_match('/\.is-a4 \.dl-pdoc__kop:has\(\.dl-pdoc__co\.is-styled\)\{height:([\d.]+)mm/', $css, $kop);
        preg_match('/\.dl-pdoc__sheet\.is-a4:has\(\.dl-pdoc__co\.is-styled\)\{padding-top:([\d.]+)mm/', $css, $pad);
        $this->assertLessThanOrEqual(5.2, ((float) $kop[1] - 14.5) - (10 - (float) $pad[1]), 'kop A4 bergaya melebihi sisa ruang lembar');
        $this->assertGreaterThanOrEqual(17.5, (float) $kop[1] - 1.5, 'baris di kop A4 harus muat tanpa terpotong');
    }
}
