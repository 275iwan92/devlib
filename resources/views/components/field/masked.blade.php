<div class="dl-field__box">
    <input id="{{ $uid }}" type="text" inputmode="numeric" name="{{ $name }}" class="dl-input" value="{{ $value }}" placeholder="{{ $placeholder }}"
        data-dl-mask="{{ $mask }}" data-invalid="{{ $invalidText ?? strtr(\Devlib\Lang::text('Must be :n digits'), [':n' => substr_count($mask, '9')]) }}" maxlength="{{ strlen($mask) }}" @disabled($disabled) @readonly($readonly)
        @if ($nikBirth || $nikGender) @php($m = $nikMessages()) data-nik-birth="{{ $nikBirth }}" data-nik-gender="{{ $nikGender }}" data-nik-male="{{ $nikMale }}" data-nik-female="{{ $nikFemale }}"
            data-nik-count="{{ $m['count'] }}" data-nik-date="{{ $m['date'] }}" data-nik-differs="{{ $m['differs'] }}" data-nik-male-label="{{ $m['male'] }}" data-nik-female-label="{{ $m['female'] }}" @endif>
</div>
