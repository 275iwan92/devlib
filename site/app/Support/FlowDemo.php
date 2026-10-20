<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

// Alur Grid -> Form -> kembali di katalog (aturan: skills/devlib/grid-form-flow.md). Tanpa basis data: baris dari DemoData, hasil aksi
// hanya disimulasikan (toast + keadaan form dibawa di alamat: status, approval, from, doc, as). Siklus ketat (user 2026-10-21):
// Save dan Confirm TETAP di form; hanya Back (dan Delete, karena barisnya hilang) kembali ke grid.
final class FlowDemo
{
    private const FLOWS = [
        'p5-flow-master' => ['title' => 'Employee', 'kind' => 'master', 'demos' => ['' => 'form-master']],
        'p5-flow-document' => ['title' => 'Document', 'kind' => 'document', 'demos' => ['co' => 'form-md', 'po' => 'form-md-po']],
        'p5-flow-single' => ['title' => 'Cash Voucher', 'kind' => 'document', 'demos' => ['' => 'form-cash-voucher']],
        'p5-flow-approval' => ['title' => 'Customer Order', 'kind' => 'document', 'demos' => ['' => 'form-md']],
    ];

    // Isian wajib per alur (kunci field => label); dicek server sebagai cadangan validasi klien.
    private const REQUIRED = [
        'p5-flow-master' => ['full_name' => 'Full name'],
        'p5-flow-single' => ['payee' => 'Payee', 'amount' => 'Amount'],
        'co' => ['co_date' => 'Order date', 'customer' => 'Customer'],
        'po' => ['po_date' => 'PO date', 'supplier' => 'Supplier'],
    ];

    // Peran simulasi: pemeriksa (approver) memutuskan approval; administrator menerbitkan OTP dan boleh menghapus master yang belum dipakai.
    public const APPROVER = 'manager';

    public const ADMIN = 'admin-app';

    // Kode OTP contoh (demo): di aplikasi nyata diterbitkan administrator di luar sistem; pemeriksaan kode adalah urusan proyek.
    public const OTP_DEMO = '482913';

    // Save pada record baru: demo tanpa penyimpanan, jadi form pindah ke record contoh bernomor ini (…/form/999).
    public const CREATED_ID = '999';

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

    public static function flash(string $tone, string $text): array
    {
        return [$tone, $text];
    }

    // Aksi yang sah per keadaan (siklus ketat); selain itu ditolak (toast kuning, status tetap).
    private static function allowed(bool $master, string $status, ?string $ap): array
    {
        if ($master) {
            return ['new' => ['save'], 'active' => ['save', 'deactivate', 'delete'], 'inactive' => ['activate']][$status] ?? [];
        }

        return match ($status) {
            'new' => ['save'],
            'draft' => ['save', 'delete', 'confirm'],
            'confirmed' => array_merge(in_array($ap, [null, 'waiting'], true) ? ['cancel'] : [], $ap === 'rejected' ? ['duplicate'] : [], $ap !== null ? ['approve', 'hold', 'reject'] : []),
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
        $ap = $r->filled('approval') ? (string) $r->input('approval') : ($row['approval'] ?? null);
        $act = (string) $r->input('_action', 'save');
        $as = (string) $r->input('as', '');
        $state = array_filter(['status' => $status, 'approval' => $status === 'confirmed' ? $ap : null]);
        $self = fn (array $q = [], ?string $newId = null) => redirect(self::formUrl($slug, $newId ?? $id, $doc, $back, $q + ($as !== '' ? ['as' => $as] : [])));
        $label = $row ? ($row['name'] ?? $row['co_no'] ?? $row['po_no'] ?? $row['cv_no']) : 'New record';
        $reason = trim((string) $r->input('_reason'));
        $deny = fn (string $text) => $self($state)->with('flow_flash', self::flash('danger', $text));
        $reject = fn (string $text) => $self($state)->with('flow_flash', self::flash('warn', $text));

        if (! in_array($act, self::allowed($master, $status, $ap), true)) {
            return $reject("Action {$act} does not apply to this record in its current state.");
        }
        $decision = in_array($act, ['approve', 'hold', 'reject'], true);
        if ($decision !== ($as === self::APPROVER)) {
            return $deny('You do not have permission for this action.');
        }
        if ($master && $act === 'delete' && ($as !== self::ADMIN || (int) $row['id'] % 3 === 0)) {
            return $deny($as !== self::ADMIN ? 'Only an administrator can delete master data.' : 'This record is used by transactions. Deactivate it instead.');
        }
        $flows = ['approve' => ['waiting', 'hold', 'rejected'], 'hold' => ['waiting'], 'reject' => ['waiting', 'hold', 'approved']];
        if ($decision && ! in_array($ap, $flows[$act], true)) {
            return $reject("Action {$act} does not apply to approval status {$ap}.");
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
            case 'approve':
            case 'hold':
            case 'reject':
                if ($act !== 'approve' && $reason === '') {
                    return $deny('A reason is required.');
                }
                if ($ap === 'rejected') {
                    if ($reason === '') {
                        return $deny('A reason is required.');
                    }
                    if (trim((string) $r->input('_otp')) !== self::OTP_DEMO) {
                        return $deny('The OTP code is not valid.');
                    }
                }
                $new = ['approve' => 'approved', 'hold' => 'hold', 'reject' => 'rejected'][$act];

                return $self(['status' => 'confirmed', 'approval' => $new])->with('flow_flash', self::flash('success', $label.' is now '.strtoupper($new).'.'));
            case 'deactivate':
                return $self(['status' => 'inactive'])->with('flow_flash', self::flash('success', $label.' deactivated. It no longer appears in pick lists.'));
            case 'activate':
                return $self(['status' => 'active'])->with('flow_flash', self::flash('success', $label.' activated.'));
            default: // duplicate
                return $self(['copy' => $id], 'new')->with('flow_flash', self::flash('info', 'Copy created as a new Draft, without a number.'));
        }
    }
}
