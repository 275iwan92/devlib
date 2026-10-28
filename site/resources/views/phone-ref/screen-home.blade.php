{{-- REMARK
Tujuan: layar contoh Beranda HP (layar biasa tanpa aksi: keadaan A, bilah lima menu, daftar modul) untuk halaman katalog p7-phone.
Sumber hulu: App\Support\PhoneRef (data fiktif).
Input: $title, $active.
Output: x-dl::phone-shell penuh layar.
Dipakai oleh: rute phone.ref (iframe x-dl::device-frame).
Status: aktif.
--}}
@extends('layout', ['title' => 'Phone shell · '.$title, 'bodyClass' => 'dl-on-phone'])
@section('body')
    <x-dl::phone-shell :title="$title" :active="$active" :tabs="\App\Support\PhoneRef::tabs()" :modules="\App\Support\PhoneRef::modules()" :user="\App\Support\PhoneRef::user()" logout-action="#" device-action="#" install-url="/pwa/">
        <x-dl::phone-tiles>
            <x-dl::phone-tile label="Active items" :value="27" icon="fa-solid fa-boxes-stacked" :url="\App\Support\PhoneRef::url('grid')" />
            <x-dl::phone-tile label="Low stock" :value="4" icon="fa-solid fa-triangle-exclamation" tone="danger" :url="\App\Support\PhoneRef::url('grid')" />
            <x-dl::phone-tile label="Near expiry" :value="2" icon="fa-solid fa-hourglass-end" tone="warn" :url="\App\Support\PhoneRef::url('report')" />
            <x-dl::phone-tile label="Open counts" :value="1" icon="fa-solid fa-clipboard-check" tone="info" :url="\App\Support\PhoneRef::url('form')" />
        </x-dl::phone-tiles>
        <x-dl::phone-link label="Edit an item" icon="fa-solid fa-pen-to-square" :url="\App\Support\PhoneRef::url('form')" />
        <x-dl::phone-link label="Stock summary report" icon="fa-solid fa-table-list" :url="\App\Support\PhoneRef::url('report')" />
    </x-dl::phone-shell>
@endsection
