<?php

namespace Tests\Support;

use App\Support\DemoPrintDocs;
use Carbon\Carbon;
use Devlib\PrintDoc\Document;
use Illuminate\Support\Facades\Blade;

// Kasus kop yang dirender untuk membuktikan keluaran lama tidak berubah (tanpa kunci style / legal_name).
trait RendersKopCases
{
    private const OLD_PAGES = ['p5-print', 'p5-print-doc', 'p5-report'];

    private const DOC_COMPANIES = [
        'plain' => ['name' => 'CV Contoh', 'initials' => 'CC', 'lines' => ['Jl. Mawar 1', 'Telp 021-555'], 'npwp' => '01.234.567.8-901.000'],
        'logo' => ['name' => 'CV Contoh', 'initials' => 'CC', 'lines' => ['Jl. Mawar 1'], 'npwp' => null, 'logo' => '/kop-logo-contoh.svg'],
        'bare' => ['name' => 'CV Contoh', 'initials' => 'CC'],
    ];

    private const OUT_COMPANIES = [
        'plain' => ['name' => 'CV Contoh', 'tagline' => 'Cetak & Kemasan', 'address' => 'Jl. Mawar 1', 'contact' => 'Telp 021-555', 'npwp' => '01.234', 'initials' => 'CC'],
        'logo' => ['name' => 'CV Contoh', 'tagline' => 'Cetak', 'address' => 'Jl. Mawar 1', 'logo' => '/kop-logo-contoh.svg', 'logoBw' => '/kop-logo-contoh-bw.svg'],
        'bare' => ['name' => 'CV Contoh'],
    ];

    /** @return array<string, string> nama kasus => HTML ternormalisasi */
    protected function kopCases(): array
    {
        Carbon::setTestNow('2026-10-22 10:00:00');
        $out = [];
        foreach (self::OLD_PAGES as $slug) {
            $res = $this->get('/lib/'.$slug)->assertOk();
            $out['page-'.$slug] = $this->kopHeaders($this->kopNormalize($res->getContent()));
        }
        foreach (self::DOC_COMPANIES as $k => $c) {
            $doc = DemoPrintDocs::deliveryNote();
            Document::companyUsing(fn () => $c);
            $out['doc-'.$k] = $this->kopNormalize(Blade::render('<x-dl::print-doc :doc="$doc" />', ['doc' => $doc]));
            $out['sheet-'.$k] = $this->kopNormalize(Blade::render('<x-dl::print-sheet title="Form" code="F-01" :sections="[[\'title\' => \'Bagian\', \'blocks\' => [[\'type\' => \'text\', \'text\' => \'Isi\']]]]" />'));
        }
        foreach (self::OUT_COMPANIES as $k => $c) {
            $ppn = ['npwp' => '02.345'] + $c;
            $out['out-'.$k] = $this->kopNormalize(Blade::render('<x-dl::print-out title="Nota" :company="$c" :company-ppn="$p" :columns="[[\'key\' => \'name\', \'label\' => \'Nama\']]" :rows="[[\'name\' => \'A\']]" />', ['c' => $c, 'p' => $ppn]));
            $out['out-nonppn-'.$k] = $this->kopNormalize(Blade::render('<x-dl::print-out title="Nota" :company="$c" :columns="[[\'key\' => \'name\', \'label\' => \'Nama\']]" :rows="[[\'name\' => \'A\']]" />', ['c' => $c]));
        }
        Document::companyUsing(null);

        return $out;
    }

    // Halaman katalog memuat teks yang bergantung pada keadaan global tes lain (kamus bahasa); yang dibandingkan hanya blok perusahaan pada kop.
    protected function kopHeaders(string $html): string
    {
        preg_match_all('#<div class="dl-(?:print|pdoc)__co[^"]*"(?: data-p-kop="\d")?>.*?</div></div>#s', $html, $m);

        return implode(chr(10), $m[0]);
    }

    // Token CSRF berubah tiap permintaan; spasi antar-tag tidak berarti di HTML.
    protected function kopNormalize(string $html): string
    {
        $html = preg_replace('/(name="_token" value=")[^"]*/', '$1X', $html);
        $html = preg_replace('/(name="csrf-token" content=")[^"]*/', '$1X', $html);
        $html = preg_replace('/(DL_CSRF = ")[^"]*/', '$1X', $html);
        $html = preg_replace('/(data-csrf=")[^"]*/', '$1X', $html);
        $html = preg_replace('/docshare-[0-9a-f]{8}/', 'docshare-R', $html);
        $html = preg_replace('/(\.php:\d+)\$\d+/', '$1$N', $html); // akhiran nomor kelas anonim bergantung urutan tes
        $html = preg_replace('#<small>\d+ baris</small>#', '<small>N baris</small>', $html);
        $html = preg_replace('#<pre class="dl-code__pre.*?</pre>#s', '<pre/>', $html); // panel kode sumber menampilkan isi view, bukan cetakan
        $html = preg_replace('/(\.(?:css|js)\?v=)\d+/', '$1V', $html);
        $html = preg_replace('/(dlf-[a-z0-9_-]+?)-[A-Za-z0-9]{5}(?=["-])/', '$1-R', $html);

        return trim(preg_replace('/>\s+</', '><', preg_replace('/\s+/', ' ', $html)));
    }
}
