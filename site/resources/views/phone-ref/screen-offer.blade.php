{{-- REMARK
Tujuan: layar contoh popup tawaran Tampilan HP di login desktop sempit (x-dl::phone-offer dipaksa tampil) untuk halaman katalog p7-phone.
Sumber hulu: -
Input: $title.
Output: login desktop (x-dl::frame + login-box) dengan popup tawaran.
Dipakai oleh: rute phone.ref (iframe x-dl::device-frame).
Status: aktif.
--}}
@extends('layout', ['title' => 'Phone shell · '.$title])
@section('body')
    <x-dl::frame>
        <x-dl::login-box show="title, username, password, forgot, button" action="#" />
    </x-dl::frame>
    <x-dl::phone-offer action="#" force />
@endsection
