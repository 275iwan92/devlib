<?php

namespace App\Engines;

use Devlib\Engine\Described;
use Devlib\Engine\Engine;
use Devlib\Engine\Mode;
use Devlib\Engine\Result;

// Contoh engine mode Calc: hitungan murni, tanpa menulis ke database.
class VolumeDiscountEngine implements Described, Engine
{
    /** @var array<int, float> batas qty => diskon */
    public const TIERS = [100 => 0.05, 500 => 0.10, 1000 => 0.15];

    public function key(): string
    {
        return 'volume-discount';
    }

    public function name(): string
    {
        return 'Volume Discount';
    }

    public function group(): string
    {
        return 'Pricing';
    }

    public function mode(): Mode
    {
        return Mode::Calc;
    }

    public function purpose(): string
    {
        return 'Gives the quantity discount of an order line and the line total from the unit price.';
    }

    public function run(array $input): Result
    {
        $qty = max(0, (int) ($input['qty'] ?? 0));
        $price = max(0.0, (float) ($input['unit_price'] ?? 0));
        $rate = 0.0;
        foreach (self::TIERS as $from => $discount) {
            if ($qty >= $from) {
                $rate = $discount;
            }
        }

        return Result::applied(
            ['discount_rate' => $rate, 'unit_price_after' => round($price * (1 - $rate), 2), 'total' => round($qty * $price * (1 - $rate), 2)],
            [$rate > 0 ? 'Quantity '.$qty.' reaches the '.($rate * 100).'% tier.' : 'Quantity '.$qty.' is below the first tier; no discount.'],
        );
    }

    public function details(): array
    {
        return [
            'inputs' => [['qty', 'Ordered quantity'], ['unit_price', 'Price of one unit before discount']],
            'outputs' => [['discount_rate', 'Discount of the highest tier reached'], ['unit_price_after', 'Unit price after discount'], ['total', 'Line total']],
            'steps' => ['Take the highest tier whose threshold the quantity reaches.', 'Apply its rate to the unit price.', 'Multiply by the quantity.'],
            'settings' => ['TIERS: 100 = 5%, 500 = 10%, 1000 = 15%'],
        ];
    }
}
