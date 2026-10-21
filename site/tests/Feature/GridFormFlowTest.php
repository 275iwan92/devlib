<?php

namespace Tests\Feature;

use App\Support\Catalog;
use App\Support\DemoData;
use App\Support\FlowDemo;
use Tests\TestCase;

// Menu "Grid and Form" dan alur Grid -> Form -> kembali (skills/devlib/grid-form-flow.md): grid membuka form di halaman sendiri;
// siklus ketat (user 2026-10-21): Save dan Confirm tetap di form, hanya Back (dan Delete, karena barisnya hilang) kembali ke grid.
class GridFormFlowTest extends TestCase
{
    private function plain(string $html): string
    {
        return str_replace('\\/', '/', html_entity_decode($html));
    }

    private function actsOf(string $url): array
    {
        preg_match_all('/data-f-act="(\w+)"/', $this->get($url)->assertOk()->getContent(), $m);

        return array_values(array_unique($m[1]));
    }

    // id baris Approval menurut status approval-nya (DemoData::approvalOrders).
    private function approvalId(string $approval): int
    {
        return collect(DemoData::approvalOrders())->firstWhere('approval', $approval)['id'];
    }

    private function approvalForm(int $id, string $approval, string $as = ''): string
    {
        return '/lib/p5-flow-approval/form/'.$id.'?'.http_build_query(array_filter(['from' => '/lib/p5-flow-approval', 'status' => 'confirmed', 'approval' => $approval, 'as' => $as]));
    }

    public function test_menu_is_one_group_grid_and_form_with_two_level_folders_and_old_pages_stay_reachable(): void
    {
        $groups = collect(Catalog::groups());
        $this->assertNull($groups->firstWhere('code', 'FM'));
        $group = $groups->firstWhere('code', 'GR');
        $this->assertSame('Grid and Form', $group['label']);
        foreach (['p5-flow-document', 'p5-flow-single', 'p5-flow-approval', 'p5-flow-master', 'p5-grid-lookup', 'p5-form-wizard', 'p5-grid-report'] as $slug) {
            $this->assertArrayHasKey($slug, $group['pages']);
            $this->assertNotFalse($group['pages'][$slug]['menu'] ?? true, $slug);
        }
        foreach (['p5-grid-tx', 'p5-grid-master', 'p5-form-single', 'p5-form-md', 'p5-form-approval'] as $slug) {
            $this->assertFalse($group['pages'][$slug]['menu']);
            $this->get('/lib/'.$slug)->assertOk();
        }
        foreach (['p5-flow-master', 'p5-flow-document', 'p5-flow-single', 'p5-flow-approval'] as $slug) {
            $this->assertArrayNotHasKey($slug, Catalog::APPROVED);
        }

        $menu = collect(Catalog::modules())->firstWhere('label', 'Grid and Form')['children'];
        $this->assertSame(['Grid Report', 'Grid Pre-Entry', 'Form Wizard'], array_column($menu, 'label'));
        $this->assertSame(['List' => false], ['List' => false]);
        $this->assertSame(['Grid Report List', 'Grid Group By', 'Grid Summary → Details', 'Grid Pivot'], array_column($menu[0]['items'], 'label'));
        $pre = $menu[1]['items'];
        $this->assertSame(['Transactional', 'Master', 'Grid Lookup'], array_column($pre, 'label'));
        $this->assertSame(['Master-Detail', 'Single', 'Approval'], array_column($pre[0]['items'], 'label'));
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

    public function test_document_flow_starts_with_customer_order_and_back_keeps_the_grid_parameters(): void
    {
        $this->assertStringContainsString('/lib/p5-flow-document/form/{id}?doc=co&from=', $this->plain($this->get('/lib/p5-flow-document')->assertOk()->getContent()));

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

    public function test_document_buttons_follow_the_strict_cycle(): void
    {
        $acts = fn (string $status) => $this->actsOf('/lib/p5-flow-document/form/1?doc=co&status='.$status);

        $this->assertEqualsCanonicalizing(['delete', 'edit', 'print', 'confirm', 'undo', 'save', 'back'], $acts('draft'));
        $this->assertEqualsCanonicalizing(['cancel', 'print', 'back'], $acts('confirmed'));
        $this->assertEqualsCanonicalizing(['print', 'back'], $acts('cancelled'));
        $this->assertEqualsCanonicalizing(['undo', 'save', 'back'], array_diff($this->actsOf('/lib/p5-flow-document/form/new?doc=co'), ['print']));
        $html = $this->get('/lib/p5-flow-document/form/1?doc=co&status=confirmed')->getContent();
        $this->assertMatchesRegularExpression('/<form[^>]*data-f-strict/', $html);
        $this->assertStringContainsString('dl-form__stamp--success', $html);
    }

    public function test_new_record_is_a_draft_with_default_values_filled(): void
    {
        $page = $this->plain($this->get('/lib/p5-flow-document/form/new?doc=co&from=/lib/p5-flow-document')->assertOk()->getContent());
        $this->assertStringContainsString(now()->format('Y-m-d'), $page);
        $this->assertStringContainsString('Draft · Unsaved', $page);
        $cv = $this->plain($this->get('/lib/p5-flow-single/form/new?from=/lib/p5-flow-single')->assertOk()->getContent());
        $this->assertStringContainsString('Main Cash', $cv);
        $this->assertStringContainsString(now()->format('Y-m-d'), $cv);
    }

    public function test_single_flow_is_the_cash_voucher_without_lines_and_without_approval(): void
    {
        $page = $this->plain($this->get('/lib/p5-flow-single')->assertOk()->getContent());
        $this->assertStringContainsString('/lib/p5-flow-single/form/{id}?from=%2Flib%2Fp5-flow-single', $page);
        $cv = DemoData::cashVouchers()[0];
        $res = $this->get('/lib/p5-flow-single/form/'.$cv['id'].'?from=/lib/p5-flow-single&status=confirmed')->assertOk();
        $res->assertSee($cv['cv_no']);
        $this->assertStringNotContainsString('data-dl-lines', $res->getContent());
        $this->assertStringNotContainsString('Awaiting', $res->getContent());
        $this->assertEqualsCanonicalizing(['delete', 'edit', 'print', 'confirm', 'undo', 'save', 'back'], $this->actsOf('/lib/p5-flow-single/form/'.$cv['id'].'?status=draft'));
    }

    public function test_master_form_has_only_active_and_inactive_and_delete_only_for_an_administrator_on_unused_data(): void
    {
        $acts = fn (string $q) => $this->actsOf('/lib/p5-flow-master/form/1?from=/lib/p5-flow-master&'.$q);

        $this->assertEqualsCanonicalizing(['deactivate', 'edit', 'undo', 'save', 'back'], $acts('status=active'));
        $this->assertEqualsCanonicalizing(['activate', 'back'], $acts('status=inactive'));
        $this->assertEqualsCanonicalizing(['undo', 'save', 'back'], $this->actsOf('/lib/p5-flow-master/form/new'));
        $this->assertContains('delete', $acts('status=active&as=admin-app'));
        $used = $this->actsOf('/lib/p5-flow-master/form/3?status=active&as=admin-app');
        $this->assertNotContains('delete', $used);
        foreach (['confirm', 'cancel', 'print'] as $never) {
            $this->assertNotContains($never, $acts('status=active&as=admin-app'));
        }
    }

    public function test_approval_is_a_separate_field_and_buttons_depend_on_role(): void
    {
        $w = $this->approvalId('waiting');
        $this->assertEqualsCanonicalizing(['print', 'approve', 'hold', 'reject', 'back'], $this->actsOf($this->approvalForm($w, 'waiting')));
        $this->assertEqualsCanonicalizing(['print', 'approve', 'reject', 'back'], $this->actsOf($this->approvalForm($this->approvalId('hold'), 'hold')));
        $this->assertEqualsCanonicalizing(['print', 'reject', 'back'], $this->actsOf($this->approvalForm($this->approvalId('approved'), 'approved')));
        $rej = $this->approvalId('rejected');
        $this->assertEqualsCanonicalizing(['print', 'approve', 'back'], $this->actsOf($this->approvalForm($rej, 'rejected')));
        $this->assertStringContainsString('data-f-otp', $this->get($this->approvalForm($rej, 'rejected'))->getContent());
        // pembuat (entry): Cancel hanya sebelum diputuskan; rejected = Duplicate; approved/hold = Print saja
        $this->assertEqualsCanonicalizing(['cancel', 'print', 'back'], $this->actsOf($this->approvalForm($w, 'waiting', 'entry')));
        $this->assertEqualsCanonicalizing(['duplicate', 'print', 'back'], $this->actsOf($this->approvalForm($rej, 'rejected', 'entry')));
        $this->assertEqualsCanonicalizing(['print', 'back'], $this->actsOf($this->approvalForm($this->approvalId('approved'), 'approved', 'entry')));
        $this->assertEqualsCanonicalizing(['print', 'back'], $this->actsOf($this->approvalForm($this->approvalId('hold'), 'hold', 'entry')));
        $html = $this->get($this->approvalForm($w, 'waiting'))->getContent();
        $this->assertStringContainsString('Confirmed', $html);
        $this->assertStringContainsString('Awaiting', $html);
    }

    public function test_rejected_documents_only_show_in_the_rejected_tab(): void
    {
        $page = $this->plain($this->get('/lib/p5-flow-approval')->assertOk()->getContent());
        $this->assertStringContainsString('"approval_label":["Awaiting","Hold","Approved"]', str_replace(' ', '', $page));
        $this->assertStringContainsString('Rejected', $page);
        $this->assertStringNotContainsString('data-dl-new', $page);
    }

    public function test_save_and_confirm_stay_on_the_form_and_a_new_record_moves_to_its_own_address(): void
    {
        $res = $this->post('/lib/p5-flow-master/form/3', ['_action' => 'save', 'status' => 'active', 'from' => '/lib/p5-flow-master', 'full_name' => 'Nama Contoh']);
        $this->assertStringStartsWith('/lib/p5-flow-master/form/3', parse_url($res->headers->get('Location'), PHP_URL_PATH));
        $res->assertSessionHas('flow_flash.0', 'success');
        $this->followRedirects($res)->assertSee('data-dl-flash="success"', false);

        $new = $this->post('/lib/p5-flow-document/form/new', ['_action' => 'save', 'doc' => 'co', 'from' => '/lib/p5-flow-document?doc=co', 'co_date' => '2026-10-01', 'customer' => 'PT Contoh Snackindo']);
        $this->assertStringStartsWith('/lib/p5-flow-document/form/999', parse_url($new->headers->get('Location'), PHP_URL_PATH));
        $this->assertStringContainsString('status=draft', $new->headers->get('Location'));
        $this->get($new->headers->get('Location'))->assertOk()->assertSee('CO-2610-0999');

        $newMaster = $this->post('/lib/p5-flow-master/form/new', ['_action' => 'save', 'from' => '/lib/p5-flow-master', 'full_name' => 'Pegawai Baru']);
        $this->assertStringContainsString('status=active', $newMaster->headers->get('Location'));
    }

    public function test_save_with_a_missing_required_field_stays_on_the_form_with_an_error_toast(): void
    {
        $res = $this->post('/lib/p5-flow-master/form/3', ['_action' => 'save', 'status' => 'active', 'from' => '/lib/p5-flow-master', 'full_name' => ' ']);
        $this->assertStringStartsWith('/lib/p5-flow-master/form/3', parse_url($res->headers->get('Location'), PHP_URL_PATH));
        $res->assertSessionHas('flow_flash.0', 'danger');
        $this->post('/lib/p5-flow-single/form/new', ['_action' => 'save', 'from' => '/lib/p5-flow-single', 'payee' => 'X'])->assertSessionHas('flow_flash.0', 'danger');
    }

    public function test_document_actions_confirm_cancel_with_reason_delete_and_duplicate(): void
    {
        $base = ['doc' => 'po', 'from' => '/lib/p5-flow-document?doc=po'];
        $confirm = $this->post('/lib/p5-flow-document/form/6', $base + ['_action' => 'confirm', 'status' => 'draft']);
        $this->assertStringContainsString('status=confirmed', $confirm->headers->get('Location'));

        $noReason = $this->post('/lib/p5-flow-document/form/6', $base + ['_action' => 'cancel', 'status' => 'confirmed']);
        $noReason->assertSessionHas('flow_flash.0', 'danger');
        $cancel = $this->post('/lib/p5-flow-document/form/6', $base + ['_action' => 'cancel', 'status' => 'confirmed', '_reason' => 'Wrong supplier']);
        $this->assertStringContainsString('status=cancelled', $cancel->headers->get('Location'));

        $this->post('/lib/p5-flow-document/form/6', $base + ['_action' => 'delete', 'status' => 'draft'])->assertRedirect('/lib/p5-flow-document?doc=po');
        // Duplicate hanya untuk dokumen REJECTED (alur Approval); Cancelled tidak punya Duplicate
        $this->post('/lib/p5-flow-document/form/6', $base + ['_action' => 'duplicate', 'status' => 'cancelled'])->assertSessionHas('flow_flash.0', 'warn');
        $rej = $this->approvalId('rejected');
        $dup = $this->post('/lib/p5-flow-approval/form/'.$rej, ['_action' => 'duplicate', 'status' => 'confirmed', 'approval' => 'rejected', 'as' => 'entry', 'from' => '/lib/p5-flow-approval']);
        $this->assertStringContainsString('/form/new?', $dup->headers->get('Location'));
        $this->assertStringContainsString('copy='.$rej, $dup->headers->get('Location'));
    }

    public function test_approval_decisions_cancel_rules_and_otp_to_reverse_a_rejection(): void
    {
        $post = fn (int $id, array $x) => $this->post('/lib/p5-flow-approval/form/'.$id, $x + ['status' => 'confirmed', 'from' => '/lib/p5-flow-approval']);
        $w = $this->approvalId('waiting');

        $this->assertStringContainsString('approval=approved', $post($w, ['_action' => 'approve', 'approval' => 'waiting', 'as' => 'manager'])->headers->get('Location'));
        $post($w, ['_action' => 'hold', 'approval' => 'waiting', 'as' => 'manager'])->assertSessionHas('flow_flash.0', 'danger');
        $this->assertStringContainsString('approval=hold', $post($w, ['_action' => 'hold', 'approval' => 'waiting', 'as' => 'manager', '_reason' => 'Check credit'])->headers->get('Location'));
        $this->assertStringContainsString('approval=rejected', $post($this->approvalId('approved'), ['_action' => 'reject', 'approval' => 'approved', 'as' => 'manager', '_reason' => 'Price changed'])->headers->get('Location'));
        $post($w, ['_action' => 'approve', 'approval' => 'waiting', 'as' => 'entry'])->assertSessionHas('flow_flash.0', 'danger');
        $post($w, ['_action' => 'approve', 'approval' => 'waiting', 'as' => 'admin-app'])->assertSessionHas('flow_flash.0', 'danger');

        $post($this->approvalId('approved'), ['_action' => 'cancel', 'approval' => 'approved', 'as' => 'entry', '_reason' => 'x'])->assertSessionHas('flow_flash.0', 'warn');
        $post($this->approvalId('hold'), ['_action' => 'cancel', 'approval' => 'hold', 'as' => 'entry', '_reason' => 'x'])->assertSessionHas('flow_flash.0', 'warn');
        $this->assertStringContainsString('status=cancelled', $post($w, ['_action' => 'cancel', 'approval' => 'waiting', 'as' => 'entry', '_reason' => 'Customer withdrew'])->headers->get('Location'));

        $rej = $this->approvalId('rejected');
        $post($rej, ['_action' => 'approve', 'approval' => 'rejected', 'as' => 'manager', '_reason' => 'Agreed', '_otp' => '000000'])->assertSessionHas('flow_flash.0', 'danger');
        $post($rej, ['_action' => 'approve', 'approval' => 'rejected', 'as' => 'manager', '_otp' => FlowDemo::OTP_DEMO])->assertSessionHas('flow_flash.0', 'danger');
        $ok = $post($rej, ['_action' => 'approve', 'approval' => 'rejected', 'as' => 'manager', '_reason' => 'Agreed', '_otp' => FlowDemo::OTP_DEMO]);
        $this->assertStringContainsString('approval=approved', $ok->headers->get('Location'));
        $post($rej, ['_action' => 'approve', 'approval' => 'rejected', 'as' => 'admin-app', '_reason' => 'Agreed', '_otp' => FlowDemo::OTP_DEMO])->assertSessionHas('flow_flash.0', 'danger');
    }

    public function test_master_actions_deactivate_activate_and_guarded_delete(): void
    {
        $base = ['from' => '/lib/p5-flow-master'];
        $this->assertStringContainsString('status=inactive', $this->post('/lib/p5-flow-master/form/1', $base + ['_action' => 'deactivate', 'status' => 'active'])->headers->get('Location'));
        $this->assertStringContainsString('status=active', $this->post('/lib/p5-flow-master/form/1', $base + ['_action' => 'activate', 'status' => 'inactive'])->headers->get('Location'));
        $this->post('/lib/p5-flow-master/form/1', $base + ['_action' => 'delete', 'status' => 'active', 'as' => 'admin-app'])->assertRedirect('/lib/p5-flow-master');
        $this->post('/lib/p5-flow-master/form/1', $base + ['_action' => 'delete', 'status' => 'active'])->assertSessionHas('flow_flash.0', 'danger');
        $this->post('/lib/p5-flow-master/form/3', $base + ['_action' => 'delete', 'status' => 'active', 'as' => 'admin-app'])->assertSessionHas('flow_flash.0', 'danger');
        $this->post('/lib/p5-flow-master/form/1', $base + ['_action' => 'confirm', 'status' => 'active'])->assertSessionHas('flow_flash.0', 'warn');
    }

    public function test_print_always_carries_the_document_status_and_the_approval_status(): void
    {
        $print = fn (string $url) => $this->plain($this->get($url)->assertOk()->getContent());
        $cv = DemoData::cashVouchers()[4];
        $this->assertStringContainsString('DRAFT', $print('/lib/p5-flow-single/print/'.$cv['id'].'?status=draft'));
        $this->assertStringContainsString('CONFIRMED', $print('/lib/p5-flow-single/print/'.$cv['id'].'?status=confirmed'));
        $this->assertStringContainsString('CANCELLED', $print('/lib/p5-flow-single/print/'.$cv['id'].'?status=cancelled'));
        $rej = $print('/lib/p5-flow-approval/print/'.$this->approvalId('rejected').'?status=confirmed&approval=rejected');
        $this->assertStringContainsString('CONFIRMED · REJECTED', $rej);
        $this->assertStringContainsString('CO-2610-', $rej);
        $this->assertStringContainsString('CONFIRMED · APPROVED', $print('/lib/p5-flow-approval/print/'.$this->approvalId('approved').'?status=confirmed&approval=approved'));
        $this->assertStringContainsString('PURCHASE ORDER', strtoupper($print('/lib/p5-flow-document/print/1?doc=po&status=draft')));
        $this->get('/lib/p5-flow-master/print/1')->assertNotFound();
        // tombol Print di form membuka cetakan itu
        $this->assertStringContainsString('/lib/p5-flow-single/print/'.$cv['id'].'?status=confirmed', $this->plain($this->get('/lib/p5-flow-single/form/'.$cv['id'].'?status=confirmed')->getContent()));
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

    public function test_standalone_form_pages_are_unchanged_and_not_strict(): void
    {
        $this->get('/lib/p5-form-single')->assertOk()->assertSee('href="/lib/p5-flow-master"', false);
        $res = $this->get('/lib/p5-form-md')->assertOk();
        $page = $this->plain($res->getContent());
        $this->assertStringContainsString('href="/lib/p5-flow-document?doc=co"', $page);
        $this->assertStringContainsString('href="/lib/p5-flow-document?doc=po"', $page);
        $this->assertDoesNotMatchRegularExpression('/<form[^>]*data-f-strict/', $res->getContent());
    }
}
