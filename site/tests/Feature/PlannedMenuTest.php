<?php

namespace Tests\Feature;

use App\Support\Catalog;
use Tests\TestCase;

// Peta jalan di menu: item belum-dibuat tampil abu-abu (badgeTone planned), halamannya hidup, dan setiap kode grup di Catalog::PLANNED ada di menu.
class PlannedMenuTest extends TestCase
{
    public function test_every_planned_group_exists_and_items_are_marked(): void
    {
        $modules = collect(Catalog::modules())->keyBy('code');
        foreach (Catalog::PLANNED as $code => $items) {
            $this->assertTrue($modules->has($code), "grup {$code} tidak ada di menu");
            foreach ($items as $slug => $d) {
                $item = collect($modules[$code]['children'])->firstWhere('code', 'PL-'.$slug);
                $this->assertNotNull($item, $slug);
                $this->assertSame(isset($d[4]) ? null : 'planned', $item['badgeTone'] ?? null, $slug);
            }
        }
    }

    public function test_planned_page_opens_and_unknown_or_existing_slug_is_404(): void
    {
        $this->get(route('planned', ['slug' => 'geofence']))->assertOk()->assertSee('Geofence (work area)')->assertSee('Planned: belum dibuat');
        $this->get(route('planned', ['slug' => 'tidak-ada']))->assertNotFound();
        $this->get(route('planned', ['slug' => 'lead-time']))->assertNotFound();   // sudah punya halaman (p9-engines)
    }
}
