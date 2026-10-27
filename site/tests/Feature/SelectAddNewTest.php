<?php

namespace Tests\Feature;

use Devlib\Components\Field\Field;
use Devlib\DevlibServiceProvider;
use Devlib\Lang;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

// x-dl::field.select :add (tombol plus + popup tambah): opt-in, tanpa prop add markup lama tidak berubah. Logika JS diuji di browser.
class SelectAddNewTest extends TestCase
{
    private const ADD = "['url' => '/demo/select-add/supplier', 'title' => 'Add Supplier', 'icon' => 'fa-truck', 'note' => 'Hati-hati', 'fields' => [['name' => 'name', 'label' => 'Nama', 'required' => true, 'maxlength' => 60], ['name' => 'phone', 'label' => 'Telepon', 'type' => 'tel']]]";

    private function select(string $add = '', string $attrs = ''): string
    {
        return Blade::render('<x-dl::field.select name="supplier_id" label="Supplier" :options="[\'S1\' => \'Satu\', \'S2\' => \'Dua\']" value="S2" '.$attrs.($add !== '' ? ' :add="'.$add.'"' : '').' />');
    }

    // Uid acak (dlf-<nama>-xxxxx) dinormalkan supaya dua render bisa dibandingkan.
    private function norm(string $html): string
    {
        return (string) preg_replace('/dlf-([a-z0-9_]+)-[A-Za-z0-9]{5}/', 'dlf-$1-X', $html);
    }

    private function squash(string $s): string
    {
        return (string) preg_replace('/\s+/', ' ', trim($s));
    }

    public function test_without_add_the_markup_is_exactly_the_old_select(): void
    {
        config(['devlib.lang' => 'en']);
        $html = $this->norm($this->select());
        foreach (['select-add', 'selectadd', 'modal', 'sa-btn'] as $x) {
            $this->assertStringNotContainsString($x, $html, $x);
        }
        $expected = <<<'HTML'
<div class="dl-dd" data-dl-select data-empty="Choose...">
    <input type="hidden" name="supplier_id" value="S2" data-dl-value>
    <button type="button" id="dlf-supplier_id-X" class="dl-dd__btn" data-dl-dd-toggle >
        <span data-dl-dd-text>Dua</span><span>▼</span>
    </button>
    <div class="dl-dd__panel" data-dl-dd-panel>
        <div class="dl-dd__search"><input type="text" placeholder="Type to search..." data-dl-dd-search></div>
        <div class="dl-dd__list" data-dl-dd-list>
            <div class="dl-dd__item" data-value="S1">Satu</div>
            <div class="dl-dd__item" data-value="S2">Dua</div>
        </div>
    </div>
</div>
HTML;
        $this->assertStringContainsString($this->squash($expected), $this->squash($html));
        $this->assertSame($html, $this->norm($this->select('[]')), 'add kosong = fitur mati');
    }

    public function test_with_add_it_renders_button_data_attributes_and_modal(): void
    {
        config(['devlib.lang' => 'en']);
        $html = $this->select(self::ADD);
        $this->assertStringContainsString('data-dl-select-add data-url="/demo/select-add/supplier" data-value-key="id" data-label-key="name"', $html);
        $this->assertStringContainsString('data-dl-sa-btn aria-haspopup="dialog" aria-label="Add New" title="Add New"', $html);
        $this->assertStringContainsString('fa-plus', $html);
        $this->assertMatchesRegularExpression('/data-dl-modal-id="dlf-supplier_id-[A-Za-z0-9]{5}-add"/', $html);
        $this->assertStringContainsString('dl-modal--sm', $html);
        $this->assertStringContainsString('Add Supplier', $html);
        $this->assertStringContainsString('fa-truck', $html);
        $this->assertStringContainsString('name="name"', $html);
        $this->assertStringContainsString('name="phone"', $html);
        $this->assertStringContainsString('type="tel"', $html);
        $this->assertStringContainsString('maxlength="60"', $html);
        $this->assertStringContainsString('data-dl-sa-err', $html);
        $this->assertStringContainsString('data-dl-sa-save', $html);
        $this->assertStringContainsString('Hati-hati', $html);
    }

    public function test_custom_keys_label_and_textarea_field(): void
    {
        $html = $this->select("['url' => '/x', 'label' => 'New supplier', 'value-key' => 'code', 'label-key' => 'title', 'fields' => [['name' => 'address', 'label' => 'Alamat', 'type' => 'textarea']]]");
        $this->assertStringContainsString('data-value-key="code" data-label-key="title"', $html);
        $this->assertStringContainsString('aria-label="New supplier"', $html);
        $this->assertStringContainsString('<textarea', $html);
    }

    public function test_radio_and_select_fields_render_inside_the_popup_and_fall_back_without_options(): void
    {
        config(['devlib.lang' => 'en']);
        $html = $this->select("['url' => '/x', 'fields' => [['name' => 'name', 'label' => 'Nama'], ['name' => 'kind', 'label' => 'Jenis', 'type' => 'radio', 'required' => true, 'options' => ['A' => 'Lokal', 'B' => 'Impor'], 'value' => 'B'], ['name' => 'lvl', 'label' => 'Tingkat', 'type' => 'select', 'options' => ['1' => 'Satu', '2' => 'Dua'], 'value' => '2'], ['name' => 'bad', 'type' => 'radio']]]");
        $this->assertMatchesRegularExpression('/<input type="radio" name="kind" value="A"\s*>/', $html);
        $this->assertMatchesRegularExpression('/<input type="radio" name="kind" value="B" checked/', $html);
        $this->assertStringContainsString('Impor', $html);
        $this->assertStringContainsString('name="lvl" value="2" data-dl-value', $html);
        $this->assertStringContainsString('<input id="', $html);
        $this->assertStringContainsString('name="bad" type="text"', $html, 'radio tanpa options = teks');
        $old = $this->select(self::ADD);
        $this->assertStringNotContainsString('type="radio"', $old, 'tanpa tipe baru markup lama tidak berubah');
    }

    public function test_script_handles_radio_defaults(): void
    {
        $js = file_get_contents(dirname(__DIR__, 3).'/resources/assets/js/components/select-add.js');
        foreach (["i.type === 'radio'", 'defaultChecked', 'Devlib.h.setField(this.modal', 'this.el.dataset.url'] as $needle) {
            $this->assertStringContainsString($needle, $js, $needle);
        }
    }

    public function test_caller_text_is_escaped(): void
    {
        $html = $this->select("['url' => '/x', 'title' => '<i>T</i>', 'label' => '<b>L</b>', 'note' => '<u>N</u>', 'fields' => [['name' => 'name', 'label' => '<s>F</s>']]]");
        foreach (['<i>T</i>', '<b>L</b>', '<u>N</u>', '<s>F</s>'] as $raw) {
            $this->assertStringNotContainsString($raw, $html, $raw);
        }
        $this->assertStringContainsString('&lt;b&gt;L&lt;/b&gt;', $html);
    }

    public function test_readonly_disabled_and_locked_screens_hide_the_button(): void
    {
        foreach (['readonly', 'disabled'] as $a) {
            $html = $this->select(self::ADD, $a);
            $this->assertStringNotContainsString('data-dl-sa-btn', $html, $a);
            $this->assertStringNotContainsString('data-dl-modal-id', $html, $a);
        }
        Field::$readonlyAll = true;
        try {
            $this->assertStringNotContainsString('data-dl-sa-btn', $this->select(self::ADD));
        } finally {
            Field::$readonlyAll = false;
        }
    }

    public function test_only_same_origin_urls_and_valid_fields_enable_the_feature(): void
    {
        $f = "'fields' => [['name' => 'name']]";
        foreach (["'https://evil.example/x'", "'//evil.example/x'", "'/\\\\evil.example'", "'javascript:alert(1)'", "''"] as $url) {
            $this->assertStringNotContainsString('data-dl-sa-btn', $this->select("['url' => $url, $f]"), $url);
        }
        $this->assertStringNotContainsString('data-dl-sa-btn', $this->select("['url' => '/x']"), 'tanpa fields');
        $this->assertStringNotContainsString('data-dl-sa-btn', $this->select("['url' => '/x', 'fields' => [['name' => 'bad name!']]]"), 'nama isian tidak sah');
        $this->assertStringContainsString('data-dl-sa-btn', $this->select("['url' => 'http://".request()->getHost()."/x', $f]"), 'satu host');
        $this->assertStringContainsString('data-dl-sa-btn', $this->select("['url' => '/x?y=1', $f]"));
    }

    public function test_two_selects_get_distinct_modal_ids(): void
    {
        $html = $this->select(self::ADD).$this->select(self::ADD);
        preg_match_all('/data-dl-modal-id="([^"]+)"/', $html, $m);
        $this->assertCount(2, $m[1]);
        $this->assertCount(2, array_unique($m[1]));
    }

    public function test_texts_follow_the_display_language(): void
    {
        foreach (['id' => ['Tambah baru', 'Menyimpan...', 'Tidak bisa menyimpan. Coba lagi.'], 'en' => ['Add New', 'Saving...', 'Could not save. Try again.']] as $lang => $words) {
            config(['devlib.lang' => $lang]);
            $this->assertStringContainsString('aria-label="'.$words[0].'"', $this->select(self::ADD), $lang);
            $this->assertSame($words[1], Lang::t('sa_saving'), $lang);
            $this->assertSame($words[2], Lang::t('sa_failed'), $lang);
        }
        foreach (['sa_add', 'sa_saving', 'sa_failed'] as $k) {
            $this->assertCount(2, Lang::all()[$k], $k);
        }
    }

    public function test_script_and_style_are_published(): void
    {
        $this->assertStringContainsString('Devlib.define("SelectAdd"', $this->get(DevlibServiceProvider::asset('devlib.js'))->getContent());
        $this->assertStringContainsString('.dl-selectadd__btn', file_get_contents(dirname(base_path()).'/resources/assets/devlib.css'));
    }
}
