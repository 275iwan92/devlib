<?php

namespace Tests\Feature;

use Devlib\Access;
use Devlib\NumberValue;
use Devlib\Terbilang;
use Tests\TestCase;

// Logika murni yang dicetak/disimpan apa adanya (terbilang di nota, angka kanonik ke DB, hak akses): kesalahan di sini tidak kelihatan di halaman katalog.
class ValueLogicTest extends TestCase
{
    public function test_terbilang_indonesian_and_english(): void
    {
        $this->assertSame('Seribu dua ratus', Terbilang::words(1200, null, 'id'));
        $this->assertSame('Seribu dua ratus rupiah', Terbilang::words(1200, 'IDR', 'id'));
        $this->assertSame('Dua belas dolar Amerika Serikat dan lima puluh sen', Terbilang::words('12.50', 'USD', 'id'));
        $this->assertSame('Dua belas koma lima', Terbilang::words(12.5, null, 'id'));
        $this->assertSame('Nol', Terbilang::words(0, null, 'id'));
        $this->assertSame('Minus five', Terbilang::words(-5, null, 'en'));
        $this->assertSame('Twelve US dollars and fifty cents', Terbilang::words('12.50', 'USD', 'en'));
    }

    public function test_number_value_reads_human_input_into_canonical_form(): void
    {
        $cases = [
            ['1234.5', 'id', '1234.5'],      // sudah kanonik
            ['1.234.567', 'id', '1234567'],  // titik ribuan (id)
            ['1.234,50', 'id', '1234.50'],   // titik+koma: yang terakhir desimal
            ['1,234.50', 'id', '1234.50'],   // tempelan Excel en
            ['1,5', 'id', '1.5'],            // koma = desimal id
            ['Rp 21.500', 'id', '21500'],    // simbol mata uang dibuang
            ['007', 'id', '7'],
            ['-0', 'id', '0'],
        ];
        foreach ($cases as [$typed, $lang, $canonical]) {
            $this->assertSame($canonical, NumberValue::of($typed, $lang)?->canonical, "{$typed} ({$lang})");
        }
        foreach (['', 'abc', '1,5,5', '1.5.5'] as $bad) {
            $this->assertNull(NumberValue::of($bad, 'id'), "'{$bad}' harus ditolak");
        }
    }

    public function test_number_value_displays_per_language(): void
    {
        $n = NumberValue::of('1234.5');
        $this->assertSame('1.234,50', $n->display(2, 'id'));
        $this->assertSame('1,234.50', $n->display(2, 'en'));
        $this->assertSame('1.234,5', $n->trimmed(2, 'id'));
        $this->assertSame('1234,5', $n->raw('id'));
    }

    public function test_access_follows_config_abilities(): void
    {
        config(['devlib.abilities' => ['po.*', 'item.view']]);
        $this->assertTrue(Access::can('po.approve'));
        $this->assertTrue(Access::can('item.view'));
        $this->assertFalse(Access::can('item.delete'));

        config(['devlib.abilities' => ['*']]);
        $this->assertTrue(Access::can('anything.at.all'));
    }
}
