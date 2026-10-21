<?php

/*
 * REMARK
 * Tujuan       : Pembungkus LeadTime untuk EngineRegistry / x-dl::engine-catalog (mode Calc): catatan pesan-masuk -> lead time efektif dan perkiraan tiba.
 * Sumber hulu  : $input dari aplikasi (records dan opsi); tanpa database, tanpa tabel proyek.
 * Input        : records, samples, min_samples, fallback_days, late_tolerance_days; opsional start, today, avg_daily_usage, safety_stock.
 * Output       : Result::applied dengan data LeadTime::summary() (+ expected, status, reorder_point bila masukannya ada).
 * Dipakai oleh : Katalog engine; aplikasi yang mendaftarkannya di EngineRegistry (opt-in).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Engine;

// Hitungan murni (Calc): tidak menulis apa pun. Angka dan kalimat dihitung oleh LeadTime; kelas ini hanya mengantar $input -> Result.
class LeadTimeEngine implements Described, Engine
{
    public function key(): string
    {
        return 'lead-time';
    }

    public function name(): string
    {
        return 'Lead Time';
    }

    public function group(): string
    {
        return 'Procurement';
    }

    public function mode(): Mode
    {
        return Mode::Calc;
    }

    public function purpose(): string
    {
        return 'Turns order-to-receipt records into the usual lead time and an expected arrival date; only a suggestion.';
    }

    public function run(array $input): Result
    {
        $lt = LeadTime::of($input['records'] ?? [], $input);
        $data = $lt->summary();
        $reasons = [$lt->describe()];
        if (isset($input['start'])) {
            $data['expected_at'] = $lt->expected($input['start']);
            $data['status'] = $lt->status($input['start'], $input['today'] ?? null);
            $reasons[] = $lt->describeExpected($input['start']);
            $reasons[] = $lt->describeStatus($input['start'], $input['today'] ?? null);
        }
        if (isset($input['avg_daily_usage'])) {
            $data['reorder_point'] = $lt->suggestReorderPoint((float) $input['avg_daily_usage'], (float) ($input['safety_stock'] ?? 0));
        }

        return Result::applied($data, $reasons, $lt->effective() === null ? ['No lead time data and no fallback.'] : []);
    }

    public function details(): array
    {
        return [
            'inputs' => [
                ['records', 'List of {start: order date, end: receipt date or null, key?}'],
                ['samples / min_samples', 'Latest finished records used (5) / minimum finished records before data is trusted (3)'],
                ['fallback_days', 'Manual lead time used while data is too thin (optional)'],
                ['late_tolerance_days', 'Days over the usual before an open order counts as late (0)'],
                ['start, today', 'Optional: an open order date (and today) to get the expected arrival and its status'],
                ['avg_daily_usage, safety_stock', 'Optional: to get a suggested reorder point'],
            ],
            'outputs' => [
                ['count, average, min, max, median, last', 'Statistics of finished records (days)'],
                ['effective, source, basis', 'Lead time used, where it came from (data | manual | none) and how many records formed it'],
                ['expected_at, status', 'Expected arrival date; elapsed vs usual days with state on_time | late | unknown'],
                ['reorder_point', 'Daily usage x effective lead time + safety stock (suggestion only)'],
            ],
            'steps' => [
                'Count calendar days from order to receipt; ignore records that are open, reversed or have an invalid date.',
                'With at least min_samples finished records: average of the latest samples (by receipt date), rounded up.',
                'Otherwise use fallback_days; with neither there is no lead time.',
                'Expected arrival = order date + lead time; an open order is late when elapsed days exceed lead time + tolerance.',
            ],
            'tests' => ['site/tests/Feature/LeadTimeTest.php'],
        ];
    }
}
