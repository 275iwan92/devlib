<?php

/*
 * REMARK
 * Tujuan       : Approval Center (mockup pr/po_approval_center): daftar dokumen menunggu (kiri) + detail (kanan):.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : docs, lineColumns, title, approveLabel, holdLabel, rejectLabel, me, source, action, log, single, back.
 * Output       : Markup Blade inline (render() kelas ini).
 * Dipakai oleh : approval-po (layar katalog/proyek).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Approval Center (mockup pr/po_approval_center): daftar dokumen menunggu (kiri) + detail (kanan):
// ringkasan, baris item, alur approval berjenjang (PLT ditandai), riwayat, Tolak / Tahan / Setujui.
// Hanya-baca (user 2026-09-30): tidak ada isian di layar; alasan Tolak/Tahan wajib, diminta di dialog konfirmasi.
// docs: [['id','no','title','amount','party','date','submitted_by','summary' => [label => nilai],
//         'lines' => [[...]], 'steps' => [['role','by','at','status' => done|current|pending','plt','submitter']],
//         submitter = pembuat (bukan level approval); sisanya = Level 1..n, yang 'current' = level berjalan.
//         'history' => [['action','by','at','note','plt']]]]
// lineColumns: [kolom => label]; kolom berakhiran :money diformat Rupiah (mis. 'price:money').
// :action = URL POST (id, action=approve|reject|hold, _reason) -> keputusan disimpan server lalu halaman dimuat ulang;
// tanpa :action = simulasi di browser (demo DevLib). :log = Log Keputusan awal dari DB.
// :single = satu dokumen saja (form yang dibuka dari grid): tanpa daftar kiri; doc 'readonly' => true (sudah diputuskan) tanpa tombol keputusan.
// :back = alamat grid asal (mode single, simulasi): setelah keputusan final (Setujui/Reject) halaman kembali ke sana; Hold tetap di form.
// Setiap keputusan = LOG (user 2026-09-30): siapa, kapan, aksi (APPROVED/REJECTED/HOLD), level & alasan. Tampil di
// Riwayat Dokumen + panel Log Keputusan, dan dipancarkan sebagai event dl:approval
// (detail: {id, no, action, status, by, at, level, role, note}) -- aplikasi WAJIB menyimpannya (mis. flow_log / doc_state_log).
class Approval extends DevlibComponent
{
    public function __construct(
        public array $docs = [],
        public array $lineColumns = [],
        public string $title = 'Waiting for My Approval',
        public string $approveLabel = 'Approve',
        public ?string $holdLabel = 'Hold',
        public string $rejectLabel = 'Reject',
        public string $me = 'Me',
        public ?string $source = null,
        public ?string $action = null,
        public array $log = [],
        public bool $single = false,
        public ?string $back = null,
    ) {}

    public function tag(): string
    {
        return 'x-dl::approval';
    }

    public function source(): string
    {
        return $this->source ?? 'dokumen menunggu approval';
    }

    public function render()
    {
        return <<<'BLADE'
            <div {{ $attributes->class(['dl-appr', 'dl-appr--single' => $single]) }} {!! $dl() !!} data-dl-approval data-docs="{{ json_encode($docs) }}" data-cols="{{ json_encode($lineColumns) }}" data-me="{{ $me === 'Me' ? \Devlib\Lang::text('Me') : $me }}" data-log="{{ json_encode($log) }}"@if ($back) data-back="{{ $back }}"@endif>
                @if ($action)<form method="post" action="{{ $action }}" data-a-form hidden>@csrf<input type="hidden" name="id"><input type="hidden" name="action"><input type="hidden" name="_reason"></form>@endif
                <div class="dl-appr__head"><h2>{{ $title }}</h2><span class="dl-badge dl-badge--pill dl-badge--warn" data-a-count></span></div>
                <div class="dl-appr__grid">
                    <div class="dl-appr__side">
                        <div class="dl-appr__list" data-a-list></div>
                        <div class="dl-appr__log" data-a-log hidden><p class="dl-appr__cap">{{ \Devlib\Lang::text('Decision Log') }}</p><ul data-a-logrows></ul></div>
                    </div>
                    <div class="dl-appr__detail" data-a-detail>
                        <div data-a-body></div>
                        {{ $slot }}
                        <div class="dl-appr__action" data-a-action>
                            <div class="dl-appr__buttons">
                                <x-dl::button preset="reject" :label="$rejectLabel" data-a-do="reject" />
                                @if ($holdLabel)<x-dl::button preset="hold" :label="$holdLabel" data-a-do="hold" />@endif
                                <x-dl::button preset="approve" :label="$approveLabel" data-a-do="approve" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            BLADE;
    }
}
