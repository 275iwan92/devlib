@if ($readonly && ! $disabled && $value)<input type="hidden" name="{{ $name }}" value="1">@endif
<label class="dl-choice dl-choice--single"><input id="{{ $uid }}" type="checkbox" name="{{ $name }}" value="1" @checked($value) @disabled($disabled || $readonly)> {{ $text }}</label>
