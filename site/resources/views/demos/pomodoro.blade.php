{{-- Pewaktu Pomodoro: pratinjau tanpa server (save-url kosong); versi compact dipakai papan widget. --}}
<div class="dl-demo">
    <x-dl::pomodoro storage-key="dl-pomodoro-demo" />
    <x-dl::pomodoro compact :focus="50" :break="10" :sessions="3" storage-key="dl-pomodoro-demo2" />
</div>
