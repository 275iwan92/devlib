<?php

/*
 * REMARK
 * Tujuan       : Kalkulator pop up ala Windows 11: tombol ikon yang membuka jendela modal berisi tiga tab (Calculator = hitung biasa dengan eksekusi langsung kiri-ke-kanan; Converter = konversi satuan dan kurs; History = riwayat hitungan). Tombol angka besar berwarna.
 * Sumber hulu  : Komponen generik DevLib; daftar besaran/satuan dan daftar mata uang masuk lewat prop (aplikasi yang menyediakan datanya), tanpa tabel/model proyek.
 * Input        : quantities, currencies, featured, base, rateUrl, id, storageKey.
 * Output       : Markup Blade inline (render() kelas ini) + js/components/calculator.js + CSS .dl-wcalc.
 * Dipakai oleh : x-dl::greeting-card (board['calculator']) dan layar proyek lewat x-dl::calculator.
 * Status       : Aktif (DevLib). Hitungan dan konversi dikerjakan di browser (angka desimal biasa, tampil maksimal 10 digit); riwayat disimpan per perangkat di localStorage.
 */

namespace Devlib\Components;

// Kalkulator pop up. $quantities = [['key' => 'LENGTH', 'label' => 'Length', 'units' => [['c' => 'mm', 'n' => 'Millimetre', 'f' => 0.001, 'o' => 0]]]]:
// nilai_dasar = nilai * f + o ; hasil = (nilai_dasar - o2) / f2 (suhu memakai o). $currencies = [['c' => 'USD', 'n' => 'US Dollar', 'r' => 15800.0]]:
// r = nilai 1 mata uang dalam $base (null = kurs belum diisi, ditandai dan tidak dihitung). $featured = key besaran yang tampil sebagai chip; sisanya lewat pilihan "All quantities".
class Calculator extends DevlibComponent
{
    public function __construct(
        public array $quantities = [],
        public array $currencies = [],
        public array $featured = [],
        public string $base = 'IDR',
        public ?string $rateUrl = null,
        public string $id = 'dl-calculator',
        public string $storageKey = 'dl-calculator',
    ) {}

    public function tag(): string
    {
        return 'x-dl::calculator id='.$this->id;
    }

    public function source(): string
    {
        return count($this->quantities).' besaran, '.count($this->currencies).' mata uang (prop)';
    }

    public function render()
    {
        return <<<'BLADE'
            <span {{ $attributes->class(['dl-wcalc-wrap']) }} {!! $dl() !!}>
                <button type="button" class="dl-wcalc-btn" data-dl-open="{{ $id }}" aria-label="{{ \Devlib\Lang::text('Calculator') }}" title="{{ \Devlib\Lang::text('Calculator') }}"><i class="fa-solid fa-calculator" aria-hidden="true"></i></button>
                <x-dl::modal :id="$id" :title="\Devlib\Lang::text('Calculator')" icon="fa-calculator" size="sm">
                    <div class="dl-wcalc" data-dl-wcalc data-key="{{ $storageKey }}" data-base="{{ $base }}" data-rate-url="{{ $rateUrl }}"
                        data-quantities="{{ json_encode(array_values($quantities), JSON_UNESCAPED_UNICODE) }}" data-currencies="{{ json_encode(array_values($currencies), JSON_UNESCAPED_UNICODE) }}" data-featured="{{ json_encode(array_values($featured)) }}"
                        data-l-currency="{{ \Devlib\Lang::text('Currency') }}" data-l-norate="{{ \Devlib\Lang::text('No rate yet') }}" data-l-setrate="{{ \Devlib\Lang::text('Set the rate in Exchange Rate') }}" data-l-all="{{ \Devlib\Lang::text('All quantities') }}" data-l-rate="{{ \Devlib\Lang::text('Rate') }}" data-l-empty="{{ \Devlib\Lang::text('No history yet') }}" data-l-err="{{ \Devlib\Lang::text('Cannot divide by zero') }}">
                        <div class="dl-wcalc__tabs" role="tablist">
                            <button type="button" role="tab" class="is-on" data-wc-tab="calc">{{ \Devlib\Lang::text('Calculator') }}</button>
                            <button type="button" role="tab" data-wc-tab="conv">{{ \Devlib\Lang::text('Converter') }}</button>
                            <button type="button" role="tab" data-wc-tab="hist" aria-label="{{ \Devlib\Lang::text('History') }}" title="{{ \Devlib\Lang::text('History') }}"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i></button>
                        </div>
                        <div data-wc-pane="calc">
                            <div class="dl-wcalc__expr" data-wc-expr></div>
                            <div class="dl-wcalc__disp" data-wc-disp>0</div>
                            <div class="dl-wcalc__keys">
                                @foreach ([['%', 'o'], ['CE', 'c'], ['C', 'c'], ['⌫', 'o'], ['7', 'n'], ['8', 'n'], ['9', 'n'], ['÷', 'o'], ['4', 'n'], ['5', 'n'], ['6', 'n'], ['×', 'o'], ['1', 'n'], ['2', 'n'], ['3', 'n'], ['−', 'o'], ['±', 'n'], ['0', 'n'], ['.', 'n'], ['+', 'o']] as [$k, $t])
                                    <button type="button" class="dl-wcalc__k dl-wcalc__k--{{ $t }}" data-wc-k="{{ $k }}">{{ $k === '.' ? ',' : $k }}</button>
                                @endforeach
                                <button type="button" class="dl-wcalc__k dl-wcalc__k--e" data-wc-k="=">=</button>
                            </div>
                        </div>
                        <div data-wc-pane="conv" hidden>
                            <div class="dl-wcalc__cats" data-wc-cats></div>
                            <div class="dl-wcalc__pair">
                                <select data-wc-from aria-label="From"></select>
                                <button type="button" class="dl-wcalc__swap" data-wc-swap aria-label="{{ \Devlib\Lang::text('Swap') }}" title="{{ \Devlib\Lang::text('Swap') }}"><i class="fa-solid fa-right-left" aria-hidden="true"></i></button>
                                <select data-wc-to aria-label="To"></select>
                            </div>
                            <div class="dl-wcalc__disp" data-wc-cin>0</div>
                            <div class="dl-wcalc__res" data-wc-cout>0</div>
                            <div class="dl-wcalc__note" data-wc-cnote></div>
                            <div class="dl-wcalc__keys dl-wcalc__keys--conv">
                                @foreach ([['7', 'n'], ['8', 'n'], ['9', 'n'], ['C', 'c'], ['4', 'n'], ['5', 'n'], ['6', 'n'], ['⌫', 'o'], ['1', 'n'], ['2', 'n'], ['3', 'n'], ['±', 'n'], ['0', 'n'], ['.', 'n']] as [$k, $t])
                                    <button type="button" class="dl-wcalc__k dl-wcalc__k--{{ $t }}" data-wc-ck="{{ $k }}">{{ $k === '.' ? ',' : $k }}</button>
                                @endforeach
                            </div>
                        </div>
                        <div data-wc-pane="hist" hidden>
                            <ul class="dl-wcalc__hist" data-wc-hist></ul>
                            <button type="button" class="dl-wcalc__clear" data-wc-clear>{{ \Devlib\Lang::text('Clear history') }}</button>
                        </div>
                    </div>
                </x-dl::modal>
            </span>
            BLADE;
    }
}
