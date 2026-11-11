<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga x-dl::shot (potongan layar bercincin), x-dl::chapters (papan kartu, bab bergambar, saringan, tautan) dan slide shot/walk/hero x-dl::deck, serta CSS-nya tanpa warna literal.
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class ChaptersTest extends TestCase
{
    private function tasks(): array
    {
        return [
            ['id' => 'a', 'group' => 'g1', 'title' => 'Task A', 'summary' => 'Sum A', 'image' => '/a.png', 'pin' => true, 'flow' => ['One', 'Two'], 'tips' => ['Tip A'], 'related' => ['b'],
                'steps' => [[10, 20, 'Step one', 'Text one'], [60, 70, 'Step two', 'Text two']]],
            ['id' => 'b', 'group' => 'g1', 'title' => 'Task B', 'image' => '/b.png', 'steps' => [[50, 50, 'Only', 'Text']]],
        ];
    }

    public function test_shot_zooms_around_the_point_and_rings_it(): void
    {
        $html = Blade::render('<x-dl::shot src="/x.png" :x="30" :y="11" :zoom="2.4" />');

        foreach (['dl-shot', 'transform-origin: 30% 11%', '--z: 2.4', 'dl-shot__ring', 'left: 30%; top: 11%', 'aria-hidden="true"'] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
        $this->assertStringNotContainsString('dl-shot__ring', Blade::render('<x-dl::shot src="/x.png" :ring="false" />'));
    }

    public function test_board_has_cards_flow_pin_and_one_chapter_per_task(): void
    {
        $html = Blade::render('<x-dl::chapters :tasks="$t" :groups="$g" present-url="/p/{id}" />', ['t' => $this->tasks(), 'g' => ['g1' => ['title' => 'Group One', 'hue' => 150]]]);

        foreach (['data-dl-chapters', 'data-ch-search', 'data-ch-group="g1"', 'dl-ch__card--pin', 'data-ch-open="a"', 'data-ch-art="a"', 'data-ch-art="b"', 'id="ch-a-s2"', 'id="ch-a-flow"', 'id="ch-a-tips"', 'href="/p/a"', 'Group One'] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
        $this->assertSame(3, substr_count($html, 'data-ch-card data-g='),'dua kartu tugas + satu kartu alur');
        $this->assertSame(2, substr_count($html, 'data-ch-art='), 'satu bab per tugas');
        $this->assertStringContainsString('data-ch-jump="ch-a-next"', $html, 'tugas A menaut ke B sebagai bacaan berikutnya');
    }

    public function test_shot_anim_and_ambient(): void
    {
        $h = Blade::render('<x-dl::shot src="/a.png" anim="/a.webp" />');
        $this->assertStringContainsString('prefers-reduced-motion', $h);
        $this->assertStringContainsString('src="/a.webp"', $h);
        $this->assertStringNotContainsString('dl-shot__ring', $h);
        $a = Blade::render('<x-dl::ambient />');
        foreach (['data-dl-ambient', 'data-am-play', 'data-am-mute'] as $n) {
            $this->assertStringContainsString($n, $a, $n);
        }
    }

    public function test_deck_picture_slides(): void
    {
        $html = Blade::render('<x-dl::deck :slides="$s" />', ['s' => [
            ['hero' => true, 'tag' => 'Tag', 'title' => 'Open', 'shot' => ['src' => '/a.png', 'x' => 10, 'y' => 20]],
            ['title' => 'Flow', 'items' => ['One', 'Two'], 'flow' => true],
            ['title' => 'Walk', 'walk' => ['src' => '/a.png', 'steps' => [[10, 20, 'S1', 'T1'], [30, 40, 'S2', 'T2']]]],
        ]]);

        foreach (['dl-deck__slide--hero', 'dl-deck__orb', 'dl-deck__tag', 'dl-deck__frame', 'dl-deck__items--flow', 'data-dk-walk', 'data-ms="8300"', 'data-dk-cam', 'data-dk-ring', 'transform-origin: 10% 20%'] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
    }

    public function test_css_uses_tokens_only(): void
    {
        $css = file_get_contents(dirname(__DIR__, 3).'/resources/assets/devlib.css');
        preg_match_all('/\.dl-(?:shot|ch|deck__(?:orb|frame|cam|ring|cap|clip|bt|tag|slide--hero|slide--shot|items--flow))[^{]*\{[^}]*\}/', $css, $m);
        $this->assertNotEmpty($m[0]);
        $this->assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{3,6}\b/', implode('', $m[0]));
    }
}
