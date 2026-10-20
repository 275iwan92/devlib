<?php

namespace Devlib\Assistant\Index;

// Pembatas pemicu (paling sering sekali per jangka per program). claim() atomik: true = giliran ini boleh jalan dan jangka baru
// dimulai; false = sudah ada yang jalan dalam jangka itu. Diikat program bila cache bawaannya tidak cocok.
interface IndexThrottle
{
    public function claim(string $key, int $seconds): bool;
}
