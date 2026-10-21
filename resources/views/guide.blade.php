{{-- REMARK
Tujuan       : Halaman Technical Guide (TG) semua object DevLib: indeks per kelompok + satu bagian per object (kerangka otomatis + rincian resources/guide/*.md).
Sumber hulu  : Devlib\Guide (Manifest + resources/guide).
Input        : $families (Guide::byFamily()).
Output       : Dokumen HTML lengkap (English), dibuka dari menu Help sebagai tab Technical Guide.
Dipakai oleh : route devlib.guide (DevlibServiceProvider); URL-nya dibaca JS link judul (window.DL_GUIDE).
Status       : Aktif.
--}}<!DOCTYPE html>
<html lang="en" data-dl-tz="{{ config('devlib.timezone') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technical Guide — {{ config('devlib.brand.name', 'DevLib') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ \Devlib\DevlibServiceProvider::asset('devlib.css') }}">
    <x-dl::theme-boot />
    <script src="{{ \Devlib\DevlibServiceProvider::asset('devlib.js') }}" defer></script>
</head>
<body class="dl-body dl-guide">
    <nav class="dl-guide__nav" aria-label="Technical Guide index">
        <h1>Technical Guide</h1>
        <input type="search" class="dl-guide__find" placeholder="Find an object…" aria-label="Find an object" data-guide-find>
        @foreach ($families as $family => $entries)
            <details open>
                <summary>{{ \Devlib\Guide::FAMILIES[$family] }} <small>{{ count($entries) }}</small></summary>
                <ul>
                    @foreach ($entries as $e)
                        <li data-guide-item><a href="#{{ $e['anchor'] }}"><code>{{ $e['tag'] }}</code>@if ($e['detail'] === null)<i class="fa-regular fa-clock" title="Detailed description pending"></i>@endif</a></li>
                    @endforeach
                </ul>
            </details>
        @endforeach
    </nav>
    <main class="dl-guide__main">
        <header class="dl-guide__intro">
            <h1>DevLib Technical Guide</h1>
            <p>Every DevLib object (<code>&lt;x-dl::…&gt;</code>) described for developers: purpose, signature, behaviour, data attributes and events, rules and examples. The title of a form, grid or card links to its entry here.</p>
        </header>
        @foreach ($families as $family => $entries)
            <h2 class="dl-guide__family">{{ \Devlib\Guide::FAMILIES[$family] }}</h2>
            @foreach ($entries as $e)
                <section class="dl-guide__obj dl-prose" id="{{ $e['anchor'] }}" data-guide-obj>
                    <h3><code>&lt;{{ $e['tag'] }}&gt;</code></h3>
                    <p class="dl-guide__purpose">{{ $e['purpose'] !== '' ? $e['purpose'] : '(no description)' }}</p>
                    <dl class="dl-guide__meta">
                        <dt>Class</dt><dd><code>{{ $e['class'] }}</code></dd>
                        <dt>Source</dt><dd><code>{{ $e['file'] }}</code>@if ($e['view']) · <code>{{ $e['view'] }}</code>@endif</dd>
                        @if ($e['js'])<dt>Behaviour (JS)</dt><dd><code>{{ $e['js'] }}</code></dd>@endif
                    </dl>
                    <h4>Signature</h4>
                    @if ($e['props'])
                        <table>
                            <thead><tr><th>Prop</th><th>Type</th><th>Default</th></tr></thead>
                            <tbody>
                                @foreach ($e['props'] as $p)
                                    <tr><td><code>{{ \Illuminate\Support\Str::kebab($p['name']) }}</code></td><td><code>{{ $p['type'] }}</code></td><td><code>{{ $p['default'] }}</code></td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p>No props.</p>
                    @endif
                    @if ($e['detail'] !== null)
                        <div class="dl-guide__detail">{!! str_replace(['<h2', '</h2>'], ['<h4', '</h4>'], \Devlib\Guide::html($e['detail'])) !!}</div>
                    @else
                        <p class="dl-guide__pending"><i class="fa-regular fa-clock"></i> Detailed description pending.</p>
                    @endif
                </section>
            @endforeach
        @endforeach
    </main>
    <script>
        document.querySelector('[data-guide-find]')?.addEventListener('input', e => {
            const q = e.target.value.trim().toLowerCase();
            document.querySelectorAll('[data-guide-item]').forEach(li => { li.hidden = q !== '' && !li.textContent.toLowerCase().includes(q); });
        });
    </script>
</body>
</html>
