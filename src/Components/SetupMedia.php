<?php

/*
 * REMARK
 * Tujuan       : Dua halaman pengaturan di dialog Setup Layer Menu (bersama Theme dan Language): Music (putar musik latar, volume, pilihan trek) dan Motion (animasi layar: ikuti perangkat / nyala / kurangi gerak). Pola dari proyek admin Jo.
 * Sumber hulu  : Komponen generik DevLib; mengendalikan x-dl::music-player (event dl:music) dan Devlib.motion (js hero-fx.js), tanpa tabel/model proyek.
 * Input        : -
 * Output       : Dua <section data-lm-page data-lm-in="setup"> + js/components/setup-media.js + CSS .dl-setup.
 * Dipakai oleh : slot profilePages x-dl::layer-shell (layar proyek).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Halaman Setup untuk musik dan gerak. Tempatkan di slot profilePages x-dl::layer-shell; Layer Menu memindahkan kedua <section> ke dialog Setup.
// Halaman Music hanya berguna bila halaman memuat x-dl::music-player (daftar trek dan kendali diambil dari pemutar itu); tanpa pemutar, pilihan trek disembunyikan dan saklar tidak berefek.
class SetupMedia extends DevlibComponent
{
    public function tag(): string
    {
        return 'x-dl::setup-media';
    }

    public function source(): string
    {
        return 'x-dl::music-player (musik) dan Devlib.motion (gerak); pilihan gerak di localStorage dl-motion';
    }

    public function render()
    {
        return <<<'BLADE'
            <section data-lm-page="music" data-lm-in="setup" data-label="{{ \Devlib\Lang::text('Music') }}" data-icon="fa-music" data-dl-setup-music>
                <div class="dl-setup" {!! $dl() !!}>
                    <label class="dl-setup__row"><input type="checkbox" data-dl-setup-on> <span>{{ \Devlib\Lang::text('Play background music') }}</span></label>
                    <label class="dl-setup__col"><span>{{ \Devlib\Lang::text('Volume') }} <b data-dl-setup-volv></b></span><input type="range" min="0" max="100" step="1" data-dl-setup-vol aria-label="{{ \Devlib\Lang::text('Music volume') }}"></label>
                    <label class="dl-setup__col" data-dl-setup-trackrow><span>{{ \Devlib\Lang::text('Track') }}</span><select data-dl-setup-track aria-label="{{ \Devlib\Lang::text('Track choice') }}"></select></label>
                    <p class="dl-setup__note">{{ \Devlib\Lang::text('Off by default. Volume and track are remembered on this device. The music always starts silent when a page opens and pauses while the tab is hidden. Title and author of the song show in the player while it plays.') }}</p>
                </div>
            </section>
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
            BLADE;
    }
}
