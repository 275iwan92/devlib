<?php

/*
 * REMARK
 * Tujuan       : Tiga halaman pengaturan di dialog Setup Layer Menu (bersama Theme dan Language): Music (putar musik latar, volume, pilihan trek), Motion (animasi layar: ikuti perangkat / nyala / kurangi gerak) dan Sound (dering Pomodoro, alarm, timer, notifikasi, chat, facility: aktif, volume, Test). Pola dari proyek admin Jo.
 * Sumber hulu  : Komponen generik DevLib; mengendalikan x-dl::music-player (event dl:music), Devlib.motion (js hero-fx.js) dan Devlib.sound (js sound.js), tanpa tabel/model proyek.
 * Input        : part (all | music | motion | sound; bawaan all).
 * Output       : Tiga <section data-lm-page data-lm-in="setup"> (atau satu bila part diisi) + js/components/setup-media.js + CSS .dl-setup.
 * Dipakai oleh : slot profilePages x-dl::layer-shell (layar proyek); atau per bagian (part) di layar biasa dalam iframe, mis. Application Setup tmerp (pemutar musik dicari di jendela induk).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Halaman Setup untuk musik, gerak, dan bunyi. Tempatkan di slot profilePages x-dl::layer-shell; Layer Menu memindahkan ketiga <section> ke dialog Setup.
// Halaman Music hanya berguna bila halaman memuat x-dl::music-player (daftar trek dan kendali diambil dari pemutar itu); tanpa pemutar, pilihan trek disembunyikan dan saklar tidak berefek.
class SetupMedia extends DevlibComponent
{
    // part: all = ketiga halaman untuk slot profilePages; music | motion | sound = satu bagian untuk layar biasa (dikelompokkan oleh x-dl::card proyek).
    public function __construct(public string $part = 'all') {}

    public function tag(): string
    {
        return 'x-dl::setup-media';
    }

    public function source(): string
    {
        return 'x-dl::music-player (musik), Devlib.motion (gerak, localStorage dl-motion) dan Devlib.sound (bunyi, localStorage dl-sound)';
    }

    public function render()
    {
        return <<<'BLADE'
            @if (in_array($part, ['all', 'music']))
            <section data-lm-page="music" data-lm-in="setup" data-label="{{ \Devlib\Lang::text('Music') }}" data-icon="fa-music" data-dl-setup-music>
                <div class="dl-setup" {!! $dl() !!}>
                    <label class="dl-setup__row"><input type="checkbox" data-dl-setup-on> <span>{{ \Devlib\Lang::text('Play background music') }}</span></label>
                    <label class="dl-setup__col"><span>{{ \Devlib\Lang::text('Volume') }} <b data-dl-setup-volv></b></span><input type="range" min="0" max="100" step="1" data-dl-setup-vol aria-label="{{ \Devlib\Lang::text('Music volume') }}"></label>
                    <label class="dl-setup__col" data-dl-setup-trackrow><span>{{ \Devlib\Lang::text('Track') }}</span><select data-dl-setup-track aria-label="{{ \Devlib\Lang::text('Track choice') }}"></select></label>
                    <p class="dl-setup__note">{{ \Devlib\Lang::text('Off by default. Volume and track are remembered on this device. The music always starts silent when a page opens and pauses while the tab is hidden. Title and author of the song show in the player while it plays.') }}</p>
                </div>
            </section>
            @endif
            @if (in_array($part, ['all', 'motion']))
            <section data-lm-page="motion" data-lm-in="setup" data-label="{{ \Devlib\Lang::text('Screen animation') }}" data-icon="fa-wand-magic-sparkles" data-dl-setup-motion>
                <div class="dl-setup">
                    <div class="dl-setup__seg" role="radiogroup" aria-label="{{ \Devlib\Lang::text('Screen animation') }}">
                        <label><input type="radio" name="dl-motion" value="auto"><span>{{ \Devlib\Lang::text('Follow device') }}</span></label>
                        <label><input type="radio" name="dl-motion" value="on"><span>{{ \Devlib\Lang::text('On') }}</span></label>
                        <label><input type="radio" name="dl-motion" value="off"><span>{{ \Devlib\Lang::text('Reduce motion') }}</span></label>
                    </div>
                    <p class="dl-setup__note">{{ \Devlib\Lang::text('Affects the movement of the greeting box. "Follow device" uses the reduce-motion setting of the system.') }}</p>
                </div>
            </section>
            @endif
            @if (in_array($part, ['all', 'sound']))
            <section data-lm-page="sound" data-lm-in="setup" data-label="{{ \Devlib\Lang::text('Sound') }}" data-icon="fa-bell" data-dl-setup-sound>
                <div class="dl-setup">
                    @foreach ([['pomodoro', 'Pomodoro'], ['alarm', 'Alarm'], ['timer', 'Timer'], ['notif', 'ERP notifications (bell)'], ['chat', 'Chat'], ['facility', 'Facility alarm']] as [$k, $label])
                        <div class="dl-setup__sound" data-kind="{{ $k }}">
                            <label class="dl-setup__row"><input type="checkbox" data-dl-sound-on> <span>{{ \Devlib\Lang::text($label) }}</span></label>
                            <input type="range" min="0" max="100" step="5" data-dl-sound-vol aria-label="{{ \Devlib\Lang::text('Volume') }}: {{ \Devlib\Lang::text($label) }}">
                            <button type="button" class="dl-btn dl-btn--ghost" data-dl-sound-test>{{ \Devlib\Lang::text('Test') }}</button>
                        </div>
                    @endforeach
                    <p class="dl-setup__note">{{ \Devlib\Lang::text('Off by default. Each sound is one soft tone, never repeated, and is remembered on this device. Turning a sound on or pressing Test plays it once.') }}</p>
                </div>
            </section>
            @endif
            BLADE;
    }
}
