@php
    $opts = array_is_list($options) ? array_combine($options, $options) : $options;
    $empty = $placeholder ?? \Devlib\Lang::t('f_choose');
    $add = $addConfig();
    $addLabel = $add ? ($add['label'] !== '' ? \Devlib\Lang::text($add['label']) : \Devlib\Lang::t('sa_add')) : '';
@endphp
@if ($add)
<div class="dl-selectadd" data-dl-select-add data-url="{{ $add['url'] }}" data-value-key="{{ $add['value-key'] }}" data-label-key="{{ $add['label-key'] }}">
@endif
<div class="dl-dd" data-dl-select data-empty="{{ $empty }}">
    <input type="hidden" name="{{ $name }}" value="{{ $value }}" data-dl-value>
    <button type="button" id="{{ $uid }}" class="dl-dd__btn" data-dl-dd-toggle @disabled($disabled || $readonly)>
        <span data-dl-dd-text>{{ $opts[$value] ?? $empty }}</span><span>▼</span>
    </button>
    <div class="dl-dd__panel" data-dl-dd-panel>
        <div class="dl-dd__search"><input type="text" placeholder="{{ \Devlib\Lang::text($searchPlaceholder) }}" data-dl-dd-search></div>
        <div class="dl-dd__list" data-dl-dd-list>
            @foreach ($opts as $v => $l)
                <div class="dl-dd__item" data-value="{{ $v }}">{{ $l }}</div>
            @endforeach
        </div>
    </div>
</div>
@if ($add)
    <button type="button" class="dl-selectadd__btn" data-dl-sa-btn aria-haspopup="dialog" aria-label="{{ $addLabel }}" title="{{ $addLabel }}"><i class="fa-solid fa-plus"></i></button>
    <x-dl::modal :id="$uid.'-add'" :title="$add['title']" :icon="$add['icon']" size="sm" data-dl-sa-modal>
        @if ($add['note'])<p class="dl-note">{{ \Devlib\Lang::text($add['note']) }}</p>@endif
        @foreach ($add['fields'] as $f)
            @if ($f['type'] === 'radio' || $f['type'] === 'select')
                @if ($f['type'] === 'radio')
                    <x-dl::field.radio :name="$f['name']" :label="$f['label']" :required="(bool) $f['required']" :options="$f['options']" :value="$f['value'] ?? null" />
                @else
                    <x-dl::field.select :name="$f['name']" :label="$f['label']" :required="(bool) $f['required']" :options="$f['options']" :value="$f['value'] ?? null" :placeholder="$f['placeholder'] ?? null" />
                @endif
            @else
            <x-dl::field.text :name="$f['name']" :label="$f['label']" :required="(bool) $f['required']" :type="$f['type'] === 'tel' ? 'tel' : 'text'" :multiline="$f['type'] === 'textarea'"
                :maxlength="isset($f['maxlength']) ? (int) $f['maxlength'] : null" :placeholder="$f['placeholder'] ?? null" />
            @endif
        @endforeach
        <div class="dl-selectadd__err" role="alert" data-dl-sa-err></div>
        <x-slot:footer>
            <button type="button" class="dl-btn dl-btn--ghost dl-btn--md" data-dl-close-modal>{{ \Devlib\Lang::t('cancel') }}</button>
            <button type="button" class="dl-btn dl-btn--primary dl-btn--md" data-dl-sa-save>{{ \Devlib\Lang::t('save') }}</button>
        </x-slot:footer>
    </x-dl::modal>
</div>
@endif
