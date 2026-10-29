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
            <x-slot:mydata>
                <x-dl::card title="Main profile" icon="fa-user">
                    <x-dl::field.text name="full_name" label="Full name" value="Sample User" readonly />
                    <x-dl::field.text name="birth" label="Date of birth" value="01-Jan-1990" readonly />
                    <x-dl::field.text name="phone" label="Phone / WhatsApp" value="+62 800-0000-0000" readonly />
                </x-dl::card>
                <x-dl::card title="Address & contacts" icon="fa-location-dot">
                    <x-dl::field.text name="address" label="Residential address" value="Sample Street 1, Sample City" readonly />
                    <x-dl::field.text name="emergency" label="Emergency contact" value="Sample Contact · +62 800-0000-0001" readonly />
                </x-dl::card>
                <x-dl::side-screen.foot hint="HR verifies the correction before it takes effect">
                    <x-dl::button icon="fa-pen-to-square" label="Request correction" variant="secondary" />
                </x-dl::side-screen.foot>
            </x-slot:mydata>
            <x-slot:job><x-dl::card title="Job Description" icon="fa-clipboard-list">Sample section. Content of this part is the project's own.</x-dl::card></x-slot:job>
            <x-slot:family><x-dl::card title="Family & Status" icon="fa-people-roof">Sample section. Content of this part is the project's own.</x-dl::card></x-slot:family>
            <x-slot:leave>
                <x-dl::card title="New request" icon="fa-calendar-plus">
                    <x-dl::field.select name="type" label="Type" :options="['annual' => 'Annual leave', 'sick' => 'Sick leave', 'permit' => 'Permit']" value="annual" />
                    <x-dl::field.date name="from" label="From" />
                    <x-dl::field.date name="to" label="To" />
                    <x-dl::field.text name="reason" label="Reason" multiline />
                </x-dl::card>
                <x-dl::side-screen.foot hint="Your supervisor is notified">
                    <x-dl::button icon="fa-paper-plane" label="Submit request" />
                </x-dl::side-screen.foot>
            </x-slot:leave>
            <x-slot:swap><x-dl::card title="Shift Swap" icon="fa-rotate">Sample section. Content of this part is the project's own.</x-dl::card></x-slot:swap>
            <x-slot:security>
                <x-dl::card title="Change Password" icon="fa-key">
                    <x-dl::field.password name="old_password" label="Old password" required autocomplete="current-password" />
                    <x-dl::field.password name="new_password" label="New password" required autocomplete="new-password" hint="Minimum 8 characters." />
                    <x-dl::field.password name="new_password_confirmation" label="Confirm new password" required autocomplete="new-password" />
                    <x-dl::button icon="fa-key" label="Update Password" block />
                </x-dl::card>
                <x-dl::card title="Account Recovery" description="Used to prove it is you if you forget your password. No email needed." icon="fa-shield-halved">
                    <x-dl::field.date name="birth_date" label="Date of birth" required />
                    <x-dl::field.text name="mother_name" label="Mother's maiden name" required />
                    <x-dl::field.phone name="phone" label="Registered phone" kind="mobile" required />
                    <x-dl::field.select name="question_id" label="Security question" placeholder="Choose a question..." :options="['pet' => 'Name of your first pet', 'school' => 'Name of your primary school', 'city' => 'City where you were born']" required />
                    <x-dl::field.text name="answer" label="Your answer" required />
                    <x-dl::button icon="fa-floppy-disk" label="Save Recovery Data" block />
                </x-dl::card>
            </x-slot:security>
        </x-dl::side-screen.layout>
    </x-dl::phone-shell>
@endsection
