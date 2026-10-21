<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

// x-dl::layer-shell :without: item menu profil yang bisa disembunyikan (opt-in); Ubah password dan Sign out selalu ada.
class LayerShellWithoutTest extends TestCase
{
    private const ALL = ['notif', 'chat', 'ai', 'edit', 'help', 'setup'];

    private function render(array $without): string
    {
        $account = ['csrf' => 't', 'profile' => ['url' => '/p'], 'password' => ['url' => '/pw'], 'notifications' => ['read' => '/r', 'items' => []]];

        return Blade::render('<x-dl::layer-shell :without="$without" :account="$account" :user="[\'name\' => \'Ann\', \'email\' => \'a@x.test\', \'role\' => \'Dev\']" logout-action="/out" />', compact('without', 'account'));
    }

    // Kunci aksi di daftar Profile.
    private function acts(string $html): array
    {
        preg_match_all('/data-lm-act="([a-z]+)"/', $html, $m);

        return array_values(array_unique($m[1]));
    }

    public function test_default_keeps_every_profile_item(): void
    {
        $html = $this->render([]);
        $this->assertSame(['notif', 'chat', 'ai', 'edit', 'help', 'pass', 'logout'], $this->acts($html));
        foreach (['data-dl-drawer="notif"', 'data-dl-drawer="chat"', 'data-dl-modal="edit"', 'data-lm-page="setup"', 'data-lm-page="theme"', 'data-lm-page="lang"'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
    }

    public function test_each_new_value_hides_only_its_own_item(): void
    {
        $notif = $this->render(['notif']);
        $this->assertNotContains('notif', $this->acts($notif));
        $this->assertStringNotContainsString('data-dl-drawer="notif"', $notif);
        $this->assertStringNotContainsString('data-dl-markread', $notif);
        $this->assertStringContainsString('data-lm-act="edit"', $notif);
        // Tanpa laci Notifikasi, modal Ubah password tetap membawa data akun (Save tersambung).
        $this->assertMatchesRegularExpression('/data-dl-modal="pass" data-dl-account="[^"]*pw/', $notif);

        $edit = $this->render(['edit']);
        $this->assertNotContains('edit', $this->acts($edit));
        $this->assertStringNotContainsString('data-dl-modal="edit"', $edit);
        $this->assertStringContainsString('data-dl-drawer="notif"', $edit);

        $setup = $this->render(['setup']);
        foreach (['setup', 'theme', 'lang'] as $page) {
            $this->assertStringNotContainsString('data-lm-page="'.$page.'"', $setup);
        }
        $this->assertSame($this->acts($this->render([])), $this->acts($setup));
    }

    public function test_full_combination_leaves_only_change_password_and_sign_out(): void
    {
        $html = $this->render(self::ALL);
        $this->assertSame(['pass', 'logout'], $this->acts($html));
        $this->assertStringContainsString('data-dl-modal="pass"', $html);
        $this->assertStringContainsString('data-dl-logout', $html);
        foreach (['data-dl-drawer="notif"', 'data-dl-drawer="chat"', 'data-dl-modal="edit"', 'data-lm-page="setup"'] as $needle) {
            $this->assertStringNotContainsString($needle, $html);
        }
        $this->assertStringContainsString('data-dl-account=', $html);
    }

    public function test_the_separator_follows_the_first_visible_item_of_the_lower_group(): void
    {
        $sepOn = function (array $without) {
            $c = new \Devlib\Components\LayerShell(without: $without);

            return collect($c->actions())->firstWhere('sep', true)['key'] ?? null;
        };
        $this->assertSame('edit', $sepOn([]));
        $this->assertSame('help', $sepOn(['edit']));
        $this->assertSame('pass', $sepOn(['edit', 'help']));
        $this->assertNull($sepOn(['notif', 'chat', 'ai']));
    }
}
