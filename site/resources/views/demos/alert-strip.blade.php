{{-- Strip notifikasi: satu notifikasi (teks diam) dan tiga notifikasi (teks berjalan). --}}
<div class="dl-demo">
    <x-dl::alert-strip :items="[['text' => 'Tasks: 4 not done (2 Do first)', 'tone' => 'warn', 'href' => '#', 'label' => 'Open Task', 'icon' => 'fa-list-check']]" />
    <x-dl::alert-strip :items="[
        ['text' => 'Material: 14 materials need attention', 'tone' => 'danger', 'href' => '#', 'icon' => 'fa-triangle-exclamation'],
        ['text' => 'Tasks: 4 not done (2 Do first)', 'tone' => 'warn', 'href' => '#', 'icon' => 'fa-list-check'],
        ['text' => 'Leave: 2 requests wait for a decision', 'tone' => 'info', 'href' => '#', 'icon' => 'fa-plane-departure'],
    ]" />
</div>
