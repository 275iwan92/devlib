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
<p class="dl-note">The assistant is a <b>model</b> of DevLib that any application installs, and it <b>learns from the application it is installed in</b>: screens come from that application's <code>Navigator</code>, help from its <code>Knowledge</code> (Help / user guide), numbers from the <code>Tool</code> (Engine in Calc mode) the application builds, and access rights from its <code>Asker</code>. Nothing is trained and DevLib stores no knowledge. The questions below are asked to a fictional host application (purchasing and sales, data in memory).</p>
<x-dl::table :columns="['q' => 'Question', 'kind' => 'Reply kind', 'title' => 'Answer', 'detail' => 'Details']" :rows="$raw($rows)" source="Devlib\Assistant\Assistant::ask()" />
<p class="dl-note">The assistant helps the users of an application with three things: <b>quick data questions</b> (a <code>Tool</code> over an Engine in Calc mode, read only; the number is never worked out by the language model), <b>where to find a menu</b> (<code>Navigator</code> gives the screen with an Open button, <code>Knowledge</code> gives the steps) and <b>questions that have nothing to do with the application</b>, answered with one fixed sentence set by the application (<code>OUT_OF_SCOPE</code>). A question that does belong to the application but has no answer in Help yet stays <code>UNKNOWN</code> and is logged, so the Help can be written. The language model (if the application binds one) only picks among candidates. The answers are the same shape the AI Assistant drawer of <code>x-dl::shell</code> reads (prop <code>assistant</code>). Rules: <code>skills/devlib/assistant.md</code>.</p>
<p class="dl-note"><b>Data scope.</b> Knowledge and quantities (stock, orders, attendance) follow the access rights of the asker; money values only for an asker the application declares as finance (<code>ValueAsker</code>, a <code>Tool</code> marked <code>Sensitivity::Value</code>, as the first question here). Applications that have salary, payroll or costing-formula modules switch on <code>DataPolicy</code>, and those are never served; an application without them is not limited at all. Personal data (ID numbers, home address, date of birth, passwords) and the people the application protects are never read or answered, for any user; the assistant may only point to the screen where you manage your own data. Rules: <code>skills/devlib/assistant.md</code>.</p>
<p class="dl-note"><b>Questions logged for the Help</b> (in the application, the same list comes from the <code>HistoryStore</code>):</p>
<x-dl::table :columns="['q' => 'Question']" :rows="$raw($gaps)" source="Devlib\Assistant\HistoryStore::recent()" />
