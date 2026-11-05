{{-- REMARK
Tujuan: layar contoh form Account (Security admin): satu akun dengan data dasar, tombol Reset Password / Reset to Default / View Password / Deactivate, dan kartu login yang tampil sekali sesudah reset.
Sumber hulu: data fiktif di view ini; tidak ada yang tersimpan, tombol hanya peraga.
Input: tidak ada.
Output: x-dl::form (kind master) + x-dl::modal berisi x-dl::login-card.
Dipakai oleh: rute account.ref.
Status: aktif (pratinjau, belum disetujui Jo).
--}}
@extends('layout', ['title' => 'Account'])
@section('body')
    <div class="dl-demo">
        <x-dl::form id="security-account-form" title="Sample User" subtitle="sample.user" panel="top" kind="master" status="active" :can-input="true"
            :hide="['deactivate', 'activate', 'new']" action="#" :back="route('home')" :tabs="['general' => 'Account']"
            created-at="2026-01-05 09:12:00" updated-at="2026-10-20 14:03:00">
            <x-slot:special>
                <x-dl::button variant="secondary" icon="fa-key" label="Reset Password" type="button" data-dl-open="acct-card" />
                <x-dl::button variant="secondary" icon="fa-rotate-left" label="Reset to Default" type="button" />
                <x-dl::button variant="secondary" icon="fa-eye" label="View Password" type="button" />
                <x-dl::button variant="danger" icon="fa-user-lock" label="Deactivate Account" type="button" />
            </x-slot:special>
            <x-slot:strip>
                <x-dl::strip-kv kind="doc" label="Login" value="sample.user" />
                <x-dl::strip-kv kind="grand" label="Status" value="Active" />
                <x-dl::strip-kv kind="grand" label="Group" value="Staff" />
                <x-dl::strip-kv kind="grand" label="Password" value="OK" />
            </x-slot:strip>
            <x-slot:general>
                <x-dl::card title="Account">
                    <x-dl::form.row :cols="3">
                        <x-dl::field.display label="Login" value="sample.user" />
                        <x-dl::field.text name="name" label="Name" required value="Sample User" />
                        <x-dl::field.text name="email" label="E-mail" :case-toggle="false" value="sample.user@example.com" />
                    </x-dl::form.row>
                    <x-dl::form.row :cols="3">
                        <x-dl::field.select name="group_id" label="Group" required :options="['1' => 'Administrator', '2' => 'Staff', '3' => 'Viewer']" value="2" placeholder="— select group —" />
                        <x-dl::field.display label="E-mail verified" value="Yes" />
                        <x-dl::field.display label="Last password change" value="20-Oct-2026 14:03" />
                    </x-dl::form.row>
                </x-dl::card>
            </x-slot:general>
        </x-dl::form>
    </div>
    {{-- Kartu login: tampil SEKALI sesudah reset; password tidak disimpan dan tidak dibaca ulang. --}}
    <x-dl::modal id="acct-card" title="New login" icon="fa-key">
        <x-dl::login-card name="Sample User" code="U-0001" login="sample.user" password="Xxxx-0000" note="Give this card to the user by hand. The password is shown only once; the user must change it at first sign-in.">
            <x-dl::button type="button" variant="secondary" label="Close" data-dl-close-modal />
        </x-dl::login-card>
    </x-dl::modal>
@endsection
