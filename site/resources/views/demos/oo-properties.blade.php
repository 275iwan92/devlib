{{-- Properti = parameter konstruktor kelas PHP (refleksi): nama, tipe, bawaan dari kode; arti + contoh dari ObjectNotes. --}}
@php($doc = $objectDoc ?? \App\Support\ObjectDoc::kanban())
@php($child = $doc->child ? class_basename($doc->child) : null)
<x-dl::table :columns="['name' => 'Property', 'attr' => 'Blade attribute', 'type' => 'Type', 'default' => 'Default', 'meaning' => 'Meaning', 'example' => 'Example value'] + ($child ? ['child' => 'In '.$child] : [])" :rows="$doc->props()" :striped="false" source="Constructor of the class (reflection)" />
<p class="dl-note">Every property is a constructor parameter, so a subclass changes a default by passing another value to <code>parent::__construct()</code>. The shape of the array properties, as written in the class file:</p>
<pre class="dl-code__pre"><code>{{ $doc->classComment() }}</code></pre>
