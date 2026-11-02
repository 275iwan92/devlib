<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga x-dl::floor-plan (denah dengan titik objek): ruang skematis atau gambar latar, kelas tone titik, titik terpilih, input tersembunyi, tautan href, legenda, teks status, slot, dan CSS tanpa warna literal.
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class FloorPlanTest extends TestCase
{
    private const ROOMS = [['code' => 'R1', 'label' => 'Meeting', 'x' => 4, 'y' => 6, 'w' => 40, 'h' => 38]];

    private const PINS = [
        ['id' => 'a', 'x' => 20, 'y' => 30, 'tone' => 'success', 'label' => 'Unit A', 'number' => '01'],
        ['id' => 'b', 'x' => 60, 'y' => 30, 'tone' => 'warn', 'label' => 'Unit B', 'status' => 'Due in 5 days'],
        ['id' => 'c', 'x' => 60, 'y' => 70, 'tone' => 'danger', 'label' => 'Unit C'],
        ['id' => 'd', 'x' => 10, 'y' => 70, 'tone' => 'bogus', 'label' => 'Unit D'],
    ];

    private function plan(string $attrs = '', string $slots = ''): string
    {
        return Blade::render('<x-dl::floor-plan :rooms="$rooms" :pins="$pins" '.$attrs.'>'.$slots.'</x-dl::floor-plan>', ['rooms' => self::ROOMS, 'pins' => self::PINS]);
    }

    public function test_draws_rooms_with_code_and_name_and_pins_with_tone_classes(): void
    {
        $html = $this->plan();

        $this->assertStringContainsString('<b>R1</b> Meeting', $html);
        $this->assertStringContainsString('left: 4%; top: 6%; width: 40%; height: 38%', $html);
        $this->assertStringContainsString('aspect-ratio: 16 / 9', $html);
        foreach (['is-success', 'is-warn', 'is-danger'] as $c) {
            $this->assertStringContainsString('dl-floorplan__pin '.$c, $html);
        }
        $this->assertStringContainsString('dl-floorplan__pin is-neutral', $html, 'tone tak dikenal jatuh ke neutral');
        $this->assertStringContainsString('left: 20%; top: 30%', $html);
        $this->assertMatchesRegularExpression('/data-pin="a"[^>]*>01</', $html, 'number dipakai sebagai teks titik');
        $this->assertMatchesRegularExpression('/data-pin="b"[^>]*>2</', $html, 'tanpa number: urutan');
        $this->assertStringContainsString('data-dl-js="FloorPlan"', $html);
    }

    public function test_status_text_is_available_not_only_color(): void
    {
        $html = $this->plan();

        $this->assertStringContainsString('aria-label="Unit A, Safe"', $html);
        $this->assertStringContainsString('aria-label="Unit B, Due in 5 days"', $html);
        $this->assertStringContainsString('aria-label="Unit C, Overdue"', $html);
        $this->assertStringContainsString('aria-label="Unit D"', $html);
        $this->assertStringContainsString('title="Unit A · Safe"', $html);
    }

    public function test_selected_pin_is_marked_and_the_hidden_input_carries_it(): void
    {
        $html = $this->plan('selected="b" name="pin"');

        $this->assertMatchesRegularExpression('/dl-floorplan__pin is-warn is-selected"[^>]*data-pin="b"[^>]*aria-pressed="true"/', $html);
        $this->assertMatchesRegularExpression('/data-pin="a"[^>]*aria-pressed="false"/', $html);
        $this->assertStringContainsString('<input type="hidden" name="pin" value="b" data-floorplan-input>', $html);
        $this->assertStringNotContainsString('type="hidden"', $this->plan());
    }

    public function test_pins_with_href_are_links_and_readonly_pins_are_not_buttons(): void
    {
        $pins = [['id' => 'a', 'x' => 1, 'y' => 1, 'tone' => 'success', 'label' => 'Unit A', 'href' => '/plan?pin=a']];
        $html = Blade::render('<x-dl::floor-plan :pins="$pins" selected="a" />', ['pins' => $pins]);
        $this->assertMatchesRegularExpression('/<a class="dl-floorplan__pin[^"]*"[^>]*href="\/plan\?pin=a"[^>]*aria-current="true"/', $html);
        $this->assertStringNotContainsString('<button', $html);

        $html = $this->plan(':readonly="true"');
        $this->assertStringNotContainsString('<button', $html);
        $this->assertStringContainsString('<span class="dl-floorplan__pin', $html);
        $this->assertStringContainsString('is-readonly', $html);
    }

    public function test_image_replaces_the_schematic_rooms_and_keeps_the_pins(): void
    {
        $html = $this->plan('image="/img/plan.png" aspect="4:3" label="Floor 2"');

        $this->assertStringContainsString('<img class="dl-floorplan__img" src="/img/plan.png" alt="Floor 2">', $html);
        $this->assertStringNotContainsString('dl-floorplan__room', $html);
        $this->assertStringContainsString('aspect-ratio: 4 / 3', $html);
        $this->assertStringContainsString('aria-label="Floor 2"', $html);
        $this->assertStringContainsString('data-pin="a"', $html);
        $this->assertStringContainsString('aspect-ratio: 16 / 9', $this->plan('aspect="nonsense"'));
    }

    public function test_legend_default_and_custom(): void
    {
        $this->assertStringNotContainsString('dl-floorplan__legend', $this->plan());

        $html = $this->plan(':legend="true"');
        $this->assertStringContainsString('dl-floorplan__key is-success"></i>Safe', $html);
        $this->assertStringContainsString('dl-floorplan__key is-warn"></i>Due soon', $html);
        $this->assertStringContainsString('dl-floorplan__key is-danger"></i>Overdue', $html);

        $html = $this->plan(':legend="[[\'tone\' => \'neutral\', \'label\' => \'Idle\']]"');
        $this->assertStringContainsString('dl-floorplan__key is-neutral"></i>Idle', $html);
        $this->assertStringNotContainsString('Overdue</li>', $html);
    }

    public function test_summary_and_detail_slots(): void
    {
        $html = $this->plan('', '<x-slot:summary>SUM-BOX</x-slot:summary><x-slot:detail>DETAIL-CARD</x-slot:detail>');

        $this->assertMatchesRegularExpression('/dl-floorplan__summary">SUM-BOX.*dl-floorplan__plan.*dl-floorplan__detail">DETAIL-CARD/s', $html);
        $plain = $this->plan();
        $this->assertStringNotContainsString('dl-floorplan__summary', $plain);
        $this->assertStringNotContainsString('dl-floorplan__detail', $plain);
    }

    public function test_component_css_uses_tokens_only(): void
    {
        $css = (string) file_get_contents(dirname(base_path()).'/resources/assets/devlib.css');
        preg_match_all('/^[^\n]*\.dl-floorplan[^\n]*$/m', $css, $m);

        $this->assertNotEmpty($m[0]);
        foreach ($m[0] as $line) {
            $this->assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{3,8}\b|\brgba?\(|\bhsla?\(/', $line, $line);
        }
    }

    public function test_js_class_is_in_the_bundle_source(): void
    {
        $js = (string) file_get_contents(dirname(base_path()).'/resources/assets/js/components/floor-plan.js');
        $this->assertStringContainsString('Devlib.define("FloorPlan"', $js);
        $this->assertStringContainsString('dl:floorplan-select', $js);
    }
}
