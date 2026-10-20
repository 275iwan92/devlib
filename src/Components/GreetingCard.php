<?php

/*
 * REMARK
 * Tujuan       : Widget sapaan berdiri sendiri (user 2026-10-07, tampilan disetujui): salam + jabatan + ringkasan hari ini,.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : board, source.
 * Output       : Markup dari resources/views/components/greeting-card.blade.php.
 * Dipakai oleh : greeting-card (layar katalog/proyek).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Widget sapaan berdiri sendiri (user 2026-10-07, tampilan disetujui): salam + jabatan + ringkasan hari ini,
// tombol Job Description & SOP, jam, check-in, cuaca. Dipakai x-dl::dashboard, bisa juga dipasang sendiri.
// $board = potongan profil dashboard (lihat Dashboard): profile, profile_label, user, brief, attendance, weather, jobdesc, sops.
class GreetingCard extends DevlibComponent
{
    public function __construct(public array $board = [], public ?string $source = null) {}

    public function tag(): string
    {
        return 'x-dl::greeting-card profile='.($this->board['profile'] ?? 'general');
    }

    public function source(): string
    {
        return $this->source ?? 'profil dashboard per jabatan';
    }

    // Id dasar untuk modal Job Description / SOP (sama dengan id Dashboard agar pemicu tombolnya tetap).
    public function key(): string
    {
        return 'dash-'.($this->board['profile'] ?? 'general');
    }

    public function render()
    {
        return view('devlib::components.greeting-card');
    }
}
