<?php

namespace App\Support;

use Devlib\PrintDoc\Document;

// Cetakan contoh untuk form alur (Print di form): satu kelas, isi dari baris yang dibuka. Cetakan SELALU membawa status dokumen
// (DRAFT / CONFIRMED / CANCELLED) dan, bila ada, status approval; HOLD dan REJECTED juga memberi tanda air (aturan: transaction-lifecycle.md).
final class FlowPrintDoc extends Document
{
    public string $type = 'demo-flow-doc';

    public string $partyLabel = 'To';

    public bool $priced = true;

    public array $extra = [['key' => 'disc', 'label' => 'Disc.', 'width' => 17], ['key' => 'due_date', 'label' => 'Ship Date', 'width' => 21, 'type' => 'date']];

    public array $receivers = [['Received by', '']];

    public function __construct(array $data, string $title, string $numberLabel)
    {
        $this->title = $title;
        $this->numberLabel = $numberLabel;
        parent::__construct($data);
    }

    // Label status di cetakan: dokumen confirmed + approval tampil "CONFIRMED · APPROVED" (waiting = CONFIRMED saja, belum ada keputusan).
    public static function statusText(string $status, ?string $approval): string
    {
        $s = strtoupper($status === 'new' ? 'draft' : $status);

        return $s === 'CONFIRMED' && $approval && $approval !== 'waiting' ? 'CONFIRMED · '.strtoupper($approval) : $s;
    }

    public static function make(string $slug, string $doc, array $row, string $status, ?string $approval): self
    {
        DemoPrintDocs::boot();
        $single = $slug === 'p5-flow-single';
        $po = ! $single && $doc === 'po';
        $rows = $single
            ? [['name' => $row['description'], 'qty' => 1, 'unit' => 'PCS', 'price' => $row['amount'], 'subtotal' => $row['amount']]]
            : array_map(fn ($l) => ['name' => $l[$po ? 'item_label' : 'product_label'], 'qty' => $l['qty'], 'unit' => $l[$po ? 'uom' : 'uom'], 'price' => $l['price'], 'subtotal' => $l['subtotal'] ?? $l['qty'] * $l['price']], $row['lines']);
        $data = [
            'number' => $row['cv_no'] ?? $row['co_no'] ?? $row['po_no'], 'date' => $row['cv_date'] ?? $row['co_date'] ?? $row['po_date'], 'status' => self::statusText($status, $approval),
            'party' => ['name' => strtoupper($row['payee'] ?? $row['customer'] ?? $row['supplier']), 'lines' => []], 'ppn' => $single ? 0 : 11, 'rows' => $rows, 'printedBy' => 'Demo',
        ];
        if ($status === 'confirmed' && in_array($approval, ['hold', 'rejected'], true)) {
            $data['watermark'] = strtoupper($approval);
        }

        return new self($data, $single ? 'Cash Voucher' : ($po ? 'Purchase Order' : 'Customer Order'), $single ? 'Voucher No.' : ($po ? 'PO No.' : 'Order No.'));
    }
}
