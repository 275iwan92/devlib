<?php

namespace Devlib;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/* REMARK
 * Tujuan       : Info jaringan permintaan untuk baris kecil di kepala dashboard (x-dl::greeting-card, kunci `network`): IP client, nama host client, IP server.
 *                Hanya data milik permintaan ini; tidak disimpan. IP client = $request->ip() (ikut pengaturan proxy tepercaya aplikasi).
 *                Nama host client: browser tidak bisa menyebut nama komputer, jadi dicari server lewat DNS balik (gethostbyaddr) HANYA untuk IP pribadi/loopback (LAN), di-cache; hasil tidak ada = null.
 *                Client di mesin yang sama dengan server (127.0.0.1 / ::1) memakai nama mesin sendiri.
 * Output       : of($request) = ['client_ip', 'client_host', 'server_ip', 'server_host'] (nilai bisa null).
 * Dipakai oleh : aplikasi pewaris yang mengisi $board['network'] (opt-in; aplikasi yang memutuskan siapa boleh melihat).
 * Status       : Aktif.
 */
final class NetworkInfo
{
    /** DNS balik bisa lambat bila server DNS tidak menjawab; hasil (juga yang kosong) disimpan selama ini. */
    public const HOST_TTL = 3600;

    /** @return array{client_ip: ?string, client_host: ?string, server_ip: ?string, server_host: ?string} */
    public static function of(Request $request, bool $resolveHost = true): array
    {
        $client = $request->ip();
        $machine = gethostname() ?: null;
        $server = self::serverIp((string) $request->server('SERVER_ADDR'), (string) $machine);

        return [
            'client_ip' => $client,
            'client_host' => self::isLoopback($client) ? $machine : ($resolveHost ? self::hostOf($client) : null),
            'server_ip' => $server,
            'server_host' => $machine,
        ];
    }

    /** SERVER_ADDR bila bukan loopback, selain itu IP dari nama mesin bila bukan loopback, selain itu apa adanya (tidak pernah dikarang); sama dengan EnvBadge mockup. */
    public static function serverIp(string $addr, string $machine): ?string
    {
        $loop = fn (string $ip) => $ip === '' || str_starts_with($ip, '127.') || $ip === '::1';
        if (! $loop($addr)) {
            return $addr;
        }
        $byName = $machine !== '' ? (string) gethostbyname($machine) : '';
        if (! $loop($byName) && $byName !== $machine) {
            return $byName;
        }

        return $addr !== '' ? $addr : null;
    }


    public static function isLoopback(?string $ip): bool
    {
        return in_array($ip, ['127.0.0.1', '::1'], true);
    }

    /** Nama host untuk IP pribadi lewat DNS balik; null bila bukan IP pribadi, tidak ada nama, atau gagal. */
    public static function hostOf(?string $ip): ?string
    {
        if ($ip === null || filter_var($ip, FILTER_VALIDATE_IP) === false || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
            return null;   // bukan IP pribadi (LAN): DNS balik ke internet lambat dan tidak berguna
        }

        return Cache::remember('devlib.net.host.'.$ip, self::HOST_TTL, function () use ($ip) {
            $name = @gethostbyaddr($ip);

            return is_string($name) && $name !== '' && $name !== $ip ? $name : '';
        }) ?: null;
    }
}
