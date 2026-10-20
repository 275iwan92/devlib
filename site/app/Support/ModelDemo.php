<?php

namespace App\Support;

use Devlib\Assistant\Asker;
use Devlib\Assistant\Assistant;
use Devlib\Assistant\EngineTool;
use Devlib\Assistant\Knowledge;
use Devlib\Assistant\MemoryHistory;
use Devlib\Assistant\Navigator;
use Devlib\Chat\ChatService;
use Devlib\Chat\Directory;
use Devlib\Chat\MemoryChat;
use Devlib\Chat\Person;
use Devlib\Engine\EngineRegistry;

// Data fiktif milik situs untuk dua model DevLib: Assistant AI (Devlib\Assistant) dan Chat (Devlib\Chat).
// Semua adaptor di sini dalam memori; aplikasi nyata mengikat adaptor berbasis database (lihat skills/devlib/assistant.md dan chat.md).
class ModelDemo
{
    public static function asker(): Asker
    {
        return new class implements Asker
        {
            public function id(): string { return 'demo-admin'; }

            public function name(): string { return 'Demo Admin'; }

            public function level(): int { return 5; }

            public function can(string $module): bool { return true; }
        };
    }

    public static function assistant(): Assistant
    {
        $navigator = new class implements Navigator
        {
            public function screens(Asker $asker): array
            {
                return [
                    ['key' => 'grid', 'label' => 'Grid', 'url' => '/lib/grid', 'path' => 'Components › Grid', 'about' => 'Data list with search, filter and export.', 'help' => 'grid'],
                    ['key' => 'engines', 'label' => 'Engine Catalog', 'url' => '/lib/p9-engines', 'path' => 'Engines › Engine Catalog', 'about' => 'Every engine with its input and output.'],
                ];
            }
        };
        $knowledge = new class implements Knowledge
        {
            public function topics(Asker $asker): array
            {
                return [['key' => 'grid', 'title' => 'Grid', 'purpose' => 'Find, filter and open records of one list.', 'words' => 'list table records',
                    'steps' => ['Type in Search to narrow the rows.', 'Use the stat boxes to filter by status.', 'Double-click a row to open it.']]];
            }
        };
        $engines = app(EngineRegistry::class);
        $discount = new EngineTool($engines, 'volume-discount', ['volume discount', 'diskon volume'],
            fn (Asker $a, string $q) => ['qty' => (int) (preg_match('/\d+/', $q, $m) ? $m[0] : 0), 'unit_price' => 10000]);

        return new Assistant($navigator, $knowledge, [$discount], history: new MemoryHistory);
    }

    /** @return list<string> */
    public static function questions(): array
    {
        return ['volume discount for 600 pieces', 'open engine catalog', 'how to use grid', 'what is the weather in Paris', 'show me the grid list'];
    }

    /** ChatService di atas data memori dengan lima orang fiktif; kembalian = data siap untuk <x-dl::chat>. */
    public static function chat(): array
    {
        $mem = new MemoryChat;
        $directory = new class implements Directory
        {
            public function people(): array
            {
                return [new Person(1, 'Demo Admin', 'Administrator'), new Person(2, 'Rina Wijaya', 'Sales'), new Person(3, 'Andi Pratama', 'Finance'),
                    new Person(4, 'Sari Kusuma', 'PPIC'), new Person(5, 'Joko Santoso', 'Production')];
            }

            public function find(string|int $id): ?Person { return collect($this->people())->first(fn ($p) => (string) $p->id === (string) $id); }
        };
        $service = new ChatService($directory, $mem, $mem, $mem);
        foreach ([2, 3, 4] as $online) {
            $service->boot($online);
        }
        $service->send(2, 1, 'Is the order from PT Contoh in yet?');
        $service->send(1, 2, 'Checking now.');
        $service->send(2, 1, 'Thanks, please tell me the delivery date.');
        $service->send(4, 1, 'Printing WO-014 can move to Wednesday.');

        return $service->boot(1);
    }
}
