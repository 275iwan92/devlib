{{-- Contoh x-dl::flow: Work Flow = siklus dokumen menurut skills/devlib/transaction-lifecycle.md dan siapa yang bertindak (nama dan data fiktif). --}}
<div class="dl-demo">
    <x-dl::flow title="Transactional document life cycle" source="skills/devlib/transaction-lifecycle.md" :steps="[
        ['key' => 'draft', 'label' => 'Draft', 'module' => 'Officer: Save, Delete, New, Print', 'icon' => 'fa-pen', 'status' => 'done', 'note' => 'A new record is always Draft; fields start with default values'],
        ['key' => 'appr', 'label' => 'Authority (optional)', 'module' => 'Superior: levels 1 to 3, in order', 'icon' => 'fa-stamp', 'status' => 'review', 'note' => 'Only for documents that need a superior to agree'],
        ['key' => 'conf', 'label' => 'Confirmed', 'module' => 'Officer: Confirm; the record becomes read-only', 'icon' => 'fa-circle-check', 'status' => 'todo', 'branch' => [['label' => 'Cancel: record kept, reversing Adjustment created automatically', 'icon' => 'fa-ban', 'status' => 'locked']]],
        ['key' => 'canc', 'label' => 'Cancelled', 'module' => 'The record always stays stored (Delete is only for Draft)', 'icon' => 'fa-box-archive', 'status' => 'locked'],
    ]" />
</div>
