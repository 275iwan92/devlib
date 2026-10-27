<?php

/*
 * REMARK
 * Tujuan       : Pilihan tunggal dengan kotak cari (mis. lokasi Kota, Provinsi, Negara).
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : options, searchPlaceholder, name, label, tooltip, hint, error, value, placeholder, required, … (17 prop).
 * Output       : Markup dari resources/views/components/field/select.blade.php.
 * Dipakai oleh : basic-themes, field-select, form-md-po, form-md dan 5 lainnya (layar katalog/proyek).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components\Field;

// Pilihan tunggal dengan kotak cari (mis. lokasi Kota, Provinsi, Negara).
class Select extends Field
{
    public function __construct(
        public array $options = [],
        public string $searchPlaceholder = 'Type to search...',
        string $name = '',
        string $label = '',
        ?string $tooltip = null,
        ?string $hint = null,
        ?string $error = null,
        mixed $value = null,
        ?string $placeholder = null,
        bool $required = false,
        bool $disabled = false,
        bool $readonly = false,
        ?string $icon = null,
        string $size = 'sm',
        ?string $i18n = null,
        ?string $source = null,
        public array $add = [],
    ) {
        parent::__construct($name, $label, $tooltip, $hint, $error, $value, $placeholder, $required, $disabled, $readonly, $icon, $size, $i18n, $source);
    }

    // Konfigurasi "Add New" (prop add) yang sudah dirapikan, atau null = fitur mati: add kosong, url bukan satu asal (same-origin),
    // atau tanpa isian. Select readonly/disabled tidak menampilkan tombolnya. Teks pemanggil tetap mentah di sini; view meng-escape-nya.
    public function addConfig(): ?array
    {
        $a = $this->add;
        $url = trim((string) ($a['url'] ?? ''));
        $sameOrigin = str_starts_with($url, '/') ? ! str_starts_with($url, '//') && ! str_starts_with($url, '/\\')
            : (in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true) && strcasecmp((string) parse_url($url, PHP_URL_HOST), request()->getHost()) === 0);
        if ($this->disabled || $this->readonly || ! $sameOrigin || ! is_array($a['fields'] ?? null)) {
            return null;
        }
        $fields = [];
        foreach ($a['fields'] as $f) {
            if (is_array($f) && preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', (string) ($f['name'] ?? ''))) {
                $f += ['label' => '', 'required' => false, 'type' => 'text'];
                // radio / select (opt-in): butuh options; tanpa options isian jatuh kembali ke teks
                if (in_array($f['type'], ['radio', 'select'], true) && ! (is_array($f['options'] ?? null) && $f['options'])) {
                    $f['type'] = 'text';
                }
                $fields[] = $f;
            }
        }

        return $fields ? [
            'url' => $url,
            'title' => (string) ($a['title'] ?? ''),
            'fields' => $fields,
            'icon' => $a['icon'] ?? null,
            'label' => (string) ($a['label'] ?? ''),
            'note' => $a['note'] ?? null,
            'value-key' => (string) ($a['value-key'] ?? 'id'),
            'label-key' => (string) ($a['label-key'] ?? 'name'),
        ] : null;
    }

    public function control(): string
    {
        return 'select';
    }
}
