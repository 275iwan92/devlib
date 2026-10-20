<?php

/*
 * REMARK
 * Tujuan       : Laporan cetak (mockup_print_stock_card, mockup_print_master_stock): sama dengan x-dl::print-out.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : title, subtitle, number, date, status, company, companyPpn, ppn, parties, columns, … (23 prop).
 * Output       : Markup Blade inline (render() kelas ini).
 * Dipakai oleh : report-master-stock, report-stock-card (layar katalog/proyek).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Laporan cetak (mockup_print_stock_card, mockup_print_master_stock): sama dengan x-dl::print-out
// (kop, kertas, halaman otomatis) + kriteria, ringkasan KPI, kelompok + subtotal, kolom berjalan.
class Report extends PrintOut
{
    public function tag(): string
    {
        return 'x-dl::report';
    }

    public function source(): string
    {
        return $this->source ?? 'laporan';
    }
}
