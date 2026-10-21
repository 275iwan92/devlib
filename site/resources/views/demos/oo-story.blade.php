{{-- Cerita object: isi dari ObjectNotes (cerita), header REMARK dan berkas kelas (dibaca dari kode). Object lain: kirim $objectDoc = ObjectDoc::... --}}
@php($doc = $objectDoc ?? \App\Support\ObjectDoc::current())
@php($story = $doc->notes()['story'] ?? [])
{{-- tiga butir pertama sejajar; butir terakhir ("does not do") selebar halaman --}}
<x-dl::cols>
    @foreach (array_slice($story, 0, -1, true) as $heading => $paragraphs)
        <x-dl::card :title="$heading">
            @foreach ($paragraphs as $text)
                <p class="dl-note">{{ $text }}</p>
            @endforeach
        </x-dl::card>
    @endforeach
</x-dl::cols>
@foreach (array_slice($story, -1, 1, true) as $heading => $paragraphs)
    <x-dl::card :title="$heading">
        @foreach ($paragraphs as $text)
            <p class="dl-note">{{ $text }}</p>
        @endforeach
    </x-dl::card>
@endforeach
<x-dl::card title="From the source" description="Read from the class file when this page opens, not copied">
    <x-dl::table :columns="['field' => 'Header field', 'value' => 'Value']" :rows="$doc->sourceRows()" :striped="false" source="REMARK header of the class file" />
</x-dl::card>
