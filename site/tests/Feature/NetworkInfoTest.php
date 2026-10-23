<?php

namespace Tests\Feature;

use Devlib\NetworkInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga info jaringan kepala dashboard: NetworkInfo (IP client, host client hanya untuk IP pribadi / loopback, IP server) dan baris kecil opt-in di x-dl::greeting-card (tanpa kunci `network` HTML sama seperti sebelumnya).
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class NetworkInfoTest extends TestCase
{
    private function request(string $ip, ?string $server = '10.0.0.5'): Request
    {
        return Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => $ip] + ($server ? ['SERVER_ADDR' => $server] : []));
    }

    public function test_loopback_client_uses_the_machine_name_and_reports_the_server_ip(): void
    {
        $info = NetworkInfo::of($this->request('127.0.0.1'));

        $this->assertSame('127.0.0.1', $info['client_ip']);
        $this->assertSame('10.0.0.5', $info['server_ip']);
        $this->assertSame(gethostname() ?: null, $info['client_host']);
    }

    public function test_public_addresses_are_never_resolved_and_invalid_ones_give_null(): void
    {
        $this->assertNull(NetworkInfo::hostOf('8.8.8.8'));
        $this->assertNull(NetworkInfo::hostOf('not-an-ip'));
        $this->assertNull(NetworkInfo::hostOf(null));
        $this->assertNull(NetworkInfo::of($this->request('8.8.8.8'))['client_host']);
    }

    public function test_a_lan_address_is_resolved_once_and_cached_even_when_nothing_is_found(): void
    {
        Cache::forget('devlib.net.host.10.255.255.254');

        $first = NetworkInfo::hostOf('10.255.255.254');
        $this->assertTrue(Cache::has('devlib.net.host.10.255.255.254'));
        $this->assertSame($first, NetworkInfo::hostOf('10.255.255.254'));
    }

    public function test_resolving_can_be_switched_off(): void
    {
        $this->assertNull(NetworkInfo::of($this->request('192.168.1.20'), false)['client_host']);
    }

    public function test_the_server_ip_falls_back_from_loopback_like_the_env_badge(): void
    {
        $this->assertSame('10.0.0.5', NetworkInfo::serverIp('10.0.0.5', 'PC'));
        $this->assertSame('127.0.0.1', NetworkInfo::serverIp('127.0.0.1', ''));   // tidak ada yang lain: apa adanya
        $this->assertNull(NetworkInfo::serverIp('', ''));
    }

    public function test_the_hero_draws_the_lines_only_when_the_board_has_a_network_key(): void
    {
        $board = ['user' => ['first' => 'Yuna'], 'profile_label' => 'Dashboard'];
        $without = Blade::render('<x-dl::greeting-card :board="$b" />', ['b' => $board]);
        $with = Blade::render('<x-dl::greeting-card :board="$b" />', ['b' => $board + ['network' => ['client_ip' => '10.0.0.21', 'client_host' => 'PC-GUDANG', 'server_ip' => '10.0.0.5', 'server_host' => 'SRV-1']]]);

        $this->assertStringNotContainsString('data-dl-network', $without);
        $this->assertStringContainsString('data-dl-network', $with);
        $this->assertStringContainsString('PC-GUDANG / 10.0.0.21 - Server: 10.0.0.5', $with);
                $this->assertStringNotContainsString('👋', $with);
    }
}
