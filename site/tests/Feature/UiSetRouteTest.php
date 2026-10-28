<?php

namespace Tests\Feature;

use Devlib\Device;
use Tests\TestCase;

// Rute ui.set (POST /ui): pilihan tampilan disimpan di cookie `ui` terenkripsi; nilai tidak sah ditolak lewat validasi.
class UiSetRouteTest extends TestCase
{
    public function test_pilihan_tersimpan_di_cookie_ui(): void
    {
        foreach (['desktop', 'phone'] as $v) {
            $r = $this->post('/ui', ['ui' => $v])->assertRedirect(route('home'));
            $this->assertSame($v, $r->getCookie(Device::COOKIE)?->getValue());
        }
    }

    public function test_nilai_tidak_sah_ditolak_tanpa_cookie(): void
    {
        foreach (['', 'tablet', 'hp;x'] as $v) {
            $r = $this->from('/login')->post('/ui', ['ui' => $v])->assertRedirect('/login')->assertSessionHasErrors('ui');
            $this->assertNull($r->getCookie(Device::COOKIE));
        }
    }
}
