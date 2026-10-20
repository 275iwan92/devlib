<?php

namespace Tests\Feature;

use Devlib\Engine\Adjustable;
use Devlib\Engine\Cancellation;
use Devlib\Engine\DocumentCancelled;
use Devlib\Engine\Engine;
use Devlib\Engine\EngineException;
use Devlib\Engine\EngineRegistry;
use Devlib\Engine\Mode;
use Devlib\Engine\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

// Kontrak engine DevLib (src/Engine): registry, aturan mode Suggest, dan alur CANCEL + ADJUSTMENT otomatis.
class EngineContractTest extends TestCase
{
    private function engine(string $key, Mode $mode, Result $result): Engine
    {
        return new class($key, $mode, $result) implements Engine
        {
            public function __construct(private string $k, private Mode $m, private Result $r) {}

            public function key(): string { return $this->k; }

            public function name(): string { return 'Test '.$this->k; }

            public function group(): string { return 'Test'; }

            public function mode(): Mode { return $this->m; }

            public function purpose(): string { return 'Test engine'; }

            public function run(array $input): Result { return $this->r; }
        };
    }

    public function test_registry_runs_lists_and_rejects_duplicates(): void
    {
        $reg = (new EngineRegistry)->register($this->engine('calc', Mode::Calc, Result::applied(['n' => 3], ['because'])));

        $this->assertSame(3, $reg->run('calc')->data['n']);
        $row = $reg->catalog()[0];
        $this->assertSame('calc', $row['mode']);
        $this->assertSame('run', $row['entries'][0]['method']);
        $this->expectException(EngineException::class);
        $reg->register($this->engine('calc', Mode::Calc, Result::applied()));
    }

    public function test_suggest_engine_cannot_apply_by_itself(): void
    {
        $reg = (new EngineRegistry)->register(
            $this->engine('ok', Mode::Suggest, Result::draft(['x' => 1], ['reason'])),
            $this->engine('bad', Mode::Suggest, Result::applied()),
        );

        $this->assertTrue($reg->run('ok')->isDraft());
        $this->expectException(EngineException::class);
        $reg->run('bad');
    }

    public function test_cancel_keeps_record_and_creates_adjustment_in_one_transaction(): void
    {
        Schema::create('docs', function ($t) { $t->id(); $t->string('status'); });
        DB::table('docs')->insert(['status' => 'confirmed']);
        Event::fake([DocumentCancelled::class]);
        $doc = new class implements Adjustable
        {
            public bool $fail = false;

            public function createAdjustment(): ?Result
            {
                if ($this->fail) {
                    return Result::rejected('Stock is already issued.');
                }
                DB::table('docs')->insert(['status' => 'adjustment']);

                return Result::applied(['status' => 'adjustment'], ['Reverses the cancelled document.']);
            }
        };
        $mark = fn () => DB::table('docs')->where('id', 1)->update(['status' => 'cancelled']);

        $result = Cancellation::run($doc, $mark);

        $this->assertTrue($result->isApplied());
        $this->assertSame('cancelled', DB::table('docs')->where('id', 1)->value('status'));  // tetap tersimpan
        $this->assertSame(1, DB::table('docs')->where('status', 'adjustment')->count());
        Event::assertDispatched(DocumentCancelled::class);

        DB::table('docs')->insert(['status' => 'confirmed']);
        $doc->fail = true;
        try {
            Cancellation::run($doc, fn () => DB::table('docs')->where('id', 3)->update(['status' => 'cancelled']));
            $this->fail('Rejected adjustment must abort the cancellation.');
        } catch (EngineException) {
            $this->assertSame('confirmed', DB::table('docs')->where('id', 3)->value('status'));  // ikut batal
        }
    }
}
