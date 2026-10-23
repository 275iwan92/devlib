<?php

namespace Devlib\Components;

/*
 * REMARK
 * Tujuan       : Teks lingkungan: akhiran judul logo ('- mockup', part title), VERSI kecil dan pudar di judul logo (part version: 'ver 0.433 A' atau 'v0.6.0', dari binding devlib.version = Devlib\AppVersion) dan baris host/IP kecil (part where, di bawah nama pengguna); title dan where tidak tampil di situs live.
 * Sumber hulu  : binding container 'devlib.env_title' / 'devlib.env_where' yang diisi aplikasi (kosong / tidak ada = tidak tampil); DevLib tidak tahu isinya.
 * Input        : part title|where|version, variant title|side|login|version, label (opsional).
 * Output       : <small class="dl-envbadge">teks</small> atau kosong.
 * Dipakai oleh : LayerMenu (judul logo + bawah nama pengguna) dan halaman login aplikasi (judul logo + bawah form).
 * Status       : Aktif. Teks terang di sidebar gelap (token --dl-side-ink), hitam di login (varian login); tanpa pil, latar, atau bingkai.
 */
class EnvBadge extends DevlibComponent
{
    // The label is data built by the host app (already translated); it is not a dictionary key.
    protected const TEXT_PROPS = [];

    protected const TEXT_LISTS = [];

    // Hanya part version: huruf tahap (A / B / RC) dan tooltip (kata lengkap tahap, atau jumlah commit sejak tag).
    public string $letter = '';

    public string $tip = '';

    public function __construct(public string $part = 'where', public string $variant = 'side', public ?string $label = null)
    {
        if ($part === 'version') {
            $v = app()->bound('devlib.version') ? (array) app('devlib.version') : [];
            $this->label ??= (string) ($v['text'] ?? '');
            $this->letter = (string) ($v['letter'] ?? '');
            $this->tip = (string) ($v['title'] ?? '');

            return;
        }
        $key = 'devlib.env_'.$part;
        $this->label ??= app()->bound($key) ? (string) app($key) : '';
    }

    public function tag(): string
    {
        return 'x-dl::env-badge part='.$this->part;
    }

    public function render()
    {
        return <<<'BLADE'
            @if ($label !== '')
                <small {{ $attributes->class(['dl-envbadge', 'dl-envbadge--'.$variant]) }} {!! $dl() !!} @if ($tip !== '') title="{{ $tip }}" @endif>{{ $label }}@if ($letter !== '') <span class="dl-envbadge__stage">{{ $letter }}</span>@endif</small>
            @endif
            BLADE;
    }
}
