<?php

namespace App\View\Components\Demo;

use Devlib\Components\Grid;

// Contoh pola override: turunan Grid standar (daftar mutasi stok, hanya-baca).
// PROPERTI ditimpa di sini (kolom, tab, saringan, mode report), METHOD ditimpa di kelas JS pasangannya (GridStockMovement).
class GridStockMovement extends Grid
{
    public function __construct(array $rows = [])
    {
        parent::__construct(
            id: 'stock-movement',
            columns: [
                ['key' => 'doc', 'label' => 'Document', 'type' => 'identity', 'code' => 'item', 'avatar' => false],
                ['key' => 'date', 'label' => 'Date', 'type' => 'date'],
                ['key' => 'type', 'label' => 'Type', 'type' => 'badge', 'tones' => ['Receipt' => 'success', 'Issue' => 'info', 'Return' => 'neutral', 'Adjustment' => 'warn']],
                ['key' => 'lot', 'label' => 'Lot'],
                ['key' => 'change', 'label' => 'Change', 'type' => 'integer', 'signed' => true],
            ],
            rows: $rows,
            rowKey: 'doc',
            tabs: [
                ['label' => 'All'],
                ['label' => 'Receipt', 'where' => ['type' => 'Receipt']],
                ['label' => 'Issue', 'where' => ['type' => 'Issue']],
                ['label' => 'Return / Adjustment', 'where' => ['type' => ['Return', 'Adjustment']]],
            ],
            filters: ['type' => 'Type', 'lot' => 'Lot'],
            search: 'Search document, item, lot...',
            mode: 'report',
        );
    }

    public function tag(): string
    {
        return 'x-demo.grid-stock-movement';
    }

    public function source(): string
    {
        return 'inventory.stock_movement (example)';
    }

    public function jsClass(): ?string
    {
        return 'GridStockMovement';
    }
}
