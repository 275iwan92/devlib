<div class="dl-demo">
    <x-dl::tabs :tabs="['umum' => 'Informasi Umum', 'baris' => 'Baris Pesanan', 'riwayat' => 'Riwayat']">
        <x-slot:umum><x-dl::field.lookup name="supplier_id" lookup="supplier" label="Supplier" /></x-slot:umum>
        <x-slot:baris>Tabel baris pesanan (Part 5: Master-Detail).</x-slot:baris>
        <x-slot:riwayat>Riwayat approval (Part 5: Approval).</x-slot:riwayat>
    </x-dl::tabs>

    <x-dl::tabs switch :tabs="['dieline' => 'Dieline', 'preview' => '3D Preview', 'material' => 'Material']">
        <x-slot:dieline>Satu baris pil; pil aksen bergeser ke tab yang diklik.</x-slot:dieline>
        <x-slot:preview>Gerak mengikuti token --dl-dur-slide dan mati bila prefers-reduced-motion.</x-slot:preview>
        <x-slot:material>Opt-in: prop switch.</x-slot:material>
    </x-dl::tabs>
</div>
