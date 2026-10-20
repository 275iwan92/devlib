<?php

/*
 * REMARK
 * Tujuan       : Form bertahap. Isi = beberapa x-dl::wizard.step. Next mengecek isian wajib langkah itu;.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : title, action, review, finishLabel.
 * Output       : Markup Blade inline (render() kelas ini).
 * Dipakai oleh : wizard-customer (layar katalog/proyek).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Form bertahap. Isi = beberapa x-dl::wizard.step. Next mengecek isian wajib langkah itu;
// langkah yang sudah dilewati bisa diklik di stepper; review=true menambah langkah Review
// (ringkasan semua isian, klik "Ubah" untuk kembali). Selesai = submit form (dl:wizard-finish).
// :done (opt-in, user 2026-10-21): wizard tanpa grid -- Finish tanpa :action menampilkan layar "Done" berisi teks ini + tombol "Start again" (isian dikosongkan,
// kembali ke langkah 1); tidak ada Back karena tidak ada grid.
class Wizard extends DevlibComponent
{
    public function __construct(
        public string $title = '',
        public ?string $action = null,
        public bool $review = true,
        public string $finishLabel = 'Finish',
        public ?string $done = null,
    ) {}

    public function tag(): string
    {
        return 'x-dl::wizard';
    }

    public function render()
    {
        return <<<'BLADE'
            <form {{ $attributes->class('dl-wizard') }} {!! $dl() !!} data-dl-wizard data-dl-guard novalidate method="POST" @if ($action) action="{{ $action }}" @endif data-review="{{ $review ? 1 : 0 }}" @if ($done) data-done @endif>
                @if ($action) @csrf @endif
                @if ($title !== '')<div class="dl-wizard__title">{{ $title }}</div>@endif
                <ol class="dl-wizard__steps" data-w-steps></ol>
                <div class="dl-wizard__body">
                    {{ $slot }}
                    @if ($review)
                        <section class="dl-wizard__step" data-w-step data-title="Review" data-icon="fa-clipboard-check" data-w-review><div data-w-summary></div></section>
                    @endif
                </div>
                @if ($done)
                    <div class="dl-wizard__done" data-w-done hidden role="status"><i class="fa-solid fa-circle-check" aria-hidden="true"></i><div class="dl-wizard__donetitle">{{ \Devlib\Lang::text('Done') }}</div><p>{{ $done }}</p><x-dl::button variant="secondary" icon="fa-rotate-left" :label="\Devlib\Lang::text('Start again')" data-w-restart /></div>
                @endif
                <div class="dl-wizard__foot">
                    <x-dl::button variant="secondary" icon="prev" label="Previous" data-w-prev />
                    <span class="dl-wizard__pos" data-w-pos></span>
                    <x-dl::button icon="next" label="Next" data-w-next />
                    <x-dl::button preset="submit" :label="$finishLabel" type="submit" data-w-finish />
                </div>
            </form>
            BLADE;
    }
}
