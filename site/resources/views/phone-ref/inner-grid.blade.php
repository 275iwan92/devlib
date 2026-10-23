{{-- REMARK
Tujuan: halaman grid biasa (x-dl::grid dengan :phone-cards) yang dibingkai shell HP pada layar contoh Grid: daftar kartu di HP, toolbar pindah ke bilah aksi.
Sumber hulu: App\Support\PhoneRef::items (data fiktif).
Input: -
Output: grid dengan stats, filter, dan kartu HP.
Dipakai oleh: phone-ref.screen-grid (rute phone.ref.inner).
Status: aktif.
--}}
@extends('layout', ['title' => 'Items', 'bodyClass' => 'dl-on-phone'])
@section('body')
    <x-dl::grid id="ref-items" :rows="\App\Support\PhoneRef::items()" actions="master" new-record="+ New Item" :open-url="route('phone.ref', 'form')" search="Search code, name, location..." row-tone="tone"
        :columns="[
            ['key' => 'name', 'label' => 'Item', 'type' => 'identity', 'code' => 'code', 'box' => true],
            ['key' => 'category', 'label' => 'Category'],
            ['key' => 'unit', 'label' => 'Unit'],
            ['key' => 'location', 'label' => 'Location'],
            ['key' => 'stock', 'label' => 'Stock', 'type' => 'number', 'decimals' => 2],
            ['key' => 'rop', 'label' => 'Reorder Point', 'type' => 'number', 'decimals' => 2],
            ['key' => 'level', 'label' => 'Level', 'type' => 'badge', 'tones' => ['OK' => 'success', 'REORDER' => 'danger']],
        ]"
        :stats="[
            ['label' => 'All items'],
            ['label' => 'Reorder', 'where' => ['level' => 'REORDER'], 'tone' => 'danger'],
        ]"
        :filters="['category' => 'Category', 'unit' => 'Unit']"
        :phone-cards="['title' => 'name', 'sub' => 'code', 'badge' => 'level', 'tones' => ['OK' => 'success', 'REORDER' => 'danger'], 'meta' => ['stock', 'unit', 'location']]" />
@endsection
