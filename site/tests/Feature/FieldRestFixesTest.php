<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

// Sisa kejanggalan sisi PHP/Blade date-time, slider, number-words, lookup: disabled tidak terkirim, kode mata uang sekali, komentar sesuai kode.
class FieldRestFixesTest extends TestCase
{
    private function r(string $tag): string
    {
        return preg_replace('/ +>/', '>', Blade::render($tag));
    }

    private function assertAllNamedDisabled(string $html, string $what): void
    {
        preg_match_all('/<(?:input|textarea)\b[^>]*\bname="[^"]*"[^>]*>/', $html, $m);
        $this->assertNotEmpty($m[0], $what.': tidak ada input bernama');
        foreach ($m[0] as $tag) {
            $this->assertMatchesRegularExpression('/\bdisabled\b/', $tag, $what.': '.$tag.' masih terkirim');
        }
    }

    private function assertNoneNamedDisabled(string $html, string $what): void
    {
        preg_match_all('/<(?:input|textarea)\b[^>]*\bname="[^"]*"[^>]*>/', $html, $m);
        foreach ($m[0] as $tag) {
            $this->assertDoesNotMatchRegularExpression('/\bdisabled\b/', $tag, $what.': '.$tag);
        }
    }

    public function test_disabled_keeps_values_out_of_the_request(): void
    {
        $tags = [
            'date-time' => '<x-dl::field.date-time name="at" value="2026-10-21 14:30" %s />',
            'slider' => '<x-dl::field.slider name="v" :min="0" :max="10" %s />',
            'slider range' => '<x-dl::field.slider name="v" :min="0" :max="10" range %s />',
            'number-words' => '<x-dl::field.number-words name="amt" value="12" %s />',
            'number-words currency' => '<x-dl::field.number-words name="amt" currency="IDR" value="12" %s />',
        ];
        foreach ($tags as $k => $tag) {
            $this->assertAllNamedDisabled($this->r(sprintf($tag, 'disabled')), $k);
            $this->assertNoneNamedDisabled($this->r(sprintf($tag, '')), $k.' (aktif)');
        }
    }

    public function test_lookup_disabled_keeps_value_out_of_the_request(): void
    {
        \Devlib\Lookup::define('rest_fix', ['key' => 'id', 'label' => 'name', 'rows' => [['id' => 1, 'name' => 'Ani']]]);
        $tag = '<x-dl::field.lookup name="who" lookup="rest_fix" label="Who" value="1" %s />';
        $html = $this->r(sprintf($tag, 'disabled'));
        $this->assertAllNamedDisabled($html, 'lookup');
        $this->assertNoneNamedDisabled($this->r(sprintf($tag, '')), 'lookup (aktif)');
    }

    public function test_number_words_currency_button_shows_the_code_once(): void
    {
        $html = $this->r('<x-dl::field.number-words name="amt" currency="IDR" picker />');
        $this->assertSame(0, substr_count($html, 'data-dl-prefix-a'));
        preg_match('/<button[^>]*dl-prefix__btn[^>]*>(.*?)<\/button>/s', $html, $m);
        $this->assertSame(1, substr_count(strip_tags($m[1]), 'IDR'));
    }

    public function test_number_words_currency_picker_is_closed_when_disabled_or_readonly(): void
    {
        $this->assertStringContainsString('data-dl-dd-toggle', $this->r('<x-dl::field.number-words name="amt" currency="IDR" picker />'));
        foreach (['disabled', 'readonly'] as $a) {
            $this->assertStringNotContainsString('data-dl-dd-toggle', $this->r('<x-dl::field.number-words name="amt" currency="IDR" picker '.$a.' />'), $a);
        }
    }

    public function test_lookup_carries_its_placeholder_for_setField(): void
    {
        \Devlib\Lookup::define('rest_fix', ['key' => 'id', 'label' => 'name', 'rows' => [['id' => 1, 'name' => 'Ani']]]);
        $this->assertStringContainsString('data-empty="Cari ani..."', $this->r('<x-dl::field.lookup name="who" lookup="rest_fix" placeholder="Cari ani..." />'));
    }

    public function test_date_time_header_comment_matches_the_real_display_format(): void
    {
        $src = file_get_contents(base_path('../src/Components/Field/DateTime.php'));
        $this->assertStringNotContainsString('1 Okt 1969, 14:30 WIB', $src);
        $this->assertStringContainsString('01-Okt-1969 14:30 WIB', $src);
    }
}
