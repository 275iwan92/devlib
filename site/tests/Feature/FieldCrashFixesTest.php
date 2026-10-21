<?php

namespace Tests\Feature;

use Devlib\Components\Field\DateTime;
use Devlib\Components\Field\NumberWords;
use Devlib\Components\Field\Slider;
use Devlib\Lookup;
use Tests\TestCase;

// Empat masukan yang dulu membuat halaman crash atau macet; sekarang dijepit / dinormalkan tanpa mengubah masukan yang sah.
class FieldCrashFixesTest extends TestCase
{
    public function test_date_time_minute_step_is_clamped(): void
    {
        $this->assertSame(1, (new DateTime(minuteStep: 0))->minuteStep);
        $this->assertSame(1, (new DateTime(minuteStep: -5))->minuteStep);
        $this->assertSame(30, (new DateTime(minuteStep: 60))->minuteStep);
        $this->assertSame(15, (new DateTime(minuteStep: 15))->minuteStep);
        $this->assertSame(5, (new DateTime)->minuteStep);
    }

    public function test_slider_empty_or_text_value_falls_back_to_min_max(): void
    {
        $s = new Slider(min: 10, max: 90, value: '', valueTo: '');
        $this->assertSame(10, $s->fromValue());
        $this->assertSame(90, $s->toValue());
        $this->assertSame(10, (new Slider(min: 10, value: 'abc'))->fromValue());
        $this->assertSame(10, (new Slider(min: 10))->fromValue());
        $this->assertSame(25, (new Slider(value: '25'))->fromValue());
        $this->assertSame(2.5, (new Slider(value: '2.5', valueTo: 7.5))->fromValue());
        $this->assertSame(7.5, (new Slider(valueTo: 7.5))->toValue());
    }

    public function test_number_words_currency_is_normalised(): void
    {
        $this->assertSame('USD', (new NumberWords(currency: 'usd'))->currency);
        $this->assertSame('IDR', (new NumberWords(currency: 'IDR'))->currency);
        $this->assertNull((new NumberWords(currency: 'XXX'))->currency);
        $this->assertNull((new NumberWords(currency: ''))->currency);
        $this->assertNull((new NumberWords)->currency);
        $this->assertSame('IDR', (new NumberWords(currency: 'XXX', picker: true))->currency);
    }

    public function test_lookup_rows_accept_collection_and_generator(): void
    {
        $plain = [['id' => 1, 'name' => 'A'], ['id' => 2, 'name' => 'B']];
        Lookup::define('t-plain', ['rows' => $plain]);
        Lookup::define('t-coll', ['rows' => fn () => collect($plain)]);
        Lookup::define('t-gen', ['rows' => fn () => (function () use ($plain) {
            yield from $plain;
        })()]);

        $this->assertSame($plain, Lookup::get('t-plain')['rows']);
        $this->assertSame($plain, Lookup::get('t-coll')['rows']);
        $this->assertSame($plain, Lookup::get('t-gen')['rows']);
    }
}
