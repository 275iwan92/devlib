<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga x-dl::net-line (baris IP client → IP server dengan status koneksi), rute ping bawaan, dan sambungannya ke footer pengguna Layer Menu (opt-in lewat binding devlib.net).
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class NetLineTest extends TestCase
{
    private const NET = ['client_ip' => '10.0.0.21', 'client_host' => 'PC-GUDANG', 'server_ip' => '10.0.0.5', 'server_host' => 'SRV-1'];

    public function test_renders_the_two_ips_with_host_names_only_in_the_tooltip(): void
    {
        $html = Blade::render('<x-dl::net-line client-ip="10.0.0.21" server-ip="10.0.0.5" client-host="PC-GUDANG" server-host="SRV-1" />');

        $this->assertStringContainsString('data-nl="client">10.0.0.21', $html);
        $this->assertStringContainsString('data-nl="server">10.0.0.5', $html);
        $this->assertStringContainsString('title="Client: PC-GUDANG&#10;Server: SRV-1"', str_replace("\n", '&#10;', $html));
        $this->assertStringNotContainsString('>PC-GUDANG<', $html);
        $this->assertStringContainsString('role="status"', $html);
        $this->assertStringContainsString('data-ping="'.route('devlib.ping').'"', $html);
        $this->assertStringContainsString('data-interval="10"', $html);
    }

    public function test_reads_the_container_binding_and_prints_nothing_without_ips(): void
    {
        $this->assertSame('', trim(Blade::render('<x-dl::net-line />')));

        app()->bind('devlib.net', fn () => self::NET);
        $this->assertStringContainsString('10.0.0.5', Blade::render('<x-dl::net-line />'));
    }

    public function test_ping_url_and_interval_props_and_the_minimum_interval(): void
    {
        $html = Blade::render('<x-dl::net-line client-ip="1.1.1.1" ping-url="/session/ping" :interval="1" />');

        $this->assertStringContainsString('data-ping="/session/ping"', $html);
        $this->assertStringContainsString('data-interval="3"', $html);
    }

    public function test_default_ping_route_is_204_without_session_and_never_cached(): void
    {
        $r = $this->get('/devlib/ping');

        $r->assertStatus(204);
        $this->assertStringContainsString('no-store', (string) $r->headers->get('Cache-Control'));
        $this->assertEmpty($r->headers->getCookies());
    }

    public function test_layer_menu_footer_uses_the_new_line_only_when_the_app_binds_network_data(): void
    {
        app()->bind('devlib.env_where', fn () => 'Host: H / 1.2.3.4 · Client: 5.6.7.8');
        $without = Blade::render('<x-dl::layer-menu id="m" user="Budi" :items="$items" />', ['items' => [['label' => 'A', 'url' => '#']]]);
        $this->assertStringContainsString('Host: H / 1.2.3.4', $without);
        $this->assertStringNotContainsString('data-dl-net-line', $without);

        app()->bind('devlib.net', fn () => self::NET);
        $with = Blade::render('<x-dl::layer-menu id="m" user="Budi" :items="$items" />', ['items' => [['label' => 'A', 'url' => '#']]]);
        $this->assertStringContainsString('data-dl-net-line', $with);
        $this->assertStringNotContainsString('Host: H / 1.2.3.4', $with);
    }
}
