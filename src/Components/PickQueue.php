<?php

/*
 * REMARK
 * Tujuan       : Antrean pilih: daftar dokumen sumber (mis. order) dengan kesiapan barang, dicentang lalu dijadikan SATU dokumen turunan lewat satu bilah aksi di atas daftar.
 * Sumber hulu  : Data dari layar (baris dokumen sumber + angka siap / dipesan); aturan "boleh digabung" = kunci group yang sama.
 * Input        : rows, states, filters, filter, action, name, button, buttonOne, buttonPartial, together, mismatch, selected, rowHead, qtyHead, dueHead, empty, title.
 * Output       : <form> POST ke action dengan name[] = id baris terpilih; filter status, pilihan, dan bilah aksi berjalan di browser (pick-queue.js).
 * Dipakai oleh : halaman katalog p5-pick-queue.
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

use Devlib\Lang;

// Antrean pilih: setiap baris = satu dokumen sumber dengan kesiapan barang (siap / dipesan). Centang beberapa baris yang
// boleh digabung (kunci `group` sama) lalu tekan SATU tombol di bilah atas untuk membuat satu dokumen turunan. Baris yang
// belum siap tidak bisa dicentang. Tidak ada tombol per baris dan tidak ada kontrol di bawah layar (HP: baris menjadi kartu).
//   rows: [['id' => 7, 'title' => 'ORD-0412', 'party' => 'Customer A', 'place' => 'City', 'group' => 'a|addr1|vat',
//           'state' => 'ready|partial|waiting', 'have' => 5000, 'want' => 5000, 'unit' => 'pcs', 'note' => 'done 29 Oct' (opsional),
//           'due' => '30 Oct', 'dueIn' => 3 (hari; negatif = lewat), 'pick' => true|false (opsional, bawaan dari states)]]
//   states: [key => ['label' => .., 'tone' => success|warn|danger|.., 'pick' => bool]]; bawaan ready / partial / waiting.
//   filters: [['key' => .., 'label' => .., 'states' => [keys] | null (= semua)]]; kosong = satu chip per state + "All".
// Teks tombol memakai :n (jumlah dipilih), :have, :want, :unit.
class PickQueue extends DevlibComponent
{
    protected const TEXT_PROPS = ['title', 'empty', 'button', 'buttonOne', 'buttonPartial', 'together', 'mismatch', 'selected', 'rowHead', 'qtyHead', 'dueHead'];

    public function __construct(
        public ?string $title = null,
        public array $rows = [],
        public array $states = [],
        public array $filters = [],
        public ?string $filter = null,
        public ?string $action = null,
        public string $name = 'ids',
        public string $button = 'Create 1 document from :n',
        public string $buttonOne = 'Create document',
        public string $buttonPartial = 'Create document (:have of :want)',
        public string $together = 'same address, can be merged',
        public string $mismatch = 'Choose items with the same customer and address',
        public string $selected = ':n selected',
        public string $rowHead = 'Order',
        public string $qtyHead = 'Ready / ordered',
        public string $dueHead = 'Due',
        public string $empty = 'Nothing in this list.',
    ) {
        $this->states = array_replace_recursive([
            'ready' => ['label' => 'Fully ready', 'tone' => 'success', 'pick' => true],
            'partial' => ['label' => 'Partly ready', 'tone' => 'warn', 'pick' => true],
            'waiting' => ['label' => 'Not ready', 'tone' => 'danger', 'pick' => false],
        ], $this->states);

        if (! $this->filters) {
            foreach ($this->states as $key => $state) {
                $this->filters[] = ['key' => $key, 'label' => $state['label'], 'states' => [$key]];
            }
            $this->filters[] = ['key' => 'all', 'label' => 'All', 'states' => null];
        }
        $this->filter ??= $this->filters[0]['key'] ?? null;
    }

    public function tag(): string
    {
        return 'x-dl::pick-queue';
    }

    // Jumlah baris per chip filter.
    public function count(array $filter): int
    {
        $states = $filter['states'] ?? null;

        return $states === null ? count($this->rows) : count(array_filter($this->rows, fn ($r) => in_array($r['state'] ?? '', $states, true)));
    }

    public function pickable(array $row): bool
    {
        return (bool) ($row['pick'] ?? $this->states[$row['state'] ?? '']['pick'] ?? true);
    }

    public function shown(array $row): bool
    {
        $active = collect($this->filters)->firstWhere('key', $this->filter);

        return $active === null || ($active['states'] ?? null) === null || in_array($row['state'] ?? '', $active['states'], true);
    }

    // "in 3 days" | "today" | "overdue 2 days"; null bila dueIn tidak diisi.
    public function dueText(array $row): ?string
    {
        if (! isset($row['dueIn'])) {
            return null;
        }
        $n = (int) $row['dueIn'];

        return match (true) {
            $n === 0 => Lang::text('today'),
            $n > 0 => str_replace(':n', (string) $n, Lang::text($n === 1 ? 'in :n day' : 'in :n days')),
            default => str_replace(':n', (string) abs($n), Lang::text($n === -1 ? 'overdue :n day' : 'overdue :n days')),
        };
    }

    public function render()
    {
        return view('devlib::components.pick-queue');
    }
}
