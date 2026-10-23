{{-- REMARK
Tujuan: halaman report biasa (x-dl::report) yang dibingkai shell HP pada layar contoh Report: bilah aksi Cetak / Kertas / Grup / Hemat tinta; lembar diperkecil agar pas lebar layar.
Sumber hulu: App\Support\PhoneRef::items (data fiktif).
Input: -
Output: report dengan pengelompokan per kategori.
Dipakai oleh: phone-ref.screen-report (rute phone.ref.inner).
Status: aktif.
--}}
@extends('layout', ['title' => 'Stock Summary', 'bodyClass' => 'dl-on-phone'])
@section('body')
    <x-dl::report title="Stock Summary" subtitle="Stock position per item" number="RPT/00001" printed-by="Sample User" paper="a4"
        :company="['name' => 'Sample Company', 'style' => 'name', 'address' => 'Sample Street 1, Sample City (example)']"
        group-by="category" :groups="['category' => 'Per Category', 'unit' => 'Per Unit']"
        :columns="[
            ['key' => 'code', 'label' => 'Code'],
            ['key' => 'name', 'label' => 'Item'],
            ['key' => 'category', 'label' => 'Category'],
            ['key' => 'unit', 'label' => 'Unit'],
            ['key' => 'stock', 'label' => 'Stock', 'type' => 'number', 'sum' => true, 'bold' => true],
            ['key' => 'rop', 'label' => 'Reorder Point', 'type' => 'number'],
        ]"
        :rows="\App\Support\PhoneRef::items()" />
@endsection
