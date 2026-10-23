{{-- REMARK
Tujuan: layar contoh Login HP (x-dl::phone-login) untuk halaman katalog p7-phone.
Sumber hulu: -
Input: $title.
Output: halaman login satu kolom.
Dipakai oleh: rute phone.ref (iframe x-dl::device-frame).
Status: aktif.
--}}
@extends('layout', ['title' => 'Phone shell · '.$title, 'bodyClass' => 'dl-on-phone'])
@section('body')
    <x-dl::phone-login action="#" forgot-url="#" device-action="#" brand="Sample App" tagline="Inventory" />
@endsection
