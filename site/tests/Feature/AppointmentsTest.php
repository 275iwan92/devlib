<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga x-dl::appointments (membungkus x-dl::calendar, penanda JS, alamat simpan, data awal) dan skripnya (mengikuti dl:calendar, mengirim seluruh daftar).
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class AppointmentsTest extends TestCase
{
    public function test_wraps_the_calendar_with_save_attributes_and_events(): void
    {
        $html = Blade::render('<x-dl::appointments :items="$i" display="month" save-url="/t/appointments" csrf="tok" />', ['i' => [['id' => 'n1', 'title' => 'Dentist', 'start' => '2026-11-09 10:00', 'end' => '2026-11-09 11:00', 'allDay' => false, 'tone' => 'accent', 'desc' => '']]]);

        foreach (['data-dl-appt', 'data-save="/t/appointments"', 'data-csrf="tok"', 'data-dl-calendar', 'data-view="month"', 'Dentist'] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
    }

    public function test_script_follows_calendar_events_and_posts_the_whole_list(): void
    {
        $js = file_get_contents(dirname(__DIR__, 3).'/resources/assets/js/components/appointments.js');

        $this->assertStringContainsString("'dl:calendar'", $js);
        $this->assertStringContainsString("type === 'delete'", $js);
        $this->assertStringContainsString('JSON.stringify({items: this.items})', $js);
    }
}
