<?php

namespace Devlib\Assistant;

use RuntimeException;

// Kegagalan konfigurasi / kontrak Assistant (bukan kegagalan menjawab: itu Reply UNKNOWN).
class AssistantException extends RuntimeException {}
