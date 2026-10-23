{{-- REMARK
Tujuan: halaman pratinjau "Lihat sebagai": halaman katalog yang sama di x-dl::device-frame (iframe) berukuran Android phone / iPhone / Tablet / Laptop / Desktop.
Sumber hulu: rute lib.as (slug, ?as=); Devlib\Components\DeviceFrame::PRESETS; Devlib\ViewOverride (?dl-view / ?dl-os di iframe).
Input: $page (Catalog::page), $slug, $preset.
Output: bingkai pratinjau satu layar.
Dipakai oleh: x-dl::view-as di lib.blade.php dan lib-cat.blade.php.
Status: aktif.
--}}
@extends('layout', ['title' => $page['label'].' · '.\Devlib\Lang::text('View as')])
@section('body')
    <x-dl::device-frame :src="route('lib', $slug)" :preset="$preset" :back="route('lib', $slug)" />
@endsection
