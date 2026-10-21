<div class="dl-otp" data-dl-otp>
    <input type="hidden" name="{{ $name }}" value="{{ $value }}" data-dl-value @disabled($disabled)>
    @for ($i = 0; $i < $length; $i++)
        <input type="text" inputmode="numeric" maxlength="1" class="dl-otp__box" value="{{ mb_substr((string) $value, $i, 1) }}" @if ($i === 0) id="{{ $uid }}" @endif @disabled($disabled) @readonly($readonly)>
    @endfor
</div>
