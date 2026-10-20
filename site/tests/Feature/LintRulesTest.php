<?php

namespace Tests\Feature;

use Devlib\Components\Form;
use Devlib\Components\Grid;
use Devlib\Lint\Finding;
use Devlib\Lint\FormRules;
use Devlib\Lint\GridRules;
use Devlib\Lint\Lint;
use Devlib\Lint\ViewLinter;
use Tests\TestCase;

// Penegak aturan baku (devlib:lint): kelas aturan murni, pemeriksa view statis, dan perintah.
class LintRulesTest extends TestCase
{
    private function codes(array $findings): array
    {
        return array_map(fn (Finding $f) => $f->code, $findings);
    }

    private function cols(int $n, bool $badge = true): array
    {
        $c = [['key' => 'no', 'label' => 'No', 'type' => 'identity', 'code' => 'party']];
        for ($i = 1; $i < $n; $i++) {
            $c[] = ['key' => 'c'.$i, 'label' => 'C'.$i];
        }
        if ($badge) {
            $c[$n - 1] = ['key' => 'status', 'label' => 'Status', 'type' => 'badge', 'tones' => ['Draft' => 'neutral']];
        }

        return $c;
    }

    public function test_compliant_transactional_grid_has_no_findings(): void
    {
        $g = new Grid(id: 'po', columns: $this->cols(6), kind: 'transactional', openUrl: '/x/{id}');
        $this->assertSame([], GridRules::check($g));
    }

    public function test_transactional_violations_are_reported(): void
    {
        $g = new Grid(id: 'po', columns: $this->cols(3, false), kind: 'transactional', rowTone: 't',
            bulk: [['key' => 'confirm']], detail: ['columns' => array_fill(0, 8, ['key' => 'qty', 'label' => 'Qty', 'type' => 'money'])]);
        $codes = $this->codes(GridRules::check($g));
        foreach (['G120', 'G121', 'G122', 'G124', 'G125', 'G127', 'G128'] as $c) {
            $this->assertContains($c, $codes);
        }
    }

    public function test_master_grid_requires_toggle_url_and_box(): void
    {
        $g = new Grid(id: 'cat', columns: [['key' => 'name', 'label' => 'Name', 'type' => 'identity']], kind: 'master');
        $this->assertSame([], array_diff($this->codes(GridRules::check($g)), ['G110']), 'box dipaksa applyKind');
        $this->assertContains('G110', $this->codes(GridRules::check($g)));
        $this->assertSame([], GridRules::check(new Grid(id: 'cat', columns: [['key' => 'name', 'label' => 'Name', 'type' => 'identity']], kind: 'master', toggleUrl: '/t/{id}')));
    }

    public function test_legacy_and_report_grids_are_not_errors(): void
    {
        $this->assertSame(['G101'], $this->codes(GridRules::check(new Grid(id: 'old'))));
        $this->assertSame([], GridRules::check(new Grid(id: 'r', mode: 'report')));
    }

    public function test_document_form_rules(): void
    {
        config(['devlib.back_history' => false]);
        $ok = new Form(title: 'PO', action: '/po', panel: 'top', back: '/po', newUrl: '/po/new', printUrl: '/po/print', kind: 'document', status: 'draft');
        $this->assertSame([], FormRules::check($ok));

        config(['devlib.back_history' => true]);
        $bad = new Form(title: 'PO', action: '/po', kind: 'document', status: 'draft', hide: ['delete']);
        $codes = $this->codes(FormRules::check($bad));
        foreach (['F109', 'F105', 'F103'] as $c) {
            $this->assertContains($c, $codes);
        }
        $this->assertContains('F108', $this->codes(FormRules::check(new Form(kind: 'master', status: 'draft'))));
        $this->assertContains('F107', $this->codes(FormRules::check(new Form(kind: 'weird'))));
    }

    public function test_collector_is_off_by_default_and_records_when_enabled(): void
    {
        $g = new Grid(id: 'x', rowTone: 't', kind: 'transactional', columns: $this->cols(3, false));
        Lint::enable(false);
        $g->dl();
        $this->assertSame([], Lint::findings());
        Lint::enable();
        $g->dl();
        $this->assertNotEmpty(Lint::findings(Finding::ERROR));
        Lint::enable(false);
    }

    public function test_view_linter_flags_static_violations(): void
    {
        $src = <<<'BLADE'
{{-- <table> di komentar diabaikan --}}
<x-dl::grid id="a" :columns="[['key'=>'x','type'=>'text']]" />
<x-dl::grid id="b" kind="master" />
<x-dl::form kind="document" title="PO">
    <x-dl::field.text name="no" label="No" />
    <x-dl::field.date name="d" label="D" :value="now()" />
</x-dl::form>
<table></table><div style="color:#ff0000"></div>
<script>alert(1)</script>
BLADE;
        $codes = $this->codes(ViewLinter::lint($src, 'v.blade.php'));
        foreach (['G101', 'G110', 'F101', 'F201', 'L001', 'L002', 'L003'] as $c) {
            $this->assertContains($c, $codes);
        }
        $this->assertSame(1, count(array_filter($codes, fn ($c) => $c === 'L001')), 'komentar tidak dihitung');
        $this->assertSame(1, count(array_filter($codes, fn ($c) => $c === 'F201')), 'field dengan :value tidak dihitung');
    }

    public function test_command_runs_on_a_directory(): void
    {
        $dir = sys_get_temp_dir().'/dl-lint-'.uniqid();
        mkdir($dir);
        file_put_contents($dir.'/a.blade.php', '<x-dl::grid id="a" kind="master" />');
        $this->artisan('devlib:lint', ['paths' => [$dir]])->expectsOutputToContain('G110')->assertFailed();
        file_put_contents($dir.'/a.blade.php', '<x-dl::grid id="a" kind="master" toggle-url="/t" />');
        $this->artisan('devlib:lint', ['paths' => [$dir]])->assertSuccessful();
        unlink($dir.'/a.blade.php');
        rmdir($dir);
    }
}
