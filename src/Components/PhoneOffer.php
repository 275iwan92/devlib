<?php

/*
 * REMARK
 * Tujuan       : Popup kecil di layar desktop yang sempit: "Ingin tampilan yang lebih nyaman di smartphone? Ketuk di sini" dan tombol kecil "Tetap desktop"; keduanya menyimpan pilihan tampilan (cookie ui) lewat POST.
 * Sumber hulu  : Devlib\Device (cookie ui, Sec-CH-UA-Mobile, User-Agent).
 * Input        : action.
 * Output       : dialog dengan dua form kecil (ui=phone / ui=desktop); tanpa JavaScript.
 * Dipakai oleh : halaman login desktop proyek; halaman katalog p7-phone.
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

use Devlib\Device;

// Tawaran pindah ke tampilan HP. Ditulis ke halaman HANYA bila pengguna belum memilih (tanpa cookie `ui` yang sah) dan perangkat ditebak desktop;
// CSS menampilkannya hanya pada lebar <= 760 px (jendela desktop sempit, tablet tegak), jadi di layar lebar tidak pernah terlihat.
// Ketuk "Ketuk di sini" = POST ui=phone; "Tetap desktop" = POST ui=desktop (diingat di cookie, popup tidak muncul lagi). Proyek menyimpan cookie di rute `action`.
class PhoneOffer extends DevlibComponent
{
    public function __construct(
        // Alamat POST yang menyimpan pilihan (field ui = hp | desktop) lalu kembali ke layar ini.
        public string $action = '',
        // true = ditulis walau cookie ada / perangkat ponsel (peraga katalog).
        public bool $force = false,
    ) {}

    public function tag(): string
    {
        return 'x-dl::phone-offer';
    }

    public function source(): string
    {
        return 'cookie ui, Sec-CH-UA-Mobile, User-Agent';
    }

    // Tampil hanya bila pilihan belum dibuat dan tebakan = desktop.
    public function wanted(): bool
    {
        $cookie = request()->cookie(Device::COOKIE);

        return $this->force || (Device::normalize($cookie) === null && Device::view(request()) === Device::DESKTOP);
    }

    public function render()
    {
        return <<<'BLADE'
            @if ($wanted())
                <div {{ $attributes->class('dl-phoneoffer') }} {!! $dl() !!} role="dialog" aria-modal="true" aria-labelledby="dl-phoneoffer-t">
                    <div class="dl-phoneoffer__box">
                        <i class="fa-solid fa-mobile-screen-button dl-phoneoffer__ic" aria-hidden="true"></i>
                        <p class="dl-phoneoffer__t" id="dl-phoneoffer-t">{{ \Devlib\Lang::t('phone_offer') }}</p>
                        <form method="POST" action="{{ $action }}">@csrf<input type="hidden" name="ui" value="phone"><button type="submit" class="dl-phoneoffer__go">{{ \Devlib\Lang::t('phone_offer_go') }}</button></form>
                        <form method="POST" action="{{ $action }}">@csrf<input type="hidden" name="ui" value="desktop"><button type="submit" class="dl-phoneoffer__stay">{{ \Devlib\Lang::t('phone_offer_stay') }}</button></form>
                    </div>
                </div>
            @endif
            BLADE;
    }
}
