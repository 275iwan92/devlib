<?php

/*
 * REMARK
 * Tujuan       : Latar (wallpaper) bergaya kaca bening: SVG dibuat di server dari bentuk + palet + seed, dengan satu kutipan di posisi acak. Tanpa JS, tanpa berkas gambar.
 * Sumber hulu  : Komponen generik DevLib; tidak membaca data proyek (kutipan diberikan pemanggil).
 * Input        : kind (ribbon, bubbles, folds, orbs), palette (sage bawaan, sky, rose, mint, amber, violet, night), quote, seed, quotePosition (kosong = acak dari seed), ratio.
 * Output       : <div class="dl-wallpaper"> berisi <svg> dan <q>; mengisi lebar induk, tinggi menurut rasio.
 * Dipakai oleh : layar proyek (latar kartu sapaan, login, dashboard: opt-in); halaman peragaan Backgrounds > Wallpaper.
 * Status       : Review (W1: empat bentuk awal).
 */

namespace Devlib\Components;

// Wallpaper: <x-dl::wallpaper kind="ribbon" palette="sage" quote="..." :seed="7" />. Seed sama = gambar sama.
class Wallpaper extends DevlibComponent
{
    public const KINDS = ['ribbon', 'bubbles', 'folds', 'orbs'];

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
    ];

    private static int $count = 0;

    private int $s;

    public string $uid;

    public function __construct(
        public string $kind = 'ribbon',
        public string $palette = 'sage',
        public ?string $quote = null,
        public ?int $seed = null,
        public ?string $quotePosition = null,
        public string $ratio = '5/4',
    ) {
        $this->kind = in_array($kind, self::KINDS, true) ? $kind : 'ribbon';
        $this->palette = isset(self::PALETTES[$palette]) ? $palette : 'sage';
        $this->ratio = preg_match('#^\d+(\.\d+)?/\d+(\.\d+)?$#', $ratio) ? $ratio : '5/4';
        $this->quotePosition = in_array($quotePosition, self::POSITIONS, true) ? $quotePosition : null;
        $this->seed = $seed ?? random_int(1, 2147483646);
        $this->s = max(1, $this->seed % 2147483647);
        $this->uid = 'dlwp'.(++self::$count);
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
        $this->s = max(1, $this->seed % 2147483647);
        $p = self::PALETTES[$this->palette];
        $u = $this->uid;
        $W = 600;
        $H = 480;
        [$defs, $body] = match ($this->kind) {
            'folds' => $this->folds($p, $u, $W, $H),
            'orbs' => $this->orbs($p, $u, $W, $H),
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

    private function ribbon(array $p, string $u, int $W, int $H): array
    {
        $cx = $W * (.35 + $this->rnd() * .3);
        $cy = $H * (.42 + $this->rnd() * .1);
        $defs = $this->grad('g'.$u, '#ffffff', $p['a'], .85, .35).$this->grad('h'.$u, '#ff9ec7', '#7fd0ff', .9, .9, 1, 0);
        $body = '';
        $n = 5;
        for ($i = 0; $i < $n; $i++) {
            $k = $i / $n;
            $y0 = $cy + ($this->rnd() - .5) * 90;
            $amp = 60 + $this->rnd() * 80;
            $x = fn (float $f) => $this->n($W * $f);
            $body .= '<path d="M-20 '.$this->n($y0 + 60).' C '.$x(.2).' '.$this->n($y0 - $amp).' '.$x(.4).' '.$this->n($y0 + $amp).' '.$x(.55).' '.$this->n($y0 - $amp * .3)
                .' S '.$x(.9).' '.$this->n($y0 + $amp * .8).' '.($W + 30).' '.$this->n($y0 - 20).' C '.$x(.8).' '.$this->n($y0 + $amp * 1.1).' '.$x(.5).' '.$this->n($y0 + $amp * .5).' '.$x(.35).' '.$this->n($y0 + $amp * 1.2)
                .' S -10 '.$this->n($y0 + 90).' -20 '.$this->n($y0 + 60)."Z\" fill=\"url(#g{$u})\" fill-opacity=\"".$this->n(.35 + $k * .3)."\" stroke=\"url(#h{$u})\" stroke-width=\"1.4\"/>";
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

    // Posisi kutipan: bila tidak diberikan, dipilih dari seed (setelah gambar, jadi gambar tidak berubah oleh posisi).
    public function position(): string
    {
        if ($this->quotePosition) {
            return $this->quotePosition;
        }
        $this->svg();

        return self::POSITIONS[(int) floor($this->rnd() * count(self::POSITIONS))];
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
