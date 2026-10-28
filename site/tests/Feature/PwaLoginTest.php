<?php

namespace Tests\Feature;

use Tests\TestCase;

// Login layar PWA React (pwa/): GET /pwa/session memberi token CSRF + bahasa; POST /login membalas JSON bila diminta; form Blade tidak berubah.
class PwaLoginTest extends TestCase
{
    public function test_session_memberi_csrf_dan_bahasa(): void
    {
        $r = $this->getJson('/pwa/session')->assertOk()->assertJsonStructure(['csrf', 'lang']);
        $this->assertNotSame('', $r->json('csrf'));
        $this->assertContains($r->json('lang'), ['id', 'en']);
    }

    public function test_session_memberi_alamat_tautan_yang_ada(): void
    {
        $links = $this->getJson('/pwa/session')->json('links');
        $this->assertSame(['register', 'forgot', 'device'], array_keys($links));
        $this->assertSame(['register' => '/pwa/register', 'forgot' => '/pwa/forgot', 'device' => '/ui'], $links);
        // Di dalam scope PWA (/pwa/): satu kolom HP, penanda contoh, dan jalan kembali ke layar login PWA.
        foreach (['register', 'forgot'] as $k) {
            $this->get($links[$k])->assertOk()->assertSee('dl-plogin', false)->assertSee('Contoh tampilan', false)
                ->assertSee('Kembali ke login', false)->assertSee('href="/pwa/"', false);
        }
    }

    public function test_bahasa_mengikuti_cookie(): void
    {
        // Cookie dari JS (document.cookie) tidak terenkripsi: Locale::lang() membacanya dari $_COOKIE.
        $_COOKIE['dl_lang'] = 'en';
        try {
            $this->getJson('/pwa/session')->assertJsonPath('lang', 'en');
        } finally {
            unset($_COOKIE['dl_lang']);
        }
    }

    public function test_kosong_dan_salah_balas_422_dengan_kode(): void
    {
        $this->postJson('/login', ['login' => '', 'password' => ''])->assertStatus(422)->assertExactJson(['error' => 'empty']);
        $this->postJson('/login', ['login' => 'tidak-ada', 'password' => 'salah'])->assertStatus(422)->assertExactJson(['error' => 'wrong']);
    }

    public function test_berhasil_balas_alamat_tujuan_dan_sesi_terisi(): void
    {
        $u = config('devlib.users')[0];
        $this->postJson('/login', ['login' => $u['login'], 'password' => $u['password']])
            ->assertOk()->assertJsonPath('redirect', route('home'));
        $this->assertSame($u['login'], session('devlib_user.login'));
    }

    public function test_form_biasa_tetap_redirect_dengan_flash(): void
    {
        $this->from('/login')->post('/login', ['login' => 'tidak-ada', 'password' => 'salah'])
            ->assertRedirect('/login')->assertSessionHas('login_error', 'wrong');
    }
}
