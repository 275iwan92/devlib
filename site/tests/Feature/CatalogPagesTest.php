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
                $status = $this->get('/lib/'.$slug)->getStatusCode();
                if ($status !== 200) {
                    $failed[$slug] = $status;
                }
            }
        }

        $this->assertSame([], $failed, 'Catalog pages that do not render (slug => status)');
    }

    public function test_site_has_no_leftover_project_packages(): void
    {
        $this->assertDirectoryDoesNotExist(base_path('packages'));
        $this->assertStringNotContainsString('MockupParts', (string) file_get_contents(base_path('bootstrap/providers.php')));
    }
}
