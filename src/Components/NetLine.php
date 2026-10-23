<?php

/*
 * REMARK
 * Tujuan       : Satu baris sangat kecil dan pudar "IP client → IP server" (di bawah nama pengguna) dengan status koneksi ke server: IP server berkedip bila server tidak terjangkau, IP client berkedip bila perangkat offline; nama host lewat tooltip.
 * Sumber hulu  : Binding container 'devlib.net' (array hasil Devlib\NetworkInfo::of) atau prop; pengecekan berkala ke alamat ping (bawaan rute devlib.ping milik DevLib) dilakukan browser.
 * Input        : clientIp, serverIp, clientHost, serverHost, pingUrl, interval.
 * Output       : <span class="dl-netline"> + perilaku resources/assets/js/components/net-line.js; kosong bila tidak ada IP sama sekali.
 * Dipakai oleh : LayerMenu (baris pengguna di footer, bila aplikasi mengikat devlib.net); halaman katalog p7-netline.
 * Status       : Aktif (DevLib).
 * View         : phone, desktop (satu baris yang sama).
 */

namespace Devlib\Components;

use Devlib\Lang;

// Baris info jaringan kecil dan pudar untuk menjawab "pengguna ini mengakses dari mana, dan apakah server terjangkau". Data = IP dari NetworkInfo::of (lewat prop atau binding 'devlib.net');
// status = ping ringan berkala dari browser ke satu alamat (bawaan GET /devlib/ping). Browser tidak bisa membedakan LAN putus dari internet putus: yang diukur hanya "server kita menjawab atau tidak".
class NetLine extends DevlibComponent
{
    public function __construct(
        public ?string $clientIp = null,
        public ?string $serverIp = null,
        public ?string $clientHost = null,
        public ?string $serverHost = null,
        // Alamat yang di-ping browser. Kosong = rute bawaan DevLib (devlib.ping).
        public ?string $pingUrl = null,
        // Detik antar pengecekan (minimum 3).
        public int $interval = 10,
    ) {
        $net = app()->bound('devlib.net') ? (array) app('devlib.net') : [];
        $this->clientIp ??= $net['client_ip'] ?? null;
        $this->serverIp ??= $net['server_ip'] ?? null;
        $this->clientHost ??= $net['client_host'] ?? null;
        $this->serverHost ??= $net['server_host'] ?? null;
        $this->pingUrl ??= route('devlib.ping');
        $this->interval = max(3, $this->interval);
    }

    public function tag(): string
    {
        return 'x-dl::net-line';
    }

    public function source(): string
    {
        return 'Devlib\NetworkInfo (binding devlib.net), ping route devlib.ping';
    }

    // Tooltip: nama host client dan server (tidak muncul di baris karena panjang).
    public function tip(): string
    {
        return collect(['Client' => $this->clientHost, 'Server' => $this->serverHost])->filter()->map(fn ($h, $k) => $k.': '.$h)->implode("\n");
    }

    public function render()
    {
        return <<<'BLADE'
            @if ($clientIp || $serverIp)
                <span {{ $attributes->class('dl-netline') }} {!! $dl() !!} data-dl-net-line data-state="ok" data-ping="{{ $pingUrl }}" data-interval="{{ $interval }}" data-t="{{ json_encode(['server' => \Devlib\Lang::t('net_server_down'), 'offline' => \Devlib\Lang::t('net_offline')], JSON_UNESCAPED_UNICODE) }}">
                    <span class="dl-netline__row" @if ($tip() !== '') title="{{ $tip() }}" @endif><span data-nl="client">{{ $clientIp }}</span> → <span data-nl="server">{{ $serverIp }}</span></span>
                    <span class="dl-netline__st" role="status" aria-live="polite" data-nl-status></span>
                </span>
            @endif
            BLADE;
    }
}
