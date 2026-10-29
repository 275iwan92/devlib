{{-- REMARK
Tujuan: layar contoh Edit profile di HP: daftar bagian (Data pribadi, Pengajuan, Lainnya) lalu satu bagian dengan tombol kembali; data baca-saja dengan "Request correction" dan form pengajuan cuti. Data fiktif.
Sumber hulu: App\Support\PhoneRef (menu); isi bagian ditulis di sini.
Input: $title, $active.
Output: x-dl::phone-shell berisi x-dl::side-screen.layout (mode HP: daftar lalu detail).
Dipakai oleh: rute phone.ref (iframe x-dl::device-frame).
Status: aktif.
--}}
@extends('layout', ['title' => 'Phone shell · '.$title, 'bodyClass' => 'dl-on-phone'])
@section('body')
    <x-dl::phone-shell :title="$title" :back="\App\Support\PhoneRef::url('home')" :active="$active" :tabs="\App\Support\PhoneRef::tabs()" :modules="\App\Support\PhoneRef::modules()" :user="\App\Support\PhoneRef::user()" logout-action="#" device-action="#">
        <x-dl::phone-tiles>
            <x-dl::phone-tile label="Leave left" value="9 days" icon="fa-solid fa-umbrella-beach" url="#" />
            <x-dl::phone-tile label="Pending requests" :value="2" icon="fa-solid fa-hourglass-half" tone="warn" url="#" />
        </x-dl::phone-tiles>
        <x-dl::side-screen.layout
            :sections="[
                'mydata' => ['My Data', 'fa-user'],
                'job' => ['Job Description', 'fa-clipboard-list'],
                'family' => ['Family & Status', 'fa-people-roof'],
                'leave' => ['Leave / Sick / Permit', 'fa-calendar-days', 1],
                'swap' => ['Shift Swap', 'fa-rotate'],
                'security' => ['Security', 'fa-shield-halved'],
            ]"
            :groups="['mydata' => 'Personal data', 'leave' => 'Requests', 'security' => 'Others']"
            :hints="[
                'mydata' => 'Read-only. If an entry is wrong, send a correction; it takes effect after HR verifies it.',
                'leave' => 'Pick the type and the dates; your supervisor approves it.',
            ]">
            <x-slot:mydata>@include('ref.profile.mydata')</x-slot:mydata>
            <x-slot:job><x-dl::card title="Job Description" icon="fa-clipboard-list">Sample section. Content of this part is the project's own.</x-dl::card></x-slot:job>
            <x-slot:family><x-dl::card title="Family & Status" icon="fa-people-roof">Sample section. Content of this part is the project's own.</x-dl::card></x-slot:family>
            <x-slot:leave>@include('ref.profile.leave')</x-slot:leave>
            <x-slot:swap><x-dl::card title="Shift Swap" icon="fa-rotate">Sample section. Content of this part is the project's own.</x-dl::card></x-slot:swap>
            <x-slot:security>@include('ref.profile.security')</x-slot:security>
        </x-dl::side-screen.layout>
    </x-dl::phone-shell>
@endsection
