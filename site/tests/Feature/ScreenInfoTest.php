<?php

namespace Tests\Feature;

use Devlib\Components\ScreenInfo;
use Devlib\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

// x-dl::screen-info (diagnostik layar): kontrak markup, sumber nilai server, kamus teks skrip, dan skrip murni sisi klien.
class ScreenInfoTest extends TestCase
{
    public function test_every_row_has_a_value_cell_and_the_page_has_the_copy_parts(): void
    {
        $html = Blade::render('<x-dl::screen-info />');
        foreach (ScreenInfo::GROUPS as $rows) {
            foreach (array_keys($rows) as $key) {
                $this->assertStringContainsString('data-sinfo="'.$key.'"', $html, $key);
            }
        }
        foreach (['data-dl-screen-info', 'data-sinfo-sum', 'data-sinfo-copy', 'data-sinfo-status', 'data-sinfo-text', 'data-sinfo-details'] as $hook) {
            $this->assertStringContainsString($hook, $html, $hook);
        }
        $this->assertStringContainsString('data-view="desktop"', $html);
        $this->assertStringContainsString('data-view-source="user-agent"', $html);
    }

    public function test_view_source_says_where_the_choice_came_from(): void
    {
        $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36';
        $make = fn (array $cookies = [], array $server = []) => Request::create('/', 'GET', [], $cookies, [], ['HTTP_USER_AGENT' => $ua] + $server);
        $this->assertSame('user-agent', Device::viewSource($make()));
        $this->assertSame('client-hint', Device::viewSource($make([], ['HTTP_SEC_CH_UA_MOBILE' => '?0'])));
        $this->assertSame('cookie', Device::viewSource($make(['ui' => 'hp'])));
        $r = $make(['ui' => 'desktop']);
        Device::override($r, 'phone');
        $this->assertSame('override', Device::viewSource($r));
        $this->assertSame('cookie', Device::viewSource($make(['ui' => 'desktop'], ['HTTP_SEC_CH_UA_MOBILE' => '?1'])));
    }

    public function test_script_texts_are_passed_from_the_server_and_every_text_the_script_uses_is_listed(): void
    {
        $js = file_get_contents(base_path('../resources/assets/js/components/screen-info.js'));
        preg_match_all("/this\.t\('([^']+)'\)/", $js, $m);
        $this->assertNotEmpty($m[1]);
        foreach (array_unique($m[1]) as $text) {
            $this->assertContains($text, ScreenInfo::TEXTS, "teks skrip tidak ada di ScreenInfo::TEXTS: $text");
        }
        $this->assertStringContainsString('data-t="', Blade::render('<x-dl::screen-info />'));
    }

    public function test_script_is_client_side_only_and_has_the_copy_fallbacks(): void
    {
        $js = file_get_contents(base_path('../resources/assets/js/components/screen-info.js'));
        foreach (['fetch(', 'XMLHttpRequest', 'sendBeacon', 'localStorage', 'sessionStorage', 'document.cookie', '<img'] as $banned) {
            $this->assertStringNotContainsString($banned, $js, "screen-info.js tidak boleh memakai $banned");
        }
        foreach (['navigator.clipboard', "execCommand('copy')", 'isSecureContext', 'visualViewport', 'safe-area-inset-top', 'svh', 'lvh', 'dvh', 'userAgentData', 'Devlib.PHONE_MQ'] as $needed) {
            $this->assertStringContainsString($needed, $js, $needed);
        }
    }

    public function test_catalog_page_opens_and_is_in_review(): void
    {
        $user = ['login' => 'x', 'name' => 'X', 'role' => 'admin', 'position' => 'admin', 'developer' => true, 'lang_code' => 'en', 'email' => 'x@x'];
        $this->withSession(['devlib_user' => $user])->get('/lib/p7-screen')->assertOk()->assertSee('data-dl-screen-info', false);
        $this->assertArrayNotHasKey('p7-screen', \App\Support\Catalog::APPROVED);
    }
}
