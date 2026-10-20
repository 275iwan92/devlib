<?php

namespace Devlib\Engine;

// Dokumen transaksional yang tahu cara membuat record ADJUSTMENT pembalik saat dibatalkan (CANCEL).
// Kembalikan null bila data masih cocok (tidak perlu pembalik); Result::applied(data = record pembalik, reasons = alasannya).
interface Adjustable
{
    public function createAdjustment(): ?Result;
}
