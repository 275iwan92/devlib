<div class="dl-kvedit" data-dl-kv data-name="{{ $name }}" role="group" @if ($label !== '') aria-labelledby="{{ $uid }}-lbl" @endif>
    <table class="dl-kvedit__table">
        <thead><tr><th>{{ \Devlib\Lang::text($keyLabel) }}</th><th>{{ \Devlib\Lang::text($valueLabel) }}</th><th></th></tr></thead>
        <tbody data-dl-kv-body>
            @foreach ((array) $value as $k => $v)
                <tr><td><input type="text" name="{{ $name }}[{{ $loop->index }}][key]" value="{{ $k }}" @disabled($disabled) @readonly($readonly)></td><td><input type="text" name="{{ $name }}[{{ $loop->index }}][value]" value="{{ $v }}" @disabled($disabled) @readonly($readonly)></td><td><button type="button" data-dl-kv-x @disabled($disabled || $readonly) title="{{ \Devlib\Lang::text('Delete row') }}">&times;</button></td></tr>
            @endforeach
        </tbody>
    </table>
    <button type="button" class="dl-kvedit__add" data-dl-kv-add @disabled($disabled || $readonly)>{{ \Devlib\Lang::text('+ Add row') }}</button>
</div>
