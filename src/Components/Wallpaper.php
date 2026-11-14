<?php

/*
 * REMARK
 * Tujuan       : Latar (wallpaper) bergaya kaca bening: SVG dibuat di server dari bentuk + palet + seed, dengan satu kutipan di posisi acak. Tanpa JS, tanpa berkas gambar.
 * Sumber hulu  : Komponen generik DevLib; tidak membaca data proyek (kutipan diberikan pemanggil).
 * Input        : kind (ribbon, waves, bubbles, shards, ripples, blob, folds, orbs, loop, panels, soft, cards), palette (sage bawaan, sky, rose, mint, amber, violet, night), quote, seed, quotePosition (kosong = acak dari seed), ratio, static (seed tetap dari kind+palette+quote), autoQuote (kutipan bawaan Indonesia bila quote kosong).
 * Output       : <div class="dl-wallpaper"> berisi <svg> dan <q>; mengisi lebar induk, tinggi menurut rasio.
 * Dipakai oleh : layar proyek (latar kartu sapaan, login, dashboard: opt-in); halaman peragaan Backgrounds > Wallpaper.
 * Status       : Review (W1 empat bentuk awal; W2 delapan bentuk lain; W3 kutipan bawaan, static, posisi menghindari bentuk utama).
 */

namespace Devlib\Components;

// Wallpaper: <x-dl::wallpaper kind="ribbon" palette="sage" quote="..." :seed="7" />. Seed sama = gambar sama.
class Wallpaper extends DevlibComponent
{
    public const KINDS = ['ribbon', 'bubbles', 'folds', 'orbs', 'waves', 'shards', 'ripples', 'blob', 'loop', 'panels', 'soft', 'cards'];

    public const POSITIONS = ['top-left', 'bottom-left', 'top-right', 'bottom-right'];

    // top/bot = gradasi dasar, a/b = warna kaca, ink = teks kutipan.
    public const PALETTES = [
        'sage' => ['top' => '#a9c0ab', 'bot' => '#eef4ec', 'a' => '#7fa386', 'b' => '#dbe9d9', 'ink' => '#2c4a35'],
        'sky' => ['top' => '#9db8ef', 'bot' => '#e8f0fb', 'a' => '#7fa6e8', 'b' => '#dbe8fb', 'ink' => '#1e3a6e'],
        'rose' => ['top' => '#f6b8c8', 'bot' => '#fdeef2', 'a' => '#ee8aa6', 'b' => '#fde0e8', 'ink' => '#7a1f40'],
        'mint' => ['top' => '#9fdcc8', 'bot' => '#eafaf4', 'a' => '#5fc2a2', 'b' => '#d6f3e8', 'ink' => '#0f5a47'],
        'amber' => ['top' => '#f7cf8e', 'bot' => '#fff5e2', 'a' => '#f2a640', 'b' => '#fde8c2', 'ink' => '#7a4a06'],
        'violet' => ['top' => '#b9a8ee', 'bot' => '#f1edfd', 'a' => '#8f78e0', 'b' => '#e3dbfa', 'ink' => '#37238a'],
        'night' => ['top' => '#0b1230', 'bot' => '#1c2a55', 'a' => '#4f7bff', 'b' => '#7f5bff', 'ink' => '#dbe6ff'],
    ];

    // Font per bentuk: tumpukan font aman (tanpa berkas font), [keluarga, bobot, gaya].
    private const FONTS = [
        'ribbon' => ["'Segoe UI Light','Helvetica Neue',Arial,sans-serif", 300, 'italic'],
        'bubbles' => ["'Trebuchet MS','Segoe UI',sans-serif", 600, 'normal'],
        'folds' => ["'Segoe UI Light','Helvetica Neue',Arial,sans-serif", 200, 'normal'],
        'orbs' => ["'Trebuchet MS','Segoe UI',sans-serif", 300, 'normal'],
        'waves' => ["'Brush Script MT','Segoe Script',cursive", 400, 'normal'],
        'shards' => ["'Palatino Linotype',Georgia,serif", 500, 'italic'],
        'ripples' => ["Georgia,'Times New Roman',serif", 400, 'normal'],
        'blob' => ["'Segoe UI',Arial,sans-serif", 700, 'normal'],
        'loop' => ["'Segoe UI Light','Helvetica Neue',Arial,sans-serif", 200, 'normal'],
        'panels' => ["'Segoe UI Light','Helvetica Neue',Arial,sans-serif", 200, 'normal'],
        'soft' => ["'Segoe UI',Arial,sans-serif", 600, 'normal'],
        'cards' => ["'Segoe UI',Arial,sans-serif", 400, 'normal'],
    ];

    // Kutipan bawaan (Indonesia, pendek): dipakai bila prop `auto-quote` menyala dan `quote` kosong.
    public const QUOTES = [
        'Mulai dari yang kecil, selesaikan dengan baik.',
        'Satu langkah hari ini cukup.',
        'Pelan-pelan asal sampai.',
        'Rapi hari ini, tenang besok.',
        'Kerjakan yang penting dulu.',
        'Hari baik dimulai dari napas yang tenang.',
        'Yang konsisten akan menang.',
        'Selesai lebih baik daripada sempurna.',
        'Terima kasih sudah bertahan hari ini.',
        'Kecil tapi terus, lama-lama jadi besar.',
        'Fokus pada satu hal, lalu satu lagi.',
        'Istirahat juga bagian dari kerja.',
    ];

    private static int $count = 0;

    // Pusat bentuk utama (0..1 dari lebar), diisi oleh bentuk yang punya satu pusat; null = tidak ada.
    private ?float $focus = null;

    private int $s;

    public string $uid;

    public function __construct(
        public string $kind = 'ribbon',
        public string $palette = 'sage',
        public ?string $quote = null,
        public ?int $seed = null,
        public ?string $quotePosition = null,
        public string $ratio = '5/4',
        public bool $static = false,
        public bool $autoQuote = false,
    ) {
        $this->kind = in_array($kind, self::KINDS, true) ? $kind : 'ribbon';
        $this->palette = isset(self::PALETTES[$palette]) ? $palette : 'sage';
        $this->ratio = preg_match('#^\d+(\.\d+)?/\d+(\.\d+)?$#', $ratio) ? $ratio : '5/4';
        $this->quotePosition = in_array($quotePosition, self::POSITIONS, true) ? $quotePosition : null;
        $this->seed = $seed ?? ($static ? (crc32($this->kind.'|'.$this->palette.'|'.($quote ?? '')) % 2147483646) + 1 : random_int(1, 2147483646));
        $this->s = max(1, $this->seed % 2147483647);
        $this->uid = 'dlwp'.(++self::$count);
        if (($this->quote === null || $this->quote === '') && $autoQuote) {
            $this->quote = self::QUOTES[($this->seed >> 3) % count(self::QUOTES)];
        }
    }

    public function tag(): string
    {
        return 'x-dl::wallpaper';
    }

    public function source(): string
    {
        return 'Dibuat di server dari kind + palette + seed (tanpa data)';
    }

    // Pengacak deterministik 0..1 (LCG Park-Miller): seed sama, urutan sama.
    private function rnd(): float
    {
        $this->s = ($this->s * 16807) % 2147483647;

        return $this->s / 2147483647;
    }

    private function grad(string $id, string $c1, string $c2, float $o1 = .9, float $o2 = .15, int $x2 = 1, int $y2 = 1): string
    {
        return "<linearGradient id=\"{$id}\" x1=\"0\" y1=\"0\" x2=\"{$x2}\" y2=\"{$y2}\"><stop offset=\"0\" stop-color=\"{$c1}\" stop-opacity=\"{$o1}\"/><stop offset=\"1\" stop-color=\"{$c2}\" stop-opacity=\"{$o2}\"/></linearGradient>";
    }

    private function n(float $v): string
    {
        return rtrim(rtrim(number_format($v, 1, '.', ''), '0'), '.');
    }

    // SVG penuh (viewBox 600x480, diiris ke kotak lewat slice).
    public function svg(): string
    {
        $this->focus = null;
        $this->s = max(1, $this->seed % 2147483647);
        $p = self::PALETTES[$this->palette];
        $u = $this->uid;
        $W = 600;
        $H = 480;
        [$defs, $body] = match ($this->kind) {
            'folds' => $this->folds($p, $u, $W, $H),
            'orbs' => $this->orbs($p, $u, $W, $H),
            'waves' => $this->ribbon($p, $u, $W, $H, true),
            'shards' => $this->shards($p, $u, $W, $H),
            'ripples' => $this->ripples($p, $u, $W, $H),
            'blob' => $this->blob($p, $u, $W, $H),
            'loop' => $this->loop($p, $u, $W, $H),
            'panels' => $this->panels($p, $u, $W, $H),
            'soft' => $this->soft($p, $u, $W, $H),
            'cards' => $this->cards($p, $u, $W, $H),
            'bubbles' => $this->bubbles($p, $u, $W, $H),
            default => $this->ribbon($p, $u, $W, $H),
        };

        return "<svg viewBox=\"0 0 {$W} {$H}\" preserveAspectRatio=\"xMidYMid slice\" aria-hidden=\"true\" focusable=\"false\"><defs>{$defs}</defs>{$body}</svg>";
    }

    // Pita dan gelembung berdiri di atas air: gradasi langit, garis cakrawala, pantulan memudar.
    private function sky(array $p, string $u, int $W, int $H, string $defs, string $body): array
    {
        $hz = $H * .8;
        $defs = "<linearGradient id=\"bg{$u}\" x1=\"0\" y1=\"0\" x2=\"0\" y2=\"1\"><stop offset=\"0\" stop-color=\"{$p['top']}\"/><stop offset=\"1\" stop-color=\"{$p['bot']}\"/></linearGradient>"
            ."<linearGradient id=\"f{$u}\" x1=\"0\" y1=\"0\" x2=\"0\" y2=\"1\"><stop offset=\"0\" stop-color=\"#fff\" stop-opacity=\".55\"/><stop offset=\"1\" stop-color=\"#fff\" stop-opacity=\"0\"/></linearGradient>"
            ."<mask id=\"m{$u}\"><rect x=\"0\" y=\"".$this->n($hz - 10).'" width="'.$W.'" height="'.$this->n($H - $hz + 10)."\" fill=\"url(#f{$u})\"/></mask>".$defs;
        $refl = '<g transform="translate(0 '.$this->n($hz * 2).") scale(1 -1)\" opacity=\".28\" mask=\"url(#m{$u})\">{$body}</g>";
        $full = "<rect width=\"{$W}\" height=\"{$H}\" fill=\"url(#bg{$u})\"/>{$body}<rect y=\"".$this->n($hz)."\" width=\"{$W}\" height=\"".$this->n($H - $hz)."\" fill=\"{$p['bot']}\" opacity=\".7\"/>{$refl}<line x1=\"0\" x2=\"{$W}\" y1=\"".$this->n($hz).'" y2="'.$this->n($hz).'" stroke="#fff" stroke-opacity=".7"/>';

        return [$defs, $full];
    }

    private function ribbon(array $p, string $u, int $W, int $H, bool $waves = false): array
    {
        $cx = $W * (.35 + $this->rnd() * .3);
        $this->focus = $cx / $W;
        $cy = $H * (.42 + $this->rnd() * .1);
        $defs = $this->grad('g'.$u, '#ffffff', $p['a'], .85, .35).$this->grad('h'.$u, '#ff9ec7', '#7fd0ff', .9, .9, 1, 0);
        $body = '';
        $n = $waves ? 3 : 5;
        $sw = $waves ? 3 : 1.4;
        for ($i = 0; $i < $n; $i++) {
            $k = $i / $n;
            $y0 = $cy + ($this->rnd() - .5) * 90;
            $amp = 60 + $this->rnd() * 80;
            $x = fn (float $f) => $this->n($W * $f);
            $body .= '<path d="M-20 '.$this->n($y0 + 60).' C '.$x(.2).' '.$this->n($y0 - $amp).' '.$x(.4).' '.$this->n($y0 + $amp).' '.$x(.55).' '.$this->n($y0 - $amp * .3)
                .' S '.$x(.9).' '.$this->n($y0 + $amp * .8).' '.($W + 30).' '.$this->n($y0 - 20).' C '.$x(.8).' '.$this->n($y0 + $amp * 1.1).' '.$x(.5).' '.$this->n($y0 + $amp * .5).' '.$x(.35).' '.$this->n($y0 + $amp * 1.2)
                .' S -10 '.$this->n($y0 + 90).' -20 '.$this->n($y0 + 60)."Z\" fill=\"url(#g{$u})\" fill-opacity=\"".$this->n(.35 + $k * .3)."\" stroke=\"url(#h{$u})\" stroke-width=\"{$sw}\"/>";
        }

        return $this->sky($p, $u, $W, $H, $defs, $body);
    }

    private function bubbles(array $p, string $u, int $W, int $H): array
    {
        $hz = $H * .8;
        $defs = $this->grad('g'.$u, '#ffffff', $p['a'], .85, .35).$this->grad('h'.$u, '#ff9ec7', '#7fd0ff', .9, .9, 1, 0);
        $body = '';
        for ($i = 0; $i < 9; $i++) {
            $r = 18 + $this->rnd() * 60;
            $x = $this->rnd() * $W;
            $y = $hz - 40 - $this->rnd() * 260;
            $defs .= "<radialGradient id=\"b{$u}{$i}\" cx=\".35\" cy=\".3\"><stop offset=\"0\" stop-color=\"#fff\" stop-opacity=\".95\"/><stop offset=\".55\" stop-color=\"{$p['b']}\" stop-opacity=\".35\"/><stop offset=\"1\" stop-color=\"{$p['a']}\" stop-opacity=\".6\"/></radialGradient>";
            $body .= '<circle cx="'.$this->n($x).'" cy="'.$this->n($y).'" r="'.$this->n($r)."\" fill=\"url(#b{$u}{$i})\" stroke=\"url(#h{$u})\" stroke-width=\"1.5\"/>";
        }

        return $this->sky($p, $u, $W, $H, $defs, $body);
    }

    private function folds(array $p, string $u, int $W, int $H): array
    {
        $defs = "<linearGradient id=\"bg{$u}\" x1=\"0\" y1=\"0\" x2=\"1\" y2=\"1\"><stop offset=\"0\" stop-color=\"#fff\"/><stop offset=\"1\" stop-color=\"{$p['top']}\"/></linearGradient>"
            .$this->grad('g'.$u, '#ffffff', $p['a'], .9, .3).$this->grad('h'.$u, '#ffd1b8', '#8ad0ff', .9, .9, 1, 0)
            ."<filter id=\"bl{$u}\"><feGaussianBlur stdDeviation=\"7\"/></filter><radialGradient id=\"w{$u}\" cx=\".15\" cy=\".85\" r=\".6\"><stop offset=\"0\" stop-color=\"#ffb59a\" stop-opacity=\".75\"/><stop offset=\"1\" stop-color=\"#ffb59a\" stop-opacity=\"0\"/></radialGradient>";
        $b = "<rect width=\"{$W}\" height=\"{$H}\" fill=\"url(#bg{$u})\"/>";
        $b .= "<path d=\"M300 90 C380 70 470 150 640 330 L640 520 L230 520Z\" fill=\"url(#g{$u})\" opacity=\".55\" filter=\"url(#bl{$u})\"/>";
        $b .= "<path d=\"M60 120 C200 60 330 120 470 270 L520 520 L-20 520 L-20 200Z\" fill=\"url(#g{$u})\" opacity=\".8\"/>";
        for ($i = 0; $i < 9; $i++) {
            $b .= '<path d="M'.(-10 + $i * 6).' '.(200 + $i * 22).' C 160 '.(150 + $i * 20).' 330 '.(200 + $i * 16).' 520 '.(340 + $i * 14).'" fill="none" stroke="#fff" stroke-opacity="'.$this->n(.12 + $this->rnd() * .2).'" stroke-width="'.$this->n(1 + $this->rnd() * 2).'"/>';
        }
        $b .= "<path d=\"M60 120 C200 60 330 120 470 270\" fill=\"none\" stroke=\"url(#h{$u})\" stroke-width=\"2.4\"/><rect width=\"{$W}\" height=\"{$H}\" fill=\"url(#w{$u})\"/>";

        return [$defs, $b];
    }

    private function orbs(array $p, string $u, int $W, int $H): array
    {
        $defs = "<filter id=\"bl{$u}\"><feGaussianBlur stdDeviation=\"22\"/></filter><linearGradient id=\"t{$u}\" x1=\"0\" y1=\"0\" x2=\"0\" y2=\"1\"><stop offset=\"0\" stop-color=\"{$p['a']}\"/><stop offset=\".7\" stop-color=\"{$p['b']}\" stop-opacity=\".1\"/><stop offset=\"1\" stop-color=\"#fff\" stop-opacity=\"0\"/></linearGradient>"
            ."<radialGradient id=\"o{$u}\" cx=\".3\" cy=\".3\" r=\".85\"><stop offset=\"0\" stop-color=\"{$p['a']}\"/><stop offset=\".5\" stop-color=\"{$p['b']}\" stop-opacity=\".85\"/><stop offset=\"1\" stop-color=\"#9ff5d4\" stop-opacity=\".9\"/></radialGradient>"
            ."<radialGradient id=\"s{$u}\" cx=\".35\" cy=\".3\"><stop offset=\"0\" stop-color=\"#fff\"/><stop offset=\".5\" stop-color=\"#d9e9ff\"/><stop offset=\"1\" stop-color=\"#7ee8f2\"/></radialGradient>"
            ."<radialGradient id=\"r{$u}\" cx=\".5\" cy=\".5\" r=\".5\"><stop offset=\".8\" stop-color=\"#fff\" stop-opacity=\"0\"/><stop offset=\"1\" stop-color=\"#fff\" stop-opacity=\".95\"/></radialGradient>";
        $b = "<rect width=\"{$W}\" height=\"{$H}\" fill=\"#fff\"/><circle cx=\"200\" cy=\"120\" r=\"170\" fill=\"url(#t{$u})\"/><circle cx=\"170\" cy=\"330\" r=\"110\" fill=\"{$p['a']}\" opacity=\".35\" filter=\"url(#bl{$u})\"/>"
            ."<circle cx=\"360\" cy=\"250\" r=\"150\" fill=\"url(#o{$u})\"/><circle cx=\"360\" cy=\"250\" r=\"150\" fill=\"url(#r{$u})\"/><circle cx=\"500\" cy=\"70\" r=\"46\" fill=\"url(#s{$u})\"/><circle cx=\"90\" cy=\"430\" r=\"52\" fill=\"url(#s{$u})\" opacity=\".8\"/>";

        return [$defs, $b];
    }

    private function shards(array $p, string $u, int $W, int $H): array
    {
        $cx = $W * (.35 + $this->rnd() * .3);
        $this->focus = $cx / $W;
        $cy = $H * (.42 + $this->rnd() * .1);
        $defs = $this->grad('g'.$u, '#ffffff', $p['a'], .85, .35).$this->grad('h'.$u, '#ff9ec7', '#7fd0ff', .9, .9, 1, 0);
        $body = '';
        for ($i = 0; $i < 7; $i++) {
            $x = $cx + ($this->rnd() - .5) * 320;
            $y = $cy + ($this->rnd() - .5) * 160;
            $z = 50 + $this->rnd() * 90;
            $a = $this->rnd() * 360;
            $body .= '<polygon points="0,-'.$this->n($z).' '.$this->n($z * .55).','.$this->n($z * .4).' -'.$this->n($z * .5).','.$this->n($z * .55).'" transform="translate('.$this->n($x).' '.$this->n($y).') rotate('.$this->n($a)."\" fill=\"url(#g{$u})\" fill-opacity=\".5\" stroke=\"url(#h{$u})\" stroke-width=\"1.6\"/>";
        }

        return $this->sky($p, $u, $W, $H, $defs, $body);
    }

    private function ripples(array $p, string $u, int $W, int $H): array
    {
        $cx = $W * (.35 + $this->rnd() * .3);
        $this->focus = $cx / $W;
        $this->rnd();
        $hz = $H * .8;
        $defs = $this->grad('g'.$u, '#ffffff', $p['a'], .85, .35).$this->grad('h'.$u, '#ff9ec7', '#7fd0ff', .9, .9, 1, 0);
        $body = '';
        for ($i = 1; $i <= 7; $i++) {
            $body .= '<ellipse cx="'.$this->n($cx).'" cy="'.$this->n($hz + 4).'" rx="'.($i * 44).'" ry="'.($i * 7)."\" fill=\"none\" stroke=\"url(#h{$u})\" stroke-opacity=\"".$this->n(.9 - $i * .1).'" stroke-width="1.6"/>';
        }
        $body .= '<circle cx="'.$this->n($cx).'" cy="'.$this->n($hz - 90)."\" r=\"70\" fill=\"url(#g{$u})\" fill-opacity=\".55\" stroke=\"url(#h{$u})\" stroke-width=\"1.5\"/>";

        return $this->sky($p, $u, $W, $H, $defs, $body);
    }

    private function blob(array $p, string $u, int $W, int $H): array
    {
        $cx = $W * (.35 + $this->rnd() * .3);
        $this->focus = $cx / $W;
        $cy = $H * (.42 + $this->rnd() * .1);
        $defs = $this->grad('g'.$u, '#ffffff', $p['a'], .85, .35).$this->grad('h'.$u, '#ff9ec7', '#7fd0ff', .9, .9, 1, 0)
            ."<filter id=\"t{$u}\"><feTurbulence baseFrequency=\".012\" numOctaves=\"2\" seed=\"".(int) floor($this->rnd() * 99)."\"/><feDisplacementMap in=\"SourceGraphic\" scale=\"80\"/></filter>";
        $body = '<ellipse cx="'.$this->n($cx).'" cy="'.$this->n($cy)."\" rx=\"150\" ry=\"90\" fill=\"url(#g{$u})\" fill-opacity=\".7\" stroke=\"url(#h{$u})\" stroke-width=\"2\" filter=\"url(#t{$u})\"/>";

        return $this->sky($p, $u, $W, $H, $defs, $body);
    }

    private function loop(array $p, string $u, int $W, int $H): array
    {
        $defs = "<linearGradient id=\"bg{$u}\" x1=\"0\" y1=\"0\" x2=\"1\" y2=\"1\"><stop offset=\"0\" stop-color=\"#fbfdff\"/><stop offset=\"1\" stop-color=\"{$p['bot']}\"/></linearGradient>"
            .$this->grad('g'.$u, '#ffffff', $p['a'], .9, .5)
            ."<linearGradient id=\"d{$u}\" x1=\"0\" y1=\"1\" x2=\"1\" y2=\"0\"><stop offset=\"0\" stop-color=\"{$p['a']}\" stop-opacity=\".95\"/><stop offset=\".55\" stop-color=\"#9a7cf2\" stop-opacity=\".85\"/><stop offset=\"1\" stop-color=\"{$p['b']}\" stop-opacity=\".2\"/></linearGradient>"
            ."<filter id=\"bl{$u}\"><feGaussianBlur stdDeviation=\"14\"/></filter>";
        $b = "<rect width=\"{$W}\" height=\"{$H}\" fill=\"url(#bg{$u})\"/>"
            ."<path d=\"M-40 520 C-10 260 150 110 330 60 C430 34 520 40 640 -20 L640 120 C520 160 470 230 400 330 C330 430 200 520 40 560Z\" fill=\"url(#g{$u})\" opacity=\".85\"/>"
            ."<path d=\"M110 540 C140 340 260 190 420 120 C500 86 570 90 640 60 L640 300 C560 330 470 400 380 470 C300 520 200 560 110 540Z\" fill=\"url(#d{$u})\"/>"
            ."<path d=\"M200 520 C240 380 330 280 440 220\" fill=\"none\" stroke=\"#fff\" stroke-width=\"22\" stroke-opacity=\".55\" filter=\"url(#bl{$u})\"/>"
            .'<path d="M-30 480 C0 260 150 120 330 70 C440 40 530 50 640 -10" fill="none" stroke="#fff" stroke-width="3" stroke-opacity=".9"/><path d="M60 520 C100 340 230 190 400 130" fill="none" stroke="#fff" stroke-width="1.6" stroke-opacity=".7"/>';

        return [$defs, $b];
    }

    private function panels(array $p, string $u, int $W, int $H): array
    {
        $warm = match ($this->palette) {
            'mint' => '#a6e86a',
            'rose' => '#ffb27a',
            default => '#8fd0ff',
        };
        $defs = "<linearGradient id=\"a{$u}\" x1=\"0\" y1=\"0\" x2=\"1\" y2=\"1\"><stop offset=\"0\" stop-color=\"{$p['a']}\" stop-opacity=\".95\"/><stop offset=\"1\" stop-color=\"{$p['b']}\" stop-opacity=\".15\"/></linearGradient>"
            ."<linearGradient id=\"c{$u}\" x1=\"1\" y1=\"1\" x2=\"0\" y2=\"0\"><stop offset=\"0\" stop-color=\"{$warm}\" stop-opacity=\".95\"/><stop offset=\"1\" stop-color=\"#fff\" stop-opacity=\".05\"/></linearGradient>";
        $b = "<rect width=\"{$W}\" height=\"{$H}\" fill=\"#fcfdfe\"/>";
        for ($i = 0; $i < 60; $i++) {
            $b .= '<line x1="'.($i * 10).'" x2="'.($i * 10).'" y1="0" y2="'.($H / 2).'" stroke="#000" stroke-opacity=".02"/>';
        }
        $b .= '<g transform="translate(-40 '.$this->n($H * .62).') rotate(-14)"><rect width="430" height="330" rx="70" fill="url(#a'.$u.')"/><rect width="430" height="330" rx="70" fill="none" stroke="#fff" stroke-opacity=".8" stroke-width="1.6"/></g>'
            .'<g transform="translate('.$this->n($W * .66).' '.($H / 2).') rotate(28)"><rect width="300" height="330" rx="70" fill="url(#c'.$u.')"/><rect x="10" y="10" width="280" height="310" rx="62" fill="none" stroke="#fff" stroke-opacity=".7" stroke-width="1.4"/></g>';

        return [$defs, $b];
    }

    // Kontrol timbul lembut (ala soft UI) berserakan di atas latar bertitik; tanpa teks agar netral.
    private function soft(array $p, string $u, int $W, int $H): array
    {
        $dark = $this->palette === 'night';
        $bg = $dark ? '#262b36' : '#e4e7eb';
        $hi = $dark ? '#343a48' : '#fff';
        $ink = $dark ? '#8d97ad' : '#aeb4bd';
        $body = $dark ? '#2d3340' : '#f4f6f8';
        $defs = "<pattern id=\"dt{$u}\" width=\"12\" height=\"12\" patternUnits=\"userSpaceOnUse\"><circle cx=\"1\" cy=\"1\" r=\".7\" fill=\"{$ink}\" opacity=\".5\"/></pattern>"
            ."<filter id=\"e{$u}\" x=\"-30%\" y=\"-30%\" width=\"160%\" height=\"170%\"><feDropShadow dx=\"-2\" dy=\"-2\" stdDeviation=\"2\" flood-color=\"{$hi}\" flood-opacity=\".95\"/><feDropShadow dx=\"3\" dy=\"4\" stdDeviation=\"3.5\" flood-color=\"#8a93a3\" flood-opacity=\"".($dark ? '.5' : '.38').'"/></filter>';
        $e = "filter=\"url(#e{$u})\"";
        $b = "<rect width=\"{$W}\" height=\"{$H}\" fill=\"{$bg}\"/><rect width=\"{$W}\" height=\"{$H}\" fill=\"url(#dt{$u})\"/>";
        for ($r = 0; $r < 6; $r++) {
            for ($c = 0; $c < 6; $c++) {
                $k = (int) floor($this->rnd() * 9);
                $x = 50 + $c * 100 + ($r % 2) * 20 + ($this->rnd() - .5) * 14;
                $y = 50 + $r * 76 + ($this->rnd() - .5) * 12;
                $b .= $this->softItem($k, $x, $y, $e, $body, $hi, $ink);
            }
        }

        return [$defs, $b];
    }

    private function softItem(int $k, float $x, float $y, string $e, string $body, string $hi, string $ink): string
    {
        $X = fn (float $d) => $this->n($x + $d);
        $Y = fn (float $d) => $this->n($y + $d);

        return match ($k) {
            0, 1 => "<g {$e}><circle cx=\"{$X(0)}\" cy=\"{$Y(0)}\" r=\"15\" fill=\"{$body}\"/></g><path d=\"M{$X(-6)} {$Y(0)}H{$X(6)}".($k === 0 ? "M{$X(0)} {$Y(-6)}V{$Y(6)}" : '')."\" stroke=\"{$ink}\" stroke-width=\"2.4\" stroke-linecap=\"round\" fill=\"none\"/>",
            2 => "<g {$e}><rect x=\"{$X(-40)}\" y=\"{$Y(-9)}\" width=\"80\" height=\"18\" rx=\"9\" fill=\"{$body}\"/></g>",
            3 => "<g {$e}><rect x=\"{$X(-22)}\" y=\"{$Y(-22)}\" width=\"44\" height=\"44\" rx=\"12\" fill=\"{$body}\"/></g>",
            4 => "<g {$e}><rect x=\"{$X(-34)}\" y=\"{$Y(-13)}\" width=\"68\" height=\"26\" rx=\"13\" fill=\"{$body}\"/><circle cx=\"{$X(-20)}\" cy=\"{$Y(0)}\" r=\"10\" fill=\"{$hi}\"/></g>",
            5 => "<line x1=\"{$X(-55)}\" x2=\"{$X(55)}\" y1=\"{$Y(0)}\" y2=\"{$Y(0)}\" stroke=\"{$ink}\" stroke-opacity=\".5\" stroke-width=\"3\" stroke-linecap=\"round\"/><g {$e}><circle cx=\"{$X(($this->rnd() - .5) * 60)}\" cy=\"{$Y(0)}\" r=\"10\" fill=\"{$body}\"/></g>",
            6 => "<g stroke=\"{$ink}\" stroke-opacity=\".6\" fill=\"none\"><rect x=\"{$X(-26)}\" y=\"{$Y(-26)}\" width=\"52\" height=\"52\"/><path d=\"M{$X(-9)} {$Y(-26)}V{$Y(26)}M{$X(9)} {$Y(-26)}V{$Y(26)}M{$X(-26)} {$Y(-9)}H{$X(26)}M{$X(-26)} {$Y(9)}H{$X(26)}\"/></g>",
            7 => $this->spoke($x, $y, $ink),
            default => "<g {$e}><circle cx=\"{$X(0)}\" cy=\"{$Y(0)}\" r=\"17\" fill=\"{$body}\"/></g><path d=\"M{$X(-4)} {$Y(-7)}L{$X(8)} {$Y(0)}L{$X(-4)} {$Y(7)}Z\" fill=\"{$ink}\" fill-opacity=\".8\"/>",
        };
    }

    private function spoke(float $x, float $y, string $ink): string
    {
        $l = '';
        for ($i = 0; $i < 12; $i++) {
            $a = $i * M_PI / 6;
            $l .= '<line x1="'.$this->n($x + cos($a) * 9).'" y1="'.$this->n($y + sin($a) * 9).'" x2="'.$this->n($x + cos($a) * 18).'" y2="'.$this->n($y + sin($a) * 18)."\" stroke=\"{$ink}\" stroke-width=\"3\" stroke-linecap=\"round\" stroke-opacity=\"".$this->n(.25 + $i / 16).'"/>';
        }

        return $l;
    }

    // Tiga kartu kaca beku dengan bola kaca dan garis teks kosong (tanpa kata; kutipan tetap dari prop quote).
    private function cards(array $p, string $u, int $W, int $H): array
    {
        $defs = "<linearGradient id=\"bg{$u}\" x1=\"0\" y1=\"0\" x2=\"1\" y2=\"1\"><stop offset=\"0\" stop-color=\"#f4f8ff\"/><stop offset=\"1\" stop-color=\"{$p['bot']}\"/></linearGradient>"
            .$this->grad('h'.$u, '#ffffff', $p['a'], .95, .2)
            ."<filter id=\"sh{$u}\" x=\"-20%\" y=\"-20%\" width=\"140%\" height=\"150%\"><feDropShadow dx=\"0\" dy=\"6\" stdDeviation=\"9\" flood-color=\"{$p['a']}\" flood-opacity=\".25\"/></filter>";
        $b = "<rect width=\"{$W}\" height=\"{$H}\" fill=\"url(#bg{$u})\"/>";
        for ($i = 0; $i < 3; $i++) {
            $x = 34 + $i * 182;
            $y = 130 + ($i === 1 ? -20 : ($i === 2 ? -60 : 0));
            $defs .= "<radialGradient id=\"o{$u}{$i}\" cx=\".35\" cy=\".3\"><stop offset=\"0\" stop-color=\"#fff\" stop-opacity=\".95\"/><stop offset=\".6\" stop-color=\"{$p['b']}\" stop-opacity=\".5\"/><stop offset=\"1\" stop-color=\"{$p['a']}\" stop-opacity=\".75\"/></radialGradient>";
            $b .= "<g filter=\"url(#sh{$u})\"><rect x=\"{$x}\" y=\"{$y}\" width=\"168\" height=\"250\" rx=\"18\" fill=\"{$p['b']}\" opacity=\".9\"/><rect x=\"{$x}\" y=\"{$y}\" width=\"168\" height=\"250\" rx=\"18\" fill=\"url(#h{$u})\" opacity=\".55\"/></g>"
                .'<circle cx="'.$this->n($x + 168 * (.35 + $i * .15)).'" cy="'.($y + 70).'" r="'.(46 + $i * 10)."\" fill=\"url(#o{$u}{$i})\" stroke=\"#fff\" stroke-opacity=\".8\" stroke-width=\"1.5\"/>"
                .'<rect x="'.($x + 16).'" y="'.($y + 140).'" width="70" height="8" rx="4" fill="'.$p['ink'].'" opacity=".3"/><rect x="'.($x + 16).'" y="'.($y + 160).'" width="110" height="12" rx="6" fill="'.$p['ink'].'" opacity=".55"/>';
        }

        return [$defs, $b];
    }

    // Posisi kutipan: bila tidak diberikan, dipilih dari seed (setelah gambar, jadi gambar tidak berubah oleh posisi).
    public function position(): string
    {
        if ($this->quotePosition) {
            return $this->quotePosition;
        }
        $this->svg();
        $r = $this->rnd();
        if ($this->focus !== null) {
            // Bentuk utama di satu sisi: kutipan ke sisi seberang; atas/bawah dari seed.
            $side = $this->focus < .5 ? 'right' : 'left';

            return ($r < .5 ? 'top-' : 'bottom-').$side;
        }

        return self::POSITIONS[(int) floor($r * count(self::POSITIONS))];
    }

    public function quoteStyle(): string
    {
        [$ff, $fw, $fs] = self::FONTS[$this->kind];

        return "font-family:{$ff};font-weight:{$fw};font-style:{$fs}";
    }

    public function render()
    {
        return <<<'BLADE'
            <div {{ $attributes->class('dl-wallpaper dl-wallpaper--'.$kind.' dl-wallpaper--'.$palette) }} {!! $dl() !!} style="aspect-ratio:{{ $ratio }};--wp-ink:{{ $ink() }}">
                {!! $svg() !!}
                @if ($quote !== null && $quote !== '')
                    <q class="dl-wallpaper__quote dl-wallpaper__quote--{{ $position() }}" style="{{ $quoteStyle() }}">{{ $quote }}</q>
                @endif
            </div>
            BLADE;
    }

    public function ink(): string
    {
        return self::PALETTES[$this->palette]['ink'];
    }
}
