{{-- Daftar engine situs ini: diambil langsung dari EngineRegistry (kontrak Devlib\Engine), jadi tidak pernah basi.
     Tanda tangan method diambil dari kode; penjelasan (input, output, langkah) dari Described::details() tiap engine. --}}
@php
    $engines = app(\Devlib\Engine\EngineRegistry::class)->catalog(base_path());
@endphp
@if ($engines)
    <x-dl::engine-catalog :engines="$engines" source="Devlib\Engine\EngineRegistry::catalog()" />
@else
    <x-dl::empty-state icon="fa-gears" title="No engines registered" message="Register engines with Devlib\Engine\EngineRegistry::register() (see skills/devlib/engine.md)." />
@endif
