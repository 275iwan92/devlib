<div class="dl-demo" style="max-width:980px">
    {{-- items: [id => label] semua yang bisa dipilih; rows: yang sudah di kanan ['id' + nilai field]; fields: pilihan per baris kanan (sticky = ikut baris terakhir). Kiri = items di luar rows. --}}
    <x-dl::form title="Crew of a shift" :actions="['Save' => 'fa-floppy-disk']" style="height:auto">
        <x-dl::transfer name="crew" id-key="employee_id" left-title="Not scheduled" right-title="Scheduled"
            :items="['27' => 'E-0027 · Achmad Syarif', '43' => 'E-0043 · Agi Purnomo', '104' => 'E-0104 · Ade Mahendra', '115' => 'E-0115 · Agus Mujiono', '9' => 'E-0009 · Agus Budi', '16' => 'E-0016 · Ahmad Alwi', '31' => 'E-0031 · Wahyu Nur']"
            :fields="[
                ['key' => 'machine_id', 'label' => 'Machine', 'options' => ['1' => 'Komori · OFF-02', '2' => 'Sakurai · OFF-01', '3' => 'Plong1 · DIE-01'], 'required' => true, 'sticky' => true],
                ['key' => 'role', 'label' => 'Job', 'options' => ['Main operator' => 'Main operator', 'Co-operator' => 'Co-operator', 'Helper' => 'Helper', 'Kabag' => 'Kabag'], 'required' => true, 'default' => 'Co-operator'],
            ]"
            :rows="[['id' => '27', 'machine_id' => '1', 'role' => 'Main operator'], ['id' => '43', 'machine_id' => '1', 'role' => 'Helper']]" />
    </x-dl::form>
    <p class="dl-note">Left = not scheduled yet, right = scheduled. Select rows (Shift for a range) and use the middle buttons, double-click a row, or drag rows to the other box. People moved to the right take the machine of the last row. Undo returns the opening state.</p>
</div>
