<?php

namespace Tests\Feature;

use Devlib\Engine\EngineRegistry;
use Devlib\Engine\LeadTime;
use Devlib\Lang;
use Tests\TestCase;

// Engine lead time pengadaan (Devlib\Engine\LeadTime + LeadTimeEngine): hitungan murni atas tanggal kalender.
class LeadTimeTest extends TestCase
{
    /** @param list<int> $days catatan selesai, dipesan 2026-01-01 + n*10 hari */
    private function done(array $days): array
    {
        $out = [];
        foreach ($days as $i => $d) {
            $start = new \DateTimeImmutable('2026-01-01 +'.($i * 10).' days');
            $out[] = ['start' => $start->format('Y-m-d'), 'end' => $start->modify("+$d days")->format('Y-m-d')];
        }

        return $out;
    }

    public function test_average_is_rounded_up_and_stats_cover_finished_records(): void
    {
        $lt = LeadTime::of($this->done([3, 4, 6]));  // rata-rata 13/3 = 4.33 -> 5

        $this->assertSame(3, $lt->count());
        $this->assertEqualsWithDelta(4.333, $lt->average(), 0.001);
        $this->assertSame([3, 6, 4.0, 6], [$lt->min(), $lt->max(), $lt->median(), $lt->last()]);
        $this->assertSame(5, $lt->effective());
        $this->assertSame(['data', 3], [$lt->source(), $lt->basis()]);
        $this->assertSame(4, LeadTime::of($this->done([3, 4, 5]))->effective());  // pas, tidak naik
        $this->assertSame(3.5, LeadTime::of($this->done([3, 4]))->median());
    }

    public function test_below_min_samples_uses_fallback_else_null(): void
    {
        $two = $this->done([3, 4]);

        $m = LeadTime::of($two, ['fallback_days' => 7]);
        $this->assertSame([7, 'manual', 0], [$m->effective(), $m->source(), $m->basis()]);

        $n = LeadTime::of($two);
        $this->assertSame([null, 'none', 0], [$n->effective(), $n->source(), $n->basis()]);
        $this->assertNull($n->expected('2026-10-01'));
        $this->assertNull($n->suggestReorderPoint(2.0));
        $this->assertSame('data', LeadTime::of($two, ['min_samples' => 2])->source());
        $this->assertSame(9, LeadTime::of([], ['fallback_days' => 8.2])->effective());
        $this->assertNull(LeadTime::of([], ['fallback_days' => -1])->effective());
    }

    public function test_only_latest_samples_by_receipt_date_are_used(): void
    {
        // urutan masukan acak; end terbaru = 4, 5, 6 hari -> rata-rata 5; dua yang lama tidak dipakai
        $rec = [
            ['start' => '2026-03-01', 'end' => '2026-03-05'],
            ['start' => '2026-01-01', 'end' => '2026-01-21'],
            ['start' => '2026-04-01', 'end' => '2026-04-06'],
            ['start' => '2026-02-01', 'end' => '2026-03-03'],
            ['start' => '2026-05-01', 'end' => '2026-05-07'],
        ];
        $lt = LeadTime::of($rec, ['samples' => 3]);

        $this->assertSame([5, 3, 5], [$lt->effective(), $lt->basis(), $lt->count()]);
        $this->assertSame(6, $lt->last());
        $this->assertSame(5, LeadTime::of($rec)->with(['samples' => 3])->effective());
        $this->assertSame(13, LeadTime::of($rec, ['samples' => 99])->effective());  // 61/5 = 12.2 -> 13
    }

    public function test_open_reversed_and_invalid_records_are_ignored(): void
    {
        $rec = array_merge($this->done([2, 4, 6]), [
            ['start' => '2026-06-01', 'end' => null],            // belum masuk
            ['start' => '2026-06-01', 'end' => ''],              // belum masuk
            ['start' => '2026-06-10', 'end' => '2026-06-01'],    // terbalik
            ['start' => 'bukan tanggal', 'end' => '2026-06-01'], // start tak sah
            ['start' => '2026-06-01', 'end' => '2026-02-30'],    // end tak sah
            ['start' => '2026-13-01', 'end' => '2026-06-01'],
            ['end' => '2026-06-01'],
        ]);
        $lt = LeadTime::of($rec);

        $this->assertSame([3, 2], [$lt->count(), $lt->openCount()]);
        $this->assertSame(4, $lt->effective());
        $this->assertSame(0, LeadTime::of([['start' => '2026-06-01', 'end' => '2026-06-01']], ['min_samples' => 1])->effective());  // 0 hari sah
    }

    public function test_expected_and_status(): void
    {
        $lt = LeadTime::of($this->done([5, 5, 5]), ['late_tolerance_days' => 2]);

        $this->assertSame('2026-10-10', $lt->expected('2026-10-05'));
        $this->assertSame('2026-03-01', $lt->expected('2026-02-24'));  // lewat akhir bulan
        $this->assertNull($lt->expected('xx'));
        $this->assertSame(['elapsed_days' => 3, 'typical_days' => 5, 'expected_at' => '2026-10-10', 'state' => 'on_time'], $lt->status('2026-10-05', '2026-10-08'));
        $this->assertSame('on_time', $lt->status('2026-10-05', '2026-10-12')['state']);  // 7 = 5 + toleransi 2, belum telat
        $this->assertSame('late', $lt->status('2026-10-05', '2026-10-13')['state']);
        $this->assertSame('late', LeadTime::of($this->done([5, 5, 5]))->status('2026-10-05', '2026-10-11')['state']);  // toleransi 0
        $this->assertSame('unknown', LeadTime::of([])->status('2026-10-05', '2026-10-20')['state']);
        $this->assertSame('unknown', $lt->status('xx', '2026-10-20')['state']);
        $this->assertSame(0, $lt->status('2026-10-05', '2026-10-01')['elapsed_days']);  // hari ini sebelum tanggal pesan: 0, bukan negatif
        $this->assertNotNull($lt->status('2026-10-05')['elapsed_days']);  // today = null memakai hari ini zona aplikasi
    }

    public function test_suggest_reorder_point(): void
    {
        $lt = LeadTime::of($this->done([4, 5, 6]));  // efektif 5

        $this->assertSame(30.0, $lt->suggestReorderPoint(5.0, 5));
        $this->assertSame(12.5, $lt->suggestReorderPoint(2.5));
        $this->assertSame(0.0, $lt->suggestReorderPoint(-3));
        $this->assertSame(20.0, LeadTime::of([], ['fallback_days' => 4])->suggestReorderPoint(5.0));
    }

    public function test_mixed_date_formats_and_calendar_days_ignore_time_and_zone(): void
    {
        $rec = [
            ['start' => '2026-10-05 23:59:59', 'end' => '2026-10-06 00:00:01'],  // 1 hari kalender walau selisih 2 detik
            ['start' => new \DateTimeImmutable('2026-10-05 00:00', new \DateTimeZone('UTC')), 'end' => '2026-10-08'],  // 3
            ['start' => new \DateTime('2026-10-05 23:30', new \DateTimeZone('America/Los_Angeles')), 'end' => new \DateTimeImmutable('2026-10-10 01:00', new \DateTimeZone('Asia/Tokyo'))],  // 5
            ['start' => '2026-10-05T23:30:00+07:00', 'end' => '2026-10-06T00:30:00-12:00'],  // zona dibuang: 1
        ];
        $lt = LeadTime::of($rec, ['min_samples' => 1, 'samples' => 10]);

        $this->assertSame([4, 1, 5], [$lt->count(), $lt->min(), $lt->max()]);
        $this->assertSame(3, $lt->effective());  // (1+3+5+1)/4 = 2.5 -> 3

        $default = date_default_timezone_get();
        date_default_timezone_set('Pacific/Kiritimati');
        try {
            $this->assertSame(3, LeadTime::of($rec, ['min_samples' => 1, 'samples' => 10])->effective());  // zona server tidak berpengaruh
        } finally {
            date_default_timezone_set($default);
        }
    }

    public function test_sentences_exist_in_both_languages(): void
    {
        $keys = ['leadtime_usual', 'leadtime_manual', 'leadtime_expected', 'leadtime_late', 'leadtime_ontime', 'leadtime_none'];
        foreach ($keys as $k) {
            $row = Lang::DICT[$k] ?? null;
            $this->assertIsArray($row, $k);
            $this->assertNotSame('', trim($row[0]));
            $this->assertNotSame('', trim($row[1]));
            $this->assertNotSame($row[0], $row[1], $k.': Indonesia dan English harus beda');
        }

        config(['devlib.lang' => 'id']);
        $lt = LeadTime::of($this->done([4, 5, 6]));
        $this->assertSame('Biasanya tiba 5 hari (rata-rata 3 pemesanan)', $lt->describe());
        $this->assertSame('Perkiraan tiba 10-Okt-2026', $lt->describeExpected('2026-10-05'));
        $this->assertSame('Sudah 9 hari, biasanya 5. Periksa pesanan.', $lt->describeStatus('2026-10-05', '2026-10-14'));
        $this->assertSame('Sudah 2 hari, biasanya 5.', $lt->describeStatus('2026-10-05', '2026-10-07'));
        $this->assertSame('Belum ada data lead time', LeadTime::of([])->describe());
        $this->assertSame('Biasanya tiba 7 hari (perkiraan manual)', LeadTime::of([], ['fallback_days' => 7])->describe());

        config(['devlib.lang' => 'en']);
        $this->assertSame('Usually arrives in 5 days (average of 3 orders)', $lt->describe());
        $this->assertSame('Expected to arrive 10-Oct-2026', $lt->describeExpected('2026-10-05'));
        $this->assertSame('9 days so far, usually 5. Check the order.', $lt->describeStatus('2026-10-05', '2026-10-14'));
    }

    public function test_engine_runs_through_the_registry_and_shows_in_the_catalog(): void
    {
        $r = app(EngineRegistry::class)->run('lead-time', [
            'records' => $this->done([4, 5, 6]), 'start' => '2026-10-05', 'today' => '2026-10-14', 'avg_daily_usage' => 2, 'safety_stock' => 3,
        ]);

        $this->assertTrue($r->isApplied());
        $this->assertSame([5, 'data', 3, '2026-10-10', 13.0], [$r->data['effective'], $r->data['source'], $r->data['basis'], $r->data['expected_at'], $r->data['reorder_point']]);
        $this->assertSame('late', $r->data['status']['state']);
        $this->assertNotEmpty($r->reasons);
        $this->assertSame([], $r->warnings);
        $this->assertNotEmpty(app(EngineRegistry::class)->run('lead-time', ['records' => []])->warnings);

        $row = collect(app(EngineRegistry::class)->catalog())->firstWhere('key', 'lead-time');
        $this->assertSame('calc', $row['mode']);
        $this->assertNotEmpty($row['inputs']);
    }
}
