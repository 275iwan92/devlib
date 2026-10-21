<?php

namespace Tests\Feature;

use App\Support\Catalog;
use App\Support\NestedDemo;
use Tests\TestCase;

// Nested Grid (skills/devlib/grid.md): grid ke grid saja, tanpa form; tingkat tidak dibatasi; jejak dibawa di ?t=.
class NestedGridTest extends TestCase
{
    private function plain(string $html): string
    {
        return str_replace('\\/', '/', html_entity_decode($html));
    }

    public function test_the_page_lives_in_grid_report_and_is_approved(): void
    {
        $this->assertSame('Grid Report', Catalog::page('p5-grid-nested')['folder']);
        $this->assertArrayHasKey('p5-grid-nested', Catalog::APPROVED);   // disetujui Jo (overall) 2026-10-21
        $menu = collect(Catalog::modules())->firstWhere('label', 'Grid and Form')['children'];
        $this->assertContains('Nested Grid', array_column($menu[0]['items'], 'label'));
        $this->assertNull(Catalog::page('p5-grid-variant'));
    }

    public function test_level_one_links_open_the_order_items_and_the_customer_orders_without_a_form(): void
    {
        $page = $this->plain($this->get('/lib/p5-grid-nested')->assertOk()->getContent());
        $this->assertStringContainsString('/lib/p5-grid-nested/order/{id}', $page);
        $this->assertStringContainsString('/lib/p5-grid-nested/customer/{customer_slug}', $page);
        $this->assertStringContainsString('dl-grid__link--ref', file_get_contents(base_path('../resources/assets/devlib.css')));
        $this->assertStringNotContainsString('/form/', $page);
    }

    public function test_customer_level_lists_only_that_customers_orders_and_links_on(): void
    {
        $o = NestedDemo::orders()[0];
        $res = $this->get(NestedDemo::url('customer', $o['customer_slug']))->assertOk();
        $page = $this->plain($res->getContent());
        $mine = array_values(array_filter(NestedDemo::orders(), fn ($x) => $x['customer_slug'] === $o['customer_slug']));
        $others = array_values(array_filter(NestedDemo::orders(), fn ($x) => $x['customer_slug'] !== $o['customer_slug']));
        $this->assertStringContainsString($mine[0]['co_no'], $page);
        $this->assertStringNotContainsString($others[0]['co_no'], $page);
        $this->assertStringContainsString('Customer: '.$o['customer'], $page);
        $this->assertStringContainsString(count($mine).' orders', $page);
        $this->assertStringContainsString('/lib/p5-grid-nested/order/{id}?t=customer%3A'.$o['customer_slug'], $page);
        $res->assertSee('href="/lib/p5-grid-nested"', false);
    }

    public function test_levels_can_go_on_and_on_and_the_trail_and_back_follow(): void
    {
        $o = NestedDemo::orders()[0];
        $c = $o['customer_slug'];
        $line = $o['lines'][0];
        $t1 = 'customer:'.$c;
        $t2 = $t1.',order:'.$o['id'];
        $t3 = $t2.',product:'.$line['product'];
        $order = $this->plain($this->get('/lib/p5-grid-nested/order/'.$o['id'].'?t='.rawurlencode($t1))->assertOk()->getContent());
        $this->assertStringContainsString($line['product_label'], $order);
        $this->assertStringContainsString('/lib/p5-grid-nested/product/{product}?t='.rawurlencode($t2), $order);
        $this->assertStringContainsString('href="/lib/p5-grid-nested/customer/'.$c.'"', $order);
        $product = $this->plain($this->get('/lib/p5-grid-nested/product/'.rawurlencode($line['product']).'?t='.rawurlencode($t2))->assertOk()->getContent());
        $this->assertStringContainsString('/lib/p5-grid-nested/customer/{customer_slug}?t='.rawurlencode($t3), $product);
        $this->assertStringContainsString('href="/lib/p5-grid-nested/order/'.$o['id'].'?t='.rawurlencode($t1).'"', $product);
        $back = $this->get('/lib/p5-grid-nested/customer/'.$c)->getContent();
        $this->assertStringContainsString('href="/lib/p5-grid-nested"', $back);
        $this->assertSame(NestedDemo::url('order', (string) $o['id'], [['customer', $c]]), NestedDemo::level('product', $line['product'], $t2)['back']);
        $this->assertSame(NestedDemo::root(), NestedDemo::level('customer', $c, null)['back']);
    }

    public function test_unknown_levels_are_404_and_a_tampered_trail_is_ignored(): void
    {
        $this->get('/lib/p5-grid-nested/customer/no-such-customer')->assertNotFound();
        $this->get('/lib/p5-grid-nested/order/99999')->assertNotFound();
        $this->get('/lib/p5-grid-nested/form/1')->assertNotFound();
        $o = NestedDemo::orders()[0];
        $res = $this->get('/lib/p5-grid-nested/order/'.$o['id'].'?t='.rawurlencode('customer:evil,javascript:alert(1),order:zzz'))->assertOk();
        $this->assertStringNotContainsString('javascript:alert', $res->getContent());
        $this->assertSame([], NestedDemo::parse('customer:evil,javascript:alert(1),order:zzz'));
    }
}
