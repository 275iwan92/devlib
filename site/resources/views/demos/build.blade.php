{{-- Papan Build Progress. Isinya disusun dari katalog DevLib sendiri: `php artisan build:catalog` (resources/data/build.json). --}}
@php($build = \App\Support\Catalog::build())
@if ($build)
    <x-dl::build-board :build="$build" :library="\App\Support\Catalog::stats()" :engines-url="route('lib', ['slug' => 'p9-engines'])" source="resources/data/build.json" />
@else
    <x-dl::empty-state icon="fa-chart-simple" title="No build catalog yet" message="Run php artisan build:catalog to create resources/data/build.json." />
@endif
