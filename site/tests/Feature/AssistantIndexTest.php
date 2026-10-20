<?php

namespace Tests\Feature;

use Devlib\Assistant\Asker;
use Devlib\Assistant\Assistant;
use Devlib\Assistant\DataPolicy;
use Devlib\Assistant\Index\Budget;
use Devlib\Assistant\Index\IndexAudit;
use Devlib\Assistant\Index\IndexEntry;
use Devlib\Assistant\Index\IndexRunner;
use Devlib\Assistant\Index\Indexer;
use Devlib\Assistant\Index\IndexSource;
use Devlib\Assistant\Index\IndexStore;
use Devlib\Assistant\Index\MemoryIndexStore;
use Devlib\Assistant\Index\MemoryThrottle;
use Devlib\Assistant\Knowledge;
use Devlib\Assistant\Navigator;
use Devlib\Assistant\Reply;
use Devlib\Assistant\ScopedValueAsker;
use Devlib\Assistant\ValueAsker;
use Devlib\Assistant\Sensitivity;
use Illuminate\Contracts\Console\Kernel;
use Tests\TestCase;

// Pengindeks Assistant tahap 1 (docs/assistant-indexer.md): hash, anggaran, kebijakan data, hak, pemicu terbatas, opt-in.
// Semua data fiktif.
class AssistantIndexTest extends TestCase
{
    private function plain(array $modules = [], int $level = 1): Asker
    {
        return new class($modules, $level) implements Asker
        {
            public function __construct(private array $m, private int $l) {}

            public function id(): string { return 'u1'; }

            public function name(): string { return 'Demo'; }

            public function level(): int { return $this->l; }

            public function can(string $module): bool { return in_array($module, $this->m, true); }
        };
    }

    /** Hak nilai global (orang keuangan menurut program). */
    private function finance(): ValueAsker
    {
        return new class implements ValueAsker
        {
            public function id(): string { return 'u3'; }

            public function name(): string { return 'Demo'; }

            public function level(): int { return 1; }

            public function can(string $module): bool { return false; }

            public function canSeeValue(): bool { return true; }
        };
    }

    /** @param list<string> $scoped kunci nilai yang boleh dilihat (ScopedValueAsker menjadi satu-satunya penentu) */
    private function valued(array $scoped): ScopedValueAsker
    {
        return new class($scoped) implements ScopedValueAsker
        {
            public function __construct(private array $s) {}

            public function id(): string { return 'u2'; }

            public function name(): string { return 'Demo'; }

            public function level(): int { return 1; }

            public function can(string $module): bool { return false; }

            public function canSeeValue(): bool { return true; }

            public function canSeeValueOf(string $toolKey): bool { return in_array($toolKey, $this->s, true); }
        };
    }

    private function source(string $key, array $entries): IndexSource
    {
        return new class($key, $entries) implements IndexSource
        {
            public int $reads = 0;

            public function __construct(public string $key, public array $entries) {}

            public function key(): string { return $this->key; }

            public function entries(): iterable
            {
                $this->reads++;

                return $this->entries;
            }
        };
    }

    private function screen(string $id, string $title, string $words = '', string $text = '', array $more = []): IndexEntry
    {
        return new IndexEntry(...[...['id' => $id, 'kind' => IndexEntry::SCREEN, 'title' => $title, 'words' => $words, 'text' => $text, 'url' => "/{$id}"], ...$more]);
    }

    private function ids(IndexStore $store, string $q, Asker $asker): array
    {
        return array_map(fn (IndexEntry $e) => $e->id, $store->search(explode(' ', $q), $asker));
    }

    private function assistant(?IndexStore $index = null, ?DataPolicy $policy = null, ?string $outOfScope = null): Assistant
    {
        $nav = new class implements Navigator
        {
            public function screens(Asker $asker): array
            {
                return [['key' => 'inv', 'label' => 'Stock Overview', 'url' => '/stock', 'path' => 'Inventory › Stock', 'about' => 'Stock per location.', 'help' => 'stock']];
            }
        };
        $kb = new class implements Knowledge
        {
            public function topics(Asker $asker): array
            {
                return [['key' => 'stock', 'title' => 'Stock Overview', 'purpose' => 'See stock.', 'steps' => ['Open Stock Overview', 'Pick a location'], 'words' => 'inventory']];
            }
        };

        return new Assistant($nav, $kb, outOfScope: $outOfScope, policy: $policy, index: $index);
    }

    public function test_unchanged_hash_is_not_processed_and_changed_one_is(): void
    {
        $store = new MemoryIndexStore;
        $entries = [$this->screen('a', 'Alpha Report'), $this->screen('b', 'Beta Report')];
        $first = (new Indexer)->pass($this->source('menu', $entries), $store);
        $this->assertSame([2, 0, true], [$first->stored, $first->unchanged, $first->complete]);

        $again = (new Indexer)->pass($this->source('menu', $entries), $store);
        $this->assertSame([0, 2], [$again->stored, $again->unchanged]);

        $entries[1] = $this->screen('b', 'Beta Report', words: 'second');
        $changed = (new Indexer)->pass($this->source('menu', $entries), $store);
        $this->assertSame([1, 1], [$changed->stored, $changed->unchanged]);
    }

    public function test_budget_is_respected_and_the_next_pass_resumes(): void
    {
        $entries = array_map(fn ($i) => $this->screen("e{$i}", "Report {$i}"), range(1, 5));
        $store = new MemoryIndexStore;
        $src = $this->source('menu', $entries);
        $budget = new Budget(size: 2);

        $p1 = (new Indexer)->pass($src, $store, $budget);
        $this->assertSame([2, false], [$p1->stored, $p1->complete]);
        $p2 = (new Indexer)->pass($src, $store, $budget);
        $this->assertSame([2, false], [$p2->stored, $p2->complete]);
        $p3 = (new Indexer)->pass($src, $store, $budget);
        $this->assertSame([1, true], [$p3->stored, $p3->complete]);
        $this->assertCount(5, $store->hashes('menu'));

        // batas waktu: jam palsu maju 100 ms tiap dibaca, anggaran 150 ms -> dua entri lalu berhenti
        $tick = 0;
        $timed = new Budget(ms: 150, size: 100, clock: function () use (&$tick) { return $tick += 100; });
        $t = (new Indexer)->pass($src, new MemoryIndexStore, $timed);
        $this->assertSame([2, false], [$t->stored, $t->complete]);

        // anggaran sekecil apa pun tetap memajukan satu entri
        $this->assertSame(1, (new Indexer)->pass($src, new MemoryIndexStore, new Budget(ms: 0, size: 1))->stored);
    }

    public function test_pause_is_nine_times_the_work_time(): void
    {
        $tick = 0;
        $budget = new Budget(clock: function () use (&$tick) { return $tick += 10; });
        $p = (new Indexer)->pass($this->source('menu', [$this->screen('a', 'Alpha')]), new MemoryIndexStore, $budget);
        $this->assertSame([10.0, 90.0], [$p->ms, $p->pauseMs]);
    }

    public function test_forbidden_entries_are_skipped_not_failed_and_old_ones_are_dropped(): void
    {
        $policy = new DataPolicy(protected: ['Budi Santoso']);
        $entries = [
            $this->screen('ok', 'Stock Overview', 'inventory'),
            $this->screen('slip', 'Slip Gaji Bulanan'),
            $this->screen('cost', 'Costing', text: 'Edit the formula HPP here'),
            $this->screen('home', 'Profile', text: 'Your alamat rumah and tanggal lahir'),
            $this->screen('who', 'Budi Santoso Targets'),
            $this->screen('lbl', 'Plain', more: ['sensitivity' => Sensitivity::Forbidden]),
        ];
        $store = new MemoryIndexStore;
        $p = (new Indexer($policy))->pass($this->source('menu', $entries), $store);

        $this->assertSame(['slip', 'cost', 'home', 'who', 'lbl'], $p->skipped);
        $this->assertSame(['ok'], array_keys($store->hashes('menu')));
        $this->assertTrue($p->complete);

        // indeks lama yang dibangun tanpa kebijakan: putaran berikut dengan kebijakan membuangnya
        $old = new MemoryIndexStore;
        (new Indexer)->pass($this->source('menu', $entries), $old);
        $this->assertContains('slip', array_keys($old->hashes('menu')));
        $clean = (new Indexer($policy))->pass($this->source('menu', $entries), $old);
        $this->assertSame(['ok'], array_keys($old->hashes('menu')));
        $this->assertSame(4, $clean->removed);
    }

    public function test_value_entries_keep_only_their_name_and_are_filtered_by_value_access(): void
    {
        $store = new MemoryIndexStore;
        $value = $this->screen('recv', 'Receivable Report', 'piutang tagihan', 'Total owed by customers', ['sensitivity' => Sensitivity::Value, 'steps' => []]);
        (new Indexer)->pass($this->source('menu', [$value]), $store);

        [[, $kept]] = $store->all();
        $this->assertSame(['Receivable Report', '', '', Sensitivity::Value], [$kept->title, $kept->words, $kept->text, $kept->sensitivity]);
        $this->assertSame([], $this->ids($store, 'piutang', $this->finance()));  // isi tidak diindeks

        $this->assertSame(['recv'], $this->ids($store, 'receivable', $this->finance()));
        $this->assertSame([], $this->ids($store, 'receivable', $this->plain()));
        // hak per kunci (ScopedValueAsker): hanya kunci yang dinyatakan program, canSeeValue() global tidak dipakai
        $this->assertSame(['recv'], $this->ids($store, 'receivable', $this->valued(['recv'])));
        $this->assertSame([], $this->ids($store, 'receivable', $this->valued(['other'])));
    }

    public function test_an_asker_without_the_module_or_level_never_gets_the_entry(): void
    {
        $store = new MemoryIndexStore;
        (new Indexer)->pass($this->source('menu', [
            $this->screen('led', 'Ledger Report', more: ['module' => 'finance']),
            $this->screen('adm', 'Admin Report', more: ['minLevel' => 5]),
            $this->screen('all', 'Open Report'),
        ]), $store);

        $this->assertSame(['all'], $this->ids($store, 'report', $this->plain([], 1)));
        $this->assertEqualsCanonicalizing(['all', 'led'], $this->ids($store, 'report', $this->plain(['finance'], 1)));
        $this->assertEqualsCanonicalizing(['all', 'adm'], $this->ids($store, 'report', $this->plain([], 5)));

        $assistant = $this->assistant($store);
        $this->assertSame(Reply::UNKNOWN, $assistant->ask($this->plain(), 'ledger report')->kind);
        $this->assertSame('led', $assistant->ask($this->plain(['finance']), 'ledger report')->key);
    }

    public function test_assistant_filters_again_when_a_program_store_filters_too_little(): void
    {
        $leaky = new class implements IndexStore
        {
            public function hashes(string $source): array { return []; }

            public function put(string $source, IndexEntry $entry): void {}

            public function forget(string $source, array $ids): void {}

            public function search(array $tokens, Asker $asker, int $limit = 10): array
            {
                return [new IndexEntry('led', IndexEntry::SCREEN, 'Ledger Report', url: '/led', module: 'finance'),
                    new IndexEntry('slip', IndexEntry::SCREEN, 'Slip Gaji Report', url: '/slip'),
                    new IndexEntry('recv', IndexEntry::SCREEN, 'Receivable Report', url: '/recv', sensitivity: Sensitivity::Value)];
            }
        };
        $a = $this->assistant($leaky, new DataPolicy);
        foreach (['ledger report', 'slip gaji report', 'receivable report'] as $q) {
            $this->assertNotSame(Reply::SCREEN, $a->ask($this->plain(), $q)->kind, $q);
        }
        $this->assertSame('led', $this->assistant($leaky)->ask($this->plain(['finance']), 'ledger report')->key);
    }

    public function test_a_forbidden_question_never_reaches_the_index(): void
    {
        $spy = new class implements IndexStore
        {
            public int $searches = 0;

            public function hashes(string $source): array { return []; }

            public function put(string $source, IndexEntry $entry): void {}

            public function forget(string $source, array $ids): void {}

            public function search(array $tokens, Asker $asker, int $limit = 10): array
            {
                $this->searches++;

                return [];
            }
        };
        $a = $this->assistant($spy, new DataPolicy(protected: ['Budi Santoso']));

        foreach (['berapa gaji bulan ini', 'rumus HPP produk A', 'jadwal Budi Santoso', 'kata sandi saya'] as $q) {
            $this->assertSame(Reply::DENIED, $a->ask($this->plain(), $q)->kind, $q);
        }
        $this->assertSame(0, $spy->searches);

        $a->ask($this->plain(), 'stock overview');
        $this->assertSame(1, $spy->searches);
    }

    public function test_without_an_index_the_assistant_behaves_exactly_as_before(): void
    {
        $questions = ['open stock overview', 'how to use stock overview', 'stock', 'weather in Paris', 'cuaca di Surabaya besok', 'inventory', ''];
        foreach ([null, 'Saya hanya memahami perihal program ini'] as $scope) {
            $plain = $this->assistant(outOfScope: $scope);
            $empty = $this->assistant(new MemoryIndexStore, outOfScope: $scope);
            foreach ($questions as $q) {
                $this->assertEquals($plain->ask($this->plain(), $q)->toArray(), $empty->ask($this->plain(), $q)->toArray(), $q);
            }
        }
    }

    public function test_the_index_adds_candidates_screens_and_help_without_overriding_the_program(): void
    {
        $store = new MemoryIndexStore;
        (new Indexer)->pass($this->source('menu', [
            $this->screen('ret', 'Retur Report', 'kembali', 'Goods sent back.', ['path' => 'Sales › Retur']),
            $this->screen('inv', 'Stock Overview Copy'),
            new IndexEntry('retur-help', IndexEntry::HELP, 'Retur Help', 'retur', 'Return goods.', ['Open Retur Report', 'Pick the order']),
        ]), $store);
        $a = $this->assistant($store, outOfScope: 'di luar');

        $s = $a->ask($this->plain(), 'open retur report');
        $this->assertSame([Reply::SCREEN, 'ret', '/ret'], [$s->kind, $s->key, $s->actions[0]['url']]);
        $h = $a->ask($this->plain(), 'how to retur help');
        $this->assertSame([Reply::HELP, ['Open Retur Report', 'Pick the order']], [$h->kind, $h->steps]);
        // kata dari indeks termasuk lingkup program (bukan OUT_OF_SCOPE)
        $this->assertSame(Reply::UNKNOWN, $a->ask($this->plain(), 'retur kembali bagaimana prosesnya hari ini ya')->kind);
        // layar program tidak ditimpa kunci yang sama
        $this->assertSame('/stock', $a->ask($this->plain(), 'open stock overview')->actions[0]['url']);
    }

    public function test_entries_missing_from_the_source_are_forgotten_only_after_a_complete_read(): void
    {
        $store = new MemoryIndexStore;
        $all = array_map(fn ($i) => $this->screen("e{$i}", "Report {$i}"), range(1, 4));
        (new Indexer)->pass($this->source('menu', $all), $store);

        $left = array_slice($all, 0, 2);
        $partial = (new Indexer)->pass($this->source('menu', [...$left, $this->screen('n1', 'New One'), $this->screen('n2', 'New Two')]), $store, new Budget(size: 1));
        $this->assertFalse($partial->complete);
        $this->assertCount(5, $store->hashes('menu'));  // anggaran habis: belum ada yang dibuang

        $done = (new Indexer)->pass($this->source('menu', $left), $store);
        $this->assertSame(3, $done->removed);
        $this->assertSame(['e1', 'e2'], array_keys($store->hashes('menu')));
    }

    public function test_the_trigger_is_limited_and_pauses_follow_the_work(): void
    {
        $now = 1000.0;
        $throttle = new MemoryThrottle(function () use (&$now) { return $now; });
        $slept = [];
        $tick = 0;
        $budget = new Budget(clock: function () use (&$tick) { return $tick += 10; });
        $store = new MemoryIndexStore;
        $runner = new IndexRunner(new Indexer, $store, $throttle, function ($ms) use (&$slept) { $slept[] = $ms; });
        $sources = [$this->source('menu', [$this->screen('a', 'Alpha')]), $this->source('help', [$this->screen('b', 'Beta')])];

        $this->assertCount(2, $runner->run($sources, $budget));
        $this->assertSame([90.0], $slept);  // satu jeda antara dua potongan, 9 x lama kerja
        $this->assertNull($runner->run($sources, $budget));  // dalam satu jam: dibatasi
        $now += 3599;
        $this->assertNull($runner->run($sources, $budget));
        $this->assertCount(2, $runner->run($sources, $budget, force: true));
        $now += 3601;
        $this->assertCount(2, $runner->run($sources, $budget));

        // batas potongan per jalan: sisanya pada jalan berikut
        $big = $this->source('menu', array_map(fn ($i) => $this->screen("e{$i}", "Report {$i}"), range(1, 5)));
        $r = new IndexRunner(new Indexer, $s2 = new MemoryIndexStore, new MemoryThrottle, fn () => null);
        $first = $r->run([$big], new Budget(size: 2, chunks: 2), force: true);
        $this->assertSame([true, false], [count($first) === 2, $first[1]->complete]);
        $this->assertCount(4, $s2->hashes('menu'));
        $r->run([$big], new Budget(size: 2, chunks: 2), force: true);
        $this->assertCount(5, $s2->hashes('menu'));
    }

    public function test_the_audit_helper_finds_forbidden_things_that_the_production_policy_missed(): void
    {
        $sources = [$this->source('menu', [$this->screen('ok', 'Stock Overview'), $this->screen('b', 'Bonus Planner'), $this->screen('p', 'Targets of Rina Wijaya')])];

        $leaks = IndexAudit::leaks($sources, new DataPolicy, ['bonus'], ['Rina Wijaya']);
        $this->assertSame(['menu/b: memuat kata atau orang terlarang', 'menu/p: memuat kata atau orang terlarang'], $leaks);
        $this->assertSame([], IndexAudit::leaks($sources, new DataPolicy(['bonus'], ['Rina Wijaya']), ['bonus'], ['Rina Wijaya']));
    }

    public function test_the_command_is_opt_in_and_runs_with_a_bound_store(): void
    {
        // program tanpa IndexStore: perintah tetap ada, hanya mencetak petunjuk dan selesai tanpa galat (tidak diam-diam hilang)
        $this->assertArrayHasKey('devlib:index', $this->app->make(Kernel::class)->all());
        $this->artisan('devlib:index')->expectsOutputToContain('Belum ada penyimpanan indeks')->assertSuccessful();

        $store = new MemoryIndexStore;
        $this->app->instance(IndexStore::class, $store);
        $this->app->instance('src-menu', $this->source('menu', [$this->screen('a', 'Alpha Report'), $this->screen('slip', 'Slip Gaji')]));
        $this->app->tag(['src-menu'], 'devlib.index-source');
        $this->app->instance(DataPolicy::class, new DataPolicy);
        $this->app->make(Kernel::class)->registerCommand($this->app->make(\Devlib\Console\Index::class));

        $this->artisan('devlib:index', ['--force' => true])->expectsOutputToContain('menu: disimpan 1, tak berubah 0, dibuang 0, dilewati kebijakan 1')->assertSuccessful();
        $this->assertSame(['a'], array_keys($store->hashes('menu')));
        $this->artisan('devlib:index')->assertSuccessful();  // klaim pertama di cache
        $this->artisan('devlib:index')->expectsOutputToContain('Dilewati')->assertSuccessful();
    }
}
