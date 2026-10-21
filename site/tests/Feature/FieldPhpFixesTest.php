<?php

namespace Tests\Feature;

use Devlib\Components\Field\DateSlider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

// Perbaikan kejanggalan sisi PHP/Blade keluarga Field: yang rusak diperbaiki, keluaran untuk masukan yang sah tidak berubah.
class FieldPhpFixesTest extends TestCase
{
    private function r(string $tag): string
    {
        // spasi sebelum '>' (sisa atribut kosong) tidak penting.
        return preg_replace('/ +>/', '>', Blade::render($tag));
    }

    // Semua <input|textarea name="..."> pada keluaran harus membawa atribut disabled.
    private function assertAllNamedDisabled(string $html, string $what): void
    {
        preg_match_all('/<(?:input|textarea)\b[^>]*\bname="[^"]*"[^>]*>/', $html, $m);
        $this->assertNotEmpty($m[0], $what.': tidak ada input bernama');
        foreach ($m[0] as $tag) {
            $this->assertMatchesRegularExpression('/\bdisabled\b/', $tag, $what.': '.$tag.' masih terkirim');
        }
    }

    public function test_readonly_checked_checkbox_is_still_posted(): void
    {
        $html = $this->r('<x-dl::field.checkbox name="agree" text="Ya" :value="true" readonly />');
        $this->assertStringContainsString('<input type="hidden" name="agree" value="1">', $html);
        $this->assertStringNotContainsString('type="hidden"', $this->r('<x-dl::field.checkbox name="agree" text="Ya" readonly />'));
        $this->assertStringNotContainsString('type="hidden"', $this->r('<x-dl::field.checkbox name="agree" text="Ya" :value="true" disabled />'));
        $this->assertStringNotContainsString('type="hidden"', $this->r('<x-dl::field.checkbox name="agree" text="Ya" :value="true" />'));
    }

    public function test_group_controls_name_the_group_instead_of_a_dangling_label_for(): void
    {
        $tags = [
            'radio' => '<x-dl::field.radio name="g" label="Gender" :options="[\'M\' => \'Male\']" />',
            'checkboxes' => '<x-dl::field.checkboxes name="g" label="Gender" :options="[\'M\' => \'Male\']" />',
            'toggle-group' => '<x-dl::field.toggle-group name="g" label="Gender" :options="[\'M\' => \'Male\']" />',
            'key-value' => '<x-dl::field.key-value name="g" label="Gender" />',
            'rating' => '<x-dl::field.rating name="g" label="Gender" />',
            'display' => '<x-dl::field.display label="Gender" value="x" />',
        ];
        foreach ($tags as $k => $tag) {
            $html = $this->r($tag);
            $this->assertDoesNotMatchRegularExpression('/<label[^>]*\bfor=/', $html, $k);
            $this->assertMatchesRegularExpression('/<label class="dl-field__label" id="(dlf-[^"]+-lbl)"/', $html, $k);
            preg_match('/<label class="dl-field__label" id="([^"]+)"/', $html, $m);
            $this->assertStringContainsString('aria-labelledby="'.$m[1].'"', $html, $k);
        }
        // Tanpa label: tidak ada acuan aria-labelledby yang menggantung.
        $this->assertStringNotContainsString('aria-labelledby', $this->r('<x-dl::field.radio name="g" :options="[\'M\' => \'Male\']" />'));
        // Kontrol berlabel biasa tetap memakai for -> id elemennya.
        $html = $this->r('<x-dl::field.text name="n" label="Nama" />');
        preg_match('/<label[^>]*\bfor="([^"]+)"/', $html, $m);
        $this->assertStringContainsString('id="'.$m[1].'"', $html);
    }

    public function test_disabled_field_does_not_post_hidden_or_extra_inputs(): void
    {
        $tags = [
            'multi-select' => '<x-dl::field.multi-select name="f" :options="[\'a\' => \'A\', \'b\' => \'B\']" :value="[\'a\']" disabled />',
            'tags' => '<x-dl::field.tags name="f" :value="[\'x\', \'y\']" disabled />',
            'date' => '<x-dl::field.date name="f" value="2026-10-01" disabled />',
            'rating' => '<x-dl::field.rating name="f" :value="3" disabled />',
            'otp' => '<x-dl::field.otp name="f" value="1234" disabled />',
            'rich-text' => '<x-dl::field.rich-text name="f" value="hai" disabled />',
            'signature' => '<x-dl::field.signature name="f" disabled />',
            'toggle-group' => '<x-dl::field.toggle-group name="f" :options="[\'a\' => \'A\']" value="a" disabled />',
            'money' => '<x-dl::field.money name="f" :value="1000" disabled />',
            'key-value' => '<x-dl::field.key-value name="f" :value="[\'k\' => \'v\']" disabled />',
            'phone' => '<x-dl::field.phone name="f" disabled />',
            'credit-card' => '<x-dl::field.credit-card name="f" disabled />',
        ];
        foreach ($tags as $k => $tag) {
            $this->assertAllNamedDisabled($this->r($tag), $k);
        }
    }

    public function test_readonly_field_still_posts_its_value(): void
    {
        $tags = [
            '<x-dl::field.rating name="f" :value="3" readonly />' => 'name="f" value="3"',
            '<x-dl::field.toggle-group name="f" :options="[\'a\' => \'A\']" value="a" readonly />' => 'name="f" value="a" data-dl-value>',
            '<x-dl::field.multi-select name="f" :options="[\'a\' => \'A\']" :value="[\'a\']" readonly />' => 'value="a" data-label="A" checked>',
            '<x-dl::field.money name="f" :value="1000" readonly />' => 'name="f_currency" value="IDR" data-dl-prefix-value>',
        ];
        foreach ($tags as $tag => $needle) {
            $this->assertStringContainsString($needle, $this->r($tag), $tag);
        }
    }

    public function test_locked_buttons_next_to_the_input_are_disabled(): void
    {
        $this->assertMatchesRegularExpression('/data-dl-dd-toggle title="[^"]*" disabled/', $this->r('<x-dl::field.date name="f" disabled />'));
        $this->assertDoesNotMatchRegularExpression('/data-dl-dd-toggle title="[^"]*" disabled/', $this->r('<x-dl::field.date name="f" />'));
        $this->assertMatchesRegularExpression('/data-dl-dd-toggle title="[^"]*" disabled/', $this->r('<x-dl::field.time name="f" disabled />'));
        $this->assertDoesNotMatchRegularExpression('/data-dl-dd-toggle title="[^"]*" disabled/', $this->r('<x-dl::field.time name="f" />'));
        $this->assertMatchesRegularExpression('/<button[^>]*data-dl-spin="1"[^>]* disabled/', $this->r('<x-dl::field.number name="f" disabled />'));
        $this->assertDoesNotMatchRegularExpression('/<button[^>]*data-dl-spin[^>]* disabled/', $this->r('<x-dl::field.number name="f" />'));
        $this->assertMatchesRegularExpression('/<button[^>]*disabled[^>]*data-dl-eye/', $this->r('<x-dl::field.password name="f" disabled />'));
        $this->assertDoesNotMatchRegularExpression('/<button[^>]*disabled[^>]*data-dl-eye/', $this->r('<x-dl::field.password name="f" />'));
        $this->assertMatchesRegularExpression('/<button[^>]*disabled[^>]*data-dl-cc-case/', $this->r('<x-dl::field.credit-card name="f" holder readonly />'));
        $this->assertDoesNotMatchRegularExpression('/<button[^>]*disabled[^>]*data-dl-cc-case/', $this->r('<x-dl::field.credit-card name="f" holder />'));
        $this->assertMatchesRegularExpression('/<button[^>]*data-dl-dd-toggle disabled><span data-dl-prefix-b>/', $this->r('<x-dl::field.money name="f" readonly />'));
        $this->assertDoesNotMatchRegularExpression('/<button[^>]*data-dl-dd-toggle disabled><span data-dl-prefix-b>/', $this->r('<x-dl::field.money name="f" />'));
        $this->assertMatchesRegularExpression('/data-dl-tag-x disabled/', $this->r('<x-dl::field.tags name="f" :value="[\'x\']" readonly />'));
        $this->assertDoesNotMatchRegularExpression('/data-dl-tag-x disabled/', $this->r('<x-dl::field.tags name="f" :value="[\'x\']" />'));
    }

    public function test_key_value_cells_and_delete_buttons_follow_the_lock(): void
    {
        $ro = $this->r('<x-dl::field.key-value name="f" :value="[\'k\' => \'v\']" readonly />');
        $this->assertSame(2, preg_match_all('/<input type="text"[^>]* readonly/', $ro));
        $this->assertMatchesRegularExpression('/data-dl-kv-x disabled/', $ro);
        $open = $this->r('<x-dl::field.key-value name="f" :value="[\'k\' => \'v\']" />');
        $this->assertSame(0, preg_match_all('/<input type="text"[^>]*(readonly|disabled)/', $open));
        $this->assertDoesNotMatchRegularExpression('/data-dl-kv-x disabled/', $open);
    }

    public function test_multi_select_summary_uses_the_label_not_the_key(): void
    {
        $html = $this->r('<x-dl::field.multi-select name="f" :options="[\'jkt\' => \'Jakarta\']" />');
        $this->assertStringContainsString('value="jkt" data-label="Jakarta"', $html);
    }

    public function test_otp_renders_value(): void
    {
        $html = $this->r('<x-dl::field.otp name="f" :length="6" value="1234" />');
        $this->assertStringContainsString('<input type="hidden" name="f" value="1234" data-dl-value>', $html);
        preg_match_all('/class="dl-otp__box" value="([^"]*)"/', $html, $m);
        $this->assertSame(['1', '2', '3', '4', '', ''], $m[1]);
        $empty = $this->r('<x-dl::field.otp name="f" :length="3" />');
        $this->assertStringContainsString('name="f" value="" data-dl-value', $empty);
    }

    public function test_display_empty_money_and_number_show_a_dash_and_required_is_ignored(): void
    {
        $this->assertStringContainsString('>—</div>', $this->r('<x-dl::field.display label="T" format="money" :value="null" />'));
        $this->assertStringContainsString('>—</div>', $this->r('<x-dl::field.display label="T" format="money" value="" />'));
        $this->assertStringContainsString('>—</div>', $this->r('<x-dl::field.display label="T" format="number" :value="null" />'));
        $this->assertStringContainsString('>IDR 0</div>', $this->r('<x-dl::field.display label="T" format="money" :value="0" />'));
        $this->assertStringContainsString('>0</div>', $this->r('<x-dl::field.display label="T" format="number" :value="0" />'));
        $this->assertStringContainsString('dl-field__req', $this->r('<x-dl::field.text name="n" label="T" required />'));
        $this->assertStringNotContainsString('dl-field__req', $this->r('<x-dl::field.display label="T" value="x" required />'));
    }

    public function test_color_label_matches_the_posted_value(): void
    {
        $html = $this->r('<x-dl::field.color name="c" value="#fff" />');
        $this->assertStringContainsString('value="#ffffff"', $html);
        $this->assertStringContainsString('>#FFFFFF</span>', $html);
        foreach (['', 'red', 'fff', '#12345'] as $bad) {
            $html = $this->r('<x-dl::field.color name="c" value="'.$bad.'" />');
            $this->assertStringContainsString('value="#2563eb"', $html, $bad);
            $this->assertStringContainsString('>#2563EB</span>', $html, $bad);
        }
        $this->assertStringContainsString('>#2563EB</span>', $this->r('<x-dl::field.color name="c" />'));
        $ok = $this->r('<x-dl::field.color name="c" value="#AbCdEf" />');
        $this->assertStringContainsString('value="#AbCdEf"', $ok);
        $this->assertStringContainsString('>#ABCDEF</span>', $ok);
        $ro = $this->r('<x-dl::field.color name="c" value="#fff" readonly />');
        $this->assertStringContainsString('<input type="hidden" name="c" value="#ffffff">', $ro);
    }

    public function test_date_slider_default_window_uses_app_timezone_and_does_not_overflow(): void
    {
        config(['devlib.timezone' => 'Asia/Jakarta']);
        // 31 Des 2026 20:00 UTC = 1 Jan 2027 03:00 WIB
        $this->travelTo(Carbon::parse('2026-12-31 20:00:00', 'UTC'));
        $s = new DateSlider;
        $this->assertSame('2027-01-01', $s->from);
        $this->assertSame('2027-03-31', $s->until);

        // 31 Des WIB: +2 bulan bukan 3 Maret (limpahan), tetapi akhir Februari
        $this->travelTo(Carbon::parse('2026-12-31 05:00:00', 'Asia/Jakarta'));
        $s = new DateSlider;
        $this->assertSame('2026-12-01', $s->from);
        $this->assertSame('2027-02-28', $s->until);

        $this->assertSame('2026-11-05', (new DateSlider(from: '2026-11-05', until: '2027-01-15'))->from);
        $this->assertSame('2027-01-15', (new DateSlider(from: '2026-11-05', until: '2027-01-15'))->until);
    }
}
