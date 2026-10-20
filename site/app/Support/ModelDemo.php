<?php

namespace App\Support;

use Devlib\Assistant\Asker;
use Devlib\Assistant\Assistant;
use Devlib\Assistant\EngineTool;
use Devlib\Assistant\Knowledge;
use Devlib\Assistant\MemoryHistory;
use Devlib\Assistant\Navigator;
use Devlib\Assistant\Sensitivity;
use Devlib\Assistant\ValueAsker;
use Devlib\Chat\ChatService;
use Devlib\Chat\Directory;
use Devlib\Chat\MemoryChat;
use Devlib\Chat\Person;
use Devlib\Engine\Engine;
use Devlib\Engine\EngineRegistry;
use Devlib\Engine\Mode;
use Devlib\Engine\Result;

// Data fiktif milik situs untuk dua model DevLib: Assistant AI (Devlib\Assistant) dan Chat (Devlib\Chat).
// Semua adaptor di sini dalam memori; aplikasi nyata mengikat adaptor berbasis database (lihat skills/devlib/assistant.md dan chat.md).
class ModelDemo
{
    public static function asker(): ValueAsker
    {
        return new class implements ValueAsker
        {
            public function id(): string { return 'demo-admin'; }

            public function name(): string { return 'Demo Admin'; }

            public function level(): int { return 5; }

            public function can(string $module): bool { return true; }

            public function canSeeValue(): bool { return true; }
        };
    }

    public static function assistant(): Assistant
    {
        $navigator = new class implements Navigator
        {
            public function screens(Asker $asker): array
            {
                return [
                    ['key' => 'po', 'label' => 'Purchase Order', 'url' => '/lib/grid', 'path' => 'Purchasing › Purchase Order', 'words' => 'po buat membuat menu pembelian',
                        'about' => 'Create and follow purchase orders.', 'help' => 'po'],
                    ['key' => 'grid', 'label' => 'Grid', 'url' => '/lib/grid', 'path' => 'Components › Grid', 'about' => 'Data list with search, filter and export.', 'help' => 'grid'],
                ];
            }
        };
        $knowledge = new class implements Knowledge
        {
            public function topics(Asker $asker): array
            {
                return [
                    ['key' => 'po', 'title' => 'Purchase Order', 'purpose' => 'Order items from a supplier.', 'words' => 'po buat membuat menu pembelian',
                        'steps' => ['Open Purchasing › Purchase Order.', 'Click New and pick the supplier.', 'Add the items and quantities.', 'Save, then send it for approval.']],
                    ['key' => 'grid', 'title' => 'Grid', 'purpose' => 'Find, filter and open records of one list.', 'words' => 'list table records',
                        'steps' => ['Type in Search to narrow the rows.', 'Use the stat boxes to filter by status.', 'Double-click a row to open it.']],
                ];
            }
        };
        // Engine Calc contoh yang hanya MEMBACA data dalam memori (12 order hari ini, 5 kemarin); registri sendiri, tidak masuk Engine Catalog.
        $orders = [...array_map(fn ($i) => ['date' => date('Y-m-d'), 'total' => 1_250_000 + $i * 175_000], range(1, 12)),
            ...array_map(fn ($i) => ['date' => date('Y-m-d', strtotime('-1 day')), 'total' => 900_000 + $i * 50_000], range(1, 5))];
        $engines = (new EngineRegistry)->register(new class($orders) implements Engine
        {
            public function __construct(private array $orders) {}

            public function key(): string { return 'orders-today'; }

            public function name(): string { return 'Orders Today'; }

            public function group(): string { return 'Sales'; }

            public function mode(): Mode { return Mode::Calc; }

            public function purpose(): string { return 'Counts the orders received on a date and adds up their value.'; }

            public function run(array $input): Result
            {
                $day = array_filter($this->orders, fn ($o) => $o['date'] === ($input['date'] ?? ''));
                $total = array_sum(array_column($day, 'total'));

                return Result::applied(['Orders' => count($day), 'Total value' => 'Rp '.number_format($total, 0, ',', '.')], ['Counted from the orders dated '.($input['date'] ?? '').'.']);
            }
        });
        $today = new EngineTool($engines, 'orders-today', ['order masuk hari ini', 'order yang masuk hari ini', 'orders today'], fn () => ['date' => date('Y-m-d')], sensitivity: Sensitivity::Value);

        return new Assistant($navigator, $knowledge, [$today], history: new MemoryHistory, outOfScope: 'Saya hanya memahami perihal program ini, lainnya saya belum paham');
    }

    /** @return list<string> */
    public static function questions(): array
    {
        return ['order yang masuk hari ini', 'di mana menu untuk buat PO', 'how to use grid', 'bagaimana cetak PO dengan logo perusahaan', 'cuaca di Surabaya besok'];
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
