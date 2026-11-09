{{-- Kalender bersama (pratinjau tanpa server): peserta @nama, ruang, pengingat, undangan yang belum dijawab, dan acara baca-saja. --}}
@php($d = date('Y-m-d'))
<div class="dl-demo">
    <x-dl::shared-calendar display="week"
        :people="[['id' => 1, 'name' => 'Nisa', 'role' => 'Operations Manager'], ['id' => 2, 'name' => 'Nina', 'role' => 'Finance'], ['id' => 3, 'name' => 'Dewi', 'role' => 'Director']]"
        :rooms="[7 => 'Meeting Room A', 8 => 'Meeting Room B']"
        team-url="/demo/team"
        :events="[
            ['id' => 'a1', 'title' => 'Weekly sync', 'start' => $d.' 10:00', 'end' => $d.' 11:00', 'tone' => 'accent', 'people' => [['id' => 1, 'name' => 'Nisa', 'rsvp' => 'ACCEPTED'], ['id' => 2, 'name' => 'Nina', 'rsvp' => 'INVITED']], 'room' => 7, 'roomName' => 'Meeting Room A', 'remind' => 15, 'vis' => 'BUSY'],
            ['id' => 'a2', 'title' => 'Budget review', 'start' => $d.' 13:00', 'end' => $d.' 14:00', 'tone' => 'violet', 'invite' => ['rsvp' => 'INVITED', 'by' => 'Dewi'], 'people' => [['id' => 3, 'name' => 'Dewi', 'rsvp' => 'ACCEPTED']]],
            ['id' => 't1', 'title' => 'Send quotation', 'start' => $d.' 15:00', 'end' => $d.' 15:30', 'tone' => 'danger', 'ro' => true, 'kind' => 'task'],
        ]" />
</div>
