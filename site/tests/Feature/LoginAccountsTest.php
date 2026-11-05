<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// Situs publik: bypass dan akun contoh dimatikan, hanya akun sec_user (hash) yang bisa masuk.
class LoginAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sec_user_login_works_and_sample_account_is_off_when_disabled(): void
    {
        config(['devlib.login_bypass' => false, 'devlib.sample_login' => false]);
        $this->artisan('devlib:user', ['login' => 'tamu', '--name' => 'Tamu'])
            ->expectsQuestion('Sandi untuk tamu', 'rahasia-uji')->expectsQuestion('Ulangi sandi', 'rahasia-uji')->assertSuccessful();
        $this->assertNotSame('rahasia-uji', DB::table('sec_user')->where('login', 'tamu')->value('pswd'));

        $this->post('/login', ['login' => 'admin', 'password' => 'admin'])->assertSessionHas('login_error', 'wrong');
        $this->post('/login', ['login' => 'tamu', 'password' => 'salah'])->assertSessionHas('login_error', 'wrong');
        $this->post('/login', ['login' => 'tamu', 'password' => 'rahasia-uji'])->assertRedirect()->assertSessionHas('devlib_user.login', 'tamu');
        $this->get('/')->assertOk();
    }
}
