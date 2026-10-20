<?php

namespace Tests\Feature;

use App\Support\Catalog;
use Tests\TestCase;

// Setiap halaman katalog situs harus terbuka (jaring pengaman saat ada demo atau paket yang dihapus / diganti).
class CatalogPagesTest extends TestCase
{
    public function test_every_catalog_page_renders(): void
    {
        $failed = [];
        foreach (Catalog::groups() as $group) {
            foreach (array_keys($group['pages']) as $slug) {
                $res = $this->get('/lib/'.$slug);
                if ($res->getStatusCode() !== 200) {
                    $failed[$slug] = $res->getStatusCode();
                }
                // Tag yang gagal di-compile (mis. kutip ganda di dalam atribut) lolos sebagai HTML mentah dengan status 200.
                // Kode contoh di panel Code memakai &lt;, jadi '<x-dl::' mentah hanya muncul bila tag tidak terkompilasi.
                elseif (str_contains($res->getContent(), '<x-dl::')) {
                    $failed[$slug] = 'tag <x-dl:: mentah';
                }
            }
        }

        $this->assertSame([], $failed, 'Catalog pages that do not render (slug => status)');
    }

    public function test_site_ships_only_the_packages_it_demonstrates(): void
    {
        // assistant dan chat dibuat ulang sebagai model DevLib; Special Forms dipindah ke aplikasi pemakai (2026-10-20), paket
        // mockup-parts tetap ada tetapi tidak ada halaman katalog yang memakai x-mp::* (bundelnya tidak dimuat di layout).
        $this->assertDirectoryDoesNotExist(base_path('packages/assistant'));
        $this->assertDirectoryDoesNotExist(base_path('packages/chat'));
        $this->assertDirectoryExists(base_path('packages/mockup-parts'));
        $this->assertNotContains('Special Forms', array_column(Catalog::groups(), 'label'));
    }
}
