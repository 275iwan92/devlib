<?php

namespace Tests\Feature;

use Devlib\Components\DevlibComponent;
use Devlib\Manifest;
use ReflectionClass;
use Tests\TestCase;

// Kontrak SEMUA komponen DevLib: tag, source, tampilan (berkas view atau render() sendiri), prop bertipe, jsClass dan manifest yang mutakhir.
class ComponentContractTest extends TestCase
{
    private function concrete(): array
    {
        return array_values(array_filter(Manifest::collect(), fn ($r) => ! $r['abstract']));
    }

    public function test_every_component_meets_the_contract(): void
    {
        $bad = [];
        foreach ($this->concrete() as $r) {
            $ref = new ReflectionClass($r['class']);
            $why = [];
            if (! str_starts_with($r['tag'], 'x-dl::')) {
                $why[] = 'tag bukan x-dl::';
            }
            $render = $ref->getMethod('render')->getDeclaringClass()->getName();
            if (! $r['view'] && ! str_starts_with($render, 'Devlib\\')) {
                $why[] = 'tanpa berkas view dan tanpa render() sendiri';
            }
            foreach ($r['props'] as $p) {
                if ($p['type'] === 'untyped' && ! in_array($p['name'], ['value', 'valueTo', 'valueEnd'], true)) {  // nilai isian boleh skalar/array
                    $why[] = 'prop '.$p['name'].' tanpa tipe';
                }
            }
            if ($ref->getConstructor()?->getNumberOfRequiredParameters()) {
                // Prop wajib sah, tapi pembuatan tanpa argumen tidak bisa diuji: lewati pemeriksaan instans.
                continue;
            }
            $c = $ref->newInstance();
            if (! is_string($c->tag()) || $c->tag() === '') {
                $why[] = 'tag() kosong';
            }
            if (! is_string($c->source())) {
                $why[] = 'source() bukan string';
            }
            $js = $c->jsClass();
            if ($js !== null && ! preg_match('/^[A-Z][A-Za-z0-9]*$/', $js)) {
                $why[] = 'jsClass() bukan nama kelas JS';
            }
            if ($js !== null) {
                $found = false;
                foreach (glob(dirname(__DIR__, 3).'/resources/assets/js/components/*.js') as $f) {
                    $found = $found || str_contains((string) file_get_contents($f), 'class '.$js);
                }
                $found || $why[] = "kelas JS {$js} tidak ada di resources/assets/js/components";
            }
            if ($why) {
                $bad[$r['class']] = implode('; ', $why);
            }
        }

        $this->assertSame([], $bad, 'Komponen yang tidak lulus kontrak');
    }

    public function test_every_component_has_a_purpose_comment(): void
    {
        $missing = array_map(fn ($r) => $r['class'], array_filter($this->concrete(), fn ($r) => $r['purpose'] === ''));
        $this->assertSame([], array_values($missing), 'Komponen tanpa komentar kepala (tujuan)');
    }

    public function test_every_component_has_a_remark_header(): void
    {
        $missing = array_map(fn ($r) => $r['file'], array_filter($this->concrete(), fn ($r) => ! $r['remark']));
        $this->assertSame([], array_values($missing), 'Komponen tanpa header REMARK baku');
    }

    public function test_manifest_file_is_up_to_date(): void
    {
        $this->artisan('devlib:manifest', ['--check' => true])->assertSuccessful();
    }

    public function test_all_components_derive_from_the_base(): void
    {
        foreach (Manifest::collect() as $r) {
            $this->assertTrue(is_subclass_of($r['class'], DevlibComponent::class), $r['class']);
        }
        $this->assertGreaterThan(100, count($this->concrete()));
    }

    public function test_skill_is_modular_and_references_exist(): void
    {
        $dir = dirname(__DIR__, 3).'/skills/devlib';
        $core = (string) file_get_contents($dir.'/SKILL.md');
        $this->assertLessThan(90, substr_count($core, "
"), 'SKILL.md inti harus pendek');
        foreach (['install', 'grid', 'form', 'transaction-lifecycle', 'help', 'components', 'engine', 'inheritance'] as $f) {
            $this->assertFileExists("{$dir}/{$f}.md");
            $this->assertStringContainsString("`{$f}.md`", $core, "SKILL.md harus merujuk {$f}.md");
        }
    }
}
