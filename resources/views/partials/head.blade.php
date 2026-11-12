{{-- REMARK
Tujuan: satu panggilan @devlib di <head> (seperti satu baris Bootstrap): ikon, CSS, theme-boot, JS.
Sumber hulu: config devlib.cdn (kosong = disajikan paket sendiri lewat /_devlib/*; berisi alamat = situs aset terpisah dari devlib:cdn-build), config devlib.icons.
Input: tidak ada. Output: tag link/script.
Dipakai oleh: layout aplikasi lewat @devlib.
Status: aktif.
--}}
@php($__dl = \Devlib\DevlibServiceProvider::headAssets())
@if ($__dl['icons'])<link rel="stylesheet" href="{{ $__dl['icons'] }}">@endif
<link rel="stylesheet" href="{{ $__dl['css'] }}">
<x-dl::theme-boot />
<script src="{{ $__dl['js'] }}" defer></script>
