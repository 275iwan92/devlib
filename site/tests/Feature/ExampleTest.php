<?php

namespace Tests\Feature;

use Tests\TestCase;

// Login situs ini hanya contoh tampilan untuk proyek lain: bawaan langsung masuk (DEVLIB_LOGIN_BYPASS); dimatikan = alur login contoh.
class ExampleTest extends TestCase
{
    public function test_guest_sees_the_home_page_by_default(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_guest_is_redirected_to_the_sample_login_when_bypass_is_off(): void
    {
        config(['devlib.login_bypass' => false]);

        $this->get('/')->assertRedirect(route('login'));
    }
}
