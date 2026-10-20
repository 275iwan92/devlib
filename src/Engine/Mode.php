<?php

namespace Devlib\Engine;

// Mode baku sebuah engine; nilainya sama dengan kunci x-dl::engine-catalog (EngineCatalog::MODES).
enum Mode: string
{
    case Suggest = 'suggest'; // menyarankan: hasilnya DRAF, officer yang memutuskan (Confirm)
    case Auto = 'auto';       // jalan sendiri di latar, hasil langsung berlaku
    case Calc = 'calc';       // hitungan murni: input -> output, tanpa menulis ke database
    case Flow = 'flow';       // alur dokumen (status, pembalik, penerusan ke dokumen lain)
}
