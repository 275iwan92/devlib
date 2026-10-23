<?php

/*
 * REMARK
 * Tujuan       : Halaman login smartphone potret: satu kolom mengalir (320 sampai sekitar 440 px), isian 52 px dan teks 16 px, tombol Masuk besar, tepi aman, dan tautan kecil kembali ke tampilan desktop.
 * Sumber hulu  : x-dl::login-box (isian, galat, tombol); Devlib\Device (pilihan tampilan).
 * Input        : action, error, username, forgotUrl, brand, tagline, show, deviceAction, contained.
 * Output       : Markup dari resources/views/components/phone-login.blade.php.
 * Dipakai oleh : halaman login HP proyek (dipilih lewat Devlib\Device); halaman katalog p7-phone.
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Login untuk ponsel: membungkus x-dl::login-box tanpa bingkai latar. Proyek memilih halaman ini bila Device::view = phone, dan login desktop (x-dl::frame + login-box + x-dl::phone-offer) bila desktop.
// Slot = pesan di atas kotak (mis. x-dl::alert status). deviceAction = alamat POST yang menyimpan ui=desktop (tautan "Desktop view").
class PhoneLogin extends DevlibComponent
{
    public function __construct(
        public string $action = '',
        public ?string $error = null,
        public string $username = '',
        public string $forgotUrl = '#',
        public ?string $brand = null,
        public ?string $tagline = null,
        // Bagian login-box yang tampil (lihat x-dl::login-box).
        public string $show = 'brand, title, username, password, forgot, button',
        // null = tanpa tautan kembali ke tampilan desktop.
        public ?string $deviceAction = null,
        // true = tinggi mengikuti pembungkus (peraga katalog), false = seluruh layar.
        public bool $contained = false,
    ) {}

    public function tag(): string
    {
        return 'x-dl::phone-login';
    }

    public function source(): string
    {
        return 'x-dl::login-box';
    }

    public function render()
    {
        return view('devlib::components.phone-login');
    }
}
