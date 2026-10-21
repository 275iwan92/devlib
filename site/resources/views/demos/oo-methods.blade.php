{{-- Method PHP (refleksi) dan method JS (dibaca dari berkas kelas JS + kelas induk di core.js); "Can override" dan "Used for" dari ObjectNotes. --}}
@php($doc = $objectDoc ?? \App\Support\ObjectDoc::kanban())
@php($child = $doc->child ? class_basename($doc->child) : null)
@php($cols = ['signature' => 'Method', 'from' => 'Declared in', 'can' => 'Can override?', 'use' => 'Used for'] + ($child ? ['child' => $child] : []))
<p class="dl-note"><b>PHP</b> · <code>{{ class_basename($doc->class) }} extends DevlibComponent</code> · overriding a method means writing it again in the subclass; call <code>parent::method()</code> to keep the parent behavior.</p>
<x-dl::table :columns="$cols" :rows="$doc->phpMethods()" :striped="false" source="Methods of the class and of DevlibComponent (reflection)" />
<p class="dl-note"><b>JavaScript</b> · <code>{{ class_basename($doc->class) }} extends Devlib.Component</code> in <code>{{ $doc->jsFile() }}</code> · the behavior of the element; overriding a method means writing it again in the JS subclass and calling <code>super.method()</code>.</p>
<x-dl::table :columns="$cols" :rows="$doc->jsMethods()" :striped="false" source="Method list of the JS class files" />
