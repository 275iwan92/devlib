{{-- REMARK
Tujuan: halaman form biasa (x-dl::form) yang dibingkai shell HP pada layar contoh Form: satu kolom di HP, tombol form pindah ke bilah aksi.
Sumber hulu: -
Input: -
Output: form master dengan data fiktif.
Dipakai oleh: phone-ref.screen-form (rute phone.ref.inner).
Status: aktif.
--}}
@extends('layout', ['title' => 'Item', 'bodyClass' => 'dl-on-phone'])
@section('body')
    <x-dl::form title="Item · A-001" subtitle="Notebook A5" panel="top" kind="master" status="active" :back="route('phone.ref', 'grid')" :new-url="route('phone.ref', 'form')" action="#">
        <x-dl::divider label="Item" />
        <x-dl::form.row :cols="1">
            <x-dl::field.text name="name" label="Item Name" value="Notebook A5" required maxlength="150" />
        </x-dl::form.row>
        <x-dl::form.row :cols="4">
            <x-dl::field.text name="code" label="Item Code" value="A-001" code :case-toggle="false" readonly />
            <x-dl::field.select name="category" label="Category" :options="['stationery' => 'Stationery', 'cleaning' => 'Cleaning', 'tools' => 'Tools']" value="stationery" required />
            <x-dl::field.select name="unit" label="Unit" :options="['pcs' => 'pcs', 'box' => 'box', 'pack' => 'pack']" value="pcs" required />
            <x-dl::field.text name="location" label="Storage Location" value="Shelf 1" />
        </x-dl::form.row>
        <x-dl::divider label="Reorder" />
        <x-dl::form.row :cols="4">
            <x-dl::field.decimal name="reorder_point" label="Reorder Point" :decimals="2" :value="10" required />
            <x-dl::field.decimal name="reorder_qty" label="Reorder Qty" :decimals="2" :value="20" required />
        </x-dl::form.row>
    </x-dl::form>
@endsection
