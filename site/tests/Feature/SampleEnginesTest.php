<?php

namespace Tests\Feature;

use Devlib\Engine\EngineException;
use Devlib\Engine\EngineRegistry;
use Tests\TestCase;

// Engine contoh situs ini (kontrak Devlib\Engine) dan halaman yang memakainya (Engine Catalog, Build Progress).
class SampleEnginesTest extends TestCase
{
    private function run_engine(string $key, array $input): \Devlib\Engine\Result
    {
        return app(EngineRegistry::class)->run($key, $input);
    }

    public function test_volume_discount_uses_the_highest_tier_reached(): void
    {
        $r = $this->run_engine('volume-discount', ['qty' => 600, 'unit_price' => 1000]);

        $this->assertTrue($r->isApplied());
        $this->assertSame(0.10, $r->data['discount_rate']);
        $this->assertSame(540000.0, $r->data['total']);
        $this->assertSame(0.0, $this->run_engine('volume-discount', ['qty' => 99, 'unit_price' => 10])->data['discount_rate']);
    }

    public function test_reorder_suggestion_is_a_draft_with_reasons(): void
    {
        $r = $this->run_engine('reorder-suggestion', ['on_hand' => 10, 'daily_use' => 5, 'lead_days' => 4, 'safety_days' => 3, 'pack_size' => 10]);

        $this->assertTrue($r->isDraft());
        $this->assertSame(35.0, $r->data['reorder_point']);
        $this->assertSame(100, $r->data['order_qty']);  // (35 + 70 - 10) = 95 -> 10 full packs of 10
        $this->assertNotEmpty($r->reasons);
        $this->assertSame(0, $this->run_engine('reorder-suggestion', ['on_hand' => 500, 'daily_use' => 5, 'lead_days' => 4])->data['order_qty']);
    }

    public function test_status_flow_follows_the_owner_rules(): void
    {
        $this->assertSame(['save', 'delete', 'new', 'print', 'confirm'], $this->run_engine('status-flow', ['status' => 'DRAFT'])->data['actions']);
        $this->assertNotContains('save', $this->run_engine('status-flow', ['status' => 'confirmed'])->data['actions']);  // CONFIRMED = read only
        $this->assertContains('deactivate', $this->run_engine('status-flow', ['kind' => 'master', 'status' => 'activated'])->data['actions']);
        $this->assertTrue($this->run_engine('status-flow', ['status' => 'approved'])->isRejected());
    }

    public function test_document_number_restarts_every_month(): void
    {
        $this->assertSame('PO-2610-0032', $this->run_engine('document-number', ['prefix' => 'po', 'last' => 'PO-2610-0031', 'date' => '2026-10-20'])->data['number']);
        $this->assertSame('PO-2611-0001', $this->run_engine('document-number', ['prefix' => 'PO', 'last' => 'PO-2610-0031', 'date' => '2026-11-02'])->data['number']);
    }

    public function test_unknown_engine_is_a_named_failure(): void
    {
        $this->expectException(EngineException::class);
        $this->run_engine('nope', []);
    }

    public function test_catalog_rows_carry_the_described_details(): void
    {
        $row = collect(app(EngineRegistry::class)->catalog())->firstWhere('key', 'volume-discount');

        $this->assertSame('calc', $row['mode']);
        $this->assertNotEmpty($row['inputs']);
        $this->assertNotEmpty($row['steps']);
    }

    public function test_engine_catalog_and_build_pages_render_devlib_own_data(): void
    {
        $this->get('/lib/p9-engines')->assertOk()->assertSee('Volume Discount')->assertSee('Reorder Suggestion');
        $this->get('/lib/p9-build')->assertOk()->assertSee('Build Progress');
    }

    public function test_site_ships_no_data_from_other_projects(): void
    {
        $this->assertDirectoryDoesNotExist(base_path('resources/data/v2'));
        $this->assertStringNotContainsString('tm2', (string) file_get_contents(base_path('resources/data/schema.json')));
    }
}
