<?php

namespace Devlib\Assistant;

// Asker opsional: program menyatakan apakah penanya orang keuangan (boleh melihat nilai rupiah).
// Asker yang tidak mengimplementasikan ini dianggap TIDAK boleh (bawaan paling aman) untuk Tool bersensitivitas Value.
interface ValueAsker extends Asker
{
    public function canSeeValue(): bool;
}
