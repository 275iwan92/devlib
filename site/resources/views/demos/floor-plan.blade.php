@php
    $units = [
        ['id' => 'u1', 'room' => 'R1', 'x' => 18, 'y' => 36, 'tone' => 'success', 'label' => 'Unit 01 · Reception', 'number' => '01', 'status' => 'Safe', 'next' => '14-02-2027'],
        ['id' => 'u2', 'room' => 'R2', 'x' => 52, 'y' => 24, 'tone' => 'warn', 'label' => 'Unit 02 · Meeting room', 'number' => '02', 'status' => 'Due soon', 'next' => '08-11-2026'],
        ['id' => 'u3', 'room' => 'R2', 'x' => 66, 'y' => 40, 'tone' => 'danger', 'label' => 'Unit 03 · Meeting room', 'number' => '03', 'status' => 'Overdue', 'next' => '12-10-2026'],
        ['id' => 'u4', 'room' => 'R3', 'x' => 18, 'y' => 74, 'tone' => 'success', 'label' => 'Unit 04 · Pantry', 'number' => '04', 'status' => 'Safe', 'next' => '03-03-2027'],
        ['id' => 'u5', 'room' => 'R4', 'x' => 78, 'y' => 76, 'tone' => 'neutral', 'label' => 'Unit 05 · Storage', 'number' => '05', 'status' => 'Not scheduled', 'next' => '-'],
    ];
    $rooms = [
        ['code' => 'R1', 'label' => 'Reception', 'x' => 3, 'y' => 4, 'w' => 28, 'h' => 46],
        ['code' => 'R2', 'label' => 'Meeting room', 'x' => 34, 'y' => 4, 'w' => 40, 'h' => 46],
        ['code' => 'R3', 'label' => 'Pantry', 'x' => 3, 'y' => 54, 'w' => 36, 'h' => 42],
        ['code' => 'R4', 'label' => 'Storage', 'x' => 62, 'y' => 54, 'w' => 35, 'h' => 42],
    ];
    $count = fn ($tone) => (string) collect($units)->where('tone', $tone)->count();
@endphp
<div class="dl-demo">
    <x-dl::floor-plan label="Floor 1 plan" :rooms="$rooms" :pins="$units" selected="u2" name="unit" :legend="true" aspect="16:9">
        <x-slot:summary>
            <x-dl::kpi-row>
                <x-dl::stat-box label="Units on this floor" :value="(string) count($units)" tone="accent" sub="Schematic plan" />
                <x-dl::stat-box label="Safe" :value="$count('success')" tone="success" />
                <x-dl::stat-box label="Due soon" :value="$count('warn')" tone="warn" />
                <x-dl::stat-box label="Overdue" :value="$count('danger')" tone="danger" />
            </x-dl::kpi-row>
        </x-slot:summary>
        <x-slot:detail>
            @foreach ($units as $u)
                <x-dl::box data-floorplan-detail="{{ $u['id'] }}">
                    <x-dl::headline>{{ $u['label'] }} <x-dl::badge :tone="$u['tone']" :label="$u['status']" /></x-dl::headline>
                    <x-dl::form.row :cols="3">
                        <x-dl::field.display label="Room" :value="raw($u['room'])" />
                        <x-dl::field.display label="Next service" :value="raw($u['next'])" />
                        <x-dl::field.display label="Status" :value="raw($u['status'])" />
                    </x-dl::form.row>
                </x-dl::box>
            @endforeach
        </x-slot:detail>
    </x-dl::floor-plan>
</div>
