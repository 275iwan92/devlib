{{-- Daftar alarm (pratinjau tanpa server) + pembunyinya (tak terlihat; di jendela paling atas). --}}
<div class="dl-demo">
    <x-dl::alarm-runner storage-key="dl-alarms-demo" />
    <x-dl::alarm-list storage-key="dl-alarms-demo" :items="[['id' => 'a1', 'time' => '06:30', 'label' => 'Shift start', 'days' => [1, 2, 3, 4, 5], 'on' => true], ['id' => 'a2', 'time' => '12:00', 'label' => '', 'days' => [], 'on' => false]]" />
</div>
