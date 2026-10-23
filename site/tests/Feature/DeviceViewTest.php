<?php

namespace Tests\Feature;

use Devlib\Device;
use Devlib\ViewOverride;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

// Multi view baku (skills/devlib/views.md): Device::view() / os() dari User-Agent nyata, cookie kanonik + alias lama, override pratinjau, saklar "Lihat sebagai".
class DeviceViewTest extends TestCase
{
    // [nama => [User-Agent, view, os]]
    private const UAS = [
        'Chrome Android ponsel' => ['Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 'phone', 'android'],
        'Chrome Android tablet' => ['Mozilla/5.0 (Linux; Android 14; SM-X810) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'desktop', 'android'],
        'Safari iPhone' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1', 'phone', 'ios'],
        'Chrome iPhone' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/141.0.0.0 Mobile/15E148 Safari/604.1', 'phone', 'ios'],
        'Safari iPad (iPadOS lama)' => ['Mozilla/5.0 (iPad; CPU OS 12_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.0 Mobile/15E148 Safari/604.1', 'desktop', 'ios'],
        'Safari iPad (UA Mac)' => ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Safari/605.1.15', 'desktop', 'other'],
        'Safari Mac' => ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Safari/605.1.15', 'desktop', 'other'],
        'Chrome Windows' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'desktop', 'other'],
        'Edge Windows' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'desktop', 'other'],
        'Edge Android ponsel' => ['Mozilla/5.0 (Linux; Android 14; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36 EdgA/141.0.0.0', 'phone', 'android'],
        'Samsung Internet ponsel' => ['Mozilla/5.0 (Linux; Android 14; SAMSUNG SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/26.0 Chrome/122.0.0.0 Mobile Safari/537.36', 'phone', 'android'],
        'Firefox Android ponsel' => ['Mozilla/5.0 (Android 14; Mobile; rv:131.0) Gecko/131.0 Firefox/131.0', 'phone', 'android'],
        'Firefox Android tablet' => ['Mozilla/5.0 (Android 14; Tablet; rv:131.0) Gecko/131.0 Firefox/131.0', 'desktop', 'android'],
        'Firefox Linux' => ['Mozilla/5.0 (X11; Linux x86_64; rv:131.0) Gecko/20100101 Firefox/131.0', 'desktop', 'other'],
    ];

    private function req(?string $ua, array $cookies = [], array $server = []): Request
    {
        return Request::create('/', 'GET', [], $cookies, [], ($ua === null ? [] : ['HTTP_USER_AGENT' => $ua]) + $server);
    }

    public function test_view_and_os_from_real_user_agents(): void
    {
        foreach (self::UAS as $name => [$ua, $view, $os]) {
            $this->assertSame($view, Device::view($this->req($ua)), "view: $name");
            $this->assertSame($os, Device::os($this->req($ua)), "os: $name");
        }
        $this->assertSame('desktop', Device::view($this->req(null)));
        $this->assertSame('other', Device::os($this->req(null)));
    }

    public function test_client_hint_decides_before_user_agent_but_the_cookie_decides_before_both(): void
    {
        [$android] = self::UAS['Chrome Android ponsel'];
        [$windows] = self::UAS['Chrome Windows'];
        $this->assertSame('desktop', Device::view($this->req($android, [], ['HTTP_SEC_CH_UA_MOBILE' => '?0'])));
        $this->assertSame('phone', Device::view($this->req($windows, [], ['HTTP_SEC_CH_UA_MOBILE' => '?1'])));
        $this->assertSame('desktop', Device::view($this->req($android, ['ui' => 'desktop'], ['HTTP_SEC_CH_UA_MOBILE' => '?1'])));
        $this->assertSame('phone', Device::view($this->req($windows, ['ui' => 'phone'], ['HTTP_SEC_CH_UA_MOBILE' => '?0'])));
    }

    public function test_old_cookie_value_hp_still_means_phone_and_junk_is_ignored(): void
    {
        [$windows] = self::UAS['Chrome Windows'];
        [$android] = self::UAS['Chrome Android ponsel'];
        $this->assertSame('phone', Device::view($this->req($windows, ['ui' => 'hp'])));
        $this->assertSame('phone', Device::HP);
        $this->assertSame('phone', Device::normalize('hp'));
        $this->assertNull(Device::normalize('tablet'));
        $this->assertNull(Device::normalize(null));
        $this->assertSame('phone', Device::view($this->req($android, ['ui' => 'junk'])), 'cookie tak sah = tebakan');
        $this->assertSame(['phone', 'desktop'], Device::CHOICES, 'nilai yang diterima POST hanya yang kanonik');
    }

    public function test_override_wins_for_one_request_and_the_middleware_reads_the_query(): void
    {
        [$windows] = self::UAS['Chrome Windows'];
        $r = $this->req($windows, ['ui' => 'desktop']);
        Device::override($r, 'phone', 'ios');
        $this->assertSame('phone', Device::view($r));
        $this->assertSame('ios', Device::os($r));
        $this->assertSame('dl-on-phone dl-os-ios', Device::bodyClass($r));

        $q = Request::create('/?dl-view=phone&dl-os=android', 'GET', [], [], [], ['HTTP_USER_AGENT' => $windows]);
        (new ViewOverride)->handle($q, fn () => response('ok'));
        $this->assertSame('phone', Device::view($q));
        $this->assertSame('android', Device::os($q));

        $junk = Request::create('/?dl-view=banana', 'GET', [], [], [], ['HTTP_USER_AGENT' => $windows]);
        (new ViewOverride)->handle($junk, fn () => response('ok'));
        $this->assertSame('desktop', Device::view($junk), 'nilai tak sah diabaikan');
    }

    public function test_desktop_view_gets_no_body_class_and_the_old_viewport(): void
    {
        foreach (self::UAS as $name => [$ua, $view, $os]) {
            $r = $this->req($ua);
            $this->assertSame($view === 'phone' ? 'dl-on-phone'.($os === 'other' ? '' : " dl-os-$os") : '', Device::bodyClass($r), $name);
            $this->assertSame($view === 'phone' ? 'width=device-width, initial-scale=1.0, viewport-fit=cover' : 'width=device-width, initial-scale=1.0', Device::viewport($r), $name);
        }
    }

    public function test_os_css_is_scoped_to_the_phone_view_only(): void
    {
        $css = file_get_contents(base_path('../resources/assets/devlib.css'));
        $block = substr($css, strpos($css, '/* ==== Multi view'), strpos($css, '/* ==== /Multi view') - strpos($css, '/* ==== Multi view'));
        $rules = array_filter(array_map('trim', preg_split('/\R/', $block)), fn ($l) => $l !== '' && ! str_starts_with($l, '/*') && ! str_starts_with($l, 'iOS') && ! str_starts_with($l, 'Android'));
        $this->assertNotEmpty($rules);
        foreach ($rules as $line) {
            $this->assertMatchesRegularExpression('/^(html:has\(body\.dl-on-phone\)|body\.dl-on-phone|@media \(pointer:coarse\)\{body\.dl-touch)/', $line, 'aturan multi view harus dibatasi view phone atau opt-in dl-touch: '.$line);
        }
    }

    public function test_device_frame_without_src_is_unchanged_and_with_src_draws_an_iframe_per_preset(): void
    {
        $slot = Blade::render('<x-dl::device-frame mode="tablet">isi</x-dl::device-frame>');
        $this->assertStringContainsString('data-mode="tablet"', $slot);
        $this->assertStringNotContainsString('<iframe', $slot);

        $html = Blade::render('<x-dl::device-frame src="/lib/x" preset="ios" back="/lib/x" />');
        $this->assertStringContainsString('width:393px;height:852px', $html);
        $this->assertStringContainsString('src="/lib/x?dl-view=phone&amp;dl-os=ios"', $html);
        $this->assertStringContainsString('data-src="/lib/x?dl-view=desktop&amp;dl-os=other"', $html);
        $this->assertSame(5, substr_count($html, 'data-preset='));
        $this->assertStringContainsString('width:360px;height:790px', Blade::render('<x-dl::device-frame src="/a?b=1" preset="nonsense" />'), 'preset tak dikenal = android');
        $this->assertStringContainsString('src="/a?b=1&amp;dl-view=phone&amp;dl-os=android"', Blade::render('<x-dl::device-frame src="/a?b=1" />'));
    }

    public function test_view_as_links_every_preset_and_the_catalog_page_shows_it(): void
    {
        $html = Blade::render('<x-dl::view-as href="/lib/x/as" />');
        foreach (['android', 'ios', 'tablet', 'laptop', 'desktop'] as $k) {
            $this->assertStringContainsString('href="/lib/x/as?as='.$k.'"', $html);
        }
        $user = ['login' => 'x', 'name' => 'X', 'role' => 'admin', 'position' => 'admin', 'developer' => true, 'lang_code' => 'en', 'email' => 'x@x'];
        $page = $this->withSession(['devlib_user' => $user])->get('/lib/p5-grid-tx')->assertOk();
        $page->assertSee('dl-viewas', false);
        $page->assertSee('content="width=device-width, initial-scale=1.0"', false);
        $this->assertStringNotContainsString('dl-on-phone', $page->getContent());

        $as = $this->withSession(['devlib_user' => $user])->get('/lib/p5-grid-tx/as?as=ios')->assertOk();
        $as->assertSee('src="'.url('/lib/p5-grid-tx').'?dl-view=phone&amp;dl-os=ios"', false);

        $inner = $this->withSession(['devlib_user' => $user])->get('/lib/p5-grid-tx?dl-view=phone&dl-os=ios')->assertOk();
        $inner->assertSee('dl-on-phone dl-os-ios', false);
        $inner->assertSee('viewport-fit=cover', false);
        $this->assertStringNotContainsString('dl-viewas', $inner->getContent(), 'pil tidak muncul di dalam iframe pratinjau');
    }
}
