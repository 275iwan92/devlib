{{-- Model Assistant AI (Devlib\Assistant): pertanyaan contoh pengguna aplikasi dijawab Assistant dengan Tool (engine Calc contoh), layar, Help, dan kalimat tetap untuk pertanyaan di luar program. Data fiktif. --}}
@php
    $assistant = \App\Support\ModelDemo::assistant();
    $asker = \App\Support\ModelDemo::asker();
    $rows = [];
    foreach (\App\Support\ModelDemo::questions() as $q) {
        $r = $assistant->ask($asker, $q);
        $steps = $r->steps;
        foreach ($r->actions as $a) {
            if (isset($a['topic'])) {
                $steps = [...$steps, ...$assistant->topic($asker, $a['topic'])->steps];
            }
        }
        $buttons = array_map(fn ($a) => '[' . $a['label'] . ']', $r->actions);
        $rows[] = ['q' => $q, 'kind' => $r->kind, 'title' => $r->title,
            'detail' => implode(' · ', array_filter([$r->text, ...array_map(fn ($x) => $x[0].': '.$x[1], $r->rows), ...$buttons, ...$steps]))];
    }
    $gaps = array_map(fn ($h) => ['q' => $h['q']], array_values(array_filter($assistant->history($asker), fn ($h) => ($h['reply']['kind'] ?? '') === \Devlib\Assistant\Reply::UNKNOWN)));
    $raw = fn (array $list) => array_map(fn ($r) => array_map(fn ($v) => \Devlib\Lang::raw($v), $r), $list);
@endphp
<x-dl::table :columns="['q' => 'Question', 'kind' => 'Reply kind', 'title' => 'Answer', 'detail' => 'Details']" :rows="$raw($rows)" source="Devlib\Assistant\Assistant::ask()" />
<p class="dl-note">The assistant helps the users of an application with three things: <b>quick data questions</b> (a <code>Tool</code> over an Engine in Calc mode, read only; the number is never worked out by the language model), <b>where to find a menu</b> (<code>Navigator</code> gives the screen with an Open button, <code>Knowledge</code> gives the steps) and <b>questions that have nothing to do with the application</b>, answered with one fixed sentence set by the application (<code>OUT_OF_SCOPE</code>). A question that does belong to the application but has no answer in Help yet stays <code>UNKNOWN</code> and is logged, so the Help can be written. The language model (if the application binds one) only picks among candidates. The answers are the same shape the AI Assistant drawer of <code>x-dl::shell</code> reads (prop <code>assistant</code>). Rules: <code>skills/devlib/assistant.md</code>.</p>
<p class="dl-note"><b>Questions logged for the Help</b> (in the application, the same list comes from the <code>HistoryStore</code>):</p>
<x-dl::table :columns="['q' => 'Question']" :rows="$raw($gaps)" source="Devlib\Assistant\HistoryStore::recent()" />
