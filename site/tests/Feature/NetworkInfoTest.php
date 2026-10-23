<?php

namespace Tests\Feature;

use Devlib\NetworkInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga NetworkInfo (IP client, host client hanya untuk IP pribadi / loopback, IP server) yang dipakai x-dl::net-line.
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
}
