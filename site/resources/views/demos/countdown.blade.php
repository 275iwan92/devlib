{{-- Timer hitung mundur dan stopwatch (pratinjau tanpa server). --}}
<div class="dl-demo">
    <x-dl::countdown storage-key="dl-countdown-demo" :presets="[['id' => 't1', 'label' => 'Tea', 'minutes' => 3], ['id' => 't2', 'label' => 'Meeting', 'minutes' => 45]]" />
    <x-dl::stopwatch storage-key="dl-stopwatch-demo" />
</div>
