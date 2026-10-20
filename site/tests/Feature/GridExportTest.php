<?php

namespace Tests\Feature;

use Devlib\DevlibServiceProvider;
use Tests\TestCase;

// Export grid (user 2026-10-21): tombol di toolbar grid membuka dua pilihan, CSV dan XLSX. XLSX dibuat di peramban tanpa pustaka.
class GridExportTest extends TestCase
{
    public function test_every_grid_toolbar_offers_csv_and_xlsx(): void
    {
        foreach (['p5-grid-report', 'p5-grid-tx', 'p5-flow-single'] as $slug) {
            $html = $this->get('/lib/'.$slug)->assertOk()->getContent();
            $this->assertStringContainsString('data-g-expmenu', $html, $slug);
            $this->assertStringContainsString('data-g-fmt="csv"', $html, $slug);
            $this->assertStringContainsString('data-g-fmt="xlsx"', $html, $slug);
        }
    }

    public function test_the_script_ships_the_xlsx_writer_and_the_grid_uses_it(): void
    {
        $js = $this->get(DevlibServiceProvider::asset('devlib.js'))->assertOk()->getContent();
        $this->assertStringContainsString('xlsxBlob', $js);
        $this->assertStringContainsString('downloadXlsx', $js);
        $this->assertStringContainsString('exportFile', $js);
    }
}
