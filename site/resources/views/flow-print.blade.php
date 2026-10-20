@extends('layout', ['title' => $doc->title.' · '.$doc->data['number'], 'dev' => false, 'bodyClass' => 'dl-page-body'])
@section('body')
    {{-- Cetakan form alur: membawa status dokumen (dan approval); dibuka dari tombol Print di tab baru. --}}
    <x-dl::action-bar>
        <x-dl::doc-share target="#flow-print-doc" type="demo-flow-doc" :number="$doc->data['number']" :title="$doc->title" />
    </x-dl::action-bar>
    <x-dl::print-doc :doc="$doc" id="flow-print-doc" />
@endsection
