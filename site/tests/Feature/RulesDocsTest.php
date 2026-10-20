<?php

namespace Tests\Feature;

use App\Support\RulesDocs;
use Devlib\Components\LayerShell;
use Tests\TestCase;

// Halaman Aturan = tampilan langsung skills/devlib/*.md: daftar harus utuh (tak ada dokumen terlewat), judul dari dokumen, dan section modul sampai ke Layer Menu.
class RulesDocsTest extends TestCase
{
    public function test_every_skill_document_is_listed_and_every_listed_one_exists(): void
    {
        $files = array_map(fn ($f) => basename($f, '.md'), glob(RulesDocs::dir().'/*.md'));
        $this->assertEqualsCanonicalizing($files, RulesDocs::names(), 'skills/devlib/*.md dan RulesDocs::FOLDERS harus sama');
        foreach (RulesDocs::names() as $name) {
            $this->assertNotNull(RulesDocs::path($name), $name);
        }
    }

    public function test_titles_come_from_the_documents_not_from_a_copy(): void
    {
        foreach (RulesDocs::pages() as $slug => $page) {
            $first = trim(explode("\n", RulesDocs::markdown($page['doc']))[0]);
            $this->assertStringStartsWith('# ', $first, $slug);
            $this->assertStringNotContainsString('DevLib:', $page['label'], $slug);
            $this->assertStringContainsString($page['label'], ucfirst(str_replace('DevLib: ', '', substr($first, 2))), $slug);
        }
        $this->assertSame('Aturan membangun object', RulesDocs::title('object'));
    }

    public function test_markdown_is_rendered_with_raw_html_escaped_and_no_front_matter(): void
    {
        $skill = RulesDocs::html('SKILL');
        $this->assertStringNotContainsString('name: devlib', $skill);
        $this->assertStringContainsString('<h1>', $skill);
        $this->assertStringContainsString('<table>', RulesDocs::html('grid'));
        $this->assertStringNotContainsString('<script', RulesDocs::html('components'));
    }

    public function test_module_section_reaches_the_root_of_the_layer_menu(): void
    {
        $shell = new LayerShell(modules: [
            ['code' => 'A1', 'label' => 'One', 'section' => 'Rules', 'children' => []],
            ['code' => 'A2', 'label' => 'Two', 'children' => []],
        ]);
        $tree = $shell->layerTree();
        $this->assertSame('Rules', $tree[0]['group']);
        $this->assertNull($tree[1]['group']);
    }
}
