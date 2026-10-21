<?php

namespace App\View\Components\Demo;

use Devlib\Components\Form;

// Contoh pola override: turunan Form standar (dokumen koreksi stok, siklus bawaan Draft > Confirmed).
// PROPERTI ditimpa di sini (judul, panel atas, jenis dokumen, status awal), METHOD ditimpa di kelas JS pasangannya (FormStockAdjustment).
class FormStockAdjustment extends Form
{
    public function __construct(string $status = 'draft', ?string $back = null, ?string $newUrl = null)
    {
        parent::__construct(
            title: 'Stock adjustment',
            subtitle: 'Count correction',
            panel: 'top',
            back: $back,
            newUrl: $newUrl,
            kind: 'document',
            status: $status,
        );
    }

    public function tag(): string
    {
        return 'x-demo.form-stock-adjustment';
    }

    public function jsClass(): ?string
    {
        return 'FormStockAdjustment';
    }
}
