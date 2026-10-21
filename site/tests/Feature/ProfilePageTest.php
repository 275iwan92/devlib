<?php

namespace Tests\Feature;

use App\Support\Catalog;
use Devlib\Components\LayerShell;
use Tests\TestCase;

// Halaman contoh p7-profile (Special Screen): merekam panel Profil Layer Menu, bentuk bawaan penuh, data fiktif.
class ProfilePageTest extends TestCase
{
    public function test_page_shows_the_full_panel_with_fictitious_data(): void
    {
        $this->get('/lib/p7-profile')->assertOk()
            ->assertSee('Budi Santoso', false)
            ->assertSee('budi@example.test', false)
            ->assertSee('data-id="demo-profile"', false)
            ->assertSee('data-lm-ov="profile"', false)
            ->assertSee('data-lm-act="logout"', false)
            ->assertSee('data-lm-page="setup"', false);
    }

    public function test_table_rows_match_the_actions_of_layer_shell(): void
    {
        $html = $this->get('/lib/p7-profile')->assertOk()->getContent();
        $labels = ['notif' => 'Notifications', 'chat' => 'Chat', 'ai' => 'AI Assistant', 'edit' => 'Edit profile', 'help' => 'Help &amp; support', 'pass' => 'Change password', 'logout' => 'Sign out'];
        $keys = array_column((new LayerShell)->actions(), 'key');
        $this->assertSame(array_keys($labels), $keys);
        foreach ($labels as $label) {
            $this->assertStringContainsString('>'.$label.'<', $html);
        }
        $this->assertStringContainsString('cannot be hidden', $html);
    }

    public function test_page_sits_in_special_screen_and_is_not_approved(): void
    {
        $group = collect(Catalog::groups())->firstWhere('code', 'SP');
        $this->assertArrayHasKey('p7-profile', $group['pages']);
        $this->assertArrayNotHasKey('p7-profile', Catalog::APPROVED);
    }
}
