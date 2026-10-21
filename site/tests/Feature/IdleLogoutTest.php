<?php

namespace Tests\Feature;

use Devlib\Components\IdleLogout;
use Devlib\Lang;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

// x-dl::idle-logout: bawaan, atribut data-*, bahasa, dan tidak merender apa pun bila dimatikan. Logika waktu diuji di browser.
class IdleLogoutTest extends TestCase
{
    public function test_defaults_render_a_hidden_alertdialog(): void
    {
        $html = Blade::render('<x-dl::idle-logout />');
        foreach (['data-dl-idle', 'data-minutes="30"', 'data-warn="2"', 'data-ping-every="5"', 'data-logout-url=""', 'data-ping-url=""', 'data-login-url=""', 'data-dl-js="IdleLogout"', 'role="alertdialog"', 'aria-labelledby="dl-idle-title"', 'aria-describedby="dl-idle-msg"', ' hidden '] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
        $this->assertStringNotContainsString('data-preview', $html);
    }

    public function test_props_become_data_attributes(): void
    {
        $html = Blade::render('<x-dl::idle-logout :minutes="1" :warn="0.75" logout-url="/logout" ping-url="/session/ping" :ping-every="2.5" login-url="/login" preview class="x" />');
        foreach (['data-minutes="1"', 'data-warn="0.75"', 'data-logout-url="/logout"', 'data-ping-url="/session/ping"', 'data-ping-every="2.5"', 'data-login-url="/login"', 'data-preview', 'class="dl-idle x"'] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
    }

    public function test_warn_is_clamped_to_minutes(): void
    {
        $this->assertSame(3.0, (new IdleLogout(minutes: 3, warn: 10))->warn);
        $this->assertSame(0.0, (new IdleLogout(warn: -1))->warn);
    }

    public function test_renders_nothing_when_switched_off(): void
    {
        $this->assertSame('', trim(Blade::render('<x-dl::idle-logout :minutes="0" />')));
    }

    public function test_texts_follow_the_display_language(): void
    {
        foreach (['id' => ['Tetap masuk', 'Keluar sekarang'], 'en' => ['Stay signed in', 'Sign out now']] as $lang => $words) {
            config(['devlib.lang' => $lang]);
            $html = Blade::render('<x-dl::idle-logout />');
            foreach ($words as $w) {
                $this->assertStringContainsString($w, $html, $lang.': '.$w);
            }
        }
        foreach (['idle_title', 'idle_msg', 'idle_stay', 'idle_out', 'idle_ended', 'idle_done', 'idle_preview', 'idle_again'] as $k) {
            $this->assertArrayHasKey($k, Lang::all());
        }
        $this->assertStringContainsString('{time}', Lang::all()['idle_msg'][0]);
    }
}
