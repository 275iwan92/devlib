<?php

/*
 * REMARK
 * Tujuan       : Daftar tugas bergaya Matriks Eisenhower: empat kuadran (1 Do first = penting dan mendesak, 2 Schedule = penting tidak mendesak, 3 Delegate = mendesak tidak penting, 4 Eliminate = tidak keduanya); tambah tugas per kuadran, centang selesai, pindah kuadran, hapus, bersihkan yang selesai.
 * Sumber hulu  : Komponen generik DevLib; daftar masuk lewat prop dan disimpan lewat saveUrl (JSON {items}) milik aplikasi.
 * Input        : items, saveUrl, csrf.
 * Output       : Markup Blade inline (render() kelas ini) + js/components/eisenhower.js + CSS .dl-eis.
 * Dipakai oleh : layar proyek lewat x-dl::eisenhower (menu Tools > Task).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Matriks Eisenhower. items = [['id', 'text' (max 200), 'q' => 1..4, 'done' => bool]]. Pindah kuadran lewat <select> pada tiap tugas (tanpa seret, jadi bisa dipakai papan ketik).
// Seluruh daftar dikirim ke $saveUrl (POST JSON {items}) 300 ms setelah perubahan terakhir; kosong = pratinjau.
class Eisenhower extends DevlibComponent
{
    public function __construct(
        public array $items = [],
        public string $saveUrl = '',
        public ?string $csrf = '',
    ) {}

    public function tag(): string
    {
        return 'x-dl::eisenhower';
    }

    public function source(): string
    {
        return $this->saveUrl ?: 'pratinjau (tanpa server)';
    }

    public function render()
    {
        return <<<'BLADE'
            <div {{ $attributes->class('dl-eis') }} {!! $dl() !!} data-dl-eis data-save="{{ $saveUrl }}" data-csrf="{{ $csrf }}" data-items="{{ json_encode(array_values($items), JSON_UNESCAPED_UNICODE) }}"
                data-l-q="{{ json_encode([1 => \Devlib\Lang::text('Do first'), 2 => \Devlib\Lang::text('Schedule'), 3 => \Devlib\Lang::text('Delegate'), 4 => \Devlib\Lang::text('Eliminate')], JSON_UNESCAPED_UNICODE) }}"
                data-l-delete="{{ \Devlib\Lang::text('Delete') }}" data-l-move="{{ \Devlib\Lang::text('Move to') }}" data-l-failed="{{ \Devlib\Lang::text('Could not save. Try again.') }}">
                <div class="dl-eis__axis"><span></span><b>{{ \Devlib\Lang::text('Urgent') }}</b><b>{{ \Devlib\Lang::text('Not urgent') }}</b></div>
                <div class="dl-eis__grid">
                    @foreach ([1 => ['Do first', 'Important and urgent'], 2 => ['Schedule', 'Important, not urgent'], 3 => ['Delegate', 'Urgent, not important'], 4 => ['Eliminate', 'Neither']] as $q => [$name, $hint])
                        <section class="dl-eis__q dl-eis__q--{{ $q }}" data-dl-eis-q="{{ $q }}">
                            <h4>{{ \Devlib\Lang::text($name) }} <small>{{ \Devlib\Lang::text($hint) }}</small></h4>
                            <ul data-dl-eis-list></ul>
                            <form data-dl-eis-add><input type="text" maxlength="200" required placeholder="{{ \Devlib\Lang::text('New task') }}" aria-label="{{ \Devlib\Lang::text('New task') }}: {{ \Devlib\Lang::text($name) }}"><button type="submit" class="dl-btn dl-btn--ghost" aria-label="{{ \Devlib\Lang::text('Add task') }}"><i class="fa-solid fa-plus" aria-hidden="true"></i></button></form>
                        </section>
                    @endforeach
                </div>
                <div class="dl-eis__foot"><button type="button" class="dl-btn dl-btn--ghost" data-dl-eis-clear><i class="fa-solid fa-broom" aria-hidden="true"></i> {{ \Devlib\Lang::text('Clear completed') }}</button></div>
            </div>
            BLADE;
    }
}
