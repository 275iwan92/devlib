<?php

namespace Devlib\Engine;

// Dikirim setelah dokumen berstatus CANCELLED (dan pembaliknya, bila ada, sudah dibuat). Aplikasi mendengarkannya bila perlu.
final class DocumentCancelled
{
    public function __construct(public readonly object $document, public readonly ?Result $adjustment = null) {}
}
