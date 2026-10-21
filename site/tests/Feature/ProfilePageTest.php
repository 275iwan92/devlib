<?php

namespace Tests\Feature;

use App\Support\Catalog;
use App\Support\ProfileVariants;
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
            ->assertSee('data-lm-page="setup"', false)
            ->assertSee('Host: HOST-01 / 10.0.0.10 · Client: 127.0.0.1', false)
            ->assertDontSee('example.test · ', false);   // header panel = nama + e-mail, tanpa jabatan
        $this->assertFalse(app()->bound('devlib.env_where'));   // binding peragaan dilepas lagi
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

    public function test_page_sits_in_special_screen_and_is_approved(): void
    {
        $group = collect(Catalog::groups())->firstWhere('code', 'SP');
        $this->assertArrayHasKey('p7-profile', $group['pages']);
        $this->assertArrayHasKey('p7-profile', Catalog::APPROVED);   // disetujui Jo 2026-10-21
    }

    // Aksi di panel hidup sebuah varian (kunci data-lm-act, tanpa duplikat).
    private function acts(string $html): array
    {
        $panel = substr($html, (int) strpos($html, 'data-id="demo-profile"'));
        preg_match_all('/data-lm-act="([a-z]+)"/', $panel, $m);

        return array_values(array_unique($m[1]));
    }

    public function test_three_variants_are_offered_and_their_items_match_actions_minus_without(): void
    {
        $this->assertSame(['tmerp', 'sstock', 'devlib'], array_keys(ProfileVariants::ALL));
        $expect = ['tmerp' => ['notif', 'chat', 'ai', 'edit', 'help', 'pass', 'logout'], 'sstock' => ['notif', 'edit', 'help', 'pass', 'logout'], 'devlib' => ['pass', 'logout']];
        foreach (ProfileVariants::ALL as $key => $variant) {
            $html = $this->get('/lib/p7-profile?variant='.$key)->assertOk()->assertSee('?variant='.$key, false)->getContent();
            $keys = array_column((new LayerShell(without: $variant['without']))->actions(), 'key');
            $this->assertSame($expect[$key], $keys);
            $this->assertSame($keys, $this->acts($html), $key);
            $this->assertStringContainsString('data-lm-act="logout"', $html);
        }
        $sstock = $this->get('/lib/p7-profile?variant=sstock')->getContent();
        $this->assertStringContainsString('data-lm-page="lang"', $sstock);
        $this->assertStringNotContainsString('data-lm-page="theme"', $sstock);
        $this->assertStringNotContainsString('data-lm-page="setup"', $sstock);
        $devlib = $this->get('/lib/p7-profile?variant=devlib')->getContent();
        $this->assertStringNotContainsString('data-lm-page="lang"', $devlib);
        $this->assertStringNotContainsString('data-dl-drawer', substr($devlib, (int) strpos($devlib, 'data-id="demo-profile"')));
    }

    public function test_default_and_explicit_tmerp_render_the_same_page(): void
    {
        $strip = fn (string $h) => preg_replace('/"[A-Za-z0-9]{40}"/', 'T', $h);
        $default = $strip($this->get('/lib/p7-profile')->getContent());
        $this->assertSame($default, $strip($this->get('/lib/p7-profile?variant=tmerp')->getContent()));
        $this->assertSame($default, $strip($this->get('/lib/p7-profile?variant=unknown')->getContent()));   // kunci tak dikenal = bawaan
    }

    public function test_variants_carry_no_real_data(): void
    {
        foreach (array_keys(ProfileVariants::ALL) as $key) {
            $this->get('/lib/p7-profile?variant='.$key)->assertOk()->assertSee('Budi Santoso', false)->assertDontSee('mockup.test', false)->assertDontSee('sstock/', false)->assertDontSee('tmerp/', false);
        }
    }
}
