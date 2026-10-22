{{-- x-dl::report (juga x-dl::print-out): prop company / companyPpn memakai kunci yang sama. Non PPN = logo_name_address, PPN = name; tombol Non VAT / VAT mengganti kop. --}}
<x-dl::report title="Laporan Stok" subtitle="Stock Report" number="RPT-KOP/26/00001" printed-by="Rina — Staff Gudang"
    :company="['name' => 'Toko Maju Sentosa', 'legal_name' => 'PT Maju Sentosa Abadi', 'style' => 'logo_name_address', 'address' => 'Jl. Contoh Industri No. 42, Surabaya (contoh)', 'contact' => 'Telp. (031) 000-0000', 'logo' => '/kop-logo-contoh.svg', 'logoBw' => '/kop-logo-contoh-bw.svg']"
    :company-ppn="['name' => 'Toko Maju Sentosa', 'legal_name' => 'PT Maju Sentosa Abadi', 'style' => 'name', 'address' => 'Jl. Contoh Industri No. 42, Surabaya (contoh)', 'npwp' => '00.000.000.0-000.000 (contoh)']"
    :columns="[
        ['key' => 'code', 'label' => 'Kode Item'],
        ['key' => 'name', 'label' => 'Nama Item'],
        ['key' => 'uom', 'label' => 'UOM'],
        ['key' => 'end', 'label' => 'Saldo Akhir', 'type' => 'number', 'sum' => true, 'bold' => true],
    ]"
    :rows="[
        ['code' => 'RM-0001', 'name' => 'Kertas HVS 80 gr', 'uom' => 'RIM', 'end' => 120],
        ['code' => 'RM-0002', 'name' => 'Tinta Cyan', 'uom' => 'KG', 'end' => 18],
        ['code' => 'RM-0003', 'name' => 'Lem Kertas', 'uom' => 'KG', 'end' => 42],
        ['code' => 'RM-0004', 'name' => 'Plastik Wrap', 'uom' => 'ROLL', 'end' => 9],
    ]" />
