<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga x-dl::music-player (kerangka, penanda JS, label tombol, kosong = tidak dirender) dan opt-in animate / music di x-dl::greeting-card; CSS tanpa warna literal.
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class MusicPlayerTest extends TestCase
{
    private const TRACKS = [['file' => 'a.mp3', 'title' => 'Calm', 'author' => 'Sample Author', 'license' => 'CC BY 4.0', 'license_url' => 'https://creativecommons.org/licenses/by/4.0/']];

    public function test_player_has_play_mute_volume_credit_and_the_track_list(): void
    {
        $html = Blade::render('<x-dl::music-player :tracks="$t" base="/audio" storage-key="k1" :volume="30" />', ['t' => self::TRACKS]);

        $this->assertStringContainsString('data-dl-music', $html);
        $this->assertStringContainsString('data-key="k1"', $html);
        $this->assertStringContainsString('data-base="/audio/"', $html);
        $this->assertStringContainsString('data-dl-music-play', $html);
        $this->assertStringContainsString('aria-label="Play music"', $html);
        $this->assertStringContainsString('aria-pressed="false"', $html);
        $this->assertStringContainsString('data-dl-music-mute', $html);
        $this->assertStringContainsString('data-dl-music-vol', $html);
        $this->assertStringContainsString('data-dl-music-credit', $html);
        $this->assertStringContainsString('&quot;title&quot;:&quot;Calm&quot;', $html);
    }

    public function test_music_is_off_by_default_until_the_user_presses_play(): void
    {
        $js = file_get_contents(dirname(__DIR__, 3).'/resources/assets/js/components/music-player.js');

        $this->assertStringContainsString("this.s = {on: false, mute: false", $js);
        $this->assertStringContainsString('this.s.on = false;', $js, 'Status on dari penyimpanan tidak boleh menyalakan musik.');
        $this->assertStringContainsString("aria-pressed=\"false\"", Blade::render('<x-dl::music-player :tracks="$t" />', ['t' => self::TRACKS]));
    }

    public function test_without_tracks_nothing_is_rendered(): void
    {
        $this->assertSame('', trim(Blade::render('<x-dl::music-player />')));
    }

    public function test_greeting_card_animation_and_music_are_opt_in(): void
    {
        $board = ['profile' => 'general', 'user' => ['first' => 'Yuna']];

        $plain = Blade::render('<x-dl::greeting-card :board="$b" />', ['b' => $board]);
        $this->assertStringNotContainsString('data-dl-hero-fx', $plain);
        $this->assertStringNotContainsString('dl-dash__fx', $plain);
        $this->assertStringNotContainsString('data-dl-music', $plain);

        $rich = Blade::render('<x-dl::greeting-card :board="$b" />', ['b' => $board + ['animate' => true, 'music' => ['tracks' => self::TRACKS, 'base' => '/audio']]]);
        $this->assertStringContainsString('data-dl-hero-fx', $rich);
        $this->assertStringContainsString('<canvas class="dl-dash__fx" aria-hidden="true">', $rich);
        $this->assertStringContainsString('data-dl-music', $rich);
    }

    public function test_motion_is_switched_off_for_reduced_motion_and_css_uses_tokens(): void
    {
        $css = file_get_contents(dirname(__DIR__, 3).'/resources/assets/devlib.css');
        $js = file_get_contents(dirname(__DIR__, 3).'/resources/assets/js/components/hero-fx.js');

        $this->assertStringContainsString('@media (prefers-reduced-motion:reduce){.dl-dash__fx{display:none}}', $css);
        $this->assertStringContainsString('prefers-reduced-motion: reduce', $js);
        preg_match_all('/^\.dl-music[^{]*\{[^}]*\}/m', $css, $rules);
        $this->assertNotEmpty($rules[0]);
        foreach ($rules[0] as $rule) {
            $this->assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{3,8}\b/', $rule, 'Warna literal di '.$rule);
        }
    }
}
