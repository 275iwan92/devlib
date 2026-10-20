<?php

namespace Tests\Feature;

use App\Support\Catalog;
use App\Support\DemoData;
use App\Support\FlowDemo;
use Tests\TestCase;

// Alur Grid -> Form -> kembali (skills/devlib/grid-form-flow.md): grid membuka form di halaman sendiri, Back / Save / Delete kembali ke grid asal.
class GridFormFlowTest extends TestCase
{
    private function plain(string $html): string
    {
        return str_replace('\\/', '/', html_entity_decode($html));
    }

    public function test_flow_pages_are_in_the_grid_form_group_and_not_approved(): void
    {
        $group = collect(Catalog::groups())->firstWhere('code', 'FM');
        $this->assertSame('Grid + Form', $group['label']);
        foreach (['p5-flow-master', 'p5-flow-document', 'p5-form-single', 'p5-form-md', 'p5-form-approval', 'p5-form-wizard'] as $slug) {
            $this->assertArrayHasKey($slug, $group['pages']);
        }
        $this->assertArrayNotHasKey('p5-flow-master', Catalog::APPROVED);
        $this->assertArrayNotHasKey('p5-flow-document', Catalog::APPROVED);
    }

    public function test_master_grid_opens_the_form_page_and_remembers_where_it_came_from(): void
    {
        $page = $this->plain($this->get('/lib/p5-flow-master')->assertOk()->getContent());
        $this->assertStringContainsString('"restore":true', $page);
        $this->assertStringContainsString('/lib/p5-flow-master/form/{id}?from=%2Flib%2Fp5-flow-master', $page);
        $this->assertStringContainsString('/lib/p5-flow-master/form/new?from=%2Flib%2Fp5-flow-master', $page);
    }

    public function test_master_form_back_goes_to_the_grid_it_came_from(): void
    {
        $emp = DemoData::employees()[2];
        $res = $this->get('/lib/p5-flow-master/form/'.$emp['id'].'?'.http_build_query(['from' => '/lib/p5-flow-master']))->assertOk();
        $res->assertSee($emp['code']);
        $res->assertSee('href="/lib/p5-flow-master"', false);
        $this->assertStringContainsString('name="from" value="/lib/p5-flow-master"', $res->getContent());
    }

    public function test_document_form_back_keeps_the_grid_parameters(): void
    {
        $from = '/lib/p5-flow-document?doc=po';
        $page = $this->plain($this->get($from)->assertOk()->getContent());
        $this->assertStringContainsString('/lib/p5-flow-document/form/{id}?doc=po&from='.urlencode($from), $page);

        $po = DemoData::purchaseOrders()[0];
        $res = $this->get('/lib/p5-flow-document/form/'.$po['id'].'?'.http_build_query(['doc' => 'po', 'from' => $from]))->assertOk();
        $res->assertSee($po['po_no']);
        $this->assertStringContainsString('href="'.$from.'"', $this->plain($res->getContent()));
        $co = $this->get('/lib/p5-flow-document/form/1?'.http_build_query(['doc' => 'co', 'from' => '/lib/p5-flow-document?doc=co']))->assertOk();
        $co->assertSee('CO-2610-0001');
        $this->assertStringContainsString('href="/lib/p5-flow-document?doc=co"', $this->plain($co->getContent()));
    }

    public function test_foreign_or_malformed_from_falls_back_to_the_module_grid(): void
    {
        foreach (['//evil.test/x', 'https://evil.test/x', 'javascript:alert(1)', '/other/page', ''] as $from) {
            $res = $this->get('/lib/p5-flow-master/form/1?'.http_build_query(['from' => $from]))->assertOk();
            $this->assertStringNotContainsString('evil.test', $res->getContent());
            $res->assertSee('href="/lib/p5-flow-master"', false);
        }
    }

    public function test_document_status_decides_the_buttons(): void
    {
        $acts = fn (string $status) => preg_match_all('/data-f-act="(\w+)"/', $this->get('/lib/p5-flow-document/form/1?doc=po&status='.$status)->assertOk()->getContent(), $m) ? array_unique($m[1]) : [];

        $draft = $acts('draft');
        $this->assertEqualsCanonicalizing(['delete', 'new', 'undo', 'confirm', 'save', 'back'], array_values(array_diff($draft, ['print', 'email'])));
        $confirmed = $acts('confirmed');
        $this->assertContains('cancel', $confirmed);
        $this->assertContains('draft', $confirmed);
        $this->assertNotContains('save', $confirmed);
        $this->assertNotContains('confirm', $confirmed);
        $cancelled = $acts('cancelled');
        $this->assertContains('duplicate', $cancelled);
        $this->assertNotContains('cancel', $cancelled);
        $this->assertEqualsCanonicalizing(['new', 'back'], array_values(array_diff($acts('closed'), ['print', 'email'])));
        $this->assertEqualsCanonicalizing(['undo', 'save', 'back'], array_values(array_diff($this->actsOf('/lib/p5-flow-document/form/new?doc=po'), ['print', 'email'])));
    }

    private function actsOf(string $url): array
    {
        preg_match_all('/data-f-act="(\w+)"/', $this->get($url)->assertOk()->getContent(), $m);

        return array_values(array_unique($m[1]));
    }

    public function test_save_returns_to_the_grid_with_the_row_highlighted_and_a_toast(): void
    {
        $res = $this->post('/lib/p5-flow-master/form/3', ['_action' => 'save', 'status' => 'draft', 'from' => '/lib/p5-flow-master', 'full_name' => 'Nama Contoh']);
        $res->assertRedirect('/lib/p5-flow-master?hl=3');
        $res->assertSessionHas('flow_flash.0', 'success');
        $this->followRedirects($res)->assertSee('data-dl-flash="success"', false);

        $doc = $this->post('/lib/p5-flow-document/form/5', ['_action' => 'save', 'doc' => 'po', 'status' => 'draft', 'from' => '/lib/p5-flow-document?doc=po', 'po_date' => '2026-10-01', 'supplier' => 'UD Lem Sejahtera']);
        $doc->assertRedirect('/lib/p5-flow-document?doc=po&hl=5');
    }

    public function test_save_with_a_missing_required_field_stays_on_the_form_with_an_error_toast(): void
    {
        $res = $this->post('/lib/p5-flow-master/form/3', ['_action' => 'save', 'status' => 'draft', 'from' => '/lib/p5-flow-master', 'full_name' => ' ']);
        $res->assertRedirect();
        $this->assertStringStartsWith('/lib/p5-flow-master/form/3', parse_url($res->headers->get('Location'), PHP_URL_PATH));
        $res->assertSessionHas('flow_flash.0', 'danger');
    }

    public function test_status_actions_keep_the_form_open_and_delete_returns_to_the_grid(): void
    {
        $base = ['doc' => 'po', 'from' => '/lib/p5-flow-document?doc=po'];
        $confirm = $this->post('/lib/p5-flow-document/form/6', $base + ['_action' => 'confirm', 'status' => 'draft']);
        $this->assertStringContainsString('status=confirmed', $confirm->headers->get('Location'));

        $noReason = $this->post('/lib/p5-flow-document/form/6', $base + ['_action' => 'cancel', 'status' => 'confirmed']);
        $noReason->assertSessionHas('flow_flash.0', 'danger');
        $cancel = $this->post('/lib/p5-flow-document/form/6', $base + ['_action' => 'cancel', 'status' => 'confirmed', '_reason' => 'Salah pemasok']);
        $this->assertStringContainsString('status=cancelled', $cancel->headers->get('Location'));

        $this->post('/lib/p5-flow-document/form/6', $base + ['_action' => 'delete', 'status' => 'draft'])->assertRedirect('/lib/p5-flow-document?doc=po');

        $dup = $this->post('/lib/p5-flow-document/form/6', $base + ['_action' => 'duplicate', 'status' => 'cancelled']);
        $this->assertStringContainsString('/form/new?', $dup->headers->get('Location'));
        $this->assertStringContainsString('copy=6', $dup->headers->get('Location'));
    }

    public function test_actions_not_valid_for_the_status_or_the_role_are_refused(): void
    {
        $bad = $this->post('/lib/p5-flow-master/form/3', ['_action' => 'delete', 'status' => 'confirmed', 'from' => '/lib/p5-flow-master']);
        $bad->assertSessionHas('flow_flash.0', 'warn');

        $role = $this->post('/lib/p5-flow-master/form/3', ['_action' => 'save', 'status' => 'draft', 'as' => 'manager', 'full_name' => 'X', 'from' => '/lib/p5-flow-master']);
        $role->assertSessionHas('flow_flash.0', 'danger');
        $this->assertStringContainsString('/form/3', $role->headers->get('Location'));
    }

    public function test_unknown_row_goes_back_to_the_grid_with_a_toast(): void
    {
        $this->get('/lib/p5-flow-master/form/99999?'.http_build_query(['from' => '/lib/p5-flow-master']))->assertRedirect('/lib/p5-flow-master')->assertSessionHas('flow_flash.0', 'danger');
        $this->post('/lib/p5-flow-document/form/99999', ['doc' => 'po', '_action' => 'save'])->assertRedirect('/lib/p5-flow-document?doc=po');
    }

    public function test_toggle_flips_the_row_without_storing(): void
    {
        $emp = DemoData::employees()[0];
        $this->post('/lib/p5-flow-master/toggle/'.$emp['id'])->assertOk()->assertExactJson(['active' => ! $emp['active']]);
        $this->assertTrue(FlowDemo::has('p5-flow-master'));
        $this->get('/lib/p5-form-single/form/1')->assertNotFound();
    }

    public function test_standalone_form_pages_go_back_to_their_flow_grid(): void
    {
        $this->get('/lib/p5-form-single')->assertOk()->assertSee('href="/lib/p5-flow-master"', false);
        $page = $this->plain($this->get('/lib/p5-form-md')->assertOk()->getContent());
        $this->assertStringContainsString('href="/lib/p5-flow-document?doc=co"', $page);
        $this->assertStringContainsString('href="/lib/p5-flow-document?doc=po"', $page);
    }
}
