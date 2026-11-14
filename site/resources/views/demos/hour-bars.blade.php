<div class="dl-demo" style="max-width:1000px">
    {{-- fields: pola isian jam per hari di form yang sama; batang membaca dan menulis isian itu. --}}
    <x-dl::form title="Shift hours" :actions="['Save' => 'fa-floppy-disk']" style="height:auto">
        <x-dl::hour-bars :fields="['start' => 'start_{day}', 'bout' => 'bout_{day}', 'bin' => 'bin_{day}', 'end' => 'end_{day}']" />
        @foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $i => $day)
            @php $work = $i < 5; @endphp
            <x-dl::form.row :cols="5">
                <x-dl::field.display :label="$i === 0 ? 'Day' : ''" :value="$day" />
                <x-dl::field.time placeholder="HH:MM" :name="'start_'.$day" :label="$i === 0 ? 'Clock in' : ''" :value="$work ? '07:00' : ($i === 5 ? '10:00' : '')" />
                <x-dl::field.time placeholder="HH:MM" :name="'bout_'.$day" :label="$i === 0 ? 'Break start' : ''" :value="$work ? '12:00' : ''" />
                <x-dl::field.time placeholder="HH:MM" :name="'bin_'.$day" :label="$i === 0 ? 'Break end' : ''" :value="$work ? '13:00' : ''" />
                <x-dl::field.time placeholder="HH:MM" :name="'end_'.$day" :label="$i === 0 ? 'Clock out' : ''" :value="$work ? '16:00' : ($i === 5 ? '18:00' : '')" />
            </x-dl::form.row>
        @endforeach
    </x-dl::form>
    <p class="dl-note">Drag in an empty area to create a day, drag a bar to move it, drag an end to change the hours, click a day name to set it off. The fields below follow the bars and the bars follow the fields.</p>
</div>
