{{-- Halaman peta jalan: komponen yang BELUM dibuat. Hanya tujuan dan kebutuhan; tanpa demo dan tanpa bilah QC. --}}
@extends('layout', ['title' => $item['label'], 'bodyClass' => 'dl-page-body'])
@section('body')
    <div class="dl-cat">
        <div class="dl-cat__nav">
            <x-dl::breadcrumb menu :items="[$item['group'] => null, $item['label'] => url()->current()]" />
        </div>
        <x-dl::alert tone="info" message="Planned: belum dibuat. Komponen ini masuk peta jalan DevLib; menu menampilkannya lebih dulu supaya terlihat apa yang akan ada." />
        <x-dl::card :title="$item['label']">
            <p>{{ $item['purpose'] }}</p>
            <p>Kelompok: <b>{{ $item['group'] }}</b></p>
            <p>Butuh HTTPS (secure context): <b>{{ $item['https'] ? 'ya' : 'tidak' }}</b></p>
        </x-dl::card>
    </div>
@endsection
