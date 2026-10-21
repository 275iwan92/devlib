<?php

namespace Devlib\Lint;

use Devlib\Components\Form;

// Penegak aturan baku Form (SKILL.md "Form" dan "Data transaksional: mode dan tombol"). Murni: memeriksa konfigurasi Form, mengembalikan Finding[].
// Input: instance Form. Output: Finding[]. Dipakai oleh: Form::lint() (Lint::record), tes. Status: opt-in, tidak mengubah tampilan.
final class FormRules
{
    private const STATUSES = [
        'master' => ['new', 'active', 'inactive'],
        'document' => ['new', 'draft', 'confirmed', 'working', 'approval', 'hold', 'approved', 'rejected', 'cancelled', 'closed'],
    ];

    // Aksi panel yang wajib ada per status dokumen.
    private const REQUIRED = ['draft' => ['save', 'delete', 'new', 'print'], 'confirmed' => ['cancel', 'print'], 'cancelled' => ['print']];

    /** @return list<Finding> */
    public static function check(Form $f): array
    {
        $out = [];
        $add = function (string $code, string $msg, string $level = Finding::WARN) use (&$out, $f) {
            $out[] = new Finding($code, $msg, $level, 'form'.($f->title !== '' ? ' "'.$f->title.'"' : ''));
        };

        if (! isset(self::STATUSES[$f->kind])) {
            $add('F107', "kind \"{$f->kind}\" tidak dikenal (master|document).", Finding::ERROR);

            return $out;
        }
        if (! in_array($f->status, self::STATUSES[$f->kind], true)) {
            $add('F108', "status \"{$f->status}\" tidak berlaku untuk kind {$f->kind}.", Finding::ERROR);
        }
        if ($f->kind === 'document' && ! $f->panel) {
            $add('F109', 'Form dokumen harus panel="top" supaya tombol baku mode datang dari status.', Finding::ERROR);
        }
        if ($f->panel && ! $f->back) {
            $add('F101', 'Tombol Back wajib :back ke grid modul (bukan history.back).', $f->kind === 'document' ? Finding::ERROR : Finding::WARN);
        }
        if ($f->panel && config('devlib.back_history', true) !== false) {
            $add('F102', 'config devlib.back_history harus false, supaya Back tidak kembali ke form isian.');
        }
        if ($f->kind === 'document') {
            foreach ($f->strict ? [] : (self::REQUIRED[$f->status] ?? []) as $need) {
                if (in_array($need, $f->hide, true)) {
                    $add('F105', "Aksi {$need} wajib ada pada status {$f->status}, tidak boleh di-hide.", Finding::ERROR);
                }
            }
            if (! $f->strict && in_array($f->status, ['draft', 'confirmed', 'cancelled'], true) && ! $f->newUrl && ! in_array('new', $f->hide, true)) {
                $add('F104', 'Tombol New tanpa :new-url tidak punya tujuan.');
            }
            if ($f->action && ! $f->printUrl && $f->status !== 'new') {
                $add('F103', 'Print wajib tersedia di semua status (cetakan membawa MODE): isi :print-url.');
            }
            if ($f->pipeline === false) {
                $add('F106', 'Form dokumen: bar tahapan (pipeline) dimatikan.', Finding::INFO);
            }
        }

        return $out;
    }
}
