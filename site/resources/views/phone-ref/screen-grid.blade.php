{{-- REMARK
Tujuan: layar contoh Grid HP (daftar kartu): shell membingkai halaman grid biasa; toolbar grid pindah ke bilah aksi (Cari, Baru, Saring, Ekspor) dan panel dari atas.
Sumber hulu: App\Support\PhoneRef; halaman bingkai phone.ref.inner (grid).
Input: $title, $active.
Output: x-dl::phone-shell dengan :frame.
Dipakai oleh: rute phone.ref (iframe x-dl::device-frame).
Status: aktif.
--}}
@extends('layout', ['title' => 'Phone shell · '.$title, 'bodyClass' => 'dl-on-phone'])
@section('body')
    <x-dl::phone-shell :title="$title" :active="$active" :tabs="\App\Support\PhoneRef::tabs()" :modules="\App\Support\PhoneRef::modules()" :user="\App\Support\PhoneRef::user()"
        logout-action="#" device-action="#" :frame="route('phone.ref.inner', 'grid')" />
@endsection
