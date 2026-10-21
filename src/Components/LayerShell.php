<?php

/*
 * REMARK
 * Tujuan       : Kerangka aplikasi setelah login: navigasi x-dl::layer-menu di kiri, TANPA top bar; tab MDI, Ctrl+K, laci, modal. Satu-satunya menu utama.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : modules, user, brand, tagline, logo, logoutAction, menuSource, chat, open, … (16 prop).
 * Output       : Markup dari resources/views/components/layer-shell.blade.php.
 * Dipakai oleh : layar home proyek (mengirim brand sendiri) dan devlib.test home / demo shell, lewat x-dl::layer-shell.
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

use Devlib\Lang;

// Kerangka aplikasi setelah login (acuan mockup main_menu_v2.html): navigasi x-dl::layer-menu di kiri dan TANPA top bar,
// tab MDI + Ctrl+K + laci + modal. Semua isi menu akun ada di roda gigi Layer Menu; chat tinggal di Layer Menu (tukar / dok kanan).
// Menu lama (Classic: mega menu + sidebar + top bar) dikubur: tag history/menu-classic-v1.
// Menu = data (baris setara sys_app_menu), bukan markup: layar pemakai cukup menyerahkan array $modules.
//
// $modules = [
//   ['code' => 'SALES', 'section' => 'Operations' (opsional: judul kelompok modul di Layer Menu), 'label' => 'Sales', 'icon' => 'fa-solid fa-…', 'children' => [
//       ['label' => 'Transactions', 'items' => [ITEM, …]],   // grup
//       ITEM,                                              // item langsung
//   ]],
// ]
// ITEM = ['code', 'label', 'url', 'type' => form|grid|report, 'icon'?, 'badge'?, 'badgeTone'?]
// $chat = ['me', 'people', 'convos', 'api'] -> Chat (x-dl::chat, versi ringkas); 'api' kosong = pratinjau tanpa server.
// $open = code item yang dibuka sebagai tab saat mulai (bila tidak ada tab yang dipulihkan dari sesi browser).
class LayerShell extends DevlibComponent
{
    public function __construct(
        public array $modules = [],
        public array $user = ['name' => 'User', 'email' => '', 'role' => ''],
        // brand / tagline / logo kosong = identitas bawaan situs (Brand::identity(), config devlib.brand.*).
        public ?string $brand = null,
        public ?string $tagline = null,
        // Url gambar logo APLIKASI; kosong = lencana gradasi bawaan. Bukan logo perusahaan (itu untuk kop cetakan).
        public ?string $logo = null,
        public string $logoutAction = '',
        public string $menuSource = 'sys_app_menu',
        public array $chat = [],
        public ?string $open = null,
        // Halaman Bantuan & dukungan (dibuka sebagai tab); kosong = pesan "segera".
        public ?string $helpUrl = null,
        // Halaman profil Layer Menu yang dibuka saat mulai (mis. kembali ke Tiket Bantuan sesudah kirim tiket).
        public ?string $profilePage = null,
        // Asisten (laci "Asisten AI"): ['api' => ['ask', 'feedback', 'csrf'], 'hello', 'chips' => [contoh pertanyaan],
        // 'history' => [['id', 'q', 'reply', 'feedback']], 'placeholder', 'thinking', 'failed']. Kosong = pratinjau tanpa server
        // (mencocokkan kata dengan nama menu). Bentuk 'reply' dan jawaban api.ask {id, reply}: kind, title, text, big,
        // rows [[label, nilai]], steps [..], note, actions [{label, url, code, icon} | {label, topic} | {label, page}].
        public array $assistant = [],
        // Menu akun (laci Notifikasi, modal Edit profil & Ubah password) tersambung server:
        // ['csrf', 'profile' => ['url', 'phone', 'role'], 'password' => ['url'],
        //  'notifications' => ['read' => url, 'items' => [['id', 'title', 'sub', 'tone', 'read', 'url']]]].
        // Url menerima POST JSON dan membalas {message} (422 {message} bila ditolak); 'read' menerima {id} atau {} = semua
        // dan membalas {unread}. Kosong = pratinjau tanpa server (isi contoh, Save hanya menutup).
        public array $account = [],
        // true tepat sesudah login: tab kerja lama dan posisi Layer Menu dibuang (boot.js) -> opening screen tampil.
        public bool $fresh = false,
        // Laci LEBAR dari kanan berisi layar penuh (iframe), dibuka oleh aksi akun berkunci sama (user 2026-10-03: Edit profile dan
        // Help & support "banyak aspek, windownya harus besar"): ['edit' => ['label', 'icon' => 'fa-...', 'url'], 'help' => [...]].
        // Tanpa laci: 'edit' = modal kecil nama / e-mail / telepon, 'help' = tab Bantuan.
        public array $drawers = [],
        // Fitur menu profil yang disembunyikan (aplikasi sederhana): 'chat' (aksi Chat + laci), 'ai' (aksi Asisten AI), 'help' (aksi Help & support), 'theme' (pilihan Tema;
        // tanpa Tema, halaman Setup diganti langsung halaman Bahasa).
        public array $without = [],
    ) {
        $identity = Brand::identity();
        $this->brand ??= $identity['name'];
        $this->tagline ??= $identity['tagline'];
        $this->logo ??= $identity['logo'];
    }

    public function tag(): string
    {
        return 'x-dl::layer-shell';
    }

    public function source(): string
    {
        return 'menu = '.$this->menuSource;
    }

    public function jsClass(): ?string
    {
        return 'LayerShell';
    }

    // Modul Shell sebagai tree Layer Menu: modul -> item, grup = caption. Kunci daun = code item (= tab MDI).
    public function layerTree(): array
    {
        $tone = ['partial' => 'warn', 'done' => 'success', 'danger' => 'danger'];
        // Item ber-'items' = submenu bertingkat (dibuka sebagai panel berikutnya), selain itu daun.
        $leaf = function (array $it, ?string $group) use (&$leaf, $tone) {
            if (isset($it['items'])) {
                return array_filter(['label' => $it['label'], 'icon' => $it['icon'] ?? null, 'group' => $it['group'] ?? $group,
                    'items' => array_map(fn ($c) => $leaf($c, null), $it['items'])]);
            }

            return array_filter([
                'key' => $it['code'], 'label' => $it['label'], 'icon' => $it['icon'] ?? null, 'group' => $it['group'] ?? $group,
                'badge' => $it['badge'] ?? null, 'badgeTitle' => $it['badgeTitle'] ?? null, 'tone' => $tone[$it['badgeTone'] ?? ''] ?? null,
            ]);
        };

        // Grup menu = submenu (dibuka ke panel berikutnya, ada breadcrumb), bukan judul + daftar panjang (user 2026-09-26).
        // 'section' (opsional) pada modul = judul kelompok di layer akar: modul berurutan dengan section sama tampil di bawah satu judul.
        return array_map(fn ($m) => ['label' => $m['label'], 'icon' => $m['icon'] ?? null, 'pin' => $m['pin'] ?? null, 'group' => $m['section'] ?? null, 'items' => collect($m['children'] ?? [])
            ->map(fn ($c) => $leaf(isset($c['items']) ? $c + ['icon' => 'fa-solid fa-folder'] : $c, null))
            ->values()->all()], $this->modules);
    }

    // Laporan Manajemen (modul MGMT) punya view sendiri di kepala panel (ikon Operasional | Laporan Manajemen).
    public const REPORT_MODULE = 'MGMT';

    public function views(): array
    {
        $ops = array_values(array_filter($this->modules, fn ($m) => $m['code'] !== self::REPORT_MODULE));
        $rep = array_values(array_filter($this->modules, fn ($m) => $m['code'] === self::REPORT_MODULE));
        if (! $rep || ! $ops) {
            return [];
        }
        $saved = $this->modules;
        $this->modules = $ops;
        $opsTree = $this->layerTree();
        $this->modules = $rep;
        $repTree = $this->layerTree()[0]['items'];
        $this->modules = $saved;

        return [
            ['key' => 'ops', 'label' => Lang::text('Operational'), 'icon' => 'fa-table-cells-large', 'items' => $opsTree],
            ['key' => 'mgmt', 'label' => $rep[0]['label'], 'icon' => 'fa-chart-line', 'items' => $repTree],
        ];
    }

    // View awal: dibuka dengan tab laporan (mis. pendaratan Direksi) -> view Laporan Manajemen.
    public function startView(): ?string
    {
        $rep = collect($this->modules)->firstWhere('code', self::REPORT_MODULE);
        $codes = collect($rep['children'] ?? [])->flatMap(fn ($c) => $c['items'] ?? [$c])->pluck('code');

        return $this->open && $codes->contains($this->open) ? 'mgmt' : null;
    }

    public function actions(): array
    {
        return array_values(array_filter([
            // Urutan panel Profile = keputusan user 2026-10-03: Notifications, Chat, AI Assistant | Edit profile, Help & support,
            // (Setup = halaman panel, disisipkan sesudah Help), Change password, Sign out.
            ['key' => 'notif', 'label' => Lang::text('Notifications'), 'icon' => 'notification'],
            in_array('chat', $this->without) ? null : ['key' => 'chat', 'label' => 'Chat', 'icon' => 'fa-comment-dots'],
            in_array('ai', $this->without) ? null : ['key' => 'ai', 'label' => Lang::text('AI Assistant'), 'icon' => 'ai'],
            ['key' => 'edit', 'label' => Lang::text('Edit profile'), 'icon' => 'fa-user-pen', 'sep' => true],
            in_array('help', $this->without) ? null : ['key' => 'help', 'label' => Lang::text('Help & support'), 'icon' => 'help'],
            ['key' => 'pass', 'label' => Lang::text('Change password'), 'icon' => 'password'],
            ['key' => 'logout', 'label' => Lang::text('Sign out'), 'icon' => 'logout', 'tone' => 'danger', 'confirm' => Lang::text('Sign out of the application?')],
        ]));
    }

    public function render()
    {
        return view('devlib::components.layer-shell');
    }
}
