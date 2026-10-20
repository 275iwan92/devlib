{{-- Halaman Aturan: tampilan langsung skills/devlib/<doc>.md (App\Support\RulesDocs), bukan salinan. Tanpa demo, ikon </> dan bilah QC. --}}
@extends('layout', ['title' => $page['label'], 'dev' => true, 'bodyClass' => 'dl-page-body'])
@section('body')
    <div class="dl-cat">
        <div class="dl-cat__nav">
            <x-dl::breadcrumb menu :items="$page['crumbs'] ?? array_fill_keys($page['trail'] ?? [$page['part']], null) + [$page['label'] => url()->current()]" />
        </div>
        <div class="dl-prose" data-dl-rules="{{ $page['doc'] }}">{!! \App\Support\RulesDocs::html($page['doc']) !!}</div>
    </div>
@endsection
