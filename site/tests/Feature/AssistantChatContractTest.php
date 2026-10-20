<?php

namespace Tests\Feature;

use Devlib\Assistant\Asker;
use Devlib\Assistant\Assistant;
use Devlib\Assistant\AssistantException;
use Devlib\Assistant\Classified;
use Devlib\Assistant\DataPolicy;
use Devlib\Assistant\EngineTool;
use Devlib\Assistant\Knowledge;
use Devlib\Assistant\LanguageModel;
use Devlib\Assistant\MemoryHistory;
use Devlib\Assistant\Navigator;
use Devlib\Assistant\Reply;
use Devlib\Assistant\Sensitivity;
use Devlib\Assistant\Tool;
use Devlib\Assistant\ScopedValueAsker;
use Devlib\Assistant\ValueAsker;
use Devlib\Chat\ChatException;
use Devlib\Chat\ChatService;
use Devlib\Chat\Directory;
use Devlib\Chat\MemoryChat;
use Devlib\Chat\Person;
use Devlib\Engine\EngineRegistry;
use Tests\TestCase;

// Model Assistant AI (Devlib\Assistant) dan Chat (Devlib\Chat): urutan jawab, gerbang hak, aturan engine, kehadiran, lampiran.
class AssistantChatContractTest extends TestCase
{
    private function asker(int $level = 1, array $modules = ['sales']): Asker
    {
        return new class($level, $modules) implements Asker
        {
            public function __construct(private int $l, private array $m) {}

            public function id(): string { return 'u1'; }

            public function name(): string { return 'Demo'; }

            public function level(): int { return $this->l; }

            public function can(string $module): bool { return in_array($module, $this->m, true); }
        };
    }

    private function assistant(?LanguageModel $model = null, int $minLevel = 0, ?MemoryHistory $history = null, ?string $outOfScope = null): Assistant
    {
        $registry = app(EngineRegistry::class);
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
        $tool = new EngineTool($registry, 'volume-discount', ['volume discount'], fn () => ['qty' => 600, 'unit_price' => 1000], 'sales', $minLevel);

        return new Assistant($nav, $kb, [$tool], $model ?? new \Devlib\Assistant\NullModel, $history, $outOfScope);
    }

    public function test_tool_answers_from_the_engine_and_checks_access(): void
    {
        $reply = $this->assistant()->ask($this->asker(), 'what is the volume discount?');
        $this->assertSame(Reply::NUMBER, $reply->kind);
        $this->assertSame('volume-discount', $reply->key);
        $this->assertNotEmpty($reply->rows);

        $this->assertSame(Reply::DENIED, $this->assistant()->ask($this->asker(modules: []), 'volume discount')->kind);
        $this->assertSame(Reply::DENIED, $this->assistant(minLevel: 5)->ask($this->asker(level: 1), 'volume discount')->kind);
    }

    public function test_screens_help_and_unknown(): void
    {
        $a = $this->assistant();
        $this->assertSame(Reply::SCREEN, $a->ask($this->asker(), 'open stock overview')->kind);
        $how = $a->ask($this->asker(), 'how to use stock overview');
        $this->assertSame(Reply::HELP, $how->kind);
        $this->assertSame(['Open Stock Overview', 'Pick a location'], $how->steps);
        $this->assertSame(Reply::UNKNOWN, $a->ask($this->asker(), 'weather in Paris')->kind);
    }

    public function test_out_of_scope_is_a_fixed_sentence_only_when_set_and_only_when_nothing_relates(): void
    {
        $sentence = 'Saya hanya memahami perihal program ini, lainnya saya belum paham';
        $a = $this->assistant(outOfScope: $sentence);

        $out = $a->ask($this->asker(), 'cuaca di Surabaya besok');
        $this->assertSame(Reply::OUT_OF_SCOPE, $out->kind);
        $this->assertSame($sentence, $out->title);
        $this->assertFalse($out->answered());
        $this->assertSame(['kind' => Reply::OUT_OF_SCOPE, 'title' => $sentence], $out->toArray());

        // dalam lingkup (ada kata yang dikenal) tetapi belum ada jawabannya: tetap UNKNOWN, bukan di luar lingkup
        $this->assertSame(Reply::UNKNOWN, $a->ask($this->asker(), 'how to print stock labels with a logo')->kind);
        // tanpa pengaturan (bawaan, opt-in) perilaku lama: UNKNOWN
        $this->assertSame(Reply::UNKNOWN, $this->assistant()->ask($this->asker(), 'cuaca di Surabaya besok')->kind);

        // jawaban yang sudah ada tidak berubah
        $this->assertSame(Reply::NUMBER, $a->ask($this->asker(), 'volume discount')->kind);
        $this->assertSame(Reply::SCREEN, $a->ask($this->asker(), 'open stock overview')->kind);
        $this->assertSame(Reply::HELP, $a->ask($this->asker(), 'how to use stock overview')->kind);
        $this->assertSame(Reply::DENIED, $a->ask($this->asker(modules: []), 'volume discount')->kind);
    }

    public function test_language_model_only_picks_among_candidates(): void
    {
        $model = new class implements LanguageModel
        {
            public array $seen = [];

            public function available(): bool { return true; }

            public function choose(string $question, array $options, array $history = []): ?string
            {
                $this->seen = $options;

                return array_key_first($options);
            }
        };
        $reply = $this->assistant($model)->ask($this->asker(), 'inventory somewhere');
        $this->assertNotSame([], $model->seen);
        $this->assertContains($reply->kind, [Reply::SCREEN, Reply::HELP]);
    }

    public function test_history_is_stored_per_asker_and_feedback_is_own_only(): void
    {
        $a = $this->assistant(history: new MemoryHistory);
        $reply = $a->ask($this->asker(), 'open stock overview');
        $this->assertCount(1, $a->history($this->asker()));
        $this->assertTrue($a->feedback($this->asker(), $reply->id, -1));
        $this->assertSame(-1, $a->history($this->asker())[0]['feedback']);
        $other = new class implements Asker
        {
            public function id(): string { return 'u2'; }

            public function name(): string { return 'Other'; }

            public function level(): int { return 0; }

            public function can(string $module): bool { return false; }
        };
        $this->assertSame([], $a->history($other));
        $this->assertFalse($a->feedback($other, $reply->id, 1));
    }

    public function test_only_calc_and_suggest_engines_become_tools(): void
    {
        $this->expectException(AssistantException::class);
        new EngineTool(app(EngineRegistry::class), 'status-flow', ['x'], fn () => []);
    }

    // --- cakupan data dan sensitivitas (skills/devlib/assistant.md) ---

    private function finance(bool $value = true, array $modules = ['inventory', 'hrm', 'payroll', 'accounting']): Asker
    {
        return new class($value, $modules) implements ValueAsker
        {
            public function __construct(private bool $v, private array $m) {}

            public function id(): string { return 'f1'; }

            public function name(): string { return 'Finance'; }

            public function level(): int { return 9; }

            public function can(string $module): bool { return in_array($module, $this->m, true); }

            public function canSeeValue(): bool { return $this->v; }
        };
    }

    /** Tool kecil; $calls->n menghitung berapa kali answer() jalan. $sensitivity null = Tool lama tanpa Classified. */
    private function tool(string $key, ?string $module, array $phrases, ?Sensitivity $sensitivity, ?object $calls = null): Tool
    {
        $calls ??= new \stdClass;
        $calls->n = 0;
        $base = new class($key, $module, $phrases, $calls) implements Tool
        {
            public function __construct(private string $k, private ?string $m, private array $p, private object $calls) {}

            public function key(): string { return $this->k; }

            public function title(): string { return ucfirst($this->k); }

            public function phrases(): array { return $this->p; }

            public function module(): ?string { return $this->m; }

            public function minLevel(): int { return 0; }

            public function answer(Asker $asker, string $question): Reply
            {
                $this->calls->n++;

                return new Reply(Reply::NUMBER, $this->title(), big: '42');
            }
        };
        if ($sensitivity === null) {
            return $base;
        }

        return new class($base, $sensitivity) implements Classified, Tool
        {
            public function __construct(private Tool $t, private Sensitivity $s) {}

            public function sensitivity(): Sensitivity { return $this->s; }

            public function key(): string { return $this->t->key(); }

            public function title(): string { return $this->t->title(); }

            public function phrases(): array { return $this->t->phrases(); }

            public function module(): ?string { return $this->t->module(); }

            public function minLevel(): int { return $this->t->minLevel(); }

            public function answer(Asker $asker, string $question): Reply { return $this->t->answer($asker, $question); }
        };
    }

    private function withTools(array $tools, ?DataPolicy $policy = null, ?Navigator $nav = null): Assistant
    {
        $nav ??= new class implements Navigator
        {
            public function screens(Asker $asker): array { return []; }
        };
        $kb = new class implements Knowledge
        {
            public function topics(Asker $asker): array { return []; }
        };

        return new Assistant($nav, $kb, $tools, policy: $policy);
    }

    public function test_quantity_is_answered_by_module_access_and_value_only_for_finance(): void
    {
        $a = $this->withTools([
            $this->tool('stock-count', 'inventory', ['stock count'], Sensitivity::Quantity),
            $this->tool('stock-worth', 'inventory', ['stock worth'], Sensitivity::Value),
        ]);
        $plain = $this->asker(modules: ['inventory']);

        $this->assertSame(Reply::NUMBER, $a->ask($plain, 'stock count')->kind);
        $this->assertSame(Reply::DENIED, $a->ask($this->asker(modules: []), 'stock count')->kind, 'quantity still needs module access');

        $this->assertSame(Reply::DENIED, $a->ask($plain, 'stock worth')->kind, 'Asker without ValueAsker: safest default = no value');
        $this->assertSame(Reply::DENIED, $a->ask($this->finance(value: false), 'stock worth')->kind);
        $this->assertSame(Reply::NUMBER, $a->ask($this->finance(), 'stock worth')->kind);
        $this->assertSame(Reply::DENIED, $a->ask($this->finance(modules: []), 'stock worth')->kind, 'finance still needs module access');
    }

    public function test_tools_without_a_declared_sensitivity_behave_as_before(): void
    {
        $legacy = $this->tool('stock-worth', 'inventory', ['stock worth'], null);
        $this->assertSame(Reply::NUMBER, $this->withTools([$legacy])->ask($this->asker(modules: ['inventory']), 'stock worth')->kind);
        // tanpa DataPolicy tidak ada daftar kata terlarang: perilaku lama (proyek yang belum memakainya)
        $odd = $this->tool('payroll-report', 'payroll', ['payroll report'], null);
        $this->assertSame(Reply::NUMBER, $this->withTools([$odd])->ask($this->asker(modules: ['payroll']), 'payroll report')->kind);
    }

    public function test_payroll_cannot_be_registered(): void
    {
        $policy = new DataPolicy;
        foreach ([['payroll-total', 'hrm'], ['total', 'hrm.payroll'], ['slip-gaji', null], ['x', 'Salary']] as [$key, $module]) {
            try {
                $this->withTools([$this->tool($key, $module, ['total'], Sensitivity::Quantity)], $policy);
                $this->fail("Expected AssistantException for [{$key}] / [{$module}]");
            } catch (AssistantException $e) {
                $this->assertStringContainsString('salary / payroll', $e->getMessage());
            }
        }
        // frasa pemicu yang menyebut gaji ditolak; kata tambahan milik program ikut berlaku
        foreach ([[['berapa gaji bulan ini'], $policy], [['bonus'], new DataPolicy(['bonus'])]] as [$phrases, $p]) {
            try {
                $this->withTools([$this->tool('total', 'hrm', $phrases, Sensitivity::Quantity)], $p);
                $this->fail('Expected AssistantException');
            } catch (AssistantException) {
                $this->addToAssertionCount(1);
            }
        }
        // sensitivitas Forbidden ditolak tanpa DataPolicy sekalipun
        $this->expectException(AssistantException::class);
        $this->withTools([$this->tool('anything', 'hrm', ['anything'], Sensitivity::Forbidden)]);
    }

    public function test_payroll_questions_screens_are_never_served_even_to_an_asker_with_all_rights(): void
    {
        $calls = new \stdClass;
        $nav = new class implements Navigator
        {
            public function screens(Asker $asker): array
            {
                return [['key' => 'payroll-run', 'label' => 'Pay Run', 'url' => '/pay', 'words' => 'pay run'],
                    ['key' => 'attendance', 'label' => 'Attendance Board', 'url' => '/att', 'words' => 'absensi kehadiran']];
            }
        };
        $a = $this->withTools([$this->tool('attendance-today', 'hrm', ['absensi hari ini'], Sensitivity::Quantity, $calls)], new DataPolicy, $nav);
        $all = $this->finance();

        foreach (['berapa gaji bulan ini', 'where is the payroll menu', 'slip gaji Budi', 'show salary report'] as $q) {
            $this->assertSame(Reply::DENIED, $a->ask($all, $q)->kind, $q);
        }
        $this->assertNotSame(Reply::SCREEN, $a->ask($all, 'open pay run')->kind, 'screen declared as payroll is not offered');
        $this->assertSame(Reply::SCREEN, $this->withTools([], null, $nav)->ask($all, 'open pay run')->kind, 'without DataPolicy the old behaviour stays');

        // absensi / kehadiran BUKAN payroll: tetap dijawab
        $this->assertSame(Reply::NUMBER, $a->ask($all, 'absensi hari ini')->kind);
        $this->assertSame(1, $calls->n);
        $this->assertSame(Reply::SCREEN, $a->ask($all, 'open attendance board')->kind);
    }

    public function test_costing_formula_is_forbidden_but_its_result_value_is_only_for_value_askers(): void
    {
        $policy = new DataPolicy;
        foreach ([['hpp-formula', 'costing'], ['hpp_formula', null], ['x', 'Costing Formula'], ['x', 'rumus-hpp']] as [$key, $module]) {
            try {
                $this->withTools([$this->tool($key, $module, ['total'], Sensitivity::Value)], $policy);
                $this->fail("Expected AssistantException for [{$key}] / [{$module}]");
            } catch (AssistantException $e) {
                $this->assertStringContainsString('costing formula', $e->getMessage());
            }
        }

        // NILAI HASIL HPP bukan formulanya: tingkat nilai, dijawab untuk keuangan saja
        $calls = new \stdClass;
        $a = $this->withTools([$this->tool('hpp-result', 'costing', ['hpp produk'], Sensitivity::Value, $calls)], $policy);
        $this->assertSame(Reply::DENIED, $a->ask($this->asker(modules: ['costing']), 'hpp produk')->kind);
        $this->assertSame(0, $calls->n);
        $this->assertSame(Reply::NUMBER, $a->ask($this->finance(modules: ['costing']), 'hpp produk')->kind);

        // pertanyaan tentang formulanya tidak pernah dijawab, siapa pun penanyanya
        foreach (['bagaimana rumus HPP produk A', 'cara hitung HPP', 'show the costing formula', 'parameter hpp bulan ini'] as $q) {
            $this->assertSame(Reply::DENIED, $a->ask($this->finance(modules: ['costing']), $q)->kind, $q);
        }
        $this->assertSame(1, $calls->n);
    }

    public function test_value_rights_can_be_split_per_tool_so_sales_sees_only_the_hpp_result(): void
    {
        $a = $this->withTools([
            $this->tool('hpp-result', 'costing', ['hpp produk'], Sensitivity::Value),
            $this->tool('stock-value', 'inventory', ['stock worth'], Sensitivity::Value),
        ], new DataPolicy);

        $sales = new class implements ScopedValueAsker
        {
            public function id(): string { return 's1'; }

            public function name(): string { return 'Sales'; }

            public function level(): int { return 9; }

            public function can(string $module): bool { return true; }

            public function canSeeValue(): bool { return false; }

            public function canSeeValueOf(string $toolKey): bool { return $toolKey === 'hpp-result'; }
        };

        $this->assertSame(Reply::NUMBER, $a->ask($sales, 'hpp produk')->kind, 'sales sees the HPP result');
        $this->assertSame(Reply::DENIED, $a->ask($sales, 'stock worth')->kind, 'but not other monetary values');

        $finance = $this->finance(modules: ['costing', 'inventory']);   // ValueAsker global: perilaku sebelumnya, semua nilai
        $this->assertSame(Reply::NUMBER, $a->ask($finance, 'hpp produk')->kind);
        $this->assertSame(Reply::NUMBER, $a->ask($finance, 'stock worth')->kind);

        $noValue = $this->finance(false, ['costing', 'inventory']);
        $this->assertSame(Reply::DENIED, $a->ask($noValue, 'hpp produk')->kind);
    }

    public function test_a_program_without_forbidden_modules_is_not_limited_at_all(): void
    {
        $calls = new \stdClass;
        $tools = [$this->tool('stock-count', 'inventory', ['stock count'], null, $calls), $this->tool('open-orders', 'sales', ['open orders'], Sensitivity::Quantity)];
        foreach ([null, new DataPolicy, new DataPolicy(['bonus'])] as $policy) {
            $a = $this->withTools($tools, $policy);
            $this->assertSame(Reply::NUMBER, $a->ask($this->asker(modules: ['inventory', 'sales']), 'stock count')->kind);
            $this->assertSame(Reply::NUMBER, $a->ask($this->asker(modules: ['inventory', 'sales']), 'open orders')->kind);
        }
        $this->assertSame(3, $calls->n, 'answered once per policy variant');
    }

    // --- data pribadi dan orang terlindung (skills/devlib/assistant.md) ---

    /** @return array{0: Assistant, 1: object, 2: object} asisten, hitungan Tool, model pengintai */
    private function personalSetup(?DataPolicy $policy, ?MemoryHistory $history = null): array
    {
        $calls = new \stdClass;
        $model = new class implements LanguageModel
        {
            public array $seen = [];

            public function available(): bool { return true; }

            public function choose(string $question, array $options, array $history = []): ?string
            {
                $this->seen[] = [$question, $history];

                return null;
            }
        };
        $nav = new class implements Navigator
        {
            public function screens(Asker $asker): array
            {
                return [['key' => 'my-profile', 'label' => 'My Profile', 'url' => '/me', 'words' => 'kata sandi password data pribadi saya'],
                    ['key' => 'attendance', 'label' => 'Attendance Board', 'url' => '/att', 'words' => 'absensi kehadiran']];
            }
        };
        $kb = new class implements Knowledge
        {
            public function topics(Asker $asker): array { return []; }
        };
        $tools = [$this->tool('customer-address', 'sales', ['alamat pelanggan'], Sensitivity::Quantity, $calls),
            $this->tool('attendance-today', 'hrm', ['absensi hari ini'], Sensitivity::Quantity, $calls),
            $this->tool('orders-by-person', 'sales', ['order dari'], Sensitivity::Quantity, $calls)];

        return [new Assistant($nav, $kb, $tools, $model, $history, policy: $policy), $calls, $model];
    }

    public function test_personal_data_questions_are_denied_for_everyone_and_reach_no_tool_or_model(): void
    {
        [$a, $calls, $model] = $this->personalSetup(new DataPolicy);
        $all = $this->finance(modules: ['sales', 'hrm']);

        foreach (['berapa nomor KTP Budi Santoso', 'NIK karyawan', 'alamat rumah pak Budi', 'tanggal lahir saya', 'show the date of birth of staff',
            'rekening pribadi karyawan', 'personal data karyawan', 'email pribadi budi', 'riwayat penyakit staf', 'ktp pelanggan'] as $q) {
            $reply = $a->ask($all, $q);
            $this->assertSame(Reply::DENIED, $reply->kind, $q);
            $this->assertNull($reply->big, $q);
        }
        $this->assertSame(0, $calls->n, 'no Tool ran for a personal-data question');
        $this->assertSame([], $model->seen, 'the language model never received a refused question');

        // data diri sendiri: tidak dibacakan, tetapi LAYAR tempat mengelolanya boleh ditunjukkan (pengetahuan menu)
        $this->assertSame('my-profile', $a->ask($all, 'data pribadi saya')->key);
        $screen = $a->ask($all, 'open my profile kata sandi');
        $this->assertSame(Reply::SCREEN, $screen->kind);
        $this->assertSame('my-profile', $screen->key);
    }

    public function test_business_questions_with_address_words_and_attendance_still_work(): void
    {
        [$a, $calls] = $this->personalSetup(new DataPolicy);
        $all = $this->finance(modules: ['sales', 'hrm']);

        $this->assertSame(Reply::NUMBER, $a->ask($all, 'alamat pelanggan PT Maju')->kind);
        $this->assertSame(Reply::NUMBER, $a->ask($all, 'absensi hari ini')->kind);
        $this->assertSame(Reply::NUMBER, $a->ask($all, 'unik teknik nikmat absensi hari ini')->kind, '"nik" only matches as a whole word');
        $this->assertSame(Reply::SCREEN, $a->ask($all, 'open attendance board')->kind);
        $this->assertSame(3, $calls->n);
    }

    public function test_protected_people_are_denied_in_any_spelling_and_run_no_tool(): void
    {
        $policy = new DataPolicy(protected: ['Budi Santoso', 'budi.s', 'Pak Budi']);
        [$a, $calls, $model] = $this->personalSetup($policy);
        $all = $this->finance(modules: ['sales', 'hrm']);

        foreach (['order dari Budi Santoso', 'ORDER DARI BUDI  SANTOSO', 'order dari santoso, budi', 'order dari budi-santoso', 'order dari budi_santoso',
            'absensi hari ini budi.s', 'absensi hari ini BUDI S', 'where is pak budi', 'open attendance board of Budi Santoso'] as $q) {
            $this->assertSame(Reply::DENIED, $a->ask($all, $q)->kind, $q);
        }
        $this->assertSame(0, $calls->n);
        $this->assertSame([], $model->seen);

        // pemilik data itu sendiri pun tidak dilayani (tidak ada pengecualian)
        $this->assertSame(Reply::DENIED, $a->ask($this->asker(modules: ['sales']), 'order dari Budi Santoso')->kind);

        // sebagian nama saja tidak otomatis cocok (akan memblokir orang lain yang senama); alias didaftarkan sebagai entri sendiri
        $this->assertSame(Reply::NUMBER, $a->ask($all, 'order dari Santoso Jaya')->kind);
        $this->assertSame(Reply::NUMBER, $a->ask($all, 'order dari budiman')->kind, 'whole words only');
    }

    public function test_protected_and_personal_entries_cannot_be_registered_as_tools(): void
    {
        try {
            $this->withTools([$this->tool('orders-budi', 'sales', ['order budi'], Sensitivity::Quantity)], new DataPolicy(protected: ['budi']));
            $this->fail('Expected AssistantException');
        } catch (AssistantException $e) {
            $this->assertStringContainsString('personal data', $e->getMessage());
        }
        $this->expectException(AssistantException::class);
        $this->withTools([$this->tool('dossier', 'hrm', ['data pribadi'], Sensitivity::Quantity)], new DataPolicy);
    }

    public function test_without_a_data_policy_personal_and_protected_wording_behaves_as_before(): void
    {
        [$a, , $model] = $this->personalSetup(null);
        $all = $this->finance(modules: ['sales', 'hrm']);

        $this->assertSame(Reply::NUMBER, $a->ask($all, 'alamat pelanggan nik tanggal lahir')->kind);
        $a->ask($all, 'kata sandi di mana');   // tanpa DataPolicy model tetap dipakai seperti sebelumnya
        $this->assertNotSame([], $model->seen);

        // DataPolicy tanpa daftar terlindung: nama tidak apa-apa
        [$b] = $this->personalSetup(new DataPolicy);
        $this->assertSame(Reply::NUMBER, $b->ask($all, 'order dari Budi Santoso')->kind);
    }

    public function test_refused_questions_in_history_never_reach_the_language_model_as_context(): void
    {
        $history = new MemoryHistory;
        [$a, , $model] = $this->personalSetup(new DataPolicy(protected: ['Budi Santoso']), $history);
        $all = $this->finance(modules: ['sales', 'hrm']);

        $a->ask($all, 'nomor KTP Budi Santoso');
        $a->ask($all, 'order dari Budi Santoso');
        $a->ask($all, 'attendance boards');   // sampai ke model
        $this->assertNotSame([], $model->seen);
        foreach ($model->seen as [$question, $context]) {
            $this->assertStringNotContainsStringIgnoringCase('budi', $question.json_encode($context));
            $this->assertStringNotContainsStringIgnoringCase('ktp', json_encode($context));
        }
    }

    private function chat(?MemoryChat $mem = null): array
    {
        $mem ??= new MemoryChat(onlineSeconds: 60, clock: fn () => 1000);
        $dir = new class implements Directory
        {
            public function people(): array { return [new Person(1, 'Ana', 'Sales'), new Person(2, 'Budi', 'Finance'), new Person(3, 'Citra')]; }

            public function find(string|int $id): ?Person { return collect($this->people())->first(fn ($p) => (string) $p->id === (string) $id); }
        };

        return [new ChatService($dir, $mem, $mem, $mem, maxChars: 20, maxMb: 1), $mem];
    }

    public function test_chat_sends_reads_and_reports_presence(): void
    {
        [$chat] = $this->chat();
        $chat->boot(1);
        $chat->boot(2);
        $chat->send(1, 2, 'hello');
        $chat->send(2, 1, 'hi');

        $boot = $chat->boot(1);
        $this->assertSame(1, $boot['convos'][2]['unread']);
        $this->assertTrue(collect($boot['people'])->firstWhere('id', 2)['online']);
        $this->assertFalse(collect($boot['people'])->firstWhere('id', 3)['online']);
        $this->assertArrayNotHasKey(3, $boot['convos']);

        $poll = $chat->poll(1, 0, open: 2);
        $this->assertSame(2, $poll['msgs'][1]['peer']);
        $this->assertSame(0, $chat->boot(1)['convos'][2]['unread']);
        $this->assertSame(2, $chat->poll(2)['read'][1], 'Ana opened the chat: Budi sees his message 2 as read by her');
        $this->assertSame(0, $chat->poll(1)['read'][2], 'Budi has not opened the chat: Ana message 1 is not read');

        $chat->signOut(2);
        $this->assertFalse($chat->poll(1)['presence'][2][0]);
    }

    public function test_chat_rules_and_attachments(): void
    {
        [$chat] = $this->chat();
        foreach ([fn () => $chat->send(1, 1, 'me'), fn () => $chat->send(1, 99, 'x'), fn () => $chat->send(1, 2, '  '),
            fn () => $chat->send(1, 2, str_repeat('a', 21))] as $bad) {
            try {
                $bad();
                $this->fail('Expected ChatException');
            } catch (ChatException) {
                $this->addToAssertionCount(1);
            }
        }

        $this->assertSame(3, $chat->upload(1, 'u-1', 0, 'abc')['received']);
        $this->expectException(ChatException::class);
        $chat->upload(1, 'u-1', 0, 'out of order');
    }

    public function test_chat_attachment_message_carries_file(): void
    {
        [$chat] = $this->chat();
        $chat->upload(1, 'u-2', 0, 'PNGDATA');
        $msg = $chat->send(1, 2, '', 'u-2', 'photo.png')['msg'];
        $this->assertSame('photo.png', $msg['file']['name']);
        $this->assertTrue($msg['file']['image']);
        $this->assertSame(7, $msg['file']['size']);
    }
}
