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

    public function test_page_is_approved(): void
    {
        $this->assertArrayHasKey('p5-override', \App\Support\Catalog::APPROVED);   // disetujui Jo 2026-10-21
    }

    // ---- Tiga object (Jo 2026-10-21): Grid, Form, Kanban, enam bagian yang sama; ?object= memilih, tanpa parameter = Kanban ----

    /** @return array<string, array{string}> */
    public static function objects(): array
    {
        return ['grid' => ['grid'], 'form' => ['form'], 'kanban' => ['kanban']];
    }

    private function doc(string $key): ObjectDoc
    {
        return ObjectDoc::$key();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('objects')]
    public function test_each_object_property_table_matches_its_constructor_and_notes(string $key): void
    {
        $doc = $this->doc($key);
        $params = array_map(fn ($p) => $p->getName(), (new ReflectionClass($doc->class))->getConstructor()->getParameters());

        $this->assertSame($params, array_column($doc->props(), 'name'));
        $this->assertEqualsCanonicalizing($params, array_keys($doc->notes()['props']), 'catatan properti harus persis sama dengan konstruktor');
        foreach ($doc->props() as $row) {
            $this->assertNotSame('', $row['meaning'], $row['name']);
            $this->assertNotSame('', $row['example'], $row['name']);
            $this->assertNotSame('', $row['child'], $row['name']);
        }
        if ($key !== 'kanban') {   // banyak properti: semuanya masuk satu kelompok
            $this->assertNotContains('', array_column($doc->props(), 'group'));
        }
    }

    public function test_grid_and_form_list_every_constructor_property(): void
    {
        $this->assertCount(28, ObjectDoc::grid()->props());
        $this->assertCount(34, ObjectDoc::form()->props());
        $grid = array_column(ObjectDoc::grid()->props(), null, 'name');
        $form = array_column(ObjectDoc::form()->props(), null, 'name');

        $this->assertSame('"entry"', $grid['mode']['default']);
        $this->assertSame('?string', $grid['openUrl']['type']);
        $this->assertSame('open-url', $grid['openUrl']['attr']);
        $this->assertSame('false', $grid['restore']['default']);
        $this->assertSame('"master"', $form['kind']['default']);
        $this->assertSame('bool', $form['strict']['type']);
        $this->assertSame('can-input', $form['canInput']['attr']);
        // turunan contoh: yang ditimpa dan yang diwarisi dibaca dari objek turunan sungguhan.
        $this->assertSame('overridden: "report"', $grid['mode']['child']);
        $this->assertSame('overridden: "stock-movement"', $grid['id']['child']);
        $this->assertSame('inherited', $grid['restore']['child']);
        $this->assertSame('overridden: "document"', $form['kind']['child']);
        $this->assertSame('overridden: "top"', $form['panel']['child']);
        $this->assertSame('inherited', $form['strict']['child']);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('objects')]
    public function test_each_object_methods_match_the_code_and_every_one_has_a_note(string $key): void
    {
        $doc = $this->doc($key);
        $php = array_column($doc->phpMethods(), null, 'name');
        $js = array_column($doc->jsMethods(), null, 'name');

        $this->assertEqualsCanonicalizing(array_keys($php), array_keys($doc->notes()['php']), 'method PHP dan catatan harus sama');
        $this->assertEqualsCanonicalizing(array_keys($js), array_keys($doc->notes()['js']), 'method JS dan catatan harus sama');
        foreach ([...$php, ...$js] as $name => $row) {
            $this->assertNotSame('', $row['can'], $name);
            $this->assertNotSame('', $row['use'], $name);
            $this->assertContains($row['child'], ['overrides', 'inherits'], $name);
        }
        $this->assertSame('Required', $php['tag']['can']);
        $this->assertSame('overrides', $php['tag']['child']);
        $this->assertSame('overrides', $php['jsClass']['child']);
    }

    public function test_grid_and_form_methods_are_the_real_ones(): void
    {
        $grid = array_column(ObjectDoc::grid()->phpMethods(), null, 'name');
        $gridJs = array_column(ObjectDoc::grid()->jsMethods(), null, 'name');
        $form = array_column(ObjectDoc::form()->phpMethods(), null, 'name');
        $formJs = array_column(ObjectDoc::form()->jsMethods(), null, 'name');

        $this->assertSame('Grid', $grid['config']['from']);
        $this->assertSame('Grid', $grid['translateTexts']['from']);   // Grid menimpa translateTexts milik DevlibComponent
        $this->assertSame('DevlibComponent', $grid['jsClass']['from']);
        $this->assertSame('overrides', $grid['source']['child']);
        $this->assertSame('inherits', $grid['render']['child']);
        $this->assertSame('Form', $form['panelActions']['from']);
        $this->assertSame('DevlibComponent', $form['source']['from']);
        $this->assertSame('inherits', $form['render']['child']);
        $this->assertSame('fmtCell(c, v)', $gridJs['fmtCell']['signature']);
        $this->assertSame('cell(c, r)', $gridJs['cell']['signature']);
        $this->assertSame('rowHtml(r, idx)', $gridJs['rowHtml']['signature']);
        $this->assertSame('act(a)', $formJs['act']['signature']);
        $this->assertSame('validate()', $formJs['validate']['signature']);
        $this->assertSame('Devlib.Component', $gridJs['on']['from']);
        $this->assertSame('overrides', $gridJs['fmtCell']['child']);
        $this->assertSame('inherits', $gridJs['cell']['child']);
        $this->assertSame('overrides', $formJs['act']['child']);
        $this->assertSame('inherits', $formJs['validate']['child']);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('objects')]
    public function test_each_child_overrides_only_methods_that_exist_in_its_parent(string $key): void
    {
        $doc = $this->doc($key);
        $short = (new ReflectionClass($doc->class))->getShortName();
        $childShort = (new ReflectionClass((string) $doc->child))->getShortName();
        $parentJs = ObjectDoc::jsClassMethods((string) file_get_contents(base_path('../'.$doc->jsFile())), $short);
        $childJs = ObjectDoc::jsClassMethods($doc->childJsBody(), $childShort);

        $this->assertNotSame([], $childJs, 'turunan JS harus menimpa minimal satu method');
        foreach (array_keys($childJs) as $m) {
            $this->assertArrayHasKey($m, $parentJs, $childShort.'::'.$m.' tidak ada di '.$short);
        }
        $this->assertStringContainsString('class '.$childShort.' extends Devlib.classes.'.$short, $doc->childJsBody());
        $this->assertStringContainsString('Devlib.register('.$childShort.')', $doc->childJsBody());
        $this->assertTrue((new ReflectionClass((string) $doc->child))->isSubclassOf($doc->class));
        $this->assertSame($childShort, (new ($doc->child)())->jsClass());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('objects')]
    public function test_each_object_has_its_story_header_and_guide_entry(string $key): void
    {
        $doc = $this->doc($key);

        $this->assertSame(['What it is', 'What it is used for', 'When to choose it', 'What it does not do'], array_keys($doc->notes()['story']));
        $this->assertNotSame([], $doc->remark());
        $this->assertSame('Aktif (DevLib).', $doc->remark()['Status']);
        $this->assertNotSame('', $doc->classComment());
        $this->assertContains(Guide::anchor($doc->tag()), array_column(Guide::entries(), 'anchor'));
        foreach (array_keys($doc->notes()['rules'] ?? []) as $slug) {
            $this->get('/lib/'.$slug)->assertOk();
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('objects')]
    public function test_page_shows_the_six_parts_for_each_object(string $key): void
    {
        $doc = $this->doc($key);
        $demo = $key === 'kanban' ? 'oo-override' : 'oo-override-'.$key;
        $html = $this->get('/lib/p5-override?object='.$key)->assertOk()->getContent();

        foreach (['oo-pick', 'oo-story', 'oo-properties', 'oo-methods', 'oo-inherit', $demo, 'oo-rules'] as $id) {
            $this->assertStringContainsString('id="'.$id.'"', $html, $id);
        }
        foreach ($doc->props() as $row) {
            $this->assertStringContainsString('<td class="">'.$row['name'].'</td>', $html, $row['name']);
        }
        foreach ([...$doc->phpMethods(), ...$doc->jsMethods()] as $row) {
            $this->assertStringContainsString(e($row['signature']), $html, $row['signature']);
        }
        $this->assertStringContainsString('What it does not do', $html);
        $this->assertStringContainsString('class '.class_basename((string) $doc->child).' extends Devlib.classes.'.$doc->name(), $html);
        $this->assertStringContainsString('href="'.route('devlib.guide').'#'.Guide::anchor($doc->tag()).'"', $html);
        $this->assertStringContainsString('?object='.$key.'"', $html);   // pemilih menandai object yang dipilih
        $this->assertStringNotContainsString('<x-dl::', $html);
        $this->assertStringNotContainsString('<x-demo.', $html);
    }

    public function test_the_live_demos_render_the_child_objects(): void
    {
        $grid = $this->get('/lib/p5-override?object=grid')->assertOk()->getContent();
        $form = $this->get('/lib/p5-override?object=form')->assertOk()->getContent();

        $this->assertStringContainsString('data-dl-js="GridStockMovement"', $grid);
        $this->assertStringContainsString('data-dl="x-demo.grid-stock-movement"', $grid);
        $this->assertStringContainsString('OUT/26/0034', $grid);
        $this->assertStringContainsString('data-dl-js="FormStockAdjustment"', $form);
        $this->assertStringContainsString('data-dl="x-demo.form-stock-adjustment"', $form);
        $this->assertStringContainsString('name="reason"', $form);
    }

    // Bagian Kanban yang sudah disetujui Jo: tanpa ?object, dengan ?object=kanban, dan dengan nilai tak dikenal isinya SAMA.
    public function test_kanban_part_is_the_default_and_unchanged(): void
    {
        $strip = fn (string $html) => preg_replace('/value="[A-Za-z0-9]{40}"/', 'TOKEN', substr($html, (int) strpos($html, '<div class="dl-cat">'), (int) strrpos($html, '</section>') - (int) strpos($html, '<div class="dl-cat">')));
        $default = $strip($this->get('/lib/p5-override')->assertOk()->getContent());

        $this->assertSame($default, $strip($this->get('/lib/p5-override?object=kanban')->getContent()));
        $this->assertSame($default, $strip($this->get('/lib/p5-override?object=bogus')->getContent()));
        $this->assertSame('kanban', ObjectDoc::key());
        $this->assertStringContainsString('1 · The object: what Kanban is, what it is for, what it does not do', $default);
        $this->assertStringContainsString('5 · Live demo: the KanbanProduksi subclass', $default);
        $this->assertStringContainsString('data-dl-js="KanbanProduksi"', $default);
        $this->assertStringNotContainsString('GridStockMovement', $default);
        $this->assertStringNotContainsString('FormStockAdjustment', $default);
    }

    public function test_no_new_slug_was_approved_for_the_new_examples(): void
    {
        $this->assertArrayNotHasKey('p5-override-grid', \App\Support\Catalog::APPROVED);
        $this->assertNull(\App\Support\Catalog::page('p5-override-grid'));
    }
}
