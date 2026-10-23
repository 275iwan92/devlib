<?php

namespace Tests\Feature;

use App\Support\DemoData;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

// x-dl::pick-queue: chip dengan jumlah, filter awal, baris belum siap tidak bisa dicentang, kunci group, bilah aksi, aksi POST.
class PickQueueTest extends TestCase
{
    private function html(string $attrs = ''): string
    {
        return Blade::render('<x-dl::pick-queue :rows="$rows" '.$attrs.' />', ['rows' => DemoData::pickQueue()]);
    }

    public function test_default_chips_count_rows_per_state_and_first_chip_is_active(): void
    {
        $html = $this->html();
        $this->assertMatchesRegularExpression('/data-pq-chip="ready"[^>]*>Fully ready <b>4<\/b>/', $html);
        $this->assertMatchesRegularExpression('/data-pq-chip="partial"[^>]*>Partly ready <b>1<\/b>/', $html);
        $this->assertMatchesRegularExpression('/data-pq-chip="waiting"[^>]*>Not ready <b>1<\/b>/', $html);
        $this->assertMatchesRegularExpression('/data-pq-chip="all"[^>]*>'.\Devlib\Lang::text('All').' <b>6<\/b>/', $html);
        $this->assertStringContainsString('data-pq-filter="ready"', $html);
        $this->assertMatchesRegularExpression('/is-active" data-pq-chip="ready"/', $html);
    }

    public function test_rows_outside_the_active_filter_are_hidden_and_waiting_rows_cannot_be_ticked(): void
    {
        $html = $this->html();
        // 4 baris siap tampil, 2 sisanya hidden.
        $this->assertSame(2, preg_match_all('/data-pq-row[^>]*\bhidden\b/', $html));
        $this->assertMatchesRegularExpression('/<input type="checkbox" name="ids\[\]" value="423" disabled/', $html);
        $this->assertDoesNotMatchRegularExpression('/value="412" disabled/', $html);
        $this->assertStringContainsString('dl-pq__row is-off', $html);

        $all = $this->html('filter="all"');
        $this->assertSame(0, preg_match_all('/data-pq-row[^>]*\bhidden\b/', $all));
    }

    public function test_group_key_numbers_and_due_text_are_rendered(): void
    {
        $html = $this->html('filter="all"');
        $this->assertStringContainsString('data-pq-group="maju|a1|vat"', $html);
        $this->assertStringContainsString('data-pq-full="0"', $html);
        $this->assertStringContainsString('3.000 / 5.000 pcs', str_replace(',', '.', $html));
        $this->assertStringContainsString('in 3 days', $html);
        $this->assertStringContainsString('overdue 2 days', $html);
        $this->assertStringContainsString('dl-pq__due is-late', $html);
    }

    public function test_action_adds_csrf_and_target_and_texts_are_data_attributes(): void
    {
        $html = $this->html('action="/deliveries" name="orders" button="Make one from :n"');
        $this->assertStringContainsString('action="/deliveries"', $html);
        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringContainsString('name="orders[]"', $html);
        $this->assertStringContainsString('data-pq-button="Make one from :n"', $html);

        $this->assertStringNotContainsString('name="_token"', $this->html());
    }

    public function test_custom_states_and_filters_are_used(): void
    {
        $html = Blade::render('<x-dl::pick-queue :rows="$rows" :states="$states" :filters="$filters" />', [
            'rows' => [['id' => 1, 'title' => 'A-1', 'party' => 'P', 'state' => 'open', 'have' => 1, 'want' => 2, 'unit' => 'kg']],
            'states' => ['open' => ['label' => 'Open', 'tone' => 'info']],
            'filters' => [['key' => 'todo', 'label' => 'To do', 'states' => ['open']]],
        ]);
        $this->assertMatchesRegularExpression('/data-pq-chip="todo"[^>]*>To do <b>1<\/b>/', $html);
        $this->assertStringContainsString(\Devlib\Lang::text('Open'), $html);
        $this->assertStringNotContainsString('value="1" disabled', $html);
    }
}
