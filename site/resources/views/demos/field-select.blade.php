<div class="dl-demo">
    <x-dl::field.select name="kota" label="Kota" placeholder="Pilih kota..." :options="$locations" value="Surabaya, Jawa Timur, Indonesia" />
</div>

{{-- Options bersarang = dropdown berkelompok (judul grup tidak bisa dipilih; pencarian menyembunyikan judul grup yang kosong) --}}
<div class="dl-demo" style="max-width:420px;margin-top:20px">
    <p class="dl-note"><b>Grouped options.</b> Nested arrays render a heading per group.</p>
    <x-dl::field.select name="box_type" label="Box type" placeholder="Choose a type..." :options="['Tuck End' => ['RTE' => 'Reverse Tuck End', 'STE' => 'Straight Tuck End'], 'Mailer' => ['MAIL' => 'Mailer Box'], 'Tray' => ['TRAY' => 'Tray Box', 'LID' => 'Lid and Base Box']]" />
</div>
