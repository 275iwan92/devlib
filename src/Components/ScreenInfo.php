<?php

/*
 * REMARK
 * Tujuan       : Halaman diagnostik layar: lebar x tinggi CSS, DPR, orientasi, tinggi svh/lvh/dvh, visualViewport, sentuh/mouse, area aman, view dan aturan layar phone DevLib, OS, User-Agent; tombol "Salin hasil" (satu blok teks polos untuk ditempel ke chat).
 * Sumber hulu  : Browser (window, screen, visualViewport, matchMedia, navigator); Devlib\Device (view yang dirender server, asal pilihan, OS dari User-Agent). Tidak ada tabel/model proyek.
 * Input        : title, note.
 * Output       : Markup Blade inline (render() kelas ini) + perilaku resources/assets/js/components/screen-info.js.
 * Dipakai oleh : halaman ukur layar proyek (mis. GET /ukur); halaman katalog p7-screen.
 * Status       : Aktif (DevLib).
 * View         : phone, desktop (satu kolom di phone, dua kolom di desktop).
 */

namespace Devlib\Components;

use Devlib\Device;
use Devlib\Lang;

// Diagnostik layar, murni sisi klien: server hanya mencetak tiga nilai dari permintaan itu sendiri (view, asal pilihan, OS), tidak ada yang dikirim atau disimpan.
// Dipakai untuk mengukur HP sungguhan (lebar CSS, DPR, tinggi nyata) sebelum menentukan breakpoint; hasilnya ditempel ke chat lewat "Salin hasil".
class ScreenInfo extends DevlibComponent
{
    // Baris per kelompok: kunci (dibaca screen-info.js) => label English (diterjemahkan kamus).
    public const GROUPS = [
        'Screen' => [
            'css' => 'CSS width x height',
            'dpr' => 'Pixel ratio (DPR)',
            'orient' => 'Orientation',
            'phys' => 'Approx. physical size (CSS x DPR)',
            'inner' => 'innerWidth / clientWidth',
            'screen' => 'screen width x height (available)',
        ],
        'Height units' => [
            'svh' => 'Small height (svh)',
            'lvh' => 'Large height (lvh)',
            'dvh' => 'Dynamic height (dvh)',
            'vv' => 'visualViewport (width x height, scale)',
        ],
        'Input and safe area' => [
            'pointer' => 'pointer',
            'anypointer' => 'any-pointer',
            'hover' => 'hover',
            'safe' => 'Safe area (top / right / bottom / left)',
        ],
        'DevLib' => [
            'rule' => 'Screen rule (under 600 px, or phone sideways)',
            'view' => 'View served by the server',
            'os' => 'OS (server / browser)',
            'bp' => 'Active max-width breakpoints',
        ],
        'Browser' => [
            'ua' => 'User-Agent',
            'uad' => 'userAgentData (optional)',
            'secure' => 'Secure context (https or localhost)',
            'time' => 'Measured at',
        ],
    ];

    // Teks yang ditulis skrip (bukan baris): diterjemahkan di server agar skrip tidak membawa kamus.
    public const TEXTS = [
        'Copied. Paste it in the chat.', 'Copy did not work here. The text below is selected: copy it by hand (long press, then Copy).',
        'portrait', 'landscape', 'yes', 'no', 'not supported', 'not available', 'none',
        'cookie ui (your choice)', 'preview override', 'client hint (Sec-CH-UA-Mobile)', 'User-Agent guess',
        'width under 600 px', 'phone sideways: height under 480 px, touch, width under 1000 px', 'width 600 px or more, not a phone sideways',
        'height under 480 px but the main pointer is not touch', 'only works on https or localhost: the copy uses the fallback',
    ];

    public function __construct(
        public string $title = 'Screen info',
        // Kalimat pembuka di bawah judul (English, diterjemahkan). Kosong = bawaan.
        public string $note = '',
    ) {}

    public function tag(): string
    {
        return 'x-dl::screen-info';
    }

    public function source(): string
    {
        return 'browser (window, screen, visualViewport, matchMedia), Devlib\Device';
    }

    // Terjemahan teks skrip sebagai peta English => teks tampil (JSON di data-t).
    public function texts(): array
    {
        return collect(self::TEXTS)->mapWithKeys(fn (string $t) => [$t => Lang::text($t)])->all();
    }

    public function render()
    {
        return <<<'BLADE'
            <section {{ $attributes->class('dl-sinfo') }} {!! $dl() !!} data-dl-screen-info data-view="{{ \Devlib\Device::view(request()) }}" data-view-source="{{ \Devlib\Device::viewSource(request()) }}" data-os="{{ \Devlib\Device::os(request()) }}" data-t="{{ json_encode($texts(), JSON_UNESCAPED_UNICODE) }}">
                <h2 class="dl-sinfo__t">{{ \Devlib\Lang::text($title) }}</h2>
                <p class="dl-sinfo__note">{{ $note !== '' ? \Devlib\Lang::text($note) : \Devlib\Lang::text('Open this page on the device you want to measure, then tap Copy result and paste it in the chat. Nothing is sent or stored on the server.') }}</p>
                <div class="dl-sinfo__sum" role="status" aria-live="polite" data-sinfo-sum>—</div>
                <button type="button" class="dl-sinfo__copy" data-sinfo-copy><i class="fa-regular fa-copy" aria-hidden="true"></i> <span>{{ \Devlib\Lang::text('Copy result') }}</span></button>
                <p class="dl-sinfo__st" role="status" aria-live="polite" data-sinfo-status></p>
                <div class="dl-sinfo__groups">
                    @foreach (\Devlib\Components\ScreenInfo::GROUPS as $group => $rows)
                        <section class="dl-sinfo__g" data-sinfo-group="{{ $group }}">
                            <h3>{{ \Devlib\Lang::text($group) }}</h3>
                            <dl>
                                @foreach ($rows as $key => $label)
                                    <div class="dl-sinfo__r"><dt>{{ \Devlib\Lang::text($label) }}</dt><dd data-sinfo="{{ $key }}">—</dd></div>
                                @endforeach
                            </dl>
                        </section>
                    @endforeach
                </div>
                <details class="dl-sinfo__txt" data-sinfo-details>
                    <summary>{{ \Devlib\Lang::text('Result text (select it to copy by hand)') }}</summary>
                    <textarea readonly rows="12" spellcheck="false" aria-label="{{ \Devlib\Lang::text('Result text (select it to copy by hand)') }}" data-sinfo-text></textarea>
                </details>
            </section>
            BLADE;
    }
}
