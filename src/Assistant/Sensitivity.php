<?php

namespace Devlib\Assistant;

// Tingkat sensitivitas jawaban sebuah Tool (kebijakan: skills/devlib/assistant.md, "Cakupan data dan sensitivitas").
// Quantity: kuantitas (jumlah, stok, order, kehadiran), cukup hak modul. Value: memuat nilai rupiah, hanya untuk ValueAsker.
// Forbidden: gaji / payroll / formula HPP, tidak boleh jadi Tool sama sekali. Label ini dinyatakan PROGRAM; DevLib tidak menilai isi jawaban.
enum Sensitivity: string
{
    case Quantity = 'qty';
    case Value = 'value';
    case Forbidden = 'forbidden';
}
