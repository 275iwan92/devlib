<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

// Alur Grid -> Form -> kembali di katalog (aturan: skills/devlib/grid-form-flow.md). Tanpa basis data: baris dari DemoData, hasil aksi
// hanya disimulasikan (toast + keadaan form dibawa di alamat: status, from, doc, as). Siklus ketat (user 2026-10-21):
// Save dan Confirm TETAP di form; hanya Back (dan Delete, karena barisnya hilang) kembali ke grid.
final class FlowDemo
{
    private const FLOWS = [
        'p5-flow-master' => ['title' => 'Employee', 'kind' => 'master', 'demos' => ['' => 'form-master']],
        'p5-flow-document' => ['title' => 'Document', 'kind' => 'document', 'demos' => ['co' => 'form-md', 'po' => 'form-md-po']],
        'p5-flow-single' => ['title' => 'Cash Voucher', 'kind' => 'document', 'demos' => ['' => 'form-cash-voucher']],
        'p5-flow-approval' => ['title' => 'Customer Order', 'kind' => 'document', 'demos' => ['' => 'form-approval']],
    ];

    // Isian wajib per alur (kunci field => label); dicek server sebagai cadangan validasi klien.
    private const REQUIRED = [
        'p5-flow-master' => ['full_name' => 'Full name'],
        'p5-flow-single' => ['payee' => 'Payee', 'amount' => 'Amount'],
        'co' => ['co_date' => 'Order date', 'customer' => 'Customer'],
        'po' => ['po_date' => 'PO date', 'supplier' => 'Supplier'],
    ];


    // Save pada record baru: demo tanpa penyimpanan, jadi form pindah ke record contoh bernomor ini (…/form/999).
    public const CREATED_ID = '999';

    // Cetakan form alur (tab baru): membawa status dokumen dan approval yang sedang tampil.
    public static function printUrl(string $slug, string $id, string $doc, string $status, ?string $approval): string
    {
        return '/lib/'.$slug.'/print/'.$id.'?'.http_build_query(array_filter(['doc' => $doc ?: null, 'status' => $status, 'approval' => $approval]));
    }

    public static function has(string $slug): bool
    {
        return isset(self::FLOWS[$slug]);
    }

    public static function isMaster(string $slug): bool
    {
        return self::FLOWS[$slug]['kind'] === 'master';
    }

    // '' untuk alur tanpa pilihan dokumen; selain itu co|po (bawaan co = Customer Order, contoh #1).
    public static function doc(string $slug, ?string $doc): string
    {
        $docs = array_keys(self::FLOWS[$slug]['demos']);

        return in_array($doc, $docs, true) ? $doc : reset($docs);
    }

    public static function demo(string $slug, string $doc): string
    {
        return self::FLOWS[$slug]['demos'][$doc];
    }

    public static function title(string $slug): string
    {
        return self::FLOWS[$slug]['title'];
    }

    // Alamat grid asal (path + query lokal).
    public static function gridUrl(string $slug, string $doc = ''): string
    {
        return '/lib/'.$slug.($doc !== '' && count(self::FLOWS[$slug]['demos']) > 1 ? '?doc='.$doc : '');
    }

    // Hanya path lokal /lib/<slug>[?query]; selain itu (kosong, //host, http://) -> grid bawaan.
    public static function back(string $slug, string $doc, ?string $from): string
    {
        return is_string($from) && preg_match('#^/lib/[A-Za-z0-9_-]+(\?[A-Za-z0-9_=&%.-]*)?$#', $from) ? $from : self::gridUrl($slug, $doc);
    }

    // {id} dibiarkan apa adanya supaya bisa dipakai sebagai templat openUrl grid.
    public static function formUrl(string $slug, string $id, string $doc, string $from, array $extra = []): string
    {
        $q = array_filter(['doc' => $doc ?: null, 'from' => $from] + $extra, fn ($v) => $v !== null);

        return '/lib/'.$slug.'/form/'.$id.'?'.http_build_query($q);
    }

    public static function rows(string $slug, string $doc): array
    {
        return match ($slug) {
            'p5-flow-master' => DemoData::employees(),
            'p5-flow-single' => DemoData::cashVouchers(),
            'p5-flow-approval' => DemoData::approvalOrders(),
            default => $doc === 'co' ? DemoData::customerOrders() : DemoData::purchaseOrders(),
        };
    }

    public static function row(string $slug, string $doc, string $id): ?array
    {
        $rows = self::rows($slug, $doc);
        foreach ($rows as $r) {
            if ((string) $r['id'] === $id) {
                return $r;
            }
        }
        if ($id !== self::CREATED_ID || ! $rows) {
            return null;
        }
        // Record "baru saja disimpan": salinan baris pertama dengan nomor …0999, Draft (dokumen) atau Active (master).
        $r = ['id' => (int) self::CREATED_ID] + $rows[0];
        foreach (['co_no', 'po_no', 'cv_no', 'code'] as $k) {
            if (isset($r[$k])) {
                $r[$k] = preg_replace('/\d+$/', '0999', (string) $r[$k]);
            }
        }
        if (isset($r['status']) && ! isset($r['active'])) {
            $r['status'] = 'Draft';
        }
        if (isset($r['active'])) {
            $r['active'] = true;
        }

        return $r;
    }

    // Status form dari record: master = active/inactive; dokumen ikut status barisnya (Draft/Confirmed/Cancelled/Closed).
    public static function status(string $slug, string $doc, ?array $row): string
    {
        if (self::isMaster($slug)) {
            return $row ? ($row['active'] ? 'active' : 'inactive') : 'new';
        }

        return $row ? strtolower($row['status']) : 'draft';
    }

    // Baris grid Approval -> dokumen untuk x-dl::approval (Form Approval baku, hanya-baca). approval: waiting|hold|approved|rejected.
    // Level 1 sudah selesai; Level 2 mengikuti status approval; Level 3 (Direksi) hanya untuk nilai > IDR 50 jt. Pihak fiktif.
    public static function approvalDoc(array $r): array
    {
        $d = fn (string $date, string $t = '') => \Devlib\Locale::date($date).($t ? ' '.$t : '');
        $ap = $r['approval'];
        $lv2 = ['waiting' => 'current', 'hold' => 'current', 'approved' => 'done', 'rejected' => 'rejected'][$ap];
        $steps = [['role' => 'Pembuat', 'by' => 'Rina Wijaya', 'at' => $d($r['co_date']), 'status' => 'done', 'submitter' => true],
            ['role' => 'Kepala Penjualan', 'by' => 'Dewi Kusuma', 'at' => $d(date('Y-m-d', strtotime($r['co_date'].' +1 day'))), 'status' => 'done'],
            ['role' => 'Operational Manager'] + ($lv2 === 'current' ? ['status' => 'current'] : ['by' => 'Operational Manager', 'at' => $d($r['ship_date']), 'status' => $lv2])];
        if ($r['total'] > 50000000) {
            $steps[] = ['role' => 'Direksi (> IDR 50 jt)', 'status' => 'pending'];
        }
        $history = [['action' => 'SUBMITTED', 'by' => 'Rina Wijaya', 'at' => $d($r['co_date'], '10:05')],
            ['action' => 'APPROVED', 'by' => 'Dewi Kusuma', 'at' => $d(date('Y-m-d', strtotime($r['co_date'].' +1 day')), '15:40'), 'note' => 'Harga sesuai kesepakatan.']];
        $last = ['approved' => ['APPROVED', 'Siap diproduksi.'], 'rejected' => ['REJECTED', 'Harga belum sesuai, mohon revisi.'], 'hold' => ['HOLD', 'Menunggu konfirmasi kredit customer.']][$ap] ?? null;
        if ($last) {
            $history[] = ['action' => $last[0], 'by' => 'Operational Manager', 'at' => $d($r['ship_date'], '09:15'), 'note' => $last[1]];
        }

        return [
            'id' => $r['id'], 'no' => $r['co_no'], 'title' => 'Customer Order', 'amount' => $r['total'], 'party' => $r['customer'], 'date' => $r['co_date'],
            'submitted_by' => 'Rina Wijaya (Sales)',
            'summary' => ['Nilai' => 'IDR '.\Devlib\Locale::number($r['total'], 0), 'Termin' => 'Net 30', 'Estimasi Kirim' => $d($r['ship_date']), 'Jumlah Item' => count($r['lines'])],
            'lines' => $r['lines'], 'steps' => $steps, 'history' => $history,
        ] + ($ap === 'hold' ? ['held' => ['by' => 'Operational Manager', 'at' => $d($r['ship_date'], '09:15'), 'note' => $last[1]]] : [])
          + (in_array($ap, ['approved', 'rejected'], true) ? ['readonly' => true] : []);
    }

    public static function flash(string $tone, string $text): array
    {
        return [$tone, $text];
    }

    // Aksi yang sah per keadaan (siklus ketat); selain itu ditolak (toast kuning, status tetap).
    private static function allowed(bool $master, string $status): array
    {
        if ($master) {
            return ['new' => ['save'], 'active' => ['save', 'deactivate', 'delete'], 'inactive' => ['activate']][$status] ?? [];
        }

        return match ($status) {
            'new' => ['save'],
            'draft' => ['save', 'delete', 'confirm'],
            'confirmed' => ['cancel'],
            default => [],
        };
    }

    // Menangani POST form. Selalu redirect (PRG).
    public static function handle(Request $r, string $slug, string $id): RedirectResponse
    {
        $doc = self::doc($slug, $r->input('doc'));
        $back = self::back($slug, $doc, $r->input('from'));
        $row = $id === 'new' ? null : self::row($slug, $doc, $id);
        if ($id !== 'new' && ! $row) {
            return redirect($back)->with('flow_flash', self::flash('danger', 'Record not found.'));
        }
        $master = self::isMaster($slug);
        $status = $id === 'new' ? 'new' : (string) $r->input('status', self::status($slug, $doc, $row));
        $act = (string) $r->input('_action', 'save');
        $state = ['status' => $status];
        $self = fn (array $q = [], ?string $newId = null) => redirect(self::formUrl($slug, $newId ?? $id, $doc, $back, $q));
        $label = $row ? ($row['name'] ?? $row['co_no'] ?? $row['po_no'] ?? $row['cv_no']) : 'New record';
        $reason = trim((string) $r->input('_reason'));
        $deny = fn (string $text) => $self($state)->with('flow_flash', self::flash('danger', $text));
        $reject = fn (string $text) => $self($state)->with('flow_flash', self::flash('warn', $text));

        if (! in_array($act, self::allowed($master, $status), true)) {
            return $reject("Action {$act} does not apply to this record in its current state.");
        }
        if ($master && $act === 'delete' && (int) $row['id'] % 3 === 0) {
            return $deny('This record is used by transactions. Deactivate it instead.');
        }

        switch ($act) {
            case 'save':
                $need = self::REQUIRED[$slug] ?? self::REQUIRED[$doc] ?? [];
                $miss = array_values(array_filter($need, fn ($l, $k) => trim((string) $r->input($k)) === '', ARRAY_FILTER_USE_BOTH));
                if ($miss) {
                    return $deny('Required fields are missing: '.implode(', ', $miss).'.');
                }
                if ($id === 'new') {
                    return $self(['status' => $master ? 'active' : 'draft'], self::CREATED_ID)->with('flow_flash', self::flash('success', 'Saved (demo: nothing is actually stored).'));
                }

                return $self($state)->with('flow_flash', self::flash('success', $label.' saved.'));
            case 'delete':
                return redirect($back)->with('flow_flash', self::flash('success', $label.' deleted (demo: nothing is actually removed).'));
            case 'confirm':
                return $self(['status' => 'confirmed'])->with('flow_flash', self::flash('success', $label.' confirmed. All fields are now read-only.'));
            case 'cancel':
                if ($reason === '') {
                    return $deny('A reason is required.');
                }

                return $self(['status' => 'cancelled'])->with('flow_flash', self::flash('success', $label.' cancelled.'));
            case 'deactivate':
                return $self(['status' => 'inactive'])->with('flow_flash', self::flash('success', $label.' deactivated. It no longer appears in pick lists.'));
            default: // activate
                return $self(['status' => 'active'])->with('flow_flash', self::flash('success', $label.' activated.'));
        }
    }
}
