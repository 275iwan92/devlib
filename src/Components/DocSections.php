<?php

/*
 * REMARK
 * Tujuan       : Menampilkan dokumen teks (mis. Job Description, SOP singkat) yang tersimpan sebagai SATU string polos secara terstruktur: judul bagian, paragraf, daftar bernomor, dan baris ringkas di atas. Teks yang tidak dikenali tampil apa adanya.
 * Sumber hulu  : Komponen generik DevLib; teks masuk lewat prop, tanpa tabel/model proyek.
 * Input        : text (string polos) atau sections (sudah terstruktur), inline (judul bagian yang tampil sebagai baris ringkas di atas).
 * Output       : Markup Blade inline (render() kelas ini) + CSS .dl-docsec.
 * Dipakai oleh : x-dl::greeting-card (board['jobdesc']['text']) dan layar proyek lewat x-dl::doc-sections.
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Aturan baca teks polos (parse): baris yang SELURUHNYA huruf kapital (3-60 karakter, tanpa titik) = judul bagian; baris "1. teks" atau "1) teks" = butir bernomor
// (baris lanjutan menempel pada butir sebelumnya); baris lain = paragraf. Tidak ada judul sama sekali = teks polos (tampil apa adanya, baris baru dipertahankan).
// Seluruh isi di-escape: teks pengguna tidak pernah menjadi HTML.
class DocSections extends DevlibComponent
{
    /** @var array<int, array{title: ?string, text: string, items: array<int, array{no: int, text: string}>}> */
    public array $parts;

    public bool $plain;

    public function __construct(public ?string $text = null, array $sections = [], public array $inline = [])
    {
        $this->parts = $sections ?: self::parse((string) $text);
        $this->plain = ! collect($this->parts)->contains(fn (array $p) => $p['title'] !== null);
    }

    // Judul bagian = baris kapital semua.
    private static function isHeading(string $line): bool
    {
        return mb_strlen($line) >= 3 && mb_strlen($line) <= 60 && preg_match('/^\p{Lu}[\p{Lu}0-9 &\/\-]+$/u', $line) === 1 && preg_match('/\p{Lu}{2}/u', $line) === 1;
    }

    /** @return array<int, array{title: ?string, text: string, items: array<int, array{no: int, text: string}>}> */
    public static function parse(string $text): array
    {
        $out = [];
        $cur = null;
        $flush = function () use (&$out, &$cur) {
            if ($cur && ($cur['text'] !== '' || $cur['items'])) {
                $out[] = $cur;
            }
            $cur = null;
        };
        foreach (preg_split('/\R/u', trim($text)) as $raw) {
            $line = trim($raw);
            if ($line === '') {
                continue;
            }
            if (self::isHeading($line)) {
                $flush();
                $cur = ['title' => $line, 'text' => '', 'items' => []];

                continue;
            }
            $cur ??= ['title' => null, 'text' => '', 'items' => []];
            if (preg_match('/^(\d{1,3})[.)]\s+(.+)$/u', $line, $m)) {
                $cur['items'][] = ['no' => (int) $m[1], 'text' => $m[2]];
            } elseif ($cur['items']) {
                $cur['items'][array_key_last($cur['items'])]['text'] .= ' '.$line;
            } else {
                $cur['text'] .= ($cur['text'] === '' ? '' : "\n").$line;
            }
        }
        $flush();

        return $out;
    }

    // Judul tampil sebagai "Tujuan Jabatan" (huruf pertama tiap kata kapital).
    public function heading(?string $title): string
    {
        return $title === null ? '' : mb_convert_case(mb_strtolower($title), MB_CASE_TITLE);
    }

    public function isInline(?string $title): bool
    {
        return $title !== null && in_array(mb_strtoupper($title), array_map('mb_strtoupper', $this->inline), true);
    }

    public function tag(): string
    {
        return 'x-dl::doc-sections sections='.count($this->parts);
    }

    public function source(): string
    {
        return 'teks polos yang dipecah per judul kapital dan butir bernomor';
    }

    public function render()
    {
        return <<<'BLADE'
            @php($meta = array_values(array_filter($parts, fn ($p) => $isInline($p['title']))))
            @php($body = array_values(array_filter($parts, fn ($p) => ! $isInline($p['title']))))
            <div {{ $attributes->class(['dl-docsec']) }} {!! $dl() !!}>
                @if ($plain)
                    <p class="dl-docsec__plain">{{ $text ?? collect($parts)->pluck('text')->implode("\n") }}</p>
                @else
                    @if ($meta)
                        <dl class="dl-docsec__meta">
                            @foreach ($meta as $m)
                                <div><dt>{{ $heading($m['title']) }}</dt><dd>{{ $m['text'] }}@foreach ($m['items'] as $it) <span>{{ $it['text'] }}</span>@endforeach</dd></div>
                            @endforeach
                        </dl>
                    @endif
                    @foreach ($body as $p)
                        <section class="dl-docsec__sec">
                            @if ($p['title'] !== null)<h4>{{ $heading($p['title']) }}</h4>@endif
                            @if ($p['text'] !== '')<p>{{ $p['text'] }}</p>@endif
                            @if ($p['items'])
                                <ol class="dl-docsec__list">@foreach ($p['items'] as $it)<li value="{{ $it['no'] }}">{{ $it['text'] }}</li>@endforeach</ol>
                            @endif
                        </section>
                    @endforeach
                @endif
            </div>
            BLADE;
    }
}
