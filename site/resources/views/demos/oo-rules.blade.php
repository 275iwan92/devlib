{{-- Aturan terkait: tautan ke halaman Aturan (skills/devlib/*.md) dan ke entri Technical Guide object ini. --}}
@php($doc = $objectDoc ?? \App\Support\ObjectDoc::kanban())
@php($guide = \Devlib\Guide::url())
<ul class="dl-note">
    <li><a href="{{ route('lib', 'rules-object') }}">Rules for building an object</a>: the steps to build one (section 2), inheriting from it (section 4), versions and release (section 6).</li>
    <li><a href="{{ route('lib', 'rules-inheritance') }}">Direction of inheritance</a>: DevLib builds, projects inherit; opt-in changes; object oriented thinking.</li>
    @if ($guide)
        <li><a href="{{ $guide.'#'.\Devlib\Guide::anchor($doc->tag()) }}" target="_blank" rel="noopener">Technical Guide: {{ $doc->tag() }}</a>: props, files and details of this object.</li>
    @endif
</ul>
