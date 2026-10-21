@php
    // Master Form (user 2026-10-21): data master, BUKAN transaksional. Hanya Active / Inactive: tidak ada Draft, Confirm, Cancel, Print.
    // Delete hanya administrator dan hanya bila data belum dipakai transaksi (:deletable); selain itu cukup Deactivate.
    $flow ??= null;
    $id = $flow['id'] ?? 'new';
    $status = $id === 'new' ? 'new' : request('status', $flow['status'] ?? 'active');
    $isNew = $status === 'new';
    $r = $flow['row'] ?? null;
    // Demo tampil sebagai Administrator (tanpa simulasi login, Jo 2026-10-21): Delete muncul bila data belum dipakai transaksi (id kelipatan 3 = dipakai).
    $used = $r && (int) $r['id'] % 3 === 0;
    $src = $r ? ['code' => $r['code'], 'full_name' => $r['name'], 'position' => $r['position'], 'join_date' => $r['join_date'], 'phone' => $r['phone'] ?? null, 'company' => 'Main Company', 'work_status' => $r['status']] : [];
    // Record baru: Active, isian bawaan terisi (tanggal hari ini, perusahaan), hanya nama yang harus diisi.
    $defaults = ['company' => 'Main Company', 'join_date' => now()->format('Y-m-d'), 'work_status' => 'KONTRAK'];
    $v = fn ($k) => $isNew ? ($defaults[$k] ?? null) : ($src[$k] ?? null);
@endphp
<x-dl::form :title="$isNew ? 'Employee · New' : 'Employee · '.$src['full_name']" :subtitle="$isNew ? null : $src['code']"
    panel="top" kind="master" :status="$status" :strict="true" :deletable="! $isNew && ! $used"
    :action="url()->current()" method="POST" :back="$flow['back']" :hidden="['status' => $status, 'from' => $flow['back']]"
    :tabs="['general' => 'General', 'personal' => 'Personal']"
    :created-by="$isNew ? null : 'registrar'" :created-at="$isNew ? null : '2026-01-02 08:10:00'" :updated-by="$isNew ? null : 'registrar'" :updated-at="$isNew ? null : '2026-09-28 14:22:00'">
    <x-slot:general>
        <x-dl::form.row :cols="3">
            <x-dl::field.text name="code" label="Employee code" :value="$v('code')" placeholder="(automatic)" code readonly :case-toggle="false" hint="Made on the first Save." />
            <x-dl::field.text name="full_name" label="Full name" :value="$v('full_name')" required />
            <x-dl::field.select name="company" label="Company" :options="['Main Company' => 'Main Company']" :value="$v('company')" required />
        </x-dl::form.row>
        <x-dl::form.row :cols="3">
            <x-dl::field.text name="position" label="Position" :value="$v('position')" />
            <x-dl::field.date name="join_date" label="Join date" :value="$v('join_date')" required />
            <x-dl::field.select name="work_status" label="Work status" :options="['TETAP' => 'Permanent', 'KONTRAK' => 'Contract', 'HARIAN' => 'Daily']" :value="$v('work_status')" required />
        </x-dl::form.row>
        <x-dl::form.row :cols="3">
            <x-dl::field.phone name="phone" label="Mobile" :value="$v('phone')" />
        </x-dl::form.row>
    </x-slot:general>
    <x-slot:personal>
        <x-dl::form.row :cols="3">
            <x-dl::field.radio name="gender" label="Gender" :options="['M' => 'Male', 'F' => 'Female']" :value="$v('gender')" />
            <x-dl::field.text name="birth_place" label="Place of birth" :value="$v('birth_place')" />
            <x-dl::field.date name="birth_date" label="Date of birth" :value="$v('birth_date')" placeholder="—" />
        </x-dl::form.row>
    </x-slot:personal>
</x-dl::form>
<p class="dl-note"><b>Master Form</b>: hanya Active / Inactive. Baru (Active, belum tersimpan): Save · Undo. Tersimpan: terkunci, Edit · Deactivate (+ Delete untuk administrator bila belum dipakai transaksi). Inactive: Activate saja; data tidak muncul di pilihan tetapi tetap tersimpan. Tidak ada Confirm, Cancel, Print.</p>
