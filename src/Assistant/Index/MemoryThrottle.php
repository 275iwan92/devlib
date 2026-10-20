<?php

namespace Devlib\Assistant\Index;

use Closure;

// IndexThrottle dalam memori untuk tes. `clock` mengembalikan detik.
final class MemoryThrottle implements IndexThrottle
{
    /** @var array<string, float> */
    private array $until = [];

    public function __construct(private readonly ?Closure $clock = null) {}

    public function claim(string $key, int $seconds): bool
    {
        $now = $this->clock ? (float) ($this->clock)() : microtime(true);
        if (($this->until[$key] ?? 0) > $now) {
            return false;
        }
        $this->until[$key] = $now + $seconds;

        return true;
    }
}
