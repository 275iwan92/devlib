<?php

/*
 * REMARK
 * Tujuan       : Induk SEMUA field (acuan mockup UIUX_components_part1.html). Bingkai yang.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : name, label, tooltip, hint, error, value, placeholder, required, disabled, readonly, … (14 prop).
 * Output       : Markup Blade inline (render() kelas ini).
 * Dipakai oleh : layar proyek lewat x-dl::field.field.
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components\Field;

use Devlib\Components\DevlibComponent;
use Illuminate\Support\Str;

// Induk SEMUA field (acuan mockup UIUX_components_part1.html). Bingkai yang
// sama untuk tiap field: label + ikon tooltip (i) + kontrol + hint + error.
// Turunan cukup menyebut kontrolnya (control()) dan properti khasnya.
// size: sm = form standar (mockup form), lg = form besar (mockup login).
abstract class Field extends DevlibComponent
{
    public string $uid;

    // Mode ReadOnly untuk SEMUA field yang dirender selama nilainya true (blok hanya-tampil: sisi ReadOnly
    // halaman DevLib, form yang dikunci). Pemakai menyalakan lalu mematikannya lagi di sekitar blok itu.
    public static bool $readonlyAll = false;

    public function __construct(
        public string $name = '',
        public string $label = '',
        public ?string $tooltip = null,
        public ?string $hint = null,
        public ?string $error = null,
        public mixed $value = null,
        public ?string $placeholder = null,
        public bool $required = false,
        public bool $disabled = false,
        public bool $readonly = false,
        public ?string $icon = null,
        public string $size = 'sm',
        public ?string $i18n = null,
        public ?string $source = null,
    ) {
        $this->readonly = $readonly || self::$readonlyAll;
        $this->uid = 'dlf-'.($name !== '' ? Str::slug($name, '_').'-' : '').Str::random(5);
    }

    // Nama kontrol = nama view di resources/views/components/field/<kontrol>.blade.php
    abstract public function control(): string;

    public function tag(): string
    {
        return 'x-dl::field.'.$this->control().($this->name !== '' ? ' name='.$this->name : '');
    }

    public function source(): string
    {
        return $this->source ?? ($this->name !== '' ? 'input "'.$this->name.'"' : '—');
    }

    public function render()
    {
        return view('devlib::components.field');
    }
}
