{{-- Contoh x-dl::flow: Data Flow = dari mana nilai datang, engine mana yang mengolah, dan di mana disimpan (data fiktif). Tampilan berurutan, bukan diagram DFD penuh. --}}
<div class="dl-demo">
    <x-dl::flow title="Reorder point: from entry to report" source="fictional sample" :steps="[
        ['key' => 'in', 'label' => 'Item Form', 'module' => 'input: item code, unit, lead time', 'icon' => 'fa-pen-to-square', 'status' => 'done', 'note' => 'The user types the master data'],
        ['key' => 'val', 'label' => 'Validation', 'module' => 'rule: code unique, lead time > 0', 'icon' => 'fa-shield-halved', 'status' => 'done'],
        ['key' => 'db', 'label' => 'Item Table', 'module' => 'store: item', 'icon' => 'fa-database', 'status' => 'done'],
        ['key' => 'eng', 'label' => 'Reorder Engine', 'module' => 'in: average usage, lead time / out: reorder point', 'icon' => 'fa-gears', 'status' => 'review', 'note' => 'Mode Suggest: the result is a draft', 'branch' => [['label' => 'Usage history (read)', 'icon' => 'fa-clock-rotate-left', 'status' => 'done']]],
        ['key' => 'rep', 'label' => 'Reorder Report', 'module' => 'output: items below the point', 'icon' => 'fa-file-lines', 'status' => 'todo'],
    ]" />
</div>
