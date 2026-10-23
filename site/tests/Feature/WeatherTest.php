<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga x-dl::weather: tempat = posisi perangkat secara bawaan, props city/lat/lon = cadangan, locate=false = tempat tetap; rantai cadangan (cache, lokasi, props, tersembunyi) diuji murni lewat node.
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class WeatherTest extends TestCase
{
    public function test_default_asks_for_the_device_location_and_is_hidden_without_a_fallback(): void
    {
        $html = Blade::render('<x-dl::weather />');

        $this->assertStringContainsString('data-locate="1"', $html);
        $this->assertStringContainsString(' hidden', $html);
        $this->assertStringNotContainsString('data-lat', $html);
        $this->assertStringNotContainsString('Surabaya', $html);
    }

    public function test_props_are_the_fallback_place_and_are_visible_at_once(): void
    {
        $html = Blade::render('<x-dl::weather city="Malang" :lat="-7.98" :lon="112.63" />');

        $this->assertStringContainsString('data-locate="1"', $html);
        $this->assertStringContainsString('data-lat="-7.98" data-lon="112.63" data-city="Malang"', $html);
        $this->assertStringNotContainsString(' hidden', $html);
        $this->assertStringContainsString('data-dl-weather-city></span>', $html);   // kota dipasang skrip, bukan server (tidak berkedip kota cadangan)
    }

    public function test_locate_false_is_the_fixed_place_printed_by_the_server(): void
    {
        $html = Blade::render('<x-dl::weather :locate="false" city="Surabaya" :lat="-7.2575" :lon="112.7521" />');

        $this->assertStringContainsString('data-locate="0"', $html);
        $this->assertStringContainsString('data-dl-weather-city> · Surabaya</span>', $html);
    }

    public function test_the_login_frame_keeps_its_fixed_place(): void
    {
        $html = Blade::render('<x-dl::frame bg="devlib" box="center" widgets="weather@top-right"><p>x</p></x-dl::frame>');

        $this->assertStringContainsString('data-locate="0"', $html);
    }

    public function test_greeting_card_forwards_the_weather_keys_and_locate(): void
    {
        $board = ['user' => ['first' => 'Yuna'], 'profile_label' => 'Dashboard'];
        $fixed = Blade::render('<x-dl::greeting-card :board="$b" />', ['b' => $board + ['weather' => ['city' => 'Surabaya', 'lat' => -7.2575, 'lon' => 112.7521]]]);
        $only = Blade::render('<x-dl::greeting-card :board="$b" />', ['b' => $board + ['weather' => ['locate' => true]]]);
        $none = Blade::render('<x-dl::greeting-card :board="$b" />', ['b' => $board]);

        $this->assertStringContainsString('data-city="Surabaya"', $fixed);
        $this->assertMatchesRegularExpression('/data-dl-weather data-locate="1"\s+hidden/', $only);
        $this->assertStringNotContainsString('data-dl-weather', $none);
    }

    public function test_the_fallback_chain_runs_in_a_browserless_script(): void
    {
        $node = trim((string) shell_exec(PHP_OS_FAMILY === 'Windows' ? 'where node 2>NUL' : 'command -v node'));
        if ($node === '') {
            $this->markTestSkipped('node tidak ada');
        }
        $p = new Process([strtok($node, "\r\n"), base_path('tests/js/weather-place.cjs'), base_path('../resources/assets/js/components/weather.js')]);
        $p->run();

        $this->assertSame(0, $p->getExitCode(), $p->getOutput().$p->getErrorOutput());
        $this->assertStringContainsString('OK', $p->getOutput());
    }
}
