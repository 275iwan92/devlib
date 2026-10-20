<?php

namespace Devlib\Assistant;

// Asker opsional untuk program yang membagi hak nilai rupiah per Tool (mis. keuangan melihat semua nilai, sales hanya hasil HPP).
// Bila diimplementasikan, canSeeValueOf() menjadi satu-satunya penentu untuk Tool bersensitivitas Value; canSeeValue() tidak dipakai.
// Penanya yang hanya ValueAsker tetap memakai canSeeValue() (perilaku sebelumnya). DevLib tidak tahu peran apa pun: program yang memutuskan.
interface ScopedValueAsker extends ValueAsker
{
    public function canSeeValueOf(string $toolKey): bool;
}
