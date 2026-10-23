<?php

namespace Devlib;

use Illuminate\Http\Request;

/* REMARK
 * Tujuan       : Model MULTI VIEW baku: dua view kanonik `phone` (smartphone Android / iOS, potret) dan `desktop` (laptop, desktop, DAN tablet), plus OS (android | ios | other); pilihan pengguna di cookie `ui` selalu menang atas tebakan.
 * Sumber hulu  : Sec-CH-UA-Mobile (hanya Chrome/Edge/Opera lewat HTTPS atau localhost), User-Agent (satu-satunya petunjuk Safari iOS: tidak mengirim client hints), cookie ui, atribut permintaan dl.view / dl.os (Device::override, dipasang ViewOverride untuk pratinjau).
 * Input        : objek Request (atau cookie, User-Agent, Sec-CH-UA-Mobile untuk detect / resolve).
 * Output       : view() = 'phone' | 'desktop'; os() = 'android' | 'ios' | 'other'; bodyClass(); viewport(); screenIsPhone() (aturan layar phone, batas 600 px + HP landscape).
 * Dipakai oleh : x-dl::device-pick, x-dl::phone-shell, x-dl::phone-offer, x-dl::view-as; middleware dan layout proyek yang memilih view; aturan di skills/devlib/views.md.
 * Status       : Aktif (DevLib).
 */
// Hanya PONSEL yang `phone`; tablet (Android tanpa kata "Mobile", iPad, iPadOS yang menyamar sebagai Mac), HP yang meminta situs desktop, layar lipat terbuka, dan jendela desktop sempit tetap `desktop` (bukan salah, itu bawaan);
// pengguna mengganti sendiri lewat x-dl::device-pick dan pilihannya disimpan di cookie. Nilai cookie lama `hp` = `phone` (alias, tetap terbaca).
final class Device
{
    public const COOKIE = 'ui';

    public const PHONE = 'phone';

    public const DESKTOP = 'desktop';

    // Nama lama (cookie `ui=hp` yang sudah tersimpan di browser pengguna, kode proyek `Device::HP`). Bukan nilai ketiga: selalu dinormalkan ke PHONE.
    public const HP = self::PHONE;

    public const ALIASES = ['hp' => self::PHONE];

    public const CHOICES = [self::PHONE, self::DESKTOP];

    public const ANDROID = 'android';

    public const IOS = 'ios';

    public const OTHER = 'other';

    // Atribut Request untuk menimpa view / OS pada SATU permintaan (pratinjau "Lihat sebagai", tes). Menang atas cookie.
    public const OVERRIDE_VIEW = 'dl.view';

    public const OVERRIDE_OS = 'dl.os';

    // Aturan LAYAR phone (sementara, keputusan Jo 2026-10-23 dari riset breakpoint; disesuaikan bila kenyataan di perangkat kurang pas): lebar CSS < 600, ATAU HP landscape =
    // tinggi < 480 DAN pointer coarse DAN lebar < 1000. Selain itu desktop (tablet, lipat dalam, laptop). Dipakai popup x-dl::phone-offer (CSS) dan Devlib.phoneScreen() (view.js);
    // PHONE_MEDIA harus sama persis dengan media query di keduanya (dijaga tes). Ini TIDAK mengubah view(): tebakan header, cookie, dan pilihan pengguna tetap.
    public const PHONE_MAX_WIDTH = 600;

    public const LANDSCAPE_MAX_HEIGHT = 480;

    public const LANDSCAPE_MAX_WIDTH = 1000;

    public const PHONE_MEDIA = '(max-width: 599.98px), (max-height: 479.98px) and (max-width: 999.98px) and (pointer: coarse)';

    // Apakah layar berukuran (lebar x tinggi CSS px, pointer utama kasar/sentuh) memenuhi aturan phone? Setara PHONE_MEDIA.
    public static function screenIsPhone(int|float $width, int|float $height, bool $coarsePointer): bool
    {
        return $width < self::PHONE_MAX_WIDTH || ($coarsePointer && $height < self::LANDSCAPE_MAX_HEIGHT && $width < self::LANDSCAPE_MAX_WIDTH);
    }

    // Nilai view yang sah ('phone' | 'desktop'), alias lama dipetakan; selain itu null.
    public static function normalize(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $value = self::ALIASES[$value] ?? $value;

        return in_array($value, self::CHOICES, true) ? $value : null;
    }

    // Tebakan view dari header. Sec-CH-UA-Mobile bila ada ('?1' ponsel, '?0' bukan), selain itu User-Agent: ponsel Android membawa kata "Mobile" (tablet Android tidak), iPhone / iPod ya,
    // iPad tidak, iPadOS 13+ mengirim UA Mac (tablet = desktop, memang tidak terbedakan di server).
    public static function detect(?string $userAgent, ?string $chMobile = null): string
    {
        $ch = trim((string) $chMobile);
        if ($ch === '?1' || $ch === '?0') {
            return $ch === '?1' ? self::PHONE : self::DESKTOP;
        }

        return preg_match('/iPhone|iPod|Windows Phone|Android.+Mobile|Mobile.+Android/i', (string) $userAgent) ? self::PHONE : self::DESKTOP;
    }

    // OS dari User-Agent: iOS (iPhone / iPad / iPod; iPadOS bermode Mac tampil `other`, sisi klien memakai maxTouchPoints: js view.js), Android (ponsel dan tablet), selain itu other.
    public static function detectOs(?string $userAgent): string
    {
        $ua = (string) $userAgent;
        if (preg_match('/iPhone|iPad|iPod/i', $ua)) {
            return self::IOS;
        }

        return stripos($ua, 'Android') !== false ? self::ANDROID : self::OTHER;
    }

    // Cookie yang sah (termasuk alias lama `hp`) menang; selain itu tebakan.
    public static function resolve(?string $cookie, ?string $userAgent, ?string $chMobile = null): string
    {
        return self::normalize($cookie) ?? self::detect($userAgent, $chMobile);
    }

    // View permintaan ini: 'phone' | 'desktop'. Urutan: override permintaan (pratinjau), cookie pengguna, tebakan header.
    public static function view(Request $request): string
    {
        $override = self::normalize($request->attributes->get(self::OVERRIDE_VIEW));
        if ($override !== null) {
            return $override;
        }
        $cookie = $request->cookie(self::COOKIE);

        return self::resolve(is_string($cookie) ? $cookie : null, $request->userAgent(), $request->header('Sec-CH-UA-Mobile'));
    }

    // OS permintaan ini: 'android' | 'ios' | 'other' (override permintaan, selain itu User-Agent).
    public static function os(Request $request): string
    {
        $override = $request->attributes->get(self::OVERRIDE_OS);

        return in_array($override, [self::ANDROID, self::IOS, self::OTHER], true) ? $override : self::detectOs($request->userAgent());
    }

    // Nama lama untuk view(); dipertahankan agar proyek yang sudah memakainya tidak patah.
    public static function fromRequest(Request $request): string
    {
        return self::view($request);
    }

    // Timpa view (dan OS) hanya untuk permintaan ini, tanpa menyentuh cookie. Dipakai pratinjau; proyek tidak perlu memanggilnya.
    public static function override(Request $request, string $view, ?string $os = null): void
    {
        $request->attributes->set(self::OVERRIDE_VIEW, self::normalize($view));
        if ($os !== null) {
            $request->attributes->set(self::OVERRIDE_OS, $os);
        }
    }

    // Kelas untuk <body> (tambahkan di layout proyek): view phone = 'dl-on-phone' + 'dl-os-android' | 'dl-os-ios'. CSS DevLib memakainya untuk memadatkan form dan layar desktop yang dibuka di dalam
    // x-dl::phone-shell (iframe) dan untuk aturan khusus OS; view desktop = '' (tidak ada yang berubah).
    public static function bodyClass(Request $request): string
    {
        if (self::view($request) !== self::PHONE) {
            return '';
        }
        $os = self::os($request);

        return 'dl-on-phone'.($os === self::OTHER ? '' : ' dl-os-'.$os);
    }

    // Isi <meta name="viewport"> untuk layout proyek. Phone: viewport-fit=cover (isi sampai tepi layar; notch / Dynamic Island / home indicator diatur env(safe-area-inset-*) di CSS DevLib). Desktop: seperti sebelumnya.
    public static function viewport(Request $request): string
    {
        return 'width=device-width, initial-scale=1.0'.(self::view($request) === self::PHONE ? ', viewport-fit=cover' : '');
    }
}
