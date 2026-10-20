<?php

namespace Tests\Feature;

use Devlib\Assistant\Asker;
use Devlib\Assistant\Assistant;
use Devlib\Assistant\AssistantException;
use Devlib\Assistant\EngineTool;
use Devlib\Assistant\Knowledge;
use Devlib\Assistant\LanguageModel;
use Devlib\Assistant\MemoryHistory;
use Devlib\Assistant\Navigator;
use Devlib\Assistant\Reply;
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

    private function assistant(?LanguageModel $model = null, int $minLevel = 0, ?MemoryHistory $history = null): Assistant
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

        return new Assistant($nav, $kb, [$tool], $model ?? new \Devlib\Assistant\NullModel, $history);
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
