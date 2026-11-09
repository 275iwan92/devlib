<?php

/*
 * REMARK
 * Tujuan       : Strip notifikasi di atas dashboard: satu notifikasi = teks diam + tombol; dua atau lebih = teks BERJALAN (ticker) yang berhenti saat disentuh/di-hover, tiap notifikasi bisa diklik ke sumbernya. Warna strip mengikuti notifikasi paling genting (danger > warn > info > success); tiap notifikasi berwarna menurut jenisnya.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : items = [['text', 'tone' => danger|warn|info|success, 'href', 'label', 'icon']].
 * Output       : Markup Blade inline (render() kelas ini) + CSS .dl-astrip (tanpa JS; animasi CSS).
 * Dipakai oleh : layar proyek (opening screen Home).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Strip notifikasi / ticker. Kosong = tidak dirender. Teks berjalan memakai dua salinan daftar (salinan kedua aria-hidden) digeser 50% agar tanpa celah;
// kecepatan tetap (detik ~ jumlah huruf) lewat --dl-astrip-dur. prefers-reduced-motion: tidak berjalan, daftar bisa digulir.
class AlertStrip extends DevlibComponent
{
    public function __construct(
        public array $items = [], // [['text', 'tone' => danger|warn|info|success, 'href' => ?url, 'label' => ?teks tombol (satu notifikasi), 'icon' => ?fa-xxx]]
    ) {}

    public function tag(): string
    {
        return 'x-dl::alert-strip';
    }

    public function render()
    {
        return <<<'BLADE'
            @php
                $items = array_values($items);
                $rank = ['danger' => 3, 'warn' => 2, 'info' => 1, 'success' => 0];
                $top = collect($items)->sortByDesc(fn ($i) => $rank[$i['tone'] ?? 'info'] ?? 1)->first()['tone'] ?? 'info';
                $many = count($items) > 1;
                $dur = max(14, (int) round(collect($items)->sum(fn ($i) => mb_strlen($i['text'] ?? '') + 12) * 0.32));
            @endphp
            @if ($items)
                <div {{ $attributes->class(['dl-astrip', 'dl-astrip--'.$top, 'is-run' => $many]) }} {!! $dl() !!} data-dl-astrip role="status">
                    @if (! $many)
                        @php($one = $items[0])
                        <i class="fa-solid {{ \Devlib\Icon::fa($one['icon'] ?? 'fa-bell') }}" aria-hidden="true"></i>
                        <span class="dl-astrip__text">{{ $one['text'] ?? '' }}</span>
                        @if (! empty($one['href']))<a class="dl-btn dl-btn--secondary dl-btn--sm" href="{{ $one['href'] }}">{{ $one['label'] ?? \Devlib\Lang::text('Open') }}</a>@endif
                    @else
                        <i class="fa-solid fa-bell-concierge" aria-hidden="true"></i>
                        <div class="dl-astrip__view">
                            <div class="dl-astrip__track" style="--dl-astrip-dur: {{ $dur }}s">
                                @foreach ([0, 1] as $copy)
                                    <div class="dl-astrip__set" @if ($copy) aria-hidden="true" @endif>
                                        @foreach ($items as $i)
                                            @php($tag = ! empty($i['href']) ? 'a' : 'span')
                                            <{{ $tag }} class="dl-astrip__item dl-astrip__item--{{ $i['tone'] ?? 'info' }}" @if (! empty($i['href'])) href="{{ $i['href'] }}" @if ($copy) tabindex="-1" @endif @endif><i class="fa-solid {{ \Devlib\Icon::fa($i['icon'] ?? 'fa-bell') }}" aria-hidden="true"></i> {{ $i['text'] ?? '' }}</{{ $tag }}>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif
            BLADE;
    }
}
