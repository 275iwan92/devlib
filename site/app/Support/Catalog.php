<?php

namespace App\Support;

use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

// Menu situs DevLib: 5 grup komponen library (demo = kode pemakaian yang tampil di ikon </>).
// status komponen: review = menunggu penilaian user; hanya user yang boleh menaikkan ke approved.
final class Catalog
{
    // Halaman yang sudah DISETUJUI user (slug => tanggal). Hanya ditambah atas persetujuan eksplisit user.
    public const APPROVED = [
        'p7-layer' => '2026-09-24',   // Layer Menu
        'p7-assistant' => '2026-10-20',   // AI Assistant model (contracts): disetujui user "untuk saat ini"
        'p7-chat-model' => '2026-10-20',   // Chat model (contracts): disetujui user "untuk saat ini"
        'shell' => '2026-09-24',      // menu utama = Layer Shell (tanpa top bar)
        'p5-grid-tx' => '2026-09-30',     // Grid Pre-Entry > Transactional -- DISETUJUI & DIKUNCI user; jangan ubah tanpa perintah user
        'p5-grid-master' => '2026-09-30', // Grid Pre-Entry > Grid Master (dulu Non-Transactional) -- tata letak = Transactional + kotak per baris
        'p5-form-single' => '2026-09-30',
        'p5-form-approval' => '2026-09-30', // Form Approval -- read-only, Level n of N, Approve/Reject final, Hold sementara, LOG keputusan
        'p5-form-wizard' => '2026-09-30', // Form Wizard -- disetujui user 2026-09-30 (dulu Documents > Wizard)
        'p5-form-md' => '2026-09-30',     // Form Master-Detail (CO & PO) -- ringkasan, tab header, Details tengah, footer tab + Summary + terbilang // Single Form -- panel atas, istilah English, siklus Draft/Confirmed/Cancelled/Closed, tab Riwayat
        'p5-grid-lookup' => '2026-09-30', // Grid Pre-Entry > Grid Lookup -- sel teks aa/Aa/AA, decimal, date/time + pemilih, kalkulator mini
        'p5-grid-report' => '2026-09-26', // Grid for Reporting (tombol Filter -> pop-up)
        'p5-grid-group' => '2026-09-26',     // Grid Report dipecah per varian 2026-09-30, isi tidak berubah
        'p5-grid-drilldown' => '2026-09-26',
        'p5-grid-pivot' => '2026-09-26',
        // Menu Dokumen disetujui user 2026-09-26. Menu Form (p5-form-single/-md/-special, form) DICABUT ke Review 2026-09-30:
        // user belum QC, "single form bukan seperti ini". p5-master / p5-master-detail (x-dl::master) dihapus 2026-10-20 atas keputusan user.
        'p5-print' => '2026-09-26',
        'p5-print-doc' => '2026-10-07',   // Print Document (A5 / A4) -- disetujui user
        'p6-greeting-card' => '2026-10-07', // Widget sapaan (Greeting Card) -- tampilan disetujui user
        'p5-report' => '2026-09-26',
        'p5-import' => '2026-09-26',
        // Basics & Themes + Menus, Accounts & Layout disetujui user 2026-09-26
        'basic-colors' => '2026-09-26',
        'basic-typography' => '2026-09-26',
        'basic-shape' => '2026-09-26',
        'basic-themes' => '2026-09-26',
        'basic-density' => '2026-09-26',
        'basic-icons' => '2026-09-26',
        'basic-breakpoints' => '2026-09-26',
        'basic-locale' => '2026-09-26',
        'p3-feedback' => '2026-09-26',
        'p3-loading' => '2026-09-26',
        'p3-notifications' => '2026-09-26',
        'p3-errors' => '2026-09-26',
        'p4-developer' => '2026-09-26',
        'p4-guard' => '2026-09-26',
        'p4-shortcuts' => '2026-09-26',
        'p4-permissions' => '2026-09-26',
        'p4-i18n' => '2026-09-26',
        'p4-metadata' => '2026-09-26',
        'p7-menus' => '2026-09-26',
        'p7-chat' => '2026-10-02',
        'login-1' => '2026-09-26',
        'login-2' => '2026-09-26',
        'login-3' => '2026-09-26',
        'register' => '2026-09-26',
        'forgot-1' => '2026-09-26',
        'forgot-2' => '2026-09-26',
        'p8-lock' => '2026-09-26',
        'p8-security' => '2026-09-26',
    ];

    public const LOGIN_CLASSES = ['Devlib\Components\Frame', 'Devlib\Components\LoginBox', 'Devlib\Components\Clock', 'Devlib\Components\Quote',
        'Devlib\Components\Greeting', 'Devlib\Components\Weather', 'Devlib\Components\Announce', 'Devlib\Components\UatAccounts'];

    public static function groups(): array
    {
        $basic = fn ($label, $icon, $classes = [], $mockup = 'UIUX_components_part1.html') => ['label' => $label, 'icon' => $icon, 'classes' => $classes, 'mockup' => $mockup];
        $c = fn (...$cls) => array_map(fn ($x) => 'Devlib\\Components\\'.$x, $cls);
        $page = fn ($label, $icon, $members, $mockup = null, $classes = null) => ['label' => $label, 'icon' => $icon, 'mockup' => $mockup, 'members' => $members,
            'classes' => $classes ?? array_values(array_unique(array_merge([], ...array_column($members, 1))))];

        // DevLib = komponen dasar & layar standar saja (keputusan user 2026-09-24). Layar khusus -> mockup.test (packages/mockup-parts).
        $groups = [
            ['code' => 'DT', 'short' => 'DT', 'label' => 'Basics & Themes', 'icon' => 'fa-solid fa-palette',
                'pages' => [
                    'basic-colors' => $basic('Colors', 'fa-solid fa-droplet', $c('TokenTable'), 'mockup_dashboardcolor.html'),
                    'basic-typography' => $basic('Typography', 'fa-solid fa-font', $c('TokenTable')),
                    'basic-shape' => $basic('Spacing · Radius · Shadow', 'fa-solid fa-vector-square', $c('TokenTable')),
                    'basic-themes' => $basic('Themes', 'fa-solid fa-swatchbook', $c('ThemePicker', 'ThemeBoot'), 'bukti_komponen_tema_navy.html'),
                    'basic-density' => $basic('Density', 'fa-solid fa-table-cells', $c('ThemePicker'), 'grid_kerapatan_baris.html'),
                    'basic-icons' => $basic('Icons', 'fa-solid fa-icons', $c('Icon'), 'pilihan_ikon_aksi_grid.html'),
                    'basic-breakpoints' => $basic('Breakpoints', 'fa-solid fa-mobile-screen', $c('TokenTable')),
                    'basic-locale' => $basic('Locale & Formats', 'fa-solid fa-earth-asia'),
                ] + self::feedbackCategories() + self::behaviorCategories()],
            ['code' => 'IN', 'short' => 'IN', 'label' => 'Inputs & Buttons', 'icon' => 'fa-solid fa-i-cursor', 'pages' => self::smallCategories()],
            // Grid and Form (user 2026-10-21, rancangan: skills/devlib/grid-form-flow.md): SATU grup, pohon dua tingkat lewat 'folder' + 'subfolder'.
            //   Grid Report (tanpa form) | Grid Pre-Entry > Transactional (Master-Detail, Single, Approval) | Master | Lookup | Form Wizard (tanpa grid).
            //   Ujung pohon = halaman alur hidup (grid -> klik baris -> form -> Back). 'menu' => false = halaman tetap hidup lewat alamat/tautan tetapi tidak
            //   di menu (halaman APPROVED lama tidak diubah, user 2026-10-21 butir 11).
            ['code' => 'GR', 'short' => 'GR', 'label' => 'Grid and Form', 'icon' => 'fa-solid fa-table',
                'pages' => [
                    'p5-grid-report' => $page('Grid Report List', 'fa-solid fa-table-list', [
                        'grid-report' => ['Grid Report List (compact, thousands of rows, Filter pop-up)', $c('Grid')],
                    ]) + ['folder' => 'Grid Report'],
                    'p5-grid-group' => $page('Grid Group By', 'fa-solid fa-layer-group', [
                        'grid-group' => ['Grid Group By (+ subtotal)', $c('Grid')],
                    ]) + ['folder' => 'Grid Report'],
                    'p5-grid-drilldown' => $page('Grid Summary → Details', 'fa-solid fa-arrow-down-short-wide', [
                        'grid-drilldown' => ['Grid Summary → Details (drill-down)', $c('Drilldown', 'Grid')],
                    ]) + ['folder' => 'Grid Report'],
                    'p5-grid-pivot' => $page('Grid Pivot', 'fa-solid fa-table-cells-large', [
                        'grid-pivot' => ['Grid Pivot (rows × columns × values)', $c('Pivot')],
                    ]) + ['folder' => 'Grid Report'],
                    // Transactional: grid kerapatan rapat, tanpa kotak per baris; form = siklus ketat (Draft/Confirmed/Cancelled), Rupiah.
                    'p5-flow-document' => $page('Master-Detail', 'fa-solid fa-file-signature', [
                        'flow-document' => ['Master-Detail flow: Customer Order (#1) / Purchase Order (#2) grid → Form Master-Detail → Edit / Confirm / Cancel / Print → Back to the grid', $c('Grid', 'Form', 'Lines')],
                    ], 'UIUX_components_part1.html') + ['folder' => 'Grid Pre-Entry', 'subfolder' => 'Transactional'],
                    'p5-flow-single' => $page('Single', 'fa-solid fa-money-bill-wave', [
                        'flow-single' => ['Single flow: Cash Voucher grid → Single Form (one record, no lines) → Edit / Confirm / Cancel / Print → Back to the grid', $c('Grid', 'Form')],
                    ], 'UIUX_components_part1.html') + ['folder' => 'Grid Pre-Entry', 'subfolder' => 'Transactional'],
                    'p5-flow-approval' => $page('Approval', 'fa-solid fa-stamp', [
                        'flow-approval' => ['Approval flow: grid of confirmed orders awaiting a decision → Form with Approve / Hold / Reject (OTP to reverse a rejection) → Back to the grid', $c('Grid', 'Form', 'Lines')],
                    ], 'mockup_po_approval_center.html') + ['folder' => 'Grid Pre-Entry', 'subfolder' => 'Transactional'],
                    'p5-flow-master' => $page('Master', 'fa-solid fa-address-book', [
                        'flow-master' => ['Master flow: Employee grid → Master Form (Active / Inactive, no Confirm) → Edit / Deactivate → Back to the grid', $c('Grid', 'Form', 'Tabs')],
                    ], 'UIUX_components_part1.html') + ['folder' => 'Grid Pre-Entry'],
                    // Grid Lookup (user 2026-09-30, dulu Grid Special): daftar bantu kecil yang jarang berubah, diisi langsung di sel (tanpa form).
                    'p5-grid-lookup' => $page('Grid Lookup', 'fa-solid fa-list-check', [
                        'grid-lookup' => ['Grid Lookup (edit in cell: Religion)', $c('LookupGrid')],
                        'grid-lookup-decimal' => ['Grid Lookup (decimal columns: 1.250.000,50)', $c('LookupGrid')],
                        'grid-lookup-date' => ['Grid Lookup (date column: Holidays, 17-Aug-2026)', $c('LookupGrid')],
                        'grid-lookup-time' => ['Grid Lookup (time column: Work Shifts, 07:00)', $c('LookupGrid')],
                        'grid-lookup-locked' => ['Grid Lookup (code locked once saved: Supplier Category)', $c('LookupGrid')],
                    ]) + ['folder' => 'Grid Pre-Entry'],
                    // Form Wizard: berdiri sendiri, tanpa grid (user 2026-10-21).
                    'p5-form-wizard' => $page('Form Wizard', 'fa-solid fa-shoe-prints', [
                        'wizard-customer' => ['Form Wizard (step by step; Finish = Done screen, no Back)', $c('Wizard', 'Wizard\\Step')],
                    ], 'UIUX_components_part1.html'),
                    // Halaman lama (APPROVED / terkunci): tidak diubah, hanya keluar dari menu.
                    'p5-grid-tx' => $page('Grid Transactional', 'fa-solid fa-file-invoice', [
                        'grid-worklist' => ['Grid Transactional (worklist dokumen)', $c('Grid')],
                    ], 'grid_kerapatan_baris.html') + ['menu' => false],
                    'p5-grid-master' => $page('Grid Master', 'fa-solid fa-address-book', [
                        'grid-master' => ['Grid Master (box per row: image or 2-letter initials)', $c('Grid')],
                    ], 'grid_kerapatan_baris.html') + ['menu' => false],
                    'p5-form-single' => $page('Single Form', 'fa-solid fa-pen-to-square', [
                        'form-single' => ['Single Form · Edit Employee (tabs, read-only, action panel on top)', $c('Form', 'Tabs')],
                    ], 'UIUX_components_part1.html') + ['menu' => false],
                    'p5-form-md' => $page('Form Master-Detail', 'fa-solid fa-file-invoice', [
                        'form-md' => ['1 · Customer Order (key summary, header tabs, details in the middle, footer totals)', $c('Form', 'Lines')],
                        'form-md-po' => ['2 · Purchase Order (same frame, supplier side)', $c('Form', 'Lines')],
                    ], 'UIUX_components_part1.html') + ['menu' => false],
                    'p5-form-approval' => $page('Form Approval', 'fa-solid fa-stamp', [
                        'approval-po' => ['Approval Center PO (read-only details, Approve / Reject / Hold)', $c('Approval')],
                    ], 'mockup_po_approval_center.html') + ['menu' => false],
                ]],
            ['code' => 'DK', 'short' => 'DK', 'label' => 'Documents', 'icon' => 'fa-solid fa-file-lines',
                'pages' => [
                    'p5-print' => $page('Print Out', 'fa-solid fa-print', ['print-co' => ['Customer Order Note (prices, VAT, amount in words, multi-page)', $c('PrintOut')], 'print-spb' => ['Goods Receipt Note (multi-level header, no prices)', $c('PrintOut')], 'doc-share' => ['Print ▾ (PDF / JPEG / HTML online + QR / Printer) + Email (with contact picker) — on every form', $c('DocShare')]], 'nota_co_mockup.html', [...$c('PrintOut', 'DocShare'), 'Devlib\\Terbilang']),
                    'p5-print-doc' => $page('Print Document (A5 / A4)', 'fa-solid fa-file-invoice', ['print-doc-a5' => ['Delivery Note — A5 landscape (1-5 rows, no amounts, no total)', $c('PrintDoc', 'DocShare')], 'print-doc-a4' => ['Purchase Order — A4 portrait (more than 5 rows, prices, VAT, amount in words)', $c('PrintDoc', 'DocShare')], 'print-doc-draft' => ['Draft document — big slanted DRAFT watermark on the sheet (status DRAFT)', $c('PrintDoc', 'DocShare')]]),
                    'p5-report' => $page('Report', 'fa-solid fa-file-lines', ['report-stock-card' => ['Stock Card (running balance, per month)', $c('Report')], 'report-master-stock' => ['Stock Master (KPI, groups + subtotal, highlight < MIN)', $c('Report')]], 'mockup_print_master_stock.html'),
                    'p5-import' => $page('Import / Export', 'fa-solid fa-file-import', ['import-items' => ['Import CSV (column mapping, validation, failed rows)', $c('Import')], 'grid-master' => ['Export CSV from Grid (visible columns)', $c('Grid')]]),
                ]],
            ['code' => 'PJ', 'short' => 'PJ', 'label' => 'Boards, Schedules & Charts', 'icon' => 'fa-solid fa-chart-pie',
                'pages' => [
                    'p5-kanban' => $page('Kanban', 'fa-solid fa-table-columns', ['kanban-production' => ['Production Board (drag, WIP, move rules, card detail)', $c('Kanban')]], 'mockup_UIUX_kanban.html'),
                    'p5-gantt' => $page('Gantt', 'fa-solid fa-bars-staggered', ['gantt-scheduling' => ['Scheduling Board (resource × hours)', $c('Gantt')], 'gantt-production' => ['Gantt: production scheduling (2 days, lock window, setup, gates, forecast, down time, eligible machines, impact check)', $c('Gantt')]], 'mockup_scheduling_board.html'),
                    'p5-calendar' => $page('Calendar', 'fa-solid fa-calendar-days', ['calendar-events' => ['Event Calendar (day / week / month)', $c('Calendar')], 'calendar-year' => ['Year Calendar (12 months, colored marks: holidays, working days)', $c('YearCalendar')]], 'mockup_UIUX_calendar.html'),
                    'p5-org' => $page('Org Chart', 'fa-solid fa-sitemap', ['org-chart' => ['Organization Structure (drag to change superior, vacancies, multiple holders)', $c('OrgChart')]], 'Organization-Structure-Mockup.html'),
                    'p6-charts' => $page('Charts', 'fa-solid fa-chart-pie', ['chart-bars' => ['Bars: bar, stacked, horizontal, combo', $c('Chart')], 'chart-lines' => ['Line, area, radar, heatmap', $c('Chart')], 'chart-pies' => ['Pie, donut, funnel', $c('Chart')]], 'mockup_dashboardcolor.html'),
                    'p6-greeting-card' => $page('Greeting Card (widget)', 'fa-solid fa-hand', ['greeting-card' => ['Greeting widget — salutation, position, today brief, Job Description & SOP buttons, clock, check-in, weather (standalone, also used by the Opening Dashboard)', $c('GreetingCard', 'Greeting', 'Clock', 'Weather')]]),
                    'p6-dashboard' => $page('Opening Dashboard (per position)', 'fa-solid fa-house-user', [
                        'dashboard-sales' => ['1 · Sales profile (Senior Staff Sales & Marketing)', $c('Dashboard', 'Kpi', 'Chart', 'Quote')],
                        'dashboard-management' => ['2 · Management profile (President Director, Director, GM — company-wide)', $c('Dashboard', 'Kpi', 'Chart', 'Quote')],
                        'dashboard-general' => ['3 · General profile (position without its own dashboard)', $c('Dashboard', 'Kpi', 'Chart', 'Quote')],
                        'widget-board' => ['4 · Widget board (the owner arranges it: Customize, Add Widget, Reset to Template)', $c('WidgetBoard', 'Kpi', 'Chart')],
                    ], 'dashboard_home_sales.html'),
                    'p6-indicators' => $page('Dashboard Indicators', 'fa-solid fa-gauge-high', ['indicators' => ['KPI, gauge, progress ring, bullet, traffic light', $c('Kpi', 'Gauge', 'ProgressRing', 'Bullet', 'TrafficLight')]]),
                    'p6-statement' => $page('Financial Statement', 'fa-solid fa-file-invoice-dollar', ['statement' => ['Statement lines: group, line, subtotal, net total; one or more amount columns (period comparison)', $c('Statement')]]),
                    'p6-timer' => $page('Timer (stopwatch)', 'fa-solid fa-stopwatch', ['timer' => ['Stopwatch hh:mm:ss: the server gives the seconds elapsed, the browser keeps counting while running (operator setup / run clock)', $c('Timer')]]),
                    'p6-geo-clock' => $page('Geo Clock (GPS attendance)', 'fa-solid fa-location-crosshairs', ['geo-clock' => ['GPS geofencing clock in / out (distance and decision come from the server)', $c('GeoClock')]]),
                    'p6-delivery' => $page('Delivery Track', 'fa-solid fa-truck-fast', ['delivery-track' => ['Delivery route (schematic SVG, stage follows Delivery Order status)', $c('DeliveryTrack')]]),
                    'p6-codes' => $page('QR & Barcode', 'fa-solid fa-qrcode', [
                        'codes' => ['QR & Barcode (Code128, EAN-13)', $c('Qr', 'Barcode')],
                        'code-generator' => ['QR & Barcode Generator (QR with error correction, Code 128 A/B/C, Code 39, EAN-13 / 8, UPC-A / E, ITF-14, ITF, MSI, Codabar, Pharmacode; live preview, content check, PNG / SVG download, print)', $c('CodeGenerator')],
                    ]),
                    // Warehouse = submenu (user 2026-10-07): Location & Mapping | Labels | Scan & Track.
                    'p6-wh-map' => $page('Location & Mapping', 'fa-solid fa-map-location-dot', [
                        'warehouse-desk' => ['Warehouse Map desk (object x-dl::warehouse-desk): building picker and shortcuts, the map with its left bin panel, capacity per floor, summary and the Generate Bins pop-up', $c('WarehouseDesk', 'WhMap', 'Qr')],
                        'wh-map' => ['Warehouse map (building outline in metres: rectangle / L shape / any shape, floors, storage areas, bins, dead stock in red, expiry mark; click an area or a bin for the left panel with its stacks and items, stack view from the top down with swap buttons, remnant and buried marks)', $c('WhMap', 'Qr')],
                    ]) + ['folder' => 'Warehouse', 'folderIcon' => 'fa-solid fa-warehouse'],
                    'p6-wh-labels' => $page('Labels', 'fa-solid fa-tags', [
                        'wh-labels' => ['Location, item and pallet labels (colour band, QR + barcode, expiry tags, three real sizes), size and printing guide, label sheet (label roll or A4)', $c('Label', 'LabelGuide', 'LabelSheet', 'Qr', 'Barcode')],
                    ]) + ['folder' => 'Warehouse'],
                    'p6-wh-scan' => $page('Scan & Track', 'fa-solid fa-barcode', [
                        'wh-scan' => ['Scan field (scanner types the code and presses Enter; camera on a phone over https)', $c('Scan')],
                    ]) + ['folder' => 'Warehouse'],
                ]],
            ['code' => 'MA', 'short' => 'MA', 'label' => 'Menus, Accounts & Layout', 'icon' => 'fa-solid fa-table-columns',
                'pages' => [
                    'shell' => ['label' => 'Shell (main menu)', 'icon' => 'fa-solid fa-table-columns', 'full' => true, 'classes' => $c('Shell', 'LayerShell', 'LayerMenu'), 'mockup' => 'main_menu_v2.html'],
                    'screen-nav' => ['label' => 'Screen Navigation (Grid ↔ Form)', 'icon' => 'fa-solid fa-route', 'classes' => $c('Shell', 'LayerMenu', 'Breadcrumb', 'Grid')],
                    'p7-menus' => $page('Menu (Ribbon · Mega · Side Panel)', 'fa-solid fa-bars-staggered', [
                        'ribbon' => ['Excel-style Ribbon (tabs, groups, multi icons, submenu, split, toggle, KeyTips)', $c('Ribbon')],
                        'mega-menu' => ['ScriptCase-style Mega Menu (color bar + 4-column icon panel)', $c('MegaMenu')],
                        'side-menu' => ['Left & right Side Panel (nested accordion, icon rail + flyout, favorites, recently opened)', $c('SideMenu')],
                    ], 'main_menu_v2.html'),
                    'p7-layer' => $page('Layer Menu', 'fa-solid fa-layer-group', ['layer-menu' => ['Layer Menu: layered left panel (module › menu › sub › sub-sub) + gear: Chat (swap / dock right) & Profile sliding up. Also used in Shell: avatar menu › Layer Menu', $c('LayerMenu', 'Chat')]]),
                    'p7-chat' => $page('Chat (WhatsApp style)', 'fa-solid fa-comment-dots', ['chat' => ['Chat: online users in bright colour, + for offline users, profile photo, file attachment, sound on a new message', $c('Chat')]], 'main_menu_v2.html'),
                    'p7-chat-model' => $page('Chat model (contracts)', 'fa-solid fa-comments', ['chat-model' => ['Chat model: Directory, ChatStore, Presence and Uploads adapters bound by the application; ChatService produces the data and answers of x-dl::chat', ['Devlib\Chat\ChatService', 'Devlib\Chat\ChatStore', 'Devlib\Chat\Presence', 'Devlib\Chat\Uploads', 'Devlib\Chat\Directory', ...$c('Chat')]]]),
                    'p7-assistant' => $page('AI Assistant model (contracts)', 'fa-solid fa-wand-magic-sparkles', ['assistant' => ['AI Assistant model: Tools (Engine based), Navigator, Knowledge, history and a language model bound by the application; answers feed the Shell drawer', ['Devlib\Assistant\Assistant', 'Devlib\Assistant\Tool', 'Devlib\Assistant\EngineTool', 'Devlib\Assistant\LanguageModel', 'Devlib\Assistant\HistoryStore']]]),
                    'login-1' => ['label' => 'Login 1 · standard', 'icon' => 'fa-solid fa-right-to-bracket', 'full' => true, 'classes' => self::LOGIN_CLASSES, 'mockup' => 'login.html'],
                    'login-2' => ['label' => 'Login 2 · glass pop-up', 'icon' => 'fa-solid fa-right-to-bracket', 'full' => true, 'classes' => self::LOGIN_CLASSES, 'mockup' => 'login.html'],
                    'login-3' => ['label' => 'Login 3 · navy left panel', 'icon' => 'fa-solid fa-right-to-bracket', 'full' => true, 'classes' => self::LOGIN_CLASSES, 'mockup' => 'login.html'],
                    'register' => ['label' => 'Register Account', 'icon' => 'fa-solid fa-user-plus', 'full' => true, 'classes' => $c('RegisterBox', 'Frame'), 'mockup' => 'register.html'],
                    'forgot-1' => ['label' => 'Forgot Password · email', 'icon' => 'fa-solid fa-key', 'full' => true, 'classes' => $c('ForgotBox', 'Frame'), 'mockup' => 'forgot-password.html'],
                    'forgot-2' => ['label' => 'Forgot Password · 4 questions', 'icon' => 'fa-solid fa-key', 'full' => true, 'classes' => $c('ForgotBox', 'Frame'), 'mockup' => 'forgot-password.html'],
                    'p8-lock' => $page('Lock Screen', 'fa-solid fa-lock', ['lock-screen' => ['Lock screen (idle / manual / Ctrl+Shift+L)', $c('LockScreen')]]),
                    'p8-security' => $page('Setup & Security', 'fa-solid fa-user-shield', ['security' => ['Users, Groups, Objects, Group Permissions, Language, Theme (vertical tabs)', $c('Tabs', 'Grid', 'PermissionMatrix', 'ThemePicker')]], 'security.html', $c('PermissionMatrix', 'Tabs')),
                ]],
            // Daftar ENGINE (user 2026-10-02): kegunaan, input, output, cara kerja. Isinya dari EngineRegistry situs ini
            // (kontrak Devlib\Engine), objeknya x-dl::engine-catalog.
            ['code' => 'EG', 'short' => 'EG', 'label' => 'Engines', 'icon' => 'fa-solid fa-gears',
                'pages' => [
                    'p9-engines' => $page('Engine Catalog', 'fa-solid fa-gears', ['engines' => ['Engine Catalog: every engine with its input, output and how it works', $c('EngineCatalog')]]),
                    // Papan Build Progress (user 2026-10-05): isinya disusun dari katalog DevLib sendiri (`php artisan build:catalog`
                    // -> resources/data/build.json), objeknya x-dl::build-board; tampil juga di halaman depan.
                    'p9-build' => $page('Build Progress', 'fa-solid fa-chart-simple', ['build' => ['Build Progress: objects, modules, screens, forms, grids, engines and features built so far', $c('BuildBoard')]]),
                ]],
            // Aturan untuk developer, bukan komponen yang dipakai user (user 2026-09-26). Halaman dokumen = skills/devlib/*.md apa adanya (RulesDocs).
            ['code' => 'DR', 'short' => 'DR', 'label' => 'DEV Rules', 'icon' => 'fa-solid fa-code',
                'pages' => RulesDocs::pages() + [
                    'p5-override' => $page('OO Methods and Properties', 'fa-solid fa-code-branch', ['oo-override' => ['Kanban subclass: properties overridden in PHP, methods overridden in JS (super)', ['App\\View\\Components\\Demo\\KanbanProduksi', ...$c('Kanban')]]], null, $c('Kanban')),
                ]],
            // Bagian Flow (user 2026-10-21): diagram alur dengan x-dl::flow; contoh fiktif, belum ditinjau user (bukan APPROVED).
            ['code' => 'FL', 'short' => 'FL', 'label' => 'Flow Diagrams', 'icon' => 'fa-solid fa-diagram-project',
                'pages' => [
                    'p10-system-flow' => $page('System Flow', 'fa-solid fa-diagram-project', ['system-flow' => ['System Flow: how modules hand a document to each other (blocks follow the document, status per block)', $c('Flow')]]),
                    'p10-data-flow' => $page('Data Flow', 'fa-solid fa-database', ['data-flow' => ['Data Flow: where a value comes from, which engine handles it and where it is stored', $c('Flow')]]),
                    'p10-work-flow' => $page('Work Flow', 'fa-solid fa-list-check', ['work-flow' => ['Work Flow: the document life cycle Draft > Confirmed > Closed, with the Cancelled branch and who acts', $c('Flow')]]),
                ]],
        ];
        $groups = self::arrange($groups);
        // Label breadcrumb halaman kategori = nama grupnya.
        foreach ($groups as &$g) {
            foreach ($g['pages'] as &$p) {
                $p['part'] = $g['label'];
                $p['trail'] = array_values(array_filter([$g['label'], $p['folder'] ?? null, $p['subfolder'] ?? null]));
            }
        }

        return $groups;
    }

    // Struktur menu (user 2026-10-21): bagian > grup > (folder) > halaman. Definisi halaman tetap di groups() (slug lama utuh);
    // di sini hanya PENEMPATANNYA. 'from' = ambil semua halaman grup lama berkode itu; 'pages' = slug; 'folders' = slug => [folder, ikon].
    // Bagian tampil sebagai judul kelompok di akar Layer Menu (LayerShell: 'section' pada modul).
    private static function layout(): array
    {
        $in = fn (string $prefix, array $slugs) => array_map(fn ($s) => $prefix.$s, $slugs);

        return [
            'Foundations & Rules' => [
                ['code' => 'DT', 'label' => 'Basics & Themes', 'icon' => 'fa-solid fa-palette', 'pages' => $in('basic-', ['colors', 'typography', 'shape', 'themes', 'density', 'icons', 'breakpoints', 'locale'])],
                ['code' => 'BH', 'label' => 'Global Behaviors', 'icon' => 'fa-solid fa-sliders', 'pages' => $in('p4-', ['developer', 'guard', 'shortcuts', 'permissions', 'i18n', 'metadata'])],
                ['code' => 'DR', 'label' => 'Rules', 'icon' => 'fa-solid fa-code', 'pages' => [...array_keys(RulesDocs::pages()), 'p5-override'],
                    'folders' => ['p5-override' => ['Develop & Coding']]],
            ],
            'Components' => [
                ['code' => 'IN', 'label' => 'Inputs & Buttons', 'icon' => 'fa-solid fa-i-cursor', 'pages' => $in('p2-', ['inputs', 'numbers', 'datetime', 'choices', 'files', 'buttons'])],
                ['code' => 'DF', 'label' => 'Display & Feedback', 'icon' => 'fa-solid fa-comment-dots', 'pages' => [...$in('p2-', ['display', 'containers', 'navigation']), ...$in('p3-', ['feedback', 'loading', 'notifications', 'errors'])]],
                ['code' => 'WU', 'label' => 'Widgets & Utilities', 'icon' => 'fa-solid fa-puzzle-piece', 'pages' => ['p6-greeting-card', 'p6-timer', 'p6-geo-clock', 'p6-codes']],
            ],
            'Screen' => [
                ['code' => 'GR', 'from' => ['GR', 'FM']],
                ['code' => 'CH', 'label' => 'Charts & Dashboards', 'icon' => 'fa-solid fa-chart-pie', 'pages' => ['p6-charts', 'p6-indicators', 'p6-dashboard', 'p5-org']],
                ['code' => 'SC', 'label' => 'Schedule', 'icon' => 'fa-solid fa-calendar-days', 'pages' => ['p5-kanban', 'p5-gantt', 'p5-calendar']],
                ['code' => 'DK', 'label' => 'Print & Report', 'icon' => 'fa-solid fa-print', 'from' => ['DK'], 'pages' => ['p6-statement']],
                ['code' => 'SP', 'label' => 'Special Screen', 'icon' => 'fa-solid fa-star', 'pages' => ['p6-wh-map', 'p6-wh-labels', 'p6-wh-scan', 'p6-delivery', 'p7-chat', 'p7-chat-model', 'p7-assistant'],
                    'folders' => ['p6-delivery' => ['Warehouse', 'fa-solid fa-warehouse'], 'p7-chat' => ['Chat', 'fa-solid fa-comments'], 'p7-chat-model' => ['Chat', 'fa-solid fa-comments'], 'p7-assistant' => ['Chat', 'fa-solid fa-comments']]],
                ['code' => 'MA', 'label' => 'Shell, Account & Security', 'icon' => 'fa-solid fa-table-columns', 'pages' => ['shell', 'screen-nav', 'p7-menus', 'p7-layer', 'login-1', 'login-2', 'login-3', 'register', 'forgot-1', 'forgot-2', 'p8-lock', 'p8-security']],
            ],
            'Flow' => [['code' => 'FL', 'from' => ['FL']]],
            'Engine' => [['code' => 'EG', 'from' => ['EG']]],
        ];
    }

    // Menyusun ulang halaman definisi groups() menurut layout(). Halaman yang belum ditempatkan masuk grup "Unplaced" (kelihatan salah di menu; dijaga tes).
    private static function arrange(array $old): array
    {
        $meta = array_column($old, null, 'code');
        $pool = array_merge([], ...array_map(fn ($g) => $g['pages'], $old));
        $groups = [];
        foreach (self::layout() as $section => $defs) {
            foreach ($defs as $d) {
                $pages = [];
                foreach ($d['from'] ?? [] as $code) {
                    foreach ($meta[$code]['pages'] ?? [] as $slug => $p) {
                        $pages[$slug] = $p;
                        unset($pool[$slug]);
                    }
                }
                foreach ($d['pages'] ?? [] as $slug) {
                    if (isset($pool[$slug])) {
                        $pages[$slug] = $pool[$slug];
                        unset($pool[$slug]);
                    }
                }
                foreach ($d['folders'] ?? [] as $slug => $f) {
                    if (isset($pages[$slug])) {
                        $pages[$slug]['folder'] = $f[0];
                        isset($f[1]) && $pages[$slug]['folderIcon'] = $f[1];
                    }
                }
                $first = $meta[$d['from'][0] ?? ''] ?? [];
                $groups[] = ['code' => $d['code'], 'short' => $d['code'], 'label' => $d['label'] ?? $first['label'], 'icon' => $d['icon'] ?? $first['icon'], 'section' => $section, 'pages' => $pages];
            }
        }
        if ($pool) {
            $groups[] = ['code' => 'XX', 'short' => 'XX', 'label' => 'Unplaced', 'icon' => 'fa-solid fa-circle-question', 'section' => 'Unplaced', 'pages' => $pool];
        }

        return $groups;
    }

    // Part 2 dikelompokkan per kategori (menu kiri ringkas); isi kategori dipilih lewat breadcrumb.
    // member: slug demo => [label, [class...]]
    public static function smallCategories(): array
    {
        $f = fn ($cls) => ['Devlib\\Components\\Field\\'.$cls];
        $c = fn (...$cls) => array_map(fn ($x) => 'Devlib\\Components\\'.$x, $cls);

        // Sembilan kelompok per jenis data (user 2026-10-05). Unsur ke-3 tiap anggota = mode yang dimiliki objeknya:
        // both = ReadWrite + ReadOnly (bawaan field), rw = hanya input / aksi, ro = hanya tampil, wide = tata letak (selebar halaman).
        $cats = [
            'p2-inputs' => ['Text / String', 'fa-solid fa-i-cursor', [
                'field-text' => ['Text', $f('Text')], 'text-affix' => ['Prefix · Suffix · Counter', $f('Text')], 'field-password' => ['Password', $f('Password')],
                'field-masked' => ['Masked (NIK)', $f('Masked')], 'field-phone' => ['Phone (mobile & office, per country)', $f('Phone')], 'field-credit-card' => ['Credit Card', $f('CreditCard')],
                'field-otp' => ['OTP', $f('Otp')], 'field-tags' => ['Tags', $f('Tags')],
                'field-key-value' => ['Key Value', $f('KeyValue')], 'field-rich-text' => ['Rich Text', $f('RichText')],
                'field-display' => ['Display (text · mono · number · money · date)', $f('Display'), 'ro'], 'input-theme' => ['Input · Themes', $f('Text'), 'wide'],
            ]],
            'p2-numbers' => ['Numeric', 'fa-solid fa-calculator', [
                'field-number' => ['Integer (spinner)', $f('Number')], 'field-decimal' => ['Decimal', $f('Decimal')], 'field-money' => ['Money', $f('Money')],
                'field-slider' => ['Slider (range · vertical)', $f('Slider')], 'field-rating' => ['Rating (half)', $f('Rating')],
            ]],
            'p2-datetime' => ['Date & Time', 'fa-solid fa-calendar-days', [
                'field-date' => ['Date', $f('Date')], 'field-time' => ['Time', $f('Time')], 'field-date-time' => ['Date Time', $f('DateTime')], 'field-date-range' => ['Date Range', $f('DateRange')], 'field-date-slider' => ['Date Slider', $f('DateSlider')],
            ]],
            'p2-choices' => ['Choice & Lookup', 'fa-solid fa-list-check', [
                'field-lookup' => ['Lookup', $f('Lookup')], 'field-select' => ['Select', $f('Select')], 'field-multi-select' => ['Multi Select', $f('MultiSelect')],
                'field-radio' => ['Radio', $f('Radio')], 'field-checkboxes' => ['Checkboxes', $f('Checkboxes')], 'field-checkbox' => ['Checkbox', $f('Checkbox')],
                'field-toggle' => ['Toggle', $f('Toggle')], 'field-toggle-group' => ['Toggle Group', $f('ToggleGroup')], 'field-color' => ['Color', $f('Color')],
                'code-chain' => ['Code Chain (linked dropdowns that build a code)', $c('CodeChain'), 'wide'], 'pick-matrix' => ['Pick Matrix (pilih pemenang per baris)', $c('PickMatrix'), 'wide'],
                'member-list' => ['Member List (people + role, Add / Remove, limited rows)', $c('MemberList'), 'rw'],
            ]],
            'p2-files' => ['Files', 'fa-solid fa-paperclip', ['field-upload' => ['Upload', $f('Upload')], 'field-signature' => ['Signature', $f('Signature')]]],
            'p2-buttons' => ['Button', 'fa-solid fa-hand-pointer', [
                'button' => ['Button · Preset · Split', $c('Button', 'SplitButton', 'BackButton'), 'rw'], 'icon-button' => ['Icon Button', $c('IconButton'), 'rw'],
            ]],
            'p2-display' => ['Status', 'fa-solid fa-tag', [
                'badge' => ['Badge', $c('Badge'), 'ro'], 'stat-box' => ['Stat Box', $c('StatBox'), 'ro'], 'status-card' => ['Status Card', $c('StatusCard'), 'ro'],
                'avatar-progress' => ['Avatar · Progress', $c('Avatar', 'Progress'), 'ro'], 'login-card' => ['Login Card (new employee, print once)', $c('LoginCard', 'Button'), 'ro'], 'photo-box' => ['Photo Box (3x4 photo, click to crop)', $c('PhotoBox', 'Modal', 'Button'), 'rw'], 'profile-banner' => ['Profile Banner (photo, grouped chips, KPI tiles)', $c('ProfileBanner', 'PhotoBox', 'Badge', 'Progress', 'Kpi'), 'ro'], 'switcher' => ['Switcher (dropdown swaps the panel below)', $c('Switcher', 'SwitchCase'), 'ro'], 'tooltip' => ['Tooltip', $c('Tooltip'), 'ro'], 'misc' => ['Alert · Divider · Brand · Language', $c('Alert', 'Divider', 'Brand', 'LangSwitch'), 'ro'],
                    'feed' => ['Feed (blog / news: narratives read in one page)', $c('Feed', 'Badge'), 'ro'],
                    'review-feed' => ['Review Feed (blog-style review: read a card, decide with big buttons)', $c('ReviewFeed', 'Feed'), 'rw'],
            ]],
            'p2-containers' => ['Container', 'fa-solid fa-table-cells-large', [
                'card-actionbar' => ['Card · Action Bar', $c('Card', 'ActionBar'), 'wide'], 'split' => ['Split (isi + panel samping kanan / kiri)', $c('Split'), 'wide'], 'table' => ['Table', $c('Table'), 'wide'], 'accordion' => ['Accordion (vertical · horizontal)', $c('Accordion', 'AccordionGroup'), 'wide'],
                'side-screen' => ['Side Screen (full-screen window: float · move · resize; sections rail, sticky foot)', $c('SideScreen', 'SideScreen\Layout', 'SideScreen\Foot'), 'wide'],
            ]],
            'p2-navigation' => ['Navigation', 'fa-solid fa-route', [
                'tabs-accordion' => ['Tabs', $c('Tabs'), 'wide'], 'breadcrumb' => ['Breadcrumb', $c('Breadcrumb'), 'wide'], 'pagination' => ['Pagination', $c('Pagination'), 'wide'],
            ]],
        ];

        $out = [];
        foreach ($cats as $slug => [$label, $icon, $members]) {
            $members = array_map(fn ($m) => $m + [2 => 'both'], $members);
            $out[$slug] = ['label' => $label, 'icon' => $icon, 'members' => $members, 'modes' => true, 'part' => '2 · Small Components',
                'classes' => array_values(array_unique(array_merge(...array_column($members, 1)))), 'mockup' => 'UIUX_components_part1.html'];
        }

        return $out;
    }

    public static function feedbackCategories(): array
    {
        $c = fn (...$cls) => array_map(fn ($x) => 'Devlib\\Components\\'.$x, $cls);
        $cats = [
            'p3-feedback' => ['Toast · Confirm · Modal · Drawer', 'fa-solid fa-comment-dots', [
                'toast' => ['Toast', []], 'confirm' => ['Confirm modal', $c('Button', 'IconButton')], 'modal-drawer' => ['Modal · Drawer', $c('Modal', 'Drawer')],
            ]],
            'p3-loading' => ['Loading · Empty', 'fa-solid fa-spinner', [
                'skeleton' => ['Skeleton', $c('Skeleton')], 'spinner' => ['Spinner', $c('Spinner')], 'empty-state' => ['Empty State', $c('EmptyState')],
            ]],
            'p3-notifications' => ['Notifications', 'fa-solid fa-bell', ['notification-list' => ['Notification List', $c('NotificationList')]], 'mockup_notification.jpg'],
            'p3-errors' => ['Error Pages', 'fa-solid fa-triangle-exclamation', ['error-pages' => ['403 · 404 · 419 · 500 · 503 · Offline', $c('ErrorPage')]]],
        ];
        $out = [];
        foreach ($cats as $slug => $cat) {
            [$label, $icon, $members] = $cat;
            $out[$slug] = ['label' => $label, 'icon' => $icon, 'members' => $members, 'part' => '3 · Feedback & States',
                'classes' => array_values(array_unique(array_merge([], ...array_column($members, 1)))), 'mockup' => $cat[3] ?? null];
        }

        return $out;
    }

    public static function behaviorCategories(): array
    {
        $c = fn (...$cls) => array_map(fn ($x) => 'Devlib\\Components\\'.$x, $cls);
        $cats = [
            'p4-developer' => ['Developer Mode', 'fa-solid fa-bug', ['dev-mode' => ['Developer Mode', $c('DevToggle')]]],
            'p4-guard' => ['Dirty Guard', 'fa-solid fa-shield-halved', ['dirty-guard' => ['Dirty Guard', $c('Form', 'BackButton')]]],
            'p4-shortcuts' => ['Keyboard Shortcuts', 'fa-solid fa-keyboard', ['shortcuts' => ['Shortcuts', $c('Button')]]],
            'p4-permissions' => ['Permissions', 'fa-solid fa-user-lock', ['permissions' => ['can="..." on every component', $c('DevlibComponent')]]],
            'p4-i18n' => ['Multi-language', 'fa-solid fa-language', ['i18n' => ['ID / EN', $c('LangSwitch', 'LangText')]]],
            'p4-metadata' => ['Metadata', 'fa-solid fa-circle-info', ['metadata' => ['Created / updated by', $c('Meta')]]],
        ];
        $out = [];
        foreach ($cats as $slug => [$label, $icon, $members]) {
            $out[$slug] = ['label' => $label, 'icon' => $icon, 'members' => $members, 'part' => '4 · Global Behaviors',
                'classes' => array_values(array_unique(array_merge([], ...array_column($members, 1)))), 'mockup' => null];
        }

        return $out;
    }

    // Angka library ini sendiri untuk x-dl::build-board (prop library).
    public static function stats(): array
    {
        $groups = self::groups();
        // Halaman dokumen Aturan (skills/devlib/*.md) bukan objek katalog: tidak dihitung.
        $slugs = array_merge([], ...array_map(fn ($g) => array_keys(array_filter($g['pages'], fn ($p) => ! isset($p['doc']))), $groups));

        return [
            'groups' => array_column($groups, 'label'),
            'pages' => count($slugs),
            'approved' => count(array_intersect($slugs, array_keys(self::APPROVED))),
            'demos' => count(glob(resource_path('views/demos/*.blade.php'))),
            'packages' => array_values(array_diff(array_map('basename', glob(base_path('packages/*'), GLOB_ONLYDIR)), ['devlib'])),
        ];
    }

    // Isi papan Build Progress: resources/data/build.json, disusun `php artisan build:catalog` dari katalog DevLib sendiri.
    public static function build(): array
    {
        $file = base_path('resources/data/build.json');

        return is_file($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];
    }

    public static function page(string $slug): ?array
    {
        foreach (self::groups() as $g) {
            if (isset($g['pages'][$slug])) {
                return $g['pages'][$slug] + ['slug' => $slug, 'full' => false];
            }
        }

        return null;
    }

    // Bentuk siap-pakai untuk <x-dl::shell :modules>. Menu DevLib = komponen saja; mockup (termasuk yang
    // belum dibangun) pindah ke mockup.test (Fase 2), tetap bisa dibuka dari tautan mockup di ikon </>.
    public static function modules(): array
    {
        $modules = [];
        // Ikon status saja (tanpa teks), selaras dengan bilah QC: keputusan QC terakhir; konstanta APPROVED tetap dihormati.
        $qcStatus = Qc::store()->statusMap();
        $mark = fn (string $slug) => match (true) {
            isset(self::APPROVED[$slug]), ($qcStatus[$slug] ?? null) === 'APPROVED' => ['badge' => 'icon:fa-solid fa-circle-check', 'badgeTone' => 'done', 'badgeTitle' => 'Approved'],
            ($qcStatus[$slug] ?? null) === 'NEEDS_IMPROVEMENT' => ['badge' => 'icon:fa-solid fa-pen-to-square', 'badgeTone' => 'danger', 'badgeTitle' => 'Need improvement'],
            default => ['badge' => 'icon:fa-regular fa-eye', 'badgeTone' => 'partial', 'badgeTitle' => 'Review'],
        };
        foreach (self::groups() as $g) {
            $items = [];
            foreach ($g['pages'] as $slug => $p) {
                if (($p['menu'] ?? true) === false) {
                    continue;
                }
                // Halaman dokumen Aturan bukan objek yang ditinjau: tanpa ikon status.
                $item = ['code' => 'P-'.$slug, 'label' => $p['label'], 'icon' => $p['icon'], 'url' => route('lib', ['slug' => $slug]),
                    'type' => 'form'] + (isset($p['doc']) ? [] : $mark($slug));
                if (! isset($p['folder'])) {
                    $items[] = $item;

                    continue;
                }
                // Halaman ber-folder dikumpulkan jadi submenu (posisi = halaman pertamanya); 'subfolder' = submenu di dalam folder.
                $at = array_search($p['folder'], array_column($items, 'label'), true);
                if ($at === false) {
                    $items[] = ['label' => $p['folder'], 'icon' => $p['folderIcon'] ?? 'fa-solid fa-folder', 'items' => []];
                    $at = array_key_last($items);
                }
                if (! isset($p['subfolder'])) {
                    $items[$at]['items'][] = $item;

                    continue;
                }
                $sub = array_search($p['subfolder'], array_column($items[$at]['items'], 'label'), true);
                if ($sub === false) {
                    $items[$at]['items'][] = ['label' => $p['subfolder'], 'icon' => 'fa-solid fa-folder', 'items' => []];
                    $sub = array_key_last($items[$at]['items']);
                }
                $items[$at]['items'][$sub]['items'][] = $item;
            }
            $modules[] = ['code' => $g['code'], 'short' => $g['short'], 'section' => $g['section'] ?? null, 'label' => $g['label'], 'icon' => $g['icon'], 'children' => $items]; // langsung isi modul, tanpa lapis "Components" (user 2026-09-30: membingungkan)
        }
        // Generator (user 2026-10-05): form permintaan + daftar permintaan, isi dari database devlib.
        $modules[] = Generator::menu() + ['section' => 'Tools'];
        // Menu kedua Layer Menu (ikon tukar Operational | Executive Summary): modul berkode MGMT.
        $modules[] = Summary::menu();

        return $modules;
    }

    public static function find(string $file): ?array
    {
        return self::files()[$file] ?? null;
    }

    // Path absolut di dalam folder mockup, null bila di luar (anti traversal).
    public static function path(string $file): ?string
    {
        $real = realpath(config('devlib.mockup_path').DIRECTORY_SEPARATOR.$file);
        $root = realpath(config('devlib.mockup_path'));

        return $real && $root && is_file($real) && str_starts_with($real, $root.DIRECTORY_SEPARATOR) ? $real : null;
    }

    /** @return array<string, array{file: string, title: string, type: string}> */
    private static function files(): array
    {
        $items = [];
        $root = config('devlib.mockup_path');
        if (! is_dir($root)) {
            return $items;
        }

        foreach ((new Finder)->files()->in($root)->name(['*.html', '*.jpg', '*.jpeg', '*.png'])->sortByName() as $f) {
            $rel = str_replace('\\', '/', $f->getRelativePathname());
            // Gambar yang punya pasangan .html bernama sama = lampiran, bukan item sendiri.
            if (strtolower($f->getExtension()) !== 'html' && is_file(preg_replace('/\\.(jpe?g|png)$/i', '.html', $f->getPathname()))) {
                continue;
            }
            $base = preg_replace('/^(mockup_|MODULE_)/', '', pathinfo($f->getFilename(), PATHINFO_FILENAME));
            $items[$rel] = [
                'file' => $rel,
                'title' => Str::of($base)->replace(['_', '-'], ' ')->title()->toString(),
                'type' => strtolower($f->getExtension()) === 'html' ? 'html' : 'image',
            ];
        }

        return $items;
    }
}
