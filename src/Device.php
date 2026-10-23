<?php

namespace Devlib;

use Illuminate\Http\Request;

/* REMARK
 * Tujuan       : Perangkat tampilan (hp | desktop): tebakan awal dari header permintaan dan pilihan pengguna di cookie `ui`; pilihan pengguna selalu menang atas tebakan.
 * Sumber hulu  : Sec-CH-UA-Mobile (hanya dikirim Chrome lewat HTTPS / localhost) dan User-Agent.
 * Input        : cookie ui, header Sec-CH-UA-Mobile, header User-Agent (atau objek Request).
 * Output       : 'hp' atau 'desktop'.
 * Dipakai oleh : x-dl::device-pick, x-dl::phone-shell; middleware proyek yang memilih view HP atau desktop.
 * Status       : Aktif (DevLib).
 */
// Hanya PONSEL yang `hp`; tablet, HP yang meminta situs desktop, dan jendela desktop sempit tetap `desktop` (bukan salah, itu bawaan);
// pengguna mengganti sendiri lewat x-dl::device-pick dan pilihannya disimpan di cookie.
final class Device
{
    public const COOKIE = 'ui';

    public const HP = 'hp';

    public const DESKTOP = 'desktop';

    public const CHOICES = [self::HP, self::DESKTOP];

    // Tebakan dari header. Sec-CH-UA-Mobile bila ada ('?1' ponsel, '?0' bukan), selain itu User-Agent: ponsel Android membawa kata "Mobile" (tablet Android tidak), iPhone / iPod ya, iPad tidak.
    public static function detect(?string $userAgent, ?string $chMobile = null): string
    {
        $ch = trim((string) $chMobile);
        if ($ch === '?1' || $ch === '?0') {
            return $ch === '?1' ? self::HP : self::DESKTOP;
        }

        return preg_match('/iPhone|iPod|Windows Phone|Android.+Mobile|Mobile.+Android/i', (string) $userAgent) ? self::HP : self::DESKTOP;
    }

    // Cookie yang sah menang; selain itu tebakan.
    public static function resolve(?string $cookie, ?string $userAgent, ?string $chMobile = null): string
    {
        return in_array($cookie, self::CHOICES, true) ? $cookie : self::detect($userAgent, $chMobile);
    }

    public static function fromRequest(Request $request): string
    {
        $cookie = $request->cookie(self::COOKIE);

        return self::resolve(is_string($cookie) ? $cookie : null, $request->userAgent(), $request->header('Sec-CH-UA-Mobile'));
    }
}
