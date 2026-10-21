<?php

/*
 * REMARK
 * Tujuan       : Info rekaman: dibuat/diubah oleh siapa & kapan (WIB), asal data.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : createdBy, createdAt, updatedBy, updatedAt, origin.
 * Output       : Markup Blade inline (render() kelas ini).
 * Dipakai oleh : metadata (layar katalog/proyek).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Info rekaman: dibuat/diubah oleh siapa & kapan (WIB), asal data.
class Meta extends DevlibComponent
{
    public function __construct(
        public ?string $createdBy = null,
        public mixed $createdAt = null,
        public ?string $updatedBy = null,
        public mixed $updatedAt = null,
        public ?string $origin = null,
    ) {}

    public function tag(): string
    {
        return 'x-dl::meta';
    }

    public function render()
    {
        return <<<'BLADE'
            <div {{ $attributes->class('dl-meta') }} {!! $dl() !!}>
                @if ($createdAt)<span><i class="fa-solid fa-circle-plus"></i> {{ \Devlib\Lang::t('created_by') }} {{ \Devlib\Locale::dateTime($createdAt) }}@if ($createdBy) {{ \Devlib\Lang::t('by') }} <b>{{ $createdBy }}</b>@endif</span>@endif
                @if ($updatedAt)<span><i class="fa-solid fa-pen"></i> {{ \Devlib\Lang::t('updated_by') }} {{ \Devlib\Locale::dateTime($updatedAt) }}@if ($updatedBy) {{ \Devlib\Lang::t('by') }} <b>{{ $updatedBy }}</b>@endif</span>@endif
                @if ($origin)<x-dl::badge :label="$origin" :tone="$origin === 'REAL' ? 'success' : 'warn'" />@endif
            </div>
            BLADE;
    }
}
