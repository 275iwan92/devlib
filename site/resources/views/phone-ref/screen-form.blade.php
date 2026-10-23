{{-- REMARK
Tujuan: layar contoh Form HP: shell membingkai halaman form biasa; tombol form pindah ke bilah aksi (Simpan, Urungkan, Kembali, Baru).
Sumber hulu: App\Support\PhoneRef; halaman bingkai phone.ref.inner (form).
Input: $title, $active.
Output: x-dl::phone-shell dengan :frame.
Dipakai oleh: rute phone.ref (iframe x-dl::device-frame).
Status: aktif.
--}}
@extends('layout', ['title' => 'Phone shell · '.$title, 'bodyClass' => 'dl-on-phone'])
@section('body')
    <x-dl::phone-shell :title="$title" :back="\App\Support\PhoneRef::url('grid')" :active="$active" :tabs="\App\Support\PhoneRef::tabs()" :modules="\App\Support\PhoneRef::modules()" :user="\App\Support\PhoneRef::user()"
        logout-action="#" device-action="#" :frame="route('phone.ref.inner', 'form')" />
@endsection
