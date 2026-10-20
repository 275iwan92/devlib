{{-- Alur Master Data: grid pegawai -> Single Form (halaman sendiri) -> Save / Back kembali ke grid ini. Aturan: skills/devlib/grid-form-flow.md --}}
@php
    $slug = 'p5-flow-master';
    $from = \App\Support\FlowDemo::gridUrl($slug);
@endphp
<p class="dl-note">Klik 2x baris (atau ikon Edit) membuka <b>Master Form</b> di halaman sendiri; <b>+ New Record</b> membuka form kosong (Active, isian bawaan terisi). Master hanya <b>Active / Inactive</b>: tidak ada Confirm atau Cancel. <b>Save</b> tetap di form; hanya <b>Back</b> kembali ke grid ini, dengan kata cari, halaman, dan kotak statistik seperti sebelumnya. Ubah isian lalu klik Back: muncul pilihan Save / Don't Save / Cancel.</p>
<x-dl::grid id="flow-master" kind="master" restore :rows="\App\Support\DemoData::employees()" search="Cari nama, kode, jabatan..."
    :open-url="\App\Support\FlowDemo::formUrl($slug, '{id}', '', $from)" :new-url="\App\Support\FlowDemo::formUrl($slug, 'new', '', $from)" :toggle-url="url('/lib/'.$slug.'/toggle/{id}')"
    :columns="[
        ['key' => 'name', 'label' => 'Pegawai', 'type' => 'identity', 'code' => 'code'],
        ['key' => 'dept', 'label' => 'Departemen'],
        ['key' => 'position', 'label' => 'Jabatan'],
        ['key' => 'status', 'label' => 'Status', 'type' => 'badge', 'tones' => ['TETAP' => 'success', 'KONTRAK' => 'info', 'HARIAN' => 'warn']],
        ['key' => 'join_date', 'label' => 'Tgl Masuk', 'type' => 'date'],
        ['key' => 'city', 'label' => 'Kota'],
    ]"
    :stats="[['label' => 'Total'], ['label' => 'Aktif', 'where' => ['active' => true], 'tone' => 'success'], ['label' => 'Nonaktif', 'where' => ['active' => false], 'tone' => 'danger']]"
    :filters="['dept' => 'Departemen', 'status' => 'Status']" />
