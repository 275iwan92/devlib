{{-- Pelacak kebiasaan, catatan tempel, matriks Eisenhower dan jam dunia (pratinjau tanpa server). --}}
<div class="dl-demo">
    <x-dl::habit-tracker :items="[['id' => 'h1', 'name' => 'Walk 20 minutes', 'log' => [date('Y-m-d'), date('Y-m-d', strtotime('-1 day'))]]]" />
    <x-dl::sticky-notes :items="[['id' => 'n1', 'text' => 'Call the supplier', 'color' => 'yellow'], ['id' => 'n2', 'text' => 'Book the meeting room', 'color' => 'blue']]" />
    <x-dl::eisenhower :items="[['id' => 'k1', 'text' => 'Month-end report', 'q' => 1, 'done' => false], ['id' => 'k2', 'text' => 'Plan training', 'q' => 2, 'done' => false]]" />
    <x-dl::world-clock :items="[['id' => 'c1', 'tz' => 'Asia/Tokyo', 'label' => 'Tokyo'], ['id' => 'c2', 'tz' => 'Europe/London', 'label' => 'London']]" />
</div>
