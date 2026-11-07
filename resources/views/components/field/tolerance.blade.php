<div class="dl-field__box dl-tol">
    <span class="dl-tol__sw">
        <select class="dl-tol__sel dl-tol__dir" name="{{ $dirName }}" aria-label="{{ \Devlib\Lang::text('Direction') }}" @disabled($disabled || $readonly)>
            @foreach ($dirs as $v => $l)<option value="{{ $v }}" @selected((string) $v === $dir)>{{ $l }}</option>@endforeach
        </select>
    </span>
    <input id="{{ $uid }}" type="text" inputmode="decimal" name="{{ $name }}" class="dl-input dl-tol__num" value="{{ \Devlib\Locale::input($value) === null ? $value : \Devlib\Locale::number($value, $decimals) }}" placeholder="{{ $placeholder ?? \Devlib\Locale::number(0, $decimals) }}" data-dl-decimal="{{ $decimals }}" data-dl-maxint="{{ $digits }}" data-dl-num @disabled($disabled) @readonly($readonly)>
    <span class="dl-tol__sw">
        <select class="dl-tol__sel dl-tol__unit" name="{{ $unitName }}" aria-label="{{ \Devlib\Lang::text('Unit') }}" @disabled($disabled || $readonly)>
            @foreach ($units as $v => $l)<option value="{{ $v }}" @selected((string) $v === $unit)>{{ $l }}</option>@endforeach
        </select>
    </span>
</div>
