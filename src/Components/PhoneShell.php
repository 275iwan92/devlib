<?php

/*
 * REMARK
 * Tujuan       : Kerangka aplikasi untuk smartphone potret: SATU ikon hamburger melayang (tanpa bilah permanen), baris judul + tombol kembali di isi halaman, dan deret aksi layar / lima menu utama yang muncul di atas lalu tertutup sendiri.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek. Daftar modul = bentuk yang sama dengan x-dl::layer-shell.
 * Input        : title, back, tabs, active, modules, user, brand, logoutAction, deviceAction, links, frame, contained.
 * Output       : Markup dari resources/views/components/phone-shell.blade.php; perilaku lembar Menu di phone-shell.js.
 * Dipakai oleh : layar HP proyek (dipilih lewat Devlib\Device); halaman katalog p7-phone.
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Kerangka smartphone potret (saudara x-dl::layer-shell untuk desktop; keduanya varian PERANGKAT, bukan versi lama / baru). Sasaran sentuh >= 44 px, isian 16 px
// (Chrome / iOS tidak memperbesar sendiri), tepi layar aman lewat env(safe-area-inset-*).
// Isi = slot, atau $frame = alamat layar desktop yang dibuka di dalam kerangka (layar yang belum punya tampilan HP).
//
// $tabs = [['key', 'label', 'icon', 'url'], …] = MENU UTAMA (Beranda, Stok, Transaksi, Opname; tombol kelima 'Menu' = daftar semua modul ditambah komponen): deretnya muncul di atas saat ikon diketuk; tidak ada bilah bawah (bilah lama dikubur: tag history/phone-shell-bottom-tabs-v1)
// $modules = bentuk x-dl::layer-shell: [['code', 'label', 'icon', 'children' => [ITEM, …]], …]; ITEM = ['code', 'label', 'url', 'icon'?, 'badge'?]
// $links = tautan tambahan di lembar Menu: [['label', 'icon', 'url'], …] (mis. Bantuan)
class PhoneShell extends DevlibComponent
{
    public function __construct(
        public string $title = '',
        // Alamat tombol kembali di baris judul; null = layar utama (tanpa tombol).
        public ?string $back = null,
        public array $tabs = [],
        // key menu utama yang ditandai di deret menu.
        public string $active = '',
        public array $modules = [],
        public array $user = ['name' => '', 'role' => ''],
        // null = identitas bawaan situs (Brand::identity()).
        public ?string $brand = null,
        public string $logoutAction = '',
        // Alamat POST yang menyimpan pilihan tampilan (field `ui`); null = lembar Menu tanpa pilihan tampilan.
        public ?string $deviceAction = null,
        public array $links = [],
        // Alamat layar desktop yang ditampilkan di iframe sebagai isi (menggantikan slot).
        public ?string $frame = null,
        // true = tinggi mengikuti pembungkus (peraga katalog), false = seluruh layar.
        public bool $contained = false,
    ) {
        $this->brand ??= Brand::identity()['name'];
    }

    public function tag(): string
    {
        return 'x-dl::phone-shell';
    }

    public function source(): string
    {
        return 'menu = modules (sama dengan x-dl::layer-shell)';
    }

    public function jsClass(): ?string
    {
        return 'PhoneShell';
    }

    // Item lembar Menu: daun langsung; submenu ber-'items' diratakan satu tingkat.
    public function flat(array $children): array
    {
        return collect($children)->flatMap(fn ($c) => $c['items'] ?? [$c])->values()->all();
    }

    public function render()
    {
        return view('devlib::components.phone-shell');
    }
}
