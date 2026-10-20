{{-- Model Assistant AI (Devlib\Assistant): pertanyaan contoh dijawab oleh Assistant dengan Tool (Engine volume-discount), layar, dan Help fiktif. --}}
@php
    $assistant = \App\Support\ModelDemo::assistant();
    $asker = \App\Support\ModelDemo::asker();
    $rows = [];
    foreach (\App\Support\ModelDemo::questions() as $q) {
        $r = $assistant->ask($asker, $q);
        $rows[] = ['q' => $q, 'kind' => $r->kind, 'title' => $r->title,
            'detail' => implode(' · ', array_filter([$r->text, ...array_map(fn ($x) => $x[0].': '.$x[1], $r->rows), ...$r->steps]))];
    }
@endphp
<x-dl::table :columns="['q' => 'Question', 'kind' => 'Reply kind', 'title' => 'Title', 'detail' => 'Detail']" :rows="array_map(fn ($r) => array_map(fn ($v) => \Devlib\Lang::raw($v), $r), $rows)" source="Devlib\Assistant\Assistant::ask()" />
<p class="dl-note">Numbers come from an Engine (<code>EngineTool</code> over <code>volume-discount</code>), help text is quoted from <code>Knowledge</code>, screens come from <code>Navigator</code>; the language model (if the application binds one) only picks among candidates. The answers are the same shape the AI Assistant drawer of <code>x-dl::shell</code> reads (prop <code>assistant</code>). Rules: <code>skills/devlib/assistant.md</code>.</p>
