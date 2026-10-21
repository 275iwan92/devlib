<?php

/*
 * REMARK
 * Tujuan       : Lead time pengadaan: lama dari barang DIPESAN sampai MASUK, dicatat dari data nyata, dirata-rata, dan dipakai memperkirakan tiba.
 * Sumber hulu  : Daftar catatan [{start, end|null, key?}] dari aplikasi (tanggal pesan, tanggal masuk); tanpa database, tanpa Eloquent, tanpa tabel proyek.
 * Input        : records; opsi samples (5), min_samples (3), fallback_days (null), late_tolerance_days (0).
 * Output       : count/average/min/max/median/last, effective() + source + basis, expected(), status(), suggestReorderPoint(), describe*().
 * Dipakai oleh : LeadTimeEngine (katalog engine) dan aplikasi pewaris (stok, pembelian).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Engine;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Devlib\DateValue;
use Devlib\Lang;
use Devlib\Locale;

// Hitungan murni, immutable. Hari = selisih TANGGAL KALENDER (jam dan zona dibuang: '2026-10-05 23:59' dan '2026-10-06 00:01' beda 1 hari).
// Hanya catatan SELESAI (end >= start, keduanya tanggal sah) yang dihitung; catatan terbalik atau tak sah diabaikan, bukan galat.
// Angka efektif hanya SARAN: tidak menulis apa pun, tidak mengubah lead time manual milik aplikasi.
final class LeadTime
{
    public const SAMPLES = 5;

    public const MIN_SAMPLES = 3;

    /** @var list<array{start: int, end: int, days: int, key: ?string}> selesai, urut menurut end (stabil) */
    private array $done = [];

    /** @var list<array{start: int, key: ?string}> belum masuk */
    private array $open = [];

    private function __construct(
        private readonly array $records,
        private readonly int $samples,
        private readonly int $minSamples,
        private readonly ?int $fallback,
        private readonly int $tolerance,
    ) {
        foreach ($records as $r) {
            $r = (array) $r;
            $s = self::day($r['start'] ?? null);
            if ($s === null) {
                continue;
            }
            $key = isset($r['key']) ? (string) $r['key'] : null;
            if (($r['end'] ?? null) === null || $r['end'] === '') {
                $this->open[] = ['start' => $s, 'key' => $key];

                continue;
            }
            $e = self::day($r['end']);
            if ($e !== null && $e >= $s) {
                $this->done[] = ['start' => $s, 'end' => $e, 'days' => $e - $s, 'key' => $key];
            }
        }
        usort($this->done, fn ($a, $b) => $a['end'] <=> $b['end']);  // usort stabil sejak PHP 8
    }

    /** @param iterable<array|object> $records [{start, end|null, key?}]  @param array $options samples, min_samples, fallback_days, late_tolerance_days */
    public static function of(iterable $records, array $options = []): self
    {
        $fb = $options['fallback_days'] ?? null;

        return new self(
            is_array($records) ? $records : iterator_to_array($records, false),
            max(1, (int) ($options['samples'] ?? self::SAMPLES)),
            max(1, (int) ($options['min_samples'] ?? self::MIN_SAMPLES)),
            is_numeric($fb) && $fb >= 0 ? (int) ceil((float) $fb) : null,
            max(0, (int) ($options['late_tolerance_days'] ?? 0)),
        );
    }

    /** Salinan dengan opsi diganti (yang tidak disebut tetap). */
    public function with(array $options): self
    {
        return self::of($this->records, $options + [
            'samples' => $this->samples, 'min_samples' => $this->minSamples,
            'fallback_days' => $this->fallback, 'late_tolerance_days' => $this->tolerance,
        ]);
    }

    public function count(): int
    {
        return count($this->done);
    }

    public function average(): ?float
    {
        return $this->done ? array_sum($this->days()) / count($this->done) : null;
    }

    public function min(): ?int
    {
        return $this->done ? min($this->days()) : null;
    }

    public function max(): ?int
    {
        return $this->done ? max($this->days()) : null;
    }

    public function median(): ?float
    {
        $d = $this->days();
        sort($d);
        $n = count($d);

        return $n === 0 ? null : ($n % 2 ? (float) $d[intdiv($n, 2)] : ($d[$n / 2 - 1] + $d[$n / 2]) / 2);
    }

    /** Lead time catatan selesai TERBARU (menurut tanggal masuk). */
    public function last(): ?int
    {
        return $this->done ? $this->done[array_key_last($this->done)]['days'] : null;
    }

    /** Jumlah pesanan yang belum masuk. */
    public function openCount(): int
    {
        return count($this->open);
    }

    /** Angka yang dipakai: rata-rata `samples` terakhir dibulatkan ke atas bila data cukup; kalau tidak, fallback_days; null bila tidak ada. */
    public function effective(): ?int
    {
        return match ($this->source()) {
            'data' => $this->dataDays(),
            'manual' => $this->fallback,
            default => null,
        };
    }

    /** 'data' | 'manual' | 'none' */
    public function source(): string
    {
        return $this->count() >= $this->minSamples ? 'data' : ($this->fallback !== null ? 'manual' : 'none');
    }

    /** Jumlah catatan yang membentuk effective() (0 bila manual / none). */
    public function basis(): int
    {
        return $this->source() === 'data' ? min($this->samples, $this->count()) : 0;
    }

    /** Perkiraan tanggal tiba (Y-m-d) untuk pesanan yang dipesan pada $start; null bila tidak ada angka atau tanggal tak sah. */
    public function expected(mixed $start): ?string
    {
        $s = self::day($start);
        $eff = $this->effective();

        return $s === null || $eff === null ? null : self::iso($s + $eff);
    }

    /**
     * Pesanan yang belum masuk. state: on_time | late (elapsed > typical + toleransi) | unknown (tanpa angka atau tanggal tak sah).
     *
     * @return array{elapsed_days: ?int, typical_days: ?int, expected_at: ?string, state: string}
     */
    public function status(mixed $start, mixed $today = null): array
    {
        $s = self::day($start);
        $t = self::day($today ?? Locale::at()->format('Y-m-d'));
        $elapsed = $s === null || $t === null ? null : max(0, $t - $s);
        $typical = $this->effective();
        $state = $elapsed === null || $typical === null ? 'unknown' : ($elapsed > $typical + $this->tolerance ? 'late' : 'on_time');

        return ['elapsed_days' => $elapsed, 'typical_days' => $typical, 'expected_at' => $this->expected($start), 'state' => $state];
    }

    /** SARAN titik pesan ulang = pemakaian harian x lead time + stok pengaman; null bila lead time tidak ada. Tidak mengubah apa pun. */
    public function suggestReorderPoint(float $avgDailyUsage, float $safetyStock = 0): ?float
    {
        $eff = $this->effective();

        return $eff === null ? null : max(0.0, $avgDailyUsage) * $eff + max(0.0, $safetyStock);
    }

    /** Hasil lengkap untuk Blade / Result engine. */
    public function summary(): array
    {
        return [
            'count' => $this->count(), 'open' => $this->openCount(), 'average' => $this->average(), 'min' => $this->min(), 'max' => $this->max(),
            'median' => $this->median(), 'last' => $this->last(),
            'effective' => $this->effective(), 'source' => $this->source(), 'basis' => $this->basis(),
        ];
    }

    /** Kalimat baku: "Biasanya tiba 5 hari (rata-rata 4 pemesanan)" / "(perkiraan manual)" / "Belum ada data lead time". */
    public function describe(): string
    {
        $eff = $this->effective();

        return match (true) {
            $eff === null => Lang::t('leadtime_none'),
            $this->source() === 'data' => self::fill('leadtime_usual', ['{n}' => $eff, '{k}' => $this->basis()]),
            default => self::fill('leadtime_manual', ['{n}' => $eff]),
        };
    }

    /** "Perkiraan tiba 10-Okt-2026"; teks 'belum ada data' bila tidak ada perkiraan. */
    public function describeExpected(mixed $start): string
    {
        $iso = $this->expected($start);

        return $iso === null ? Lang::t('leadtime_none') : self::fill('leadtime_expected', ['{date}' => DateValue::of($iso)->display()]);
    }

    /** "Sudah 9 hari, biasanya 5. Periksa pesanan." (late) / "Sudah 3 hari, biasanya 5." (on_time) / teks 'belum ada data' (unknown). */
    public function describeStatus(mixed $start, mixed $today = null): string
    {
        $st = $this->status($start, $today);
        if ($st['state'] === 'unknown') {
            return Lang::t('leadtime_none');
        }

        return self::fill($st['state'] === 'late' ? 'leadtime_late' : 'leadtime_ontime', ['{n}' => $st['elapsed_days'], '{m}' => $st['typical_days']]);
    }

    /** @return list<int> */
    private function days(): array
    {
        return array_column($this->done, 'days');
    }

    private function dataDays(): int
    {
        $d = array_slice($this->days(), -$this->samples);

        return max(0, intdiv(array_sum($d) + count($d) - 1, count($d)));  // pembulatan ke atas dalam bilangan bulat
    }

    private static function fill(string $key, array $vars): string
    {
        return strtr(Lang::t($key), array_map('strval', $vars));
    }

    // Nomor hari kalender (hari sejak 1970-01-01) dari tanggal apa adanya: jam dan zona dibuang. null = bukan tanggal sah.
    private static function day(mixed $v): ?int
    {
        if ($v instanceof DateTimeInterface) {
            $y = (int) $v->format('Y');
            $m = (int) $v->format('n');
            $d = (int) $v->format('j');
        } elseif (is_string($v) && preg_match('/^\s*(\d{4})-(\d{1,2})-(\d{1,2})(?!\d)/', $v, $x)) {
            [$y, $m, $d] = [(int) $x[1], (int) $x[2], (int) $x[3]];
        } else {
            return null;
        }

        return checkdate($m, $d, $y) ? intdiv(gmmktime(0, 0, 0, $m, $d, $y), 86400) : null;
    }

    private static function iso(int $day): string
    {
        return gmdate('Y-m-d', $day * 86400);
    }
}
