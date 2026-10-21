<?php

namespace Tests\Feature;

use App\Support\Catalog;
use App\Support\NestedDemo;
use Tests\TestCase;

// Nested Grid (skills/devlib/grid.md): grid ke grid saja, tanpa form; tingkat tidak dibatasi; jejak dibawa di ?t= sejak tingkat pertama.
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

    public function test_level_one_links_carry_the_trail_from_the_first_level_and_never_open_a_form(): void
    {
        $page = $this->plain($this->get('/lib/p5-grid-nested')->assertOk()->getContent());
        $this->assertStringContainsString('/lib/p5-grid-nested/order/{id}?t=home%3Aorders', $page);
        $this->assertStringContainsString('/lib/p5-grid-nested/customer/{customer_slug}?t=home%3Aorders', $page);
        $this->assertStringContainsString('/lib/p5-grid-nested/po/{id}?t=home%3Apurchases', $page);   // contoh kedua, halaman yang sama
        $this->assertStringContainsString('/lib/p5-grid-nested/supplier/{supplier_slug}?t=home%3Apurchases', $page);
        $this->assertStringContainsString('dl-grid__link--ref', file_get_contents(base_path('../resources/assets/devlib.css')));
        $this->assertStringNotContainsString('/form/', $page);
    }

    public function test_customer_level_lists_only_that_customers_orders_and_links_on(): void
    {
        $o = NestedDemo::orders()[0];
        $res = $this->get(NestedDemo::url('customer', $o['customer_slug'], NestedDemo::home('orders')))->assertOk();
        $page = $this->plain($res->getContent());
        $mine = array_values(array_filter(NestedDemo::orders(), fn ($x) => $x['customer_slug'] === $o['customer_slug']));
        $others = array_values(array_filter(NestedDemo::orders(), fn ($x) => $x['customer_slug'] !== $o['customer_slug']));
        $this->assertStringContainsString($mine[0]['co_no'], $page);
        $this->assertStringNotContainsString($others[0]['co_no'], $page);
        $this->assertStringContainsString('Customer: '.$o['customer'], $page);
        $this->assertStringContainsString(count($mine).' orders', $page);
        $this->assertStringContainsString('/lib/p5-grid-nested/order/{id}?t=home%3Aorders%2Ccustomer%3A'.$o['customer_slug'], $page);
        $res->assertSee('href="/lib/p5-grid-nested#grid-nested"', false);   // Back = daftar awal
        $res->assertSee('>Customer Orders<', false);                         // jejak di breadcrumb sejak tingkat pertama
    }

    public function test_levels_can_go_on_and_on_and_the_trail_and_back_follow(): void
    {
        $o = NestedDemo::orders()[0];
        $c = $o['customer_slug'];
        $line = $o['lines'][0];
        $t1 = 'home:orders,customer:'.$c;
        $t2 = $t1.',order:'.$o['id'];
        $t3 = $t2.',product:'.$line['product'];
        $order = $this->plain($this->get('/lib/p5-grid-nested/order/'.$o['id'].'?t='.rawurlencode($t1))->assertOk()->getContent());
        $this->assertStringContainsString($line['product_label'], $order);
        $this->assertStringContainsString('/lib/p5-grid-nested/product/{product}?t='.rawurlencode($t2), $order);
        $this->assertStringContainsString('href="/lib/p5-grid-nested/customer/'.$c.'?t=home%3Aorders"', $order);
        $product = $this->plain($this->get('/lib/p5-grid-nested/product/'.rawurlencode($line['product']).'?t='.rawurlencode($t2))->assertOk()->getContent());
        $this->assertStringContainsString('/lib/p5-grid-nested/customer/{customer_slug}?t='.rawurlencode($t3), $product);
        $this->assertStringContainsString('href="/lib/p5-grid-nested/order/'.$o['id'].'?t='.rawurlencode($t1).'"', $product);
        $this->assertSame(NestedDemo::url('order', (string) $o['id'], NestedDemo::parse($t1)), NestedDemo::level('product', $line['product'], $t2)['back']);
        $this->assertSame(NestedDemo::root().'#grid-nested', NestedDemo::level('customer', $c, 'home:orders')['back']);   // Back dari tingkat dua = daftar awal
        $this->assertSame(NestedDemo::root(), NestedDemo::level('customer', $c, null)['back']);                            // alamat tanpa jejak: tetap ke daftar awal
    }

    public function test_unknown_levels_are_404_and_a_tampered_trail_is_normalised_without_error(): void
    {
        $this->get('/lib/p5-grid-nested/customer/no-such-customer')->assertNotFound();
        $this->get('/lib/p5-grid-nested/order/99999')->assertNotFound();
        $this->get('/lib/p5-grid-nested/form/1')->assertNotFound();
        $o = NestedDemo::orders()[0];
        $plain = NestedDemo::url('order', (string) $o['id']);
        $this->get('/lib/p5-grid-nested/order/'.$o['id'].'?t='.rawurlencode('customer:evil,javascript:alert(1),order:zzz'))->assertRedirect($plain);   // segmen rusak dibuang
        $this->get('/lib/p5-grid-nested/order/'.$o['id'].'?t[]=x')->assertRedirect($plain);                                                              // t bukan teks
        $this->get($plain)->assertOk();
        $this->assertSame([], NestedDemo::parse('customer:evil,javascript:alert(1),order:zzz'));
        $this->assertSame([['home', 'orders']], NestedDemo::parse('home:orders,home:purchases,home:nope'));   // home hanya di awal
        $this->assertSame([], NestedDemo::parse(str_repeat(',', 5000)));
    }

    public function test_the_second_example_nests_supplier_po_item_with_other_data(): void
    {
        $p = NestedDemo::purchases()[0];
        $l = $p['lines'][0];
        $h = 'home:purchases';
        $sup = $this->plain($this->get(NestedDemo::url('supplier', $p['supplier_slug'], NestedDemo::parse($h)))->assertOk()->getContent());
        $this->assertStringContainsString('Supplier: '.$p['supplier'], $sup);
        $this->assertStringContainsString('/lib/p5-grid-nested/po/{id}?t='.rawurlencode($h.',supplier:'.$p['supplier_slug']), $sup);
        $po = $this->plain($this->get('/lib/p5-grid-nested/po/'.$p['id'].'?t='.rawurlencode($h))->assertOk()->getContent());
        $this->assertStringContainsString($l['item_label'], $po);
        $this->assertStringContainsString('/lib/p5-grid-nested/item/{item}?t='.rawurlencode($h.',po:'.$p['id']), $po);
        $item = $this->plain($this->get('/lib/p5-grid-nested/item/'.rawurlencode($l['item']).'?t='.rawurlencode($h.',po:'.$p['id']))->assertOk()->getContent());
        $this->assertStringContainsString('Item: '.$l['item_label'], $item);
        $this->assertStringContainsString($p['po_no'], $item);
        $this->assertStringContainsString('/lib/p5-grid-nested/supplier/{supplier_slug}?t=', $item);
        $this->assertStringNotContainsString('/form/', $item);
        $this->get('/lib/p5-grid-nested/po/99999')->assertNotFound();
        $this->assertStringContainsString('id="grid-nested-purchase"', $this->get('/lib/p5-grid-nested')->getContent());
    }

    public function test_a_level_met_again_cuts_the_trail_and_a_long_trail_shortens_its_breadcrumb(): void
    {
        $p = NestedDemo::purchases()[0];
        $item = $p['lines'][0]['item'];
        $long = 'home:purchases,po:'.$p['id'].',item:'.$item;
        $loop = NestedDemo::level('po', (string) $p['id'], $long);
        $this->assertSame([['home', 'purchases']], $loop['trail']);   // tingkat yang sama berulang = kembali ke sana
        $this->assertSame(NestedDemo::root().'#grid-nested-purchase', $loop['back']);
        $this->assertNull($loop['start']);
        $this->get('/lib/p5-grid-nested/po/'.$p['id'].'?t='.rawurlencode($long))->assertRedirect($loop['self']);

        $orders = NestedDemo::orders();
        $t = 'home:orders,customer:'.$orders[0]['customer_slug'].',order:1,product:'.$orders[0]['lines'][0]['product'].',customer:'.$orders[1]['customer_slug'].',order:2';
        $prod = $orders[1]['lines'][0]['product'];
        $last = NestedDemo::level('product', $prod, $t);
        $this->assertCount(6, $last['trail']);
        $this->assertCount(5, $last['crumbs']);                  // pertama, "…", tiga terakhir
        $this->assertSame('Customer Orders', array_key_first($last['crumbs']));
        $this->assertArrayHasKey('…', $last['crumbs']);
        $this->assertSame(NestedDemo::root().'#grid-nested', $last['start']);
        $res = $this->get(NestedDemo::url('product', $prod, NestedDemo::parse($t)))->assertOk();
        $res->assertSee('data-dl-key="alt+arrowup"', false);     // pintasan Back
        $res->assertSee('Start', false);
        $this->assertNull(NestedDemo::level('product', $prod, 'home:orders,order:1')['start']);   // jejak pendek: Back sudah cukup
    }

    public function test_the_grid_keeps_its_state_when_a_link_cell_is_followed(): void
    {
        $js = file_get_contents(base_path('../resources/assets/js/components/grid.js'));
        $this->assertStringContainsString("if (gl && gl.getAttribute('href') !== '#') this.keep();", $js);   // keep() hanya bekerja pada grid restore
        $this->assertStringContainsString('colf: this.st.colf', $js);
        $this->assertStringNotContainsString('mode="report" :rows', file_get_contents(resource_path('views/demos/grid-nested-level.blade.php')));   // setiap tingkat memakai restore
    }
}
