<?php

namespace Tests\Feature;

use Devlib\Components\LookupGrid;
use Devlib\Components\Sheet;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

// Prop opt-in row-tone: warna baris dari kolom data; tanpa prop konfigurasi dan markup <tr> tidak berubah.
class SheetRowToneTest extends TestCase
{
    public function test_default_is_off_for_sheet_and_lookup_grid(): void
    {
        $this->assertNull((new Sheet)->config()['rowTone']);
        $this->assertNull((new LookupGrid)->config()['rowTone']);
    }

    public function test_lookup_grid_passes_the_prop_to_the_config(): void
    {
        $html = Blade::render('<x-dl::lookup-grid row-tone="tone" :rows="[[\'id\' => 1, \'a\' => \'x\', \'tone\' => \'danger\']]" :columns="[[\'key\' => \'a\', \'label\' => \'A\']]" />');

        $this->assertStringContainsString('&quot;rowTone&quot;:&quot;tone&quot;', $html);
    }

    public function test_script_and_style_know_the_tone_classes(): void
    {
        $root = dirname(__DIR__, 3).'/resources/assets';
        $js = (string) file_get_contents($root.'/js/components/sheet.js');
        $css = (string) file_get_contents($root.'/devlib.css');

        $this->assertStringContainsString('is-tone-${esc(t)}', $js);
        foreach (['danger', 'warn', 'success'] as $tone) {
            $this->assertStringContainsString("tr.is-tone-{$tone} td", $css);
        }
    }
}
