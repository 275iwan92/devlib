<?php

/*
 * REMARK
 * Tujuan       : Form khusus Warehouse Map (user 2026-10-02, docs/warehouselayout_tier1.md): pemilih gedung + pintasan ke Buildings / Storage.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : desk.
 * Output       : Markup dari resources/views/components/warehouse-desk.blade.php.
 * Dipakai oleh : warehouse-desk (layar katalog/proyek).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Form khusus Warehouse Map (user 2026-10-02, docs/warehouselayout_tier1.md): pemilih gedung + pintasan ke Buildings / Storage
// Areas / Bins / Stack Monitor, peta gedung (objek x-dl::wh-map: lantai, area, bin, tumpukan, tombol bin Transfer / Label / Block /
// Unblock), kartu Capacity per Floor, ringkasan, dan pop-up Generate Bins. Tiga keadaan: belum ada gedung, gedung belum punya
// ukuran, peta lengkap. Layar hanya menyerahkan $desk (array siap pakai dari kelas data aplikasi, kunci: wh, home, title, options, links, generate, map); isi bin dan kapasitas dihitung
// oleh aplikasi pemakai.
// Objek DevLib (dulu x-mp::inv.warehouse-desk, user 2026-10-07): data contoh lengkap ada di devlib.test; MockupParts\...\WarehouseDesk
// turunannya menimpa sources() (nama sumber data per peran) dan penerjemah src().
class WarehouseDesk extends DevlibComponent
{
    public function __construct(
        public array $desk = [],
    ) {}

    public function tag(): string
    {
        return 'x-dl::warehouse-desk';
    }

    // Kolom kartu Capacity per Floor (tetap; angka per lantai dari $desk['map']['capacity']).
    public function capacityColumns(): array
    {
        return [
            ['key' => 'floor', 'label' => 'Floor'],
            ['key' => 'area', 'label' => 'Floor Area (m²)', 'type' => 'number', 'sum' => true],
            ['key' => 'height', 'label' => 'Clear Height (m)', 'type' => 'number'],
            ['key' => 'room', 'label' => 'Room Volume (m³)', 'type' => 'number', 'sum' => true],
            ['key' => 'storage', 'label' => 'Storage Areas (m²)', 'type' => 'number', 'sum' => true],
            ['key' => 'storage_pct', 'label' => 'Storage % of Floor', 'type' => 'number'],
            ['key' => 'cap_m3', 'label' => 'Storage Volume (m³)', 'type' => 'number', 'sum' => true],
            ['key' => 'used_m3', 'label' => 'In Use (m³)', 'type' => 'number', 'sum' => true],
            ['key' => 'pct_m3', 'label' => 'Volume Used %', 'type' => 'progress'],
            ['key' => 'cap_kg', 'label' => 'Capacity (kg)', 'type' => 'number', 'sum' => true],
            ['key' => 'used_kg', 'label' => 'In Use (kg)', 'type' => 'number', 'sum' => true],
            ['key' => 'pct_kg', 'label' => 'Weight Used %', 'type' => 'progress'],
            ['key' => 'bins', 'label' => 'Bins', 'type' => 'number', 'sum' => true],
        ];
    }

    // Sumber data untuk tooltip, per peran: building, map, capacity, zone => daftar nama sumber. Netral: aplikasi pemakai menimpanya
    // dengan nama tabel/kolomnya sendiri; tanpa sumber, tooltip dilewati.
    protected function sources(): array
    {
        return [];
    }

    // Dipakai view: tooltip sumber data untuk satu peran, null bila aplikasi tidak menyebut sumbernya.
    public function srcFor(string $role): ?string
    {
        $refs = $this->sources()[$role] ?? [];

        return $refs ? $this->src(...$refs) : null;
    }

    public function src(string ...$tables): ?string
    {
        return $tables ? implode(' + ', $tables) : null;
    }

    public function render()
    {
        return view('devlib::components.warehouse-desk');
    }
}
