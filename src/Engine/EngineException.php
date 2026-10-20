<?php

namespace Devlib\Engine;

use RuntimeException;

// Satu-satunya jenis kegagalan engine: selalu membawa kunci engine, supaya terlacak ke satu kelas (bukan false / null diam-diam).
class EngineException extends RuntimeException
{
    public function __construct(public readonly string $engine, string $message, ?\Throwable $previous = null)
    {
        parent::__construct("[{$engine}] {$message}", 0, $previous);
    }
}
