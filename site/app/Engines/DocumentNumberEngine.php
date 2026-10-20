<?php

namespace App\Engines;

use Devlib\Engine\Described;
use Devlib\Engine\Engine;
use Devlib\Engine\Mode;
use Devlib\Engine\Result;

// Contoh engine mode Auto: jalan sendiri tanpa persetujuan; hasilnya langsung berlaku.
class DocumentNumberEngine implements Described, Engine
{
    public function key(): string
    {
        return 'document-number';
    }

    public function name(): string
    {
        return 'Document Number';
    }

    public function group(): string
    {
        return 'Documents';
    }

    public function mode(): Mode
    {
        return Mode::Auto;
    }

    public function purpose(): string
    {
        return 'Gives the next document number (PREFIX-YYMM-0001), restarting at 0001 every month.';
    }

    public function run(array $input): Result
    {
        $prefix = strtoupper(trim((string) ($input['prefix'] ?? 'DOC')));
        $period = date('ym', strtotime((string) ($input['date'] ?? 'today')) ?: time());
        $next = 1;
        if (preg_match('/^'.preg_quote($prefix, '/').'-(\d{4})-(\d+)$/', (string) ($input['last'] ?? ''), $m) && $m[1] === $period) {
            $next = (int) $m[2] + 1;
        }

        return Result::applied(['number' => sprintf('%s-%s-%04d', $prefix, $period, $next)], [$next === 1 ? 'First number of period '.$period.'.' : 'Follows the last number of period '.$period.'.']);
    }

    public function details(): array
    {
        return [
            'inputs' => [['prefix', 'Document code, e.g. PO'], ['last', 'Last number issued, e.g. PO-2610-0031'], ['date', 'Document date, default today']],
            'outputs' => [['number', 'The next number']],
            'steps' => ['Period = year and month of the date (YYMM).', 'Same period as the last number: add 1.', 'New period: restart at 0001.'],
        ];
    }
}
