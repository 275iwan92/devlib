{{-- Contoh x-dl::feed: umpan narasi gaya blog (markdown dibaca penuh, yang disematkan di atas). --}}
{{-- Larik dibuat di blok php: tanda kutip ganda di dalam atribut :posts memutus parser tag Blade (tag tertinggal mentah). --}}
@php
    $posts = [
        ['title' => 'Why the old screen documents were removed', 'meta' => 'Agent Iwan · Report · 07-Oct-2026', 'badge' => 'Unread', 'tone' => 'warn', 'pinned' => true,
            'body' => "The per-screen documents described screens that were replaced.

- Replaced by v2 screens
- Still in git history

| File | Reason |
|---|---|
| screen_a.md | Replaced |"],
        ['title' => 'Second story', 'meta' => 'Agent Jo · Note · 06-Oct-2026', 'body' => 'A **short** note.'],
    ];
@endphp
<div class="dl-demo">
    <x-dl::feed :posts="$posts" />
</div>
