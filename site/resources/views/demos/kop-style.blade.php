{{-- Gaya kop perusahaan pada x-dl::print-doc: kunci company `style` (logo_name_address | logo_name | name) dan `legal_name` (nama PT).
     Sumber kop satu: Document::companyUsing(). Demo memasangnya per dokumen; nilai contoh fiktif. --}}
@php
    $co = ['name' => 'Toko Maju Sentosa', 'initials' => 'MS', 'legal_name' => 'PT Maju Sentosa Abadi', 'npwp' => '00.000.000.0-000.000 (contoh)',
        'lines' => ['Jl. Contoh Industri No. 42, Surabaya (contoh)', 'Telp. (031) 000-0000 · contoh@contoh.test'], 'logo' => '/kop-logo-contoh.svg'];
    $cases = [
        ['1. style = logo_name_address (logo + nama + nama PT + alamat)', $co + ['style' => 'logo_name_address']],
        ['2. style = logo_name (logo + nama + nama PT, tanpa alamat)', $co + ['style' => 'logo_name']],
        ['3. style = name (nama + nama PT, tanpa logo dan alamat)', $co + ['style' => 'name']],
        ['4. legal_name sama dengan name (beda huruf besar/kecil dan spasi saja): baris nama PT tidak muncul dua kali', ['legal_name' => 'TOKO  maju sentosa', 'style' => 'logo_name_address'] + $co],
        ['5. style memakai logo tetapi logo kosong: jatuh ke nama saja (tanpa kotak inisial)', ['logo' => null, 'style' => 'logo_name_address'] + $co],
    ];
@endphp
@foreach ($cases as [$caption, $company])
    <p class="dl-hint"><b>{{ $caption }}</b></p>
    @php($doc = \App\Support\DemoPrintDocs::deliveryNote())
    @php(\Devlib\PrintDoc\Document::companyUsing(fn () => $company))
    <x-dl::print-doc :doc="$doc" />
@endforeach
<p class="dl-hint"><b>6. A4 (lebih dari 5 baris), style = logo_name_address: kop dengan alamat tidak mendorong isi lembar</b></p>
@php($doc = \App\Support\DemoPrintDocs::purchaseOrder())
@php(\Devlib\PrintDoc\Document::companyUsing(fn () => $co + ['style' => 'logo_name_address']))
<x-dl::print-doc :doc="$doc" />
@php(\App\Support\DemoPrintDocs::boot())
