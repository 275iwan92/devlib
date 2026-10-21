<?php

namespace Tests\Feature;

use Devlib\Guide;
use Tests\TestCase;

// Technical Guide: setiap object punya entri (otomatis), dan rincian tulisan tangan wajib lengkap. Object yang belum punya rincian
// tercatat di resources/guide/PENDING.txt; daftar itu hanya boleh menyusut (object baru wajib langsung ditulis rinciannya).
class GuideContractTest extends TestCase
{
    public function test_pending_list_matches_objects_without_a_written_entry(): void
    {
        $pending = Guide::pending();
        $listed = Guide::pendingList();
        $this->assertSame([], array_values(array_diff($pending, $listed)), 'Object baru tanpa resources/guide/<tag>.md: tulis rinciannya (bukan menambah PENDING.txt)');
        $this->assertSame([], array_values(array_diff($listed, $pending)), 'Sudah ditulis atau tidak ada lagi: hapus barisnya dari PENDING.txt');
    }

    public function test_written_entries_are_complete(): void
    {
        $bad = [];
        foreach (Guide::entries() as $e) {
            if ($e['detail'] === null) {
                continue;
            }
            foreach (Guide::SECTIONS as $section) {
                if (! preg_match('/^## '.preg_quote($section, '/').'\s*$/m', $e['detail'])) {
                    $bad[$e['tag']][] = 'bagian "'.$section.'" tidak ada';
                }
            }
            foreach ($e['props'] as $p) {
                $kebab = \Illuminate\Support\Str::kebab($p['name']);
                if (! str_contains($e['detail'], '`'.$kebab.'`') && ! str_contains($e['detail'], '`:'.$kebab.'`')) {
                    $bad[$e['tag']][] = 'prop `'.$kebab.'` tidak dijelaskan';
                }
            }
        }

        $this->assertSame([], $bad, 'Rincian Technical Guide tidak lengkap');
    }

    public function test_guide_page_lists_every_object_and_the_title_script_matches_the_anchor(): void
    {
        $res = $this->get(route('devlib.guide'));
        $res->assertOk();
        foreach (Guide::entries() as $e) {
            $this->assertStringContainsString('id="'.$e['anchor'].'"', $res->getContent(), $e['tag']);
        }
        // JS menghitung anchor dengan rumus yang sama (guide-link.js).
        $this->assertSame('x-dl-field-text', Guide::anchor('x-dl::field.text'));
        $js = (string) file_get_contents(dirname(base_path()).'/resources/assets/js/components/guide-link.js');
        $this->assertStringContainsString("replace(/[^a-z0-9]+/gi, '-').replace(/^-|-\$/g, '')", $js);
    }

    public function test_title_links_are_configured_from_the_site(): void
    {
        $this->assertSame(route('devlib.guide'), Guide::url());
        $page = $this->get('/')->getContent();
        $this->assertStringContainsString('window.DL_GUIDE = "'.str_replace('/', '\/', route('devlib.guide')).'"', $page);
    }
}
