<?php

namespace Devlib\Lint;

use Devlib\Components\Grid;

// Penegak aturan baku Grid (skills/devlib/SKILL.md "Tipe grid"; keputusan user 2026-10-20). Murni: memeriksa konfigurasi Grid, mengembalikan Finding[].
// Input: instance Grid (setelah applyKind). Output: Finding[]. Dipakai oleh: Grid::lint() (Lint::record), tes. Status: opt-in, tidak mengubah tampilan.
final class GridRules
{
    private const FORBIDDEN_BULK = ['confirm', 'approve', 'cancel', 'delete', 'reject'];

    /** @return list<Finding> */
    public static function check(Grid $g): array
    {
        $out = [];
        $add = function (string $code, string $msg, string $level = Finding::WARN) use (&$out, $g) {
            $out[] = new Finding($code, $msg, $level, 'grid#'.$g->id);
        };

        if ($g->isReport()) {
            return $out;  // report: zebra + tanpa centang/aksi/new sudah dipaksa konstruktor
        }
        if ($g->kind === null) {
            $add('G101', 'Grid entry tanpa kind="master|transactional" (perilaku lama).', Finding::INFO);

            return $out;
        }

        $cols = array_values($g->columns);
        $first = $cols[0] ?? [];
        if ($g->kind === 'master') {
            if (($first['type'] ?? 'text') !== 'identity' || empty($first['box'])) {
                $add('G111', 'Grid master: kolom pertama harus identity berkotak (box).');
            }
            if (! $g->toggleUrl) {
                $add('G110', 'Grid master: Aktif/Nonaktif wajib :toggle-url, tanpa itu hanya pratinjau.', Finding::ERROR);
            }

            return $out;
        }

        // transaksional
        if (! empty($first['box']) || ! empty($first['hue']) || (($first['type'] ?? '') === 'identity' && ($first['avatar'] ?? true) !== false)) {
            $add('G112', 'Grid transaksional: kolom pertama harus teks tebal polos (tanpa kotak/avatar/hue).');
        }
        if (! array_filter($cols, fn ($c) => ($c['type'] ?? '') === 'badge' && (! empty($c['tones']) || ! empty($c['toneKey'])))) {
            $add('G120', 'Grid transaksional: wajib kolom Status type=badge dengan tones (satu-satunya warna di baris).', Finding::ERROR);
        }
        if (array_filter($cols, fn ($c) => in_array($c['type'] ?? '', ['progress', 'rating', 'spark', 'steps'], true) || ! empty($c['hue']))) {
            $add('G113', 'Grid transaksional: hanya kolom Status yang boleh berwarna.');
        }
        $visible = count(array_filter($cols, fn ($c) => ($c['visible'] ?? true) !== false));
        if ($visible < 5 || $visible > 8) {
            $add('G121', "Grid transaksional: {$visible} kolom tampil, aturannya 5-8.");
        }
        if ($g->rowTone) {
            $add('G122', 'Grid transaksional: rowTone tidak dipakai (hanya Status yang berwarna).', Finding::ERROR);
        }
        foreach ($cols as $c) {
            $key = strtolower((string) ($c['key'] ?? ''));
            if (in_array($c['type'] ?? '', ['integer', 'number'], true) && preg_match('/^(items?|lines?|rows?|(item|line|row)_?(count|cnt|total)|(count|total|jml)_?(items?|lines?|rows?))$/', $key)) {
                $add('G123', "Grid transaksional: tanpa kolom jumlah item/baris ({$key}).");
            }
        }
        if ($g->detail) {
            $dcols = $g->detail['columns'] ?? [];
            if (count($dcols) > 7) {
                $add('G124', 'Detail grid: '.count($dcols).' kolom, maksimal 7.', Finding::ERROR);
            }
            if (! array_filter($dcols, fn ($c) => preg_match('/name|item|product|material|description|barang/i', ($c['key'] ?? '').' '.($c['label'] ?? '')))) {
                $add('G125', 'Detail grid: wajib memuat nama item/product.', Finding::ERROR);
            }
            if (is_string($g->detail['title'] ?? null) && preg_match('/\b(count|jumlah)\b|\{\s*(count|lines?|rows?)\s*\}/i', $g->detail['title'])) {
                $add('G126', 'Detail grid: judul tanpa jumlah baris.');
            }
            if (array_filter($dcols, fn ($c) => ($c['type'] ?? '') === 'money' || preg_match('/price|harga/i', $c['key'] ?? ''))) {
                $add('G127', 'Detail grid: tanpa harga.');
            }
        }
        foreach ($g->bulk as $b) {
            if (in_array(strtolower((string) ($b['key'] ?? '')), self::FORBIDDEN_BULK, true)) {
                $add('G128', 'Grid transaksional: '.$b['key'].' tidak ada di grid, hanya di form dokumen.', Finding::ERROR);
            }
        }
        if ($g->toggleUrl || array_filter($cols, fn ($c) => ($c['type'] ?? '') === 'toggle')) {
            $add('G129', 'Grid transaksional: transaksi tidak pernah dinonaktifkan (tanpa toggle).');
        }

        return $out;
    }
}
