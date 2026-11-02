<?php

/*
 * REMARK
 * Tujuan       : Denah dengan titik objek (floor plan): persegi ruang berlabel "KODE Nama" (atau gambar denah sebagai latar) dengan titik bulat berwarna menurut status (success | warn | danger | neutral) dan bernomor; titik dipilih dengan klik, ketuk, atau papan ketik; kartu detail di bawah diisi layar pemanggil.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop dan slot, tanpa tabel/model proyek.
 * Input        : rooms, pins, image, aspect, selected, legend, name, readonly, label; slot summary dan detail.
 * Output       : Markup dari resources/views/components/floor-plan.blade.php + perilaku resources/assets/js/components/floor-plan.js (event dl:floorplan-select).
 * Dipakai oleh : floor-plan (halaman katalog p6-floor-plan).
 * Status       : Aktif (DevLib).
 * View         : phone, desktop (denah menyusut mengikuti lebar; target sentuh titik 44 px di phone).
 */

namespace Devlib\Components;

use Devlib\Lang;

// Denah dengan titik objek. Semua posisi dan ukuran dalam PERSEN dari kotak denah.
//
// $rooms = [['code' => 'R1', 'label' => 'Meeting', 'x' => 4, 'y' => 6, 'w' => 40, 'h' => 38], ...]   persegi ruang (diabaikan bila $image diisi)
// $pins  = [['id' => 'u1', 'x' => 20, 'y' => 30, 'tone' => 'success|warn|danger|neutral', 'label' => 'Unit 01', 'status' => 'Safe',
//            'number' => '01', 'title' => 'tooltip', 'href' => '/url?pin=u1'], ...]   href = titik berupa tautan (tanpa JS); tanpa href = tombol
// $legend = true (Safe / Due soon / Overdue) | [['tone' => 'success', 'label' => '...'], ...] | false
// Slot: summary (di atas denah), detail (kartu titik terpilih, di bawah; elemen [data-floorplan-detail="<id>"] dipilih JS).
class FloorPlan extends DevlibComponent
{
    public const TONES = ['success', 'warn', 'danger', 'neutral'];

    private const DEFAULT_LEGEND = ['success' => 'Safe', 'warn' => 'Due soon', 'danger' => 'Overdue'];

    public function __construct(
        public array $rooms = [],
        public array $pins = [],
        public ?string $image = null,
        public string $aspect = '16:9',
        public ?string $selected = null,
        public array|bool $legend = false,
        public ?string $name = null,
        public bool $readonly = false,
        public ?string $label = null,
    ) {}

    public function tag(): string
    {
        return 'x-dl::floor-plan';
    }

    public function jsClass(): ?string
    {
        return 'FloorPlan';
    }

    public function source(): string
    {
        return 'rooms, pins (props), selection posted by hidden input `name`';
    }

    // "16:9" | "16/9" | "1.6" -> nilai CSS aspect-ratio.
    public function ratio(): string
    {
        return preg_match('/^\s*(\d+(?:\.\d+)?)\s*[:\/x]\s*(\d+(?:\.\d+)?)\s*$/', $this->aspect, $m) && $m[2] > 0 ? $m[1].' / '.$m[2] : '16 / 9';
    }

    public function tone(array $pin): string
    {
        return in_array($pin['tone'] ?? null, self::TONES, true) ? $pin['tone'] : 'neutral';
    }

    /** @return array<int, array{tone: string, label: string}> */
    public function legendRows(): array
    {
        if ($this->legend === true) {
            return array_map(fn ($t, $l) => ['tone' => $t, 'label' => Lang::text($l)], array_keys(self::DEFAULT_LEGEND), self::DEFAULT_LEGEND);
        }

        return $this->legend ?: [];
    }

    // Teks status titik (warna tidak boleh jadi satu-satunya pembawa arti): 'status' milik titik, atau label bawaan untuk tone-nya.
    public function status(array $pin): string
    {
        return (string) ($pin['status'] ?? (isset(self::DEFAULT_LEGEND[$this->tone($pin)]) ? Lang::text(self::DEFAULT_LEGEND[$this->tone($pin)]) : ''));
    }

    public function render()
    {
        return view('devlib::components.floor-plan');
    }
}
