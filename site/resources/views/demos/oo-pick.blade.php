{{-- Pemilih object halaman p5-override: ?object=grid|form|kanban (bawaan Kanban); tautan bisa dibagikan. --}}
<p class="dl-note">This page tells the story of one object at a time, in the same six parts. Pick the object:</p>
<div class="dl-row">
    @foreach (\App\Support\ObjectDoc::OBJECTS as $key => $label)
        <x-dl::button size="sm" :variant="$key === \App\Support\ObjectDoc::key() ? 'primary' : 'secondary'" :label="$label" :href="route('lib', 'p5-override').'?object='.$key" />
    @endforeach
</div>
