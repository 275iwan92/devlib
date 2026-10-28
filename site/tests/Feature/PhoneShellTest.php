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

    public function test_only_phones_are_guessed_as_phone(): void
    {
        $this->assertSame('phone', Device::detect(self::CHROME_PHONE));
        $this->assertSame('phone', Device::detect(self::IPHONE));
        $this->assertSame('desktop', Device::detect(self::CHROME_TABLET));
        $this->assertSame('desktop', Device::detect(self::IPAD));
        $this->assertSame('desktop', Device::detect(self::WINDOWS));
        $this->assertSame('desktop', Device::detect(null));
    }

    public function test_client_hint_wins_over_user_agent_and_a_valid_cookie_wins_over_both(): void
    {
        $this->assertSame('phone', Device::detect(self::WINDOWS, '?1'));
        $this->assertSame('desktop', Device::detect(self::CHROME_PHONE, '?0'));
        $this->assertSame('desktop', Device::resolve('desktop', self::CHROME_PHONE, '?1'));
        $this->assertSame('phone', Device::resolve('hp', self::WINDOWS, '?0'));
        $this->assertSame('phone', Device::resolve('rubbish', self::CHROME_PHONE), 'cookie tak sah = tebakan');
    }

    public function test_device_pick_preselects_the_given_value_and_posts_ui(): void
    {
        $html = Blade::render('<x-dl::device-pick value="hp" />');
        $this->assertMatchesRegularExpression('/name="ui" value="phone" checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/value="desktop" checked/', $html);
    }

    public function test_login_box_draws_the_device_part_only_when_asked(): void
    {
        $this->assertStringNotContainsString('dl-devpick', Blade::render('<x-dl::login-box />'));
        $this->assertStringContainsString('dl-devpick', Blade::render('<x-dl::login-box show="username, device, button" />'));
    }

    public function test_phone_shell_has_one_floating_hamburger_no_bars_and_a_row_of_main_menus_with_a_module_list(): void
    {
        $html = Blade::render('<x-dl::phone-shell title="Home" active="a" :tabs="$tabs" :modules="$modules" logout-action="/out" device-action="/ui" :user="$user">isi</x-dl::phone-shell>', [
            'user' => ['name' => 'Ann', 'role' => 'Dev'],
            'tabs' => array_map(fn ($k) => ['key' => $k, 'label' => strtoupper($k), 'icon' => 'fa-solid fa-circle', 'url' => "/$k"], ['a', 'b', 'c', 'd']),
            'modules' => [['code' => 'M', 'label' => 'Group One', 'children' => [['code' => 'X', 'label' => 'Item X', 'url' => '/x'], ['label' => 'Sub', 'items' => [['code' => 'Y', 'label' => 'Item Y', 'url' => '/y']]]]]],
        ]);
        $this->assertSame(1, substr_count($html, 'data-dl-phone-menu'), 'satu tombol hamburger');
        $this->assertSame(1, substr_count($html, 'class="dl-phone__burger"'));
        $this->assertStringContainsString('data-state="A"', $html, 'awal setiap layar = A');
        $this->assertStringNotContainsString('dl-phone__tabs', $html, 'tidak ada bilah tab bawah');
        $this->assertStringNotContainsString('dl-phone__top', $html, 'tidak ada bilah atas permanen');
        $this->assertSame(5, preg_match_all('/class="dl-phone__q[ "]/', $html), 'empat menu utama + tombol Menu');
        $this->assertSame(1, substr_count($html, 'data-dl-phone-more'));
        $this->assertStringContainsString('aria-current="page"', $html);
        $this->assertStringContainsString('aria-expanded="false"', $html);
        foreach (['data-l-a=', 'data-l-b=', 'data-l-c=', 'data-l-dirty=', 'Group One', 'href="/x"', 'href="/y"', 'action="/out"', 'action="/ui"', 'name="ui" value="desktop"', 'dl-phone__head', 'dl-phone__body', 'isi'] as $needle) {
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
        $this->assertStringContainsString('dl-phone__burger', $html, 'hamburger tetap ada di layar anak, kembali di sebelahnya');
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
        $this->assertStringContainsString('name="ui" value="phone"', $render([], self::WINDOWS));
        $this->assertStringContainsString('name="ui" value="desktop"', $render([], self::WINDOWS));
        $this->assertStringNotContainsString('dl-phoneoffer', $render([], self::CHROME_PHONE), 'ponsel = tampilan HP langsung');
        $this->assertStringNotContainsString('dl-phoneoffer', $render(['ui' => 'desktop'], self::WINDOWS), 'pilihan sudah dibuat');
        $this->assertStringContainsString('dl-phoneoffer', Blade::render('<x-dl::phone-offer action="/ui" force />'));
    }

    public function test_phone_offer_auto_is_opt_in_and_the_default_markup_has_no_script_hooks(): void
    {
        $req = \Illuminate\Http\Request::create('/', 'GET', [], [], [], ['HTTP_USER_AGENT' => self::WINDOWS]);
        app()->instance('request', $req);

        $plain = Blade::render('<x-dl::phone-offer action="/ui" />');
        $auto = Blade::render('<x-dl::phone-offer action="/ui" auto />');
        $this->assertStringNotContainsString('data-dl-phone-offer', $plain, 'bawaan: murni HTML + CSS, tanpa kait JS');
        $this->assertStringNotContainsString('data-auto', $plain);
        $this->assertStringContainsString('data-dl-phone-offer', $auto);
        $this->assertStringContainsString('data-auto', $auto);
        $this->assertStringContainsString('name="ui" value="phone"', $auto, 'popup tetap ada sebagai cadangan');
        $this->assertStringContainsString('class PhoneOffer', \Devlib\DevlibServiceProvider::bundle());
    }

    public function test_auto_chosen_view_counts_as_not_chosen_and_the_shell_returns_only_for_it(): void
    {
        $render = function (array $cookies, string $ua, string $tag) {
            app()->instance('request', \Illuminate\Http\Request::create('/', 'GET', [], $cookies, [], ['HTTP_USER_AGENT' => $ua]));

            return Blade::render($tag);
        };
        $offer = '<x-dl::phone-offer action="/ui" auto />';
        $this->assertStringContainsString('dl-phoneoffer', $render(['ui' => 'desktop', 'ui_auto' => '1'], self::WINDOWS, $offer), 'ui automatis = belum dipilih pengguna');
        $this->assertStringNotContainsString('dl-phoneoffer', $render(['ui' => 'desktop'], self::WINDOWS, $offer), 'pilihan pengguna tetap menang');

        $shell = '<x-dl::phone-shell title="Home" device-action="/ui" auto-return />';
        $this->assertStringContainsString('data-auto-return', $render(['ui' => 'phone', 'ui_auto' => '1'], self::WINDOWS, $shell));
        $this->assertStringNotContainsString('data-auto-return', $render(['ui' => 'phone'], self::WINDOWS, $shell), 'pilihan pengguna: tidak kembali sendiri');
        $this->assertStringContainsString('data-auto-return', $render([], self::WINDOWS, $shell), 'dibuka langsung sementara view = desktop: boleh kembali');
        $this->assertStringNotContainsString('data-auto-return', $render([], self::CHROME_PHONE, $shell), 'ponsel sungguhan: tetap phone');
        $this->assertStringNotContainsString('data-auto-return', $render(['ui' => 'phone', 'ui_auto' => '1'], self::WINDOWS, '<x-dl::phone-shell title="Home" device-action="/ui" />'), 'opt-in');
        $this->assertTrue(\Devlib\Device::autoChosen(\Illuminate\Http\Request::create('/', 'GET', [], ['ui_auto' => '1'])));
        $this->assertStringContainsString('initAutoReturn', \Devlib\DevlibServiceProvider::bundle());
    }

    public function test_lines_become_cards_only_in_the_phone_context_and_cells_carry_their_labels(): void
    {
        $css = file_get_contents(__DIR__.'/../../../resources/assets/devlib.css');
        preg_match_all('/^.*dl-lines__table tbody tr\[data-i\].*$/m', $css, $m);
        $this->assertNotEmpty($m[0]);
        foreach ($m[0] as $rule) {
            $this->assertStringContainsString(':is(body.dl-on-phone,.dl-phone)', $rule, 'aturan kartu Lines hanya di konteks phone');
        }
        $this->assertStringContainsString('dataset.label', file_get_contents(__DIR__.'/../../../resources/assets/js/components/lines.js'));
    }

    public function test_lookup_popup_and_pager_phone_rules_are_scoped_to_the_phone_context(): void
    {
        $css = file_get_contents(__DIR__.'/../../../resources/assets/devlib.css');
        foreach ([':is(body.dl-on-phone,.dl-phone) .dl-lines__pop{', ':is(body.dl-on-phone,.dl-phone) .dl-lookup__table thead{display:none}', ':is(body.dl-on-phone,.dl-phone) .dl-pager button{'] as $rule) {
            $this->assertStringContainsString($rule, $css);
        }
        $this->assertStringNotContainsString("
.dl-lines__pop{left:8px", $css, 'popup lookup bawaan (desktop) tidak diubah');
    }

    public function test_report_phone_list_is_opt_in_and_scoped_to_the_phone_screen(): void
    {
        $data = ['cols' => [['key' => 'a', 'label' => 'A']], 'rows' => [['a' => 1]]];
        $plain = Blade::render('<x-dl::report title="R" :columns="$cols" :rows="$rows" />', $data);
        $opt = Blade::render('<x-dl::report title="R" :columns="$cols" :rows="$rows" phone-list />', $data);
        $this->assertStringNotContainsString('data-phone-list', $plain, 'bawaan: lembar seperti sebelumnya');
        $this->assertStringContainsString('data-phone-list', $opt);

        $css = file_get_contents(__DIR__.'/../../../resources/assets/devlib.css');
        $this->assertStringContainsString(':is(body.dl-on-phone,.dl-phone) .dl-print[data-phone-list] .dl-print__pages{display:none}', $css);
        $this->assertStringNotContainsString("\n.dl-print__pages{display:none}", $css);
        $this->assertStringContainsString("hasAttribute('data-phone-list')", file_get_contents(__DIR__.'/../../../resources/assets/js/components/print.js'));
    }

    public function test_phone_parts_render_with_tone_badge_columns_and_the_login_page_links_back_to_desktop(): void
    {
        $tile = Blade::render('<x-dl::phone-tile label="Low" :value="4" url="/low" tone="danger" />');
        $this->assertStringContainsString('dl-ptile dl-ptile--danger', $tile);
        $this->assertStringContainsString('href="/low"', $tile);
        $this->assertStringContainsString('<strong>4</strong>', $tile);
        $this->assertStringNotContainsString('<strong>', Blade::render('<x-dl::phone-tile label="Low" />'));

        $link = Blade::render('<x-dl::phone-link label="Count" url="/c" badge="2" tone="info" />');
        $this->assertStringContainsString('dl-plink--info', $link);
        $this->assertStringContainsString('<em>2</em>', $link);

        $this->assertStringContainsString('grid-template-columns:1fr 2fr', Blade::render('<x-dl::phone-bar columns="1fr 2fr">x</x-dl::phone-bar>'));
        $this->assertStringNotContainsString('style=', Blade::render('<x-dl::phone-bar>x</x-dl::phone-bar>'));
        $this->assertStringContainsString('dl-ptiles', Blade::render('<x-dl::phone-tiles>x</x-dl::phone-tiles>'));

        $login = Blade::render('<x-dl::phone-login action="/in" device-action="/ui">msg</x-dl::phone-login>');
        foreach (['dl-plogin', 'action="/in"', 'action="/ui"', 'name="ui" value="desktop"', 'msg', 'dl-loginbox'] as $needle) {
            $this->assertStringContainsString($needle, $login);
        }
        $this->assertStringNotContainsString('dl-plogin__desk', Blade::render('<x-dl::phone-login action="/in" />'));
    }

    public function test_form_panel_buttons_sit_in_a_wrapper_and_phone_form_css_only_matches_the_phone_context(): void
    {
        $html = Blade::render('<x-dl::form title="T" back="/b" panel />');
        $this->assertStringContainsString('<div class="dl-form__acts">', $html);
        $this->assertSame(1, substr_count($html, 'dl-form__acts'));

        $css = (string) file_get_contents(base_path('../resources/assets/devlib.css'));
        $this->assertStringContainsString('.dl-form__acts{display:contents}', $css, 'desktop: pembungkus tidak mengubah tata letak');
        $block = substr($css, strpos($css, '/* ==== Form di tampilan HP'));
        $rules = array_filter(array_map('trim', preg_split('/\R/', $block)), fn ($l) => $l !== '' && ! str_starts_with($l, '/*') && ! str_starts_with($l, 'Tidak berlaku'));
        foreach ($rules as $line) {
            $this->assertMatchesRegularExpression('/^(:is\(body\.dl-on-phone,\.dl-phone\)|body\.dl-phone-(shelled|search|print)|\.dl-phone|@media) /', $line.' ', 'aturan form HP harus dibatasi konteks HP: '.$line);
        }
    }

    public function test_body_class_marks_only_the_phone_device(): void
    {
        $req = fn (string $ua, array $cookies = []) => \Illuminate\Http\Request::create('/', 'GET', [], $cookies, [], ['HTTP_USER_AGENT' => $ua]);
        $this->assertSame('dl-on-phone dl-os-android', Device::bodyClass($req(self::CHROME_PHONE)));
        $this->assertSame('', Device::bodyClass($req(self::WINDOWS)));
        $this->assertSame('', Device::bodyClass($req(self::CHROME_PHONE, ['ui' => 'desktop'])));
        $this->assertSame('dl-on-phone', Device::bodyClass($req(self::WINDOWS, ['ui' => 'hp'])));
    }

    public function test_grid_phone_cards_are_opt_in_and_leave_the_desktop_config_untouched(): void
    {
        $cols = [['key' => 'name', 'label' => 'Name']];
        $plain = Blade::render('<x-dl::grid id="g" :rows="[]" :columns="$c" />', ['c' => $cols]);
        $this->assertStringNotContainsString('phoneCards', $plain);
        $this->assertStringNotContainsString('data-g-view', $plain, 'tanpa kartu tidak ada tombol Tabel/Kartu');

        $with = Blade::render('<x-dl::grid id="g" :rows="[]" :columns="$c" :phone-cards="$pc" />', ['c' => $cols, 'pc' => ['title' => 'name']]);
        $this->assertStringContainsString('phoneCards', $with);
        $this->assertStringNotContainsString('data-g-view', $with, 'phone-cards tidak memunculkan tombol Tabel/Kartu di desktop');

        $css = (string) file_get_contents(base_path('../resources/assets/devlib.css'));
        $block = substr($css, strpos($css, '/* Grid di tampilan HP'));
        foreach (array_filter(array_map('trim', preg_split('/\R/', $block))) as $line) {
            if (str_starts_with($line, '/*') || str_starts_with($line, '.dl-phone ')) {
                continue;
            }
            $this->assertMatchesRegularExpression('/^(:is\(body\.dl-on-phone,\.dl-phone\)|body\.dl-phone-(shelled|search|print)|\.dl-phone|@media)/', $line, 'aturan grid HP harus dibatasi konteks HP: '.$line);
        }
    }

    public function test_table_stack_is_opt_in_and_only_adds_labels_and_a_class(): void
    {
        $cols = ['item' => 'Item', 'qty' => ['Qty', 'num' => true]];
        $rows = [['item' => 'Pensil', 'qty' => 5]];
        $plain = Blade::render('<x-dl::table :columns="$c" :rows="$r" />', ['c' => $cols, 'r' => $rows]);
        $this->assertStringNotContainsString('dl-table--stack', $plain);
        $this->assertStringNotContainsString('data-label', $plain, 'tanpa stack markup desktop identik');

        $stack = Blade::render('<x-dl::table :columns="$c" :rows="$r" :stack="true" />', ['c' => $cols, 'r' => $rows]);
        $this->assertStringContainsString('dl-table-wrap dl-table--stack', $stack);
        $this->assertStringContainsString('data-label="Item"', $stack);
        $this->assertStringContainsString('data-label="Qty"', $stack);

        $css = (string) file_get_contents(base_path('../resources/assets/devlib.css'));
        preg_match_all('/^[^
]*dl-table--stack[^
]*$/m', $css, $m);
        $this->assertNotEmpty($m[0]);
        foreach ($m[0] as $line) {
            $this->assertStringStartsWith(':is(body.dl-on-phone,.dl-phone)', ltrim($line), 'aturan stack hanya di konteks HP');
        }
    }
}
