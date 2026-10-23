{{-- REMARK
Tujuan: layar contoh Report HP: shell membingkai halaman report biasa; bilah aksi berisi Cetak, Kertas, Grup, Hemat tinta; lembar cetak diperkecil agar pas lebar layar.
Sumber hulu: App\Support\PhoneRef; halaman bingkai phone.ref.inner (report).
Input: $title, $active.
Output: x-dl::phone-shell dengan :frame.
Dipakai oleh: rute phone.ref (iframe x-dl::device-frame).
Status: aktif.
--}}
@extends('layout', ['title' => 'Phone shell · '.$title, 'bodyClass' => 'dl-on-phone'])
@section('body')
    <x-dl::phone-shell :title="$title" :back="\App\Support\PhoneRef::url('home')" :active="$active" :tabs="\App\Support\PhoneRef::tabs()" :modules="\App\Support\PhoneRef::modules()" :user="\App\Support\PhoneRef::user()"
        logout-action="#" device-action="#" :frame="route('phone.ref.inner', 'report')" />
@endsection
