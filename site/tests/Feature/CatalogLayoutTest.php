<?php

namespace Tests\Feature;

use App\Support\Catalog;
use App\Support\RulesDocs;
use Tests\TestCase;

// Struktur menu 5 bagian (Foundations & Rules, Components, Screen, Flow, Engine): semua halaman punya tempat, slug lama utuh, dokumen Aturan tidak jadi objek QC.
class CatalogLayoutTest extends TestCase
{
    public function test_every_page_is_placed_and_sections_are_in_order(): void
    {
        $groups = Catalog::groups();
        $this->assertNotContains('Unplaced', array_column($groups, 'section'), 'halaman belum ditempatkan di Catalog::layout()');
        $this->assertSame(['Foundations & Rules', 'Components', 'Screen', 'Flow', 'Engine'], array_values(array_unique(array_column($groups, 'section'))));
    }

    public function test_page_slugs_are_unique_and_old_approved_slugs_still_exist(): void
    {
        $slugs = array_merge([], ...array_map(fn ($g) => array_keys($g['pages']), Catalog::groups()));
        $this->assertSame($slugs, array_values(array_unique($slugs)), 'slug ganda');
        foreach (['p5-form-single', 'p5-grid-report', 'p7-layer', 'shell', 'login-1', 'p5-print', 'basic-colors'] as $slug) {
            $this->assertContains($slug, $slugs, $slug);
        }
    }

    public function test_no_new_page_is_approved_and_rules_pages_are_not_counted_as_objects(): void
    {
        foreach (array_merge(array_keys(RulesDocs::pages()), ['p10-system-flow', 'p10-data-flow', 'p10-work-flow']) as $slug) {
            $this->assertArrayNotHasKey($slug, Catalog::APPROVED);
        }
        $this->assertSame(0, count(array_intersect(array_keys(RulesDocs::pages()), array_keys(Catalog::APPROVED))));
        $objects = array_merge([], ...array_map(fn ($g) => array_keys(array_filter($g['pages'], fn ($p) => ! isset($p['doc']))), Catalog::groups()));
        $this->assertSame(count($objects), Catalog::stats()['pages']);
    }

    public function test_modules_carry_the_section_as_root_caption(): void
    {
        $modules = Catalog::modules();
        $sections = array_values(array_unique(array_filter(array_column($modules, 'section'))));
        $this->assertSame(['Foundations & Rules', 'Components', 'Screen', 'Flow', 'Engine', 'Tools'], $sections);
        $rules = collect($modules)->firstWhere('label', 'Rules');
        $this->assertSame(['Develop & Coding', 'Screens', 'Models', 'Reference'], array_column($rules['children'], 'label'));
        $doc = collect($rules['children'][0]['items'])->firstWhere('url', route('lib', ['slug' => 'rules-object']));
        $this->assertArrayNotHasKey('badge', $doc, 'halaman dokumen tanpa ikon status QC');
    }

    public function test_rules_page_shows_the_document_itself(): void
    {
        $res = $this->get('/lib/rules-object');
        $res->assertOk()->assertSee('Aturan membangun object', false)->assertSee('data-dl-rules="object"', false);
        $this->get('/lib/p10-work-flow')->assertOk()->assertSee('Transactional document life cycle', false);
    }
}
