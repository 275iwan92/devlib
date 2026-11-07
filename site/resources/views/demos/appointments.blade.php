{{-- Janji pribadi (pratinjau tanpa server): kalender yang menyimpan daftarnya sendiri. --}}
<div class="dl-demo">
    <x-dl::appointments display="month" :items="[['id' => 'n1', 'title' => 'Dentist', 'start' => date('Y-m-d').' 10:00', 'end' => date('Y-m-d').' 11:00', 'allDay' => false, 'tone' => 'accent', 'desc' => '']]" />
</div>
