{{-- Properti = parameter konstruktor kelas PHP (refleksi): nama, tipe, bawaan dari kode; arti + contoh dari ObjectNotes. Object dengan banyak properti: satu tabel per kelompok (elemen ketiga catatan). --}}
@php($doc = $objectDoc ?? \App\Support\ObjectDoc::current())
@php($child = $doc->child ? class_basename($doc->child) : null)
@foreach (collect($doc->props())->groupBy('group') as $group => $rows)
    @if ($group !== '')
        <p class="dl-note"><b>{{ $group }}</b></p>
    @endif
    <x-dl::table :columns="['name' => 'Property', 'attr' => 'Blade attribute', 'type' => 'Type', 'default' => 'Default', 'meaning' => 'Meaning', 'example' => 'Example value'] + ($child ? ['child' => 'In '.$child] : [])" :rows="$rows->all()" :striped="false" source="Constructor of the class (reflection)" />
@endforeach
<p class="dl-note">Every property is a constructor parameter, so a subclass changes a default by passing another value to <code>parent::__construct()</code>. The shape of the array properties, as written in the class file:</p>
<pre class="dl-code__pre"><code>{{ $doc->classComment() }}</code></pre>
