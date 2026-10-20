<?php

namespace Devlib\Assistant\Index;

use Illuminate\Support\Facades\Cache;

// IndexThrottle bawaan: Cache::add (atomik di store yang mendukung kunci, mis. database / redis / file).
final class CacheThrottle implements IndexThrottle
{
    public function claim(string $key, int $seconds): bool
    {
        return Cache::add("devlib:{$key}", 1, $seconds);
    }
}
