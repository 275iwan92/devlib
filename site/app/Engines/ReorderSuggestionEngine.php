<?php

namespace App\Engines;

use Devlib\Engine\Described;
use Devlib\Engine\Engine;
use Devlib\Engine\Mode;
use Devlib\Engine\Result;

// Contoh engine mode Suggest: hasilnya DRAF berikut alasannya; officer yang memutuskan memesan.
class ReorderSuggestionEngine implements Described, Engine
{
    public function key(): string
    {
        return 'reorder-suggestion';
    }

    public function name(): string
    {
        return 'Reorder Suggestion';
    }

    public function group(): string
    {
        return 'Inventory';
    }

    public function mode(): Mode
    {
        return Mode::Suggest;
    }

    public function purpose(): string
    {
        return 'Suggests how much to order when stock falls to the reorder point; the officer decides.';
    }

    public function run(array $input): Result
    {
        $onHand = (float) ($input['on_hand'] ?? 0);
        $daily = max(0.0, (float) ($input['daily_use'] ?? 0));
        $lead = max(0, (int) ($input['lead_days'] ?? 0));
        $safety = max(0, (int) ($input['safety_days'] ?? 3));
        $pack = max(1, (int) ($input['pack_size'] ?? 1));
        $point = $daily * ($lead + $safety);

        if ($onHand > $point) {
            return Result::draft(['order_qty' => 0, 'reorder_point' => $point], ['Stock '.$onHand.' is above the reorder point '.$point.'; nothing to order.']);
        }
        $target = $point + $daily * 14;
        $qty = (int) (ceil(($target - $onHand) / $pack) * $pack);

        return Result::draft(
            ['order_qty' => $qty, 'reorder_point' => $point],
            ['Stock '.$onHand.' is at or below the reorder point '.$point.' ('.$lead.' lead + '.$safety.' safety days of use).', 'Orders up to 14 days of use above the point, in packs of '.$pack.'.'],
            $daily === 0.0 ? ['Daily use is 0; the suggestion is not meaningful.'] : [],
        );
    }

    public function details(): array
    {
        return [
            'inputs' => [['on_hand', 'Stock now'], ['daily_use', 'Average use per day'], ['lead_days', 'Supplier lead time (days)'], ['safety_days', 'Safety days, default 3'], ['pack_size', 'Order in multiples of this pack']],
            'outputs' => [['order_qty', 'Suggested quantity (0 = nothing to order)'], ['reorder_point', 'Daily use x (lead + safety days)']],
            'steps' => ['Reorder point = daily use x (lead + safety days).', 'Above the point: suggest nothing.', 'At or below: order up to the point plus 14 days of use, rounded up to full packs.'],
        ];
    }
}
