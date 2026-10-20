<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

// Alur Grid -> Form -> kembali di katalog (aturan: skills/devlib/grid-form-flow.md). Tanpa basis data: baris dari DemoData, hasil Simpan/Hapus
// hanya disimulasikan (toast + kembali ke grid), keadaan form dibawa di alamat (status, from, doc).
final class FlowDemo
{
    private const FLOWS = [
        'p5-flow-master' => ['title' => 'Employee', 'demos' => ['' => 'form-single']],
        'p5-flow-document' => ['title' => 'Document', 'demos' => ['co' => 'form-md', 'po' => 'form-md-po']],
    ];

    // Aksi yang sah per status; selain itu ditolak (toast kuning, status tetap).
    private const ALLOWED = [
        'new' => ['save'], 'draft' => ['save', 'delete', 'confirm'], 'confirmed' => ['cancel', 'draft'], 'cancelled' => ['duplicate'], 'closed' => [],
    ];

    // Pemeriksa (admin-app, manager) tidak boleh input: hanya Cancel / Back to Draft.
    private const READ_ONLY_ROLES = ['admin-app', 'manager'];

    public static function has(string $slug): bool
    {
        return isset(self::FLOWS[$slug]);
    }

    // '' untuk alur tanpa pilihan dokumen; selain itu co|po (bawaan po).
    public static function doc(string $slug, ?string $doc): string
    {
        $docs = array_keys(self::FLOWS[$slug]['demos']);

        return in_array($doc, $docs, true) ? $doc : end($docs);
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
        return match ($doc) {
            'co' => DemoData::customerOrders(),
            'po' => DemoData::purchaseOrders(),
            default => DemoData::employees(),
        };
    }

    public static function row(string $slug, string $doc, string $id): ?array
    {
        foreach (self::rows($slug, $doc) as $r) {
            if ((string) $r['id'] === $id) {
                return $r;
            }
        }

        return null;
    }

    // Status form dari record: dokumen ikut status barisnya; master/Single Form mulai dari Draft.
    public static function status(string $doc, ?array $row): string
    {
        return $doc !== '' && $row ? strtolower($row['status']) : 'draft';
    }

    public static function flash(string $tone, string $text): array
    {
        return [$tone, $text];
    }

    // Menangani POST form (Simpan, Hapus, Confirm, Cancel, Back to Draft, Duplicate). Selalu redirect (PRG).
    public static function handle(Request $r, string $slug, string $id): RedirectResponse
    {
        $doc = self::doc($slug, $r->input('doc'));
        $back = self::back($slug, $doc, $r->input('from'));
        $row = $id === 'new' ? null : self::row($slug, $doc, $id);
        if ($id !== 'new' && ! $row) {
            return redirect($back)->with('flow_flash', self::flash('danger', 'Data tidak ditemukan.'));
        }
        $status = $id === 'new' ? 'new' : (string) $r->input('status', self::status($doc, $row));
        $act = (string) $r->input('_action', 'save');
        $self = fn (array $extra = [], ?string $newId = null) => redirect(self::formUrl($slug, $newId ?? $id, $doc, $back, $extra + ($r->filled('as') ? ['as' => $r->input('as')] : [])));
        $label = ($row ? ($row['name'] ?? $row['co_no'] ?? $row['po_no']) : 'Data baru');

        if (in_array($r->input('as'), self::READ_ONLY_ROLES, true) && ! in_array($act, ['cancel', 'draft'], true)) {
            return $self(['status' => $status])->with('flow_flash', self::flash('danger', 'Tidak punya hak untuk aksi ini.'));
        }
        if (! in_array($act, self::ALLOWED[$status] ?? [], true)) {
            return $self(['status' => $status])->with('flow_flash', self::flash('warn', "Aksi {$act} tidak berlaku pada status {$status}."));
        }

        switch ($act) {
            case 'save':
                $need = $doc === '' ? ['full_name' => 'Nama Lengkap'] : ($doc === 'co' ? ['co_date' => 'Tanggal Pesan', 'customer' => 'Customer'] : ['po_date' => 'Tanggal PO', 'supplier' => 'Supplier']);
                $miss = array_values(array_filter($need, fn ($l, $k) => trim((string) $r->input($k)) === '', ARRAY_FILTER_USE_BOTH));
                if ($miss) {
                    return $self(['status' => $status])->with('flow_flash', self::flash('danger', 'Isian wajib belum lengkap: '.implode(', ', $miss).'.'));
                }
                $to = $back.(str_contains($back, '?') ? '&' : '?').($row ? 'hl='.$row['id'] : 'saved=1');

                return redirect($to)->with('flow_flash', self::flash('success', $label.' tersimpan'.($row ? '.' : ' (contoh: data demo tidak benar-benar bertambah).')));
            case 'delete':
                return redirect($back)->with('flow_flash', self::flash('success', $label.' dihapus (contoh: data demo tidak benar-benar hilang).'));
            case 'confirm':
                return $self(['status' => 'confirmed'])->with('flow_flash', self::flash('success', $label.' dikonfirmasi. Isian terkunci.'));
            case 'cancel':
            case 'draft':
                if (trim((string) $r->input('_reason')) === '') {
                    return $self(['status' => $status])->with('flow_flash', self::flash('danger', 'Alasan wajib diisi.'));
                }

                return $self(['status' => $act === 'cancel' ? 'cancelled' : 'draft'])->with('flow_flash', self::flash('success', $act === 'cancel' ? $label.' dibatalkan.' : $label.' dikembalikan ke Draft.'));
            default: // duplicate
                return $self(['copy' => $id], 'new')->with('flow_flash', self::flash('info', 'Salinan dibuat sebagai Draft baru, tanpa nomor.'));
        }
    }
}
