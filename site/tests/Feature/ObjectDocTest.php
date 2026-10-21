<?php

namespace Tests\Feature;

use App\Support\ObjectDoc;
use App\Support\ObjectNotes;
use Devlib\Components\DevlibComponent;
use Devlib\Components\Kanban;
use Devlib\Guide;
use ReflectionClass;
use Tests\TestCase;

// Halaman p5-override (cerita object Kanban): tabel properti dan method harus cocok dengan kode asli; catatan tulisan tangan tidak boleh menyimpang.
class ObjectDocTest extends TestCase
{
    public function test_every_constructor_property_has_a_row_and_a_note_and_nothing_extra(): void
    {
        $params = array_map(fn ($p) => $p->getName(), (new ReflectionClass(Kanban::class))->getConstructor()->getParameters());
        $doc = ObjectDoc::kanban();

        $this->assertSame($params, array_column($doc->props(), 'name'));
        $this->assertEqualsCanonicalizing($params, array_keys(ObjectNotes::for(Kanban::class)['props']), 'catatan properti harus persis sama dengan konstruktor');
        foreach ($doc->props() as $row) {
            $this->assertNotSame('', $row['meaning'], $row['name']);
            $this->assertNotSame('', $row['example'], $row['name']);
        }
    }

    public function test_property_types_defaults_and_overrides_come_from_the_code(): void
    {
        $rows = array_column(ObjectDoc::kanban()->props(), null, 'name');

        $this->assertSame('array', $rows['columns']['type']);
        $this->assertSame('?string', $rows['moveUrl']['type']);
        $this->assertSame('"Me"', $rows['me']['default']);
        $this->assertSame('true', $rows['addColumn']['default']);
        $this->assertSame('add-column', $rows['addColumn']['attr']);
        // KanbanProduksi: kolom, transisi, me, addColumn ditimpa; cards dan readonly diwarisi.
        $this->assertStringStartsWith('overridden', $rows['columns']['child']);
        $this->assertStringStartsWith('overridden', $rows['addColumn']['child']);
        $this->assertSame('inherited', $rows['readonly']['child']);
    }

    public function test_php_methods_match_reflection_and_every_one_has_a_note(): void
    {
        $doc = ObjectDoc::kanban();
        $rows = array_column($doc->phpMethods(), null, 'name');

        $this->assertEqualsCanonicalizing(array_keys($rows), array_keys(ObjectNotes::for(Kanban::class)['php']));
        foreach ($rows as $name => $row) {
            $this->assertNotSame('', $row['can'], $name);
            $this->assertNotSame('', $row['use'], $name);
        }
        // abstract di induk = wajib; method yang ditimpa KanbanProduksi: tag, source, jsClass.
        $this->assertTrue((new \ReflectionMethod(DevlibComponent::class, 'tag'))->isAbstract());
        $this->assertSame('Required', $rows['tag']['can']);
        $this->assertSame('overrides', $rows['jsClass']['child']);
        $this->assertSame('inherits', $rows['render']['child']);
        $this->assertSame('Kanban', $rows['render']['from']);
        $this->assertSame('DevlibComponent', $rows['jsClass']['from']);
    }

    public function test_js_methods_are_read_from_the_js_class_and_every_one_has_a_note(): void
    {
        $doc = ObjectDoc::kanban();
        $rows = array_column($doc->jsMethods(), null, 'name');

        $this->assertEqualsCanonicalizing(array_keys($rows), array_keys(ObjectNotes::for(Kanban::class)['js']), 'method JS dan catatan harus sama');
        foreach (['allowed', 'cardHtml', 'move', 'persist', 'openCard', 'init', 'render'] as $m) {
            $this->assertArrayHasKey($m, $rows);
        }
        $this->assertSame('allowed(from, to)', $rows['allowed']['signature']);
        $this->assertSame('Kanban', $rows['allowed']['from']);
        $this->assertSame('Devlib.Component', $rows['emit']['from']);
        // method yang ditimpa demo JS KanbanProduksi dibaca dari berkasnya.
        $this->assertSame('overrides', $rows['allowed']['child']);
        $this->assertSame('overrides', $rows['cardHtml']['child']);
        $this->assertSame('inherits', $rows['move']['child']);
        foreach ($rows as $name => $row) {
            $this->assertNotSame('', $row['can'], $name);
        }
    }

    public function test_story_header_and_example_code_are_read_from_the_files(): void
    {
        $doc = ObjectDoc::kanban();

        $this->assertStringStartsWith('Papan Kanban', $doc->remark()['Tujuan']);
        $this->assertSame('Aktif (DevLib).', $doc->remark()['Status']);
        $this->assertStringContainsString('columns:', $doc->classComment());
        $this->assertSame('x-dl::kanban', $doc->tag());
        $this->assertSame('resources/assets/js/components/kanban.js', $doc->jsFile());
        $this->assertSame(['What it is', 'What it is used for', 'When to choose it', 'What it does not do'], array_keys($doc->notes()['story']));
        $this->assertStringContainsString('class KanbanProduksi extends Kanban', $doc->childPhpSource());
        $this->assertStringContainsString('class KanbanProduksi extends Devlib.classes.Kanban', $doc->childJsBody());
        $this->assertSame('site/app/View/Components/Demo/KanbanProduksi.php', $doc->childPhpPath());
    }

    public function test_page_shows_the_six_parts_and_the_tables_match_the_class(): void
    {
        $html = $this->get('/lib/p5-override')->assertOk()->getContent();

        foreach (['oo-story', 'oo-properties', 'oo-methods', 'oo-inherit', 'oo-override', 'oo-rules'] as $id) {
            $this->assertStringContainsString('id="'.$id.'"', $html, $id);
        }
        foreach (ObjectDoc::kanban()->props() as $row) {
            $this->assertStringContainsString('<td class="">'.$row['name'].'</td>', $html, $row['name']);
        }
        foreach ([...ObjectDoc::kanban()->phpMethods(), ...ObjectDoc::kanban()->jsMethods()] as $row) {
            $this->assertStringContainsString(e($row['signature']), $html, $row['signature']);
        }
        $this->assertStringContainsString('What it does not do', $html);
        $this->assertStringContainsString('class KanbanProduksi extends Devlib.classes.Kanban', $html);
        $this->assertStringNotContainsString('<x-dl::', $html);
    }

    public function test_links_point_to_the_rules_pages_and_to_a_real_guide_entry(): void
    {
        $html = $this->get('/lib/p5-override')->assertOk()->getContent();
        $anchor = Guide::anchor('x-dl::kanban');

        $this->assertContains($anchor, array_column(Guide::entries(), 'anchor'));
        $this->assertStringContainsString('href="'.route('devlib.guide').'#'.$anchor.'"', $html);
        foreach (['rules-object', 'rules-inheritance'] as $slug) {
            $this->assertStringContainsString('href="'.route('lib', $slug).'"', $html);
            $this->get('/lib/'.$slug)->assertOk();
        }
    }

    public function test_page_is_still_in_review_not_approved(): void
    {
        $this->assertArrayNotHasKey('p5-override', \App\Support\Catalog::APPROVED);
    }
}
