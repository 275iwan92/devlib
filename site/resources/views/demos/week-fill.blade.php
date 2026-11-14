<div class="dl-demo" style="max-width:980px">
    {{-- map: peran => ['from' => isian sumber (di slot), 'to' => pola isian target dengan {day}]. Apply menyalin ke hari terpilih; baris per hari tetap bisa diubah. --}}
    <x-dl::form title="Shift hours" :actions="['Save' => 'fa-floppy-disk']" style="height:auto">
        <x-dl::week-fill :map="['start' => ['from' => 'qf_start', 'to' => 'start_{day}'], 'bout' => ['from' => 'qf_bout', 'to' => 'bout_{day}'], 'bin' => ['from' => 'qf_bin', 'to' => 'bin_{day}'], 'end' => ['from' => 'qf_end', 'to' => 'end_{day}']]">
            <x-dl::field.time placeholder="HH:MM" name="qf_start" label="Clock in" value="07:00" />
            <x-dl::field.time placeholder="HH:MM" name="qf_bout" label="Break start" value="12:00" />
            <x-dl::field.time placeholder="HH:MM" name="qf_bin" label="Break end" value="13:00" />
            <x-dl::field.time placeholder="HH:MM" name="qf_end" label="Clock out" value="16:00" />
        </x-dl::week-fill>
        @foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $i => $day)
            <x-dl::form.row :cols="5">
                <x-dl::field.display :label="$i === 0 ? 'Day' : ''" :value="$day" />
                <x-dl::field.time placeholder="HH:MM" :name="'start_'.$day" :label="$i === 0 ? 'Clock in' : ''" />
                <x-dl::field.time placeholder="HH:MM" :name="'bout_'.$day" :label="$i === 0 ? 'Break start' : ''" />
                <x-dl::field.time placeholder="HH:MM" :name="'bin_'.$day" :label="$i === 0 ? 'Break end' : ''" />
                <x-dl::field.time placeholder="HH:MM" :name="'end_'.$day" :label="$i === 0 ? 'Clock out' : ''" />
            </x-dl::form.row>
        @endforeach
    </x-dl::form>
    <p class="dl-note">Pick the days with the chips (or Weekdays / All / Clear), fill the four times once and press Apply: the times are copied to the chosen days below. Each day can still be edited by hand.</p>
</div>
