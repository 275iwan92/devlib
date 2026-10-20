<?php

namespace Devlib\Chat;

use RuntimeException;

// Satu-satunya jenis kegagalan Chat (lawan bicara tidak ada, pesan kosong / kepanjangan, lampiran terlalu besar, bukan peserta).
class ChatException extends RuntimeException {}
