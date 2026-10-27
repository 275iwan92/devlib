@php
    $suppliers = ['S01' => 'CV Sumber Rejeki', 'S02' => 'PT Maju Bersama', 'S03' => 'UD Tiga Putra'];
    $add = ['url' => '/demo/select-add/supplier', 'title' => 'Add Supplier', 'icon' => 'fa-truck', 'value-key' => 'id', 'label-key' => 'name',
        'note' => 'Demo: tidak ada yang disimpan. Nama kosong atau "dup" ditolak, "fail" meniru galat server.',
        'fields' => [
            ['name' => 'name', 'label' => 'Nama', 'required' => true, 'maxlength' => 60],
            ['name' => 'phone', 'label' => 'Telepon', 'type' => 'tel', 'maxlength' => 20],
            ['name' => 'contact', 'label' => 'Nama Kontak', 'maxlength' => 60],
        ]];
@endphp
<div class="dl-demo" style="max-width:720px">
    <p class="dl-note"><b>1. Select biasa.</b> Tombol plus di kanan membuka popup; setelah Save, supplier baru muncul dan langsung terpilih.</p>
    <x-dl::field.select name="supplier_a" label="Supplier" placeholder="Pilih supplier..." :options="$suppliers" :add="$add" />

    <p class="dl-note" style="margin-top:20px"><b>2. Di dalam form dengan Save dokumen.</b> Isian popup tidak ikut form: Save dokumen hanya mengirim <code>supplier_b</code>.</p>
    <x-dl::form title="Penerimaan Barang" action="{{ url('/demo/select-add/document') }}" :actions="['Save' => 'fa-floppy-disk']" style="height:auto">
        <x-dl::form.col>
            <x-dl::field.select name="supplier_b" label="Supplier" placeholder="Pilih supplier..." :options="$suppliers" :add="$add" required />
        </x-dl::form.col>
    </x-dl::form>

    <p class="dl-note" style="margin-top:20px"><b>3. Popup dengan pilihan (radio dan select).</b> Isian <code>type</code> bertipe <code>radio</code>, <code>level</code> bertipe <code>select</code>; setelah Save keduanya kembali ke nilai awalnya.</p>
    <x-dl::field.select name="supplier_d" label="Supplier" placeholder="Pilih supplier..." :options="$suppliers" :add="['url' => '/demo/select-add/supplier', 'title' => 'Add Supplier', 'icon' => 'fa-truck', 'fields' => [
        ['name' => 'name', 'label' => 'Nama', 'required' => true, 'maxlength' => 60],
        ['name' => 'type', 'label' => 'Jenis', 'type' => 'radio', 'required' => true, 'options' => ['LOCAL' => 'Lokal', 'IMPORT' => 'Impor'], 'value' => 'LOCAL'],
        ['name' => 'level', 'label' => 'Tingkat', 'type' => 'select', 'options' => ['A' => 'A', 'B' => 'B'], 'value' => 'B', 'placeholder' => 'Pilih...'],
    ]]" />

    <p class="dl-note" style="margin-top:20px"><b>4. Terkunci (<code>readonly</code>).</b> Tombol plus tidak tampil.</p>
    <x-dl::field.select name="supplier_c" label="Supplier" :options="$suppliers" value="S02" :add="$add" readonly />
</div>
