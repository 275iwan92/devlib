<?php

namespace Tests\Feature;

use Tests\TestCase;

// Halaman contoh dua model DevLib: isinya harus datang dari Assistant / ChatService, bukan halaman kosong.
class ModelPagesTest extends TestCase
{
    public function test_assistant_page_shows_engine_numbers_screen_help_unknown_and_out_of_scope(): void
    {
        $this->get('/lib/p7-assistant')->assertOk()
            ->assertSee('Orders Today', false)
            ->assertSee('NUMBER', false)
            ->assertSee('Orders: 12', false)
            ->assertSee('SCREEN', false)
            ->assertSee('[Open Purchase Order]', false)
            ->assertSee('Click New and pick the supplier.', false)
            ->assertSee('UNKNOWN', false)
            ->assertSee('OUT_OF_SCOPE', false)
            ->assertSee('Saya hanya memahami perihal program ini, lainnya saya belum paham', false)
            ->assertSee('Double-click a row to open it.', false);
    }

    public function test_chat_page_shows_people_from_the_chat_service(): void
    {
        $this->get('/lib/p7-chat-model')->assertOk()->assertSee('Rina Wijaya', false)->assertSee('Is the order from PT Contoh in yet?', false);
    }
}
