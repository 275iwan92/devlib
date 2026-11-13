<div class="dl-demo" style="max-width:1100px">
    {{-- rows: baris matriks (shift); cols: kolom (unit kerja); people: semua orang; placed: yang sudah di petak [id => "rowKey|colKey"]. Yang tidak ada di placed = di kolam kiri. --}}
    <x-dl::form title="Weekly roster" :actions="['Save' => 'fa-floppy-disk']" style="height:auto">
        <x-dl::slot-board name="slots" pool-title="Not scheduled" row-title="Shift"
            :rows="[['key' => 's1', 'label' => 'Shift 1', 'sub' => '05:00-13:15', 'need' => 3], ['key' => 's2', 'label' => 'Shift 2', 'sub' => '13:15-21:15', 'need' => 3]]"
            :cols="[['key' => 'a', 'label' => 'Press A', 'sub' => 'target 3.200 pcs/h'], ['key' => 'b', 'label' => 'Press B', 'sub' => 'target 2.800 pcs/h'], ['key' => 'c', 'label' => 'Die cut', 'sub' => 'target 1.500 pcs/h']]"
            :people="[
                ['id' => 1, 'name' => 'Anna Putri', 'short' => 'Anna', 'code' => 'E-01', 'group' => 'Press'], ['id' => 2, 'name' => 'Budi Santoso', 'short' => 'Budi', 'code' => 'E-02', 'group' => 'Press'],
                ['id' => 3, 'name' => 'Citra Dewi', 'short' => 'Citra', 'code' => 'E-03', 'group' => 'Press'], ['id' => 4, 'name' => 'Dedi Kurnia', 'short' => 'Dedi', 'code' => 'E-04', 'group' => 'Die cut'],
                ['id' => 5, 'name' => 'Eko Prasetyo', 'short' => 'Eko', 'code' => 'E-05', 'group' => 'Die cut'], ['id' => 6, 'name' => 'Fajar Nugraha', 'short' => 'Fajar', 'code' => 'E-06', 'group' => 'Press'],
                ['id' => 7, 'name' => 'Gita Lestari', 'short' => 'Gita', 'code' => 'E-07', 'group' => 'Press'], ['id' => 8, 'name' => 'Hadi (day labour)', 'short' => 'Hadi', 'code' => 'B-01', 'group' => 'Day labour', 'flag' => true],
            ]"
            :placed="['1' => 's1|a', '2' => 's1|a', '4' => 's1|c']" />
    </x-dl::form>
    <p class="dl-note">Drag a person to a slot, or tick several people and click a slot to place them all. Double-click a placed person (or drag back to the left box) to unplace. Dashed orange ring = flagged person (for example day labour). Undo returns the opening state.</p>
</div>
