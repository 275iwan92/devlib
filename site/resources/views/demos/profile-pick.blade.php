{{-- Pemilih varian halaman p7-profile: ?variant=tmerp|sstock|devlib (bawaan tmerp); tautan bisa dibagikan. --}}
@php($v = \App\Support\ProfileVariants::current())
<p class="dl-note">The same panel is assembled differently by each application, through the <code>without</code> prop of the Layer Shell. Pick the variant:</p>
<div class="dl-row">
    @foreach (\App\Support\ProfileVariants::ALL as $key => $variant)
        <x-dl::button size="sm" :variant="$key === $v['key'] ? 'primary' : 'secondary'" :label="$variant['label']" :href="route('lib', 'p7-profile').'?variant='.$key" />
    @endforeach
</div>
<p class="dl-note"><b>{{ $v['label'] }}</b>: {{ $v['summary'] }} Shape taken from the pattern: {{ $v['pattern'] }}.</p>
