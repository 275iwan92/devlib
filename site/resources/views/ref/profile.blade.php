{{-- REMARK
Tujuan: layar contoh Edit profile di laptop/desktop: rel bagian di kiri, satu bagian di kanan (side-screen.layout); isi bagian sama dengan versi HP (partial ref/profile/*).
Sumber hulu: partial ref/profile/{mydata,leave,security}; bagian lain contoh kosong milik proyek.
Input: tidak ada.
Output: halaman penuh berisi x-dl::side-screen.layout (mode desktop).
Dipakai oleh: rute profile.ref.
Status: aktif.
--}}
@extends('layout', ['title' => 'Edit profile'])
@section('body')
    <div style="height:100vh">
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
                'security' => 'Your password and the data used to recover your account.',
            ]">
            <x-slot:mydata>@include('ref.profile.mydata')</x-slot:mydata>
            <x-slot:job><x-dl::card title="Job Description" icon="fa-clipboard-list">Sample section. Content of this part is the project's own.</x-dl::card></x-slot:job>
            <x-slot:family><x-dl::card title="Family & Status" icon="fa-people-roof">Sample section. Content of this part is the project's own.</x-dl::card></x-slot:family>
            <x-slot:leave>@include('ref.profile.leave')</x-slot:leave>
            <x-slot:swap><x-dl::card title="Shift Swap" icon="fa-rotate">Sample section. Content of this part is the project's own.</x-dl::card></x-slot:swap>
            <x-slot:security>@include('ref.profile.security')</x-slot:security>
        </x-dl::side-screen.layout>
    </div>
@endsection
