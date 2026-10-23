<?php

namespace Tests\Feature;

use Devlib\Device;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

// Tampilan HP: tebakan perangkat (Device), pilihan tampilan (x-dl::device-pick) dan kerangka x-dl::phone-shell.
class PhoneShellTest extends TestCase
{
    private const CHROME_PHONE = 'Mozilla/5.0 (Linux; Android 15; CPH2700) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36';

    private const CHROME_TABLET = 'Mozilla/5.0 (Linux; Android 14; SM-X810) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36';

    private const IPAD = 'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';

    private const IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';

    private const WINDOWS = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36';

    public function test_only_phones_are_guessed_as_hp(): void
    {
        $this->assertSame('hp', Device::detect(self::CHROME_PHONE));
        $this->assertSame('hp', Device::detect(self::IPHONE));
        $this->assertSame('desktop', Device::detect(self::CHROME_TABLET));
        $this->assertSame('desktop', Device::detect(self::IPAD));
        $this->assertSame('desktop', Device::detect(self::WINDOWS));
        $this->assertSame('desktop', Device::detect(null));
    }

    public function test_client_hint_wins_over_user_agent_and_a_valid_cookie_wins_over_both(): void
    {
        $this->assertSame('hp', Device::detect(self::WINDOWS, '?1'));
        $this->assertSame('desktop', Device::detect(self::CHROME_PHONE, '?0'));
        $this->assertSame('desktop', Device::resolve('desktop', self::CHROME_PHONE, '?1'));
        $this->assertSame('hp', Device::resolve('hp', self::WINDOWS, '?0'));
        $this->assertSame('hp', Device::resolve('rubbish', self::CHROME_PHONE), 'cookie tak sah = tebakan');
    }

    public function test_device_pick_preselects_the_given_value_and_posts_ui(): void
    {
        $html = Blade::render('<x-dl::device-pick value="hp" />');
        $this->assertMatchesRegularExpression('/name="ui" value="hp" checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/value="desktop" checked/', $html);
    }

    public function test_login_box_draws_the_device_part_only_when_asked(): void
    {
        $this->assertStringNotContainsString('dl-devpick', Blade::render('<x-dl::login-box />'));
        $this->assertStringContainsString('dl-devpick', Blade::render('<x-dl::login-box show="username, device, button" />'));
    }

    public function test_phone_shell_has_five_bottom_tabs_and_a_menu_sheet_of_all_modules(): void
    {
        $html = Blade::render('<x-dl::phone-shell title="Home" active="a" :tabs="$tabs" :modules="$modules" logout-action="/out" device-action="/ui" :user="[\'name\' => \'Ann\', \'role\' => \'Dev\']">isi</x-dl::phone-shell>', [
            'tabs' => array_map(fn ($k) => ['key' => $k, 'label' => strtoupper($k), 'icon' => 'fa-solid fa-circle', 'url' => "/$k"], ['a', 'b', 'c', 'd']),
            'modules' => [['code' => 'M', 'label' => 'Group One', 'children' => [['code' => 'X', 'label' => 'Item X', 'url' => '/x'], ['label' => 'Sub', 'items' => [['code' => 'Y', 'label' => 'Item Y', 'url' => '/y']]]]]],
        ]);
        $this->assertSame(5, substr_count($html, 'dl-phone__tab'.'"') + substr_count($html, 'dl-phone__tab is-active"'), 'empat tautan + Menu');
        $this->assertStringContainsString('aria-current="page"', $html);
        foreach (['Group One', 'href="/x"', 'href="/y"', 'action="/out"', 'action="/ui"', 'name="ui"', 'isi'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
        $this->assertStringNotContainsString('<iframe', $html);
    }

    public function test_phone_shell_frame_replaces_the_slot_with_an_iframe(): void
    {
        $html = Blade::render('<x-dl::phone-shell title="Items" back="/" frame="/products">slot</x-dl::phone-shell>');
        $this->assertStringContainsString('<iframe class="dl-phone__frame" src="/products"', $html);
        $this->assertStringNotContainsString('>slot<', $html);
        $this->assertStringContainsString('href="/"', $html);
    }

    public function test_phone_demo_page_opens(): void
    {
        $this->get('/lib/p7-phone')->assertOk()->assertSee('dl-phone', false);
    }

    public function test_phone_offer_is_written_only_when_no_view_is_chosen_and_the_device_is_desktop(): void
    {
        $tag = '<x-dl::phone-offer action="/ui" />';

        $render = function (array $cookies, string $ua) use ($tag) {
            $req = \Illuminate\Http\Request::create('/', 'GET', [], $cookies, [], ['HTTP_USER_AGENT' => $ua]);
            app()->instance('request', $req);

            return Blade::render($tag);
        };
        $this->assertStringContainsString('name="ui" value="hp"', $render([], self::WINDOWS));
        $this->assertStringContainsString('name="ui" value="desktop"', $render([], self::WINDOWS));
        $this->assertStringNotContainsString('dl-phoneoffer', $render([], self::CHROME_PHONE), 'ponsel = tampilan HP langsung');
        $this->assertStringNotContainsString('dl-phoneoffer', $render(['ui' => 'desktop'], self::WINDOWS), 'pilihan sudah dibuat');
        $this->assertStringContainsString('dl-phoneoffer', Blade::render('<x-dl::phone-offer action="/ui" force />'));
    }
}
