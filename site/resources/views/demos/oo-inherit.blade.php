{{-- Cara membuat turunan: langkah (teks) + kode contoh ASLI yang dibaca dari berkas (KanbanProduksi.php dan skrip demo oo-override), bukan salinan. --}}
@php($doc = $objectDoc ?? \App\Support\ObjectDoc::kanban())
@php($child = class_basename((string) $doc->child))
<p class="dl-note"><b>PHP side: properties</b></p>
<ol class="dl-note">
    <li>Write <code>class {{ $child }} extends {{ class_basename($doc->class) }}</code> in your project.</li>
    <li>In the constructor call <code>parent::__construct(...)</code> with the new values (columns, transitions, members, ...): that overrides the properties.</li>
    <li>Return your own tag from <code>tag()</code>, and <code>source()</code> if the data comes from somewhere else.</li>
    <li>Return the name of your JS class from <code>jsClass()</code> when you override behavior in JavaScript.</li>
</ol>
<div class="dl-code__file">{{ $doc->childPhpPath() }}</div>
<pre class="dl-code__pre"><code>{{ $doc->childPhpSource() }}</code></pre>
<p class="dl-note"><b>JavaScript side: methods</b></p>
<ol class="dl-note">
    <li>Write <code>class {{ $child }} extends Devlib.classes.{{ class_basename($doc->class) }}</code> and write only the methods that differ.</li>
    <li>Inside a method call <code>super.method(...)</code> to keep the parent behavior, then add to it.</li>
    <li>Register it with <code>Devlib.register({{ $child }})</code>; the element picks it up through <code>data-dl-js</code> (the <code>jsClass()</code> above).</li>
    <li>To change every {{ class_basename($doc->class) }} of the application instead, use <code>Devlib.define('{{ class_basename($doc->class) }}', {{ $child }})</code> (core.js).</li>
</ol>
<div class="dl-code__file">{{ $doc->childJsPath() }}</div>
<pre class="dl-code__pre"><code>{{ $doc->childJsBody() }}</code></pre>
<p class="dl-note">A change in the parent reaches every subclass; behavior that other projects need goes up into the parent, not into the child.</p>
