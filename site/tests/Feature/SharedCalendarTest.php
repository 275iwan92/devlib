<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga x-dl::shared-calendar (alamat server, daftar orang dan ruang di x-dl::calendar) serta skripnya (kirim per acara, undangan, kalender tim, @).
 * Dipakai oleh : php artisan test.
 * Status       : Aktif.
 */
class SharedCalendarTest extends TestCase
{
    public function test_renders_wrapper_with_urls_people_and_rooms(): void
    {
        $html = Blade::render('<x-dl::shared-calendar :events="[]" :people="$p" :rooms="[7 => \'Room A\']" save-url="/e" delete-url="/e/:id" rsvp-url="/e/:id/rsvp" team-url="/t" csrf="tok" />', ['p' => [['id' => 1, 'name' => 'Nisa', 'role' => '']]]);

        foreach (['data-dl-shared', 'data-save="/e"', 'data-delete="/e/:id"', 'data-rsvp="/e/:id/rsvp"', 'data-team="/t"', 'data-csrf="tok"', 'data-dl-calendar', 'data-people=', 'Nisa', 'data-rooms=', 'Room A'] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
    }

    public function test_plain_calendar_has_no_sharing_attributes(): void
    {
        $html = Blade::render('<x-dl::calendar :events="[]" />');

        $this->assertStringNotContainsString('data-people', $html);
        $this->assertStringNotContainsString('data-rooms', $html);
    }

    public function test_scripts_send_per_event_and_support_mention_and_rsvp(): void
    {
        $dir = dirname(__DIR__, 3).'/resources/assets/js/components/';
        $shared = file_get_contents($dir.'shared-calendar.js');
        $cal = file_get_contents($dir.'calendar.js');

        foreach (["'DELETE'", "'GET'", 'setTeam', "type === 'rsvp'", 'users='] as $needle) {
            $this->assertStringContainsString($needle, $shared, $needle);
        }
        foreach (['data-m-in', 'rsvpBox', "'DECLINED'", 'data-e-room', 'data-e-vis', "_emit('open'"] as $needle) {
            $this->assertStringContainsString($needle, $cal, $needle);
        }
    }
}
