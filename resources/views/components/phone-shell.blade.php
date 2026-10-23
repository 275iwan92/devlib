<div {{ $attributes->class(['dl-phone', 'dl-phone--contained' => $contained]) }} {!! $dl() !!} data-dl-phone>
    <header class="dl-phone__top">
        <button type="button" class="dl-phone__burger" data-dl-phone-menu aria-label="{{ \Devlib\Lang::text('Menu') }}" aria-haspopup="dialog" aria-expanded="false"><i class="fa-solid fa-bars" aria-hidden="true"></i></button>
        @if ($back)
            <a class="dl-phone__back" href="{{ $back }}" aria-label="{{ \Devlib\Lang::text('Back') }}"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
        @endif
        <h1 class="dl-phone__title">{{ $title !== '' ? \Devlib\Lang::text($title) : $brand }}</h1>
        @isset($actions)<div class="dl-phone__acts">{{ $actions }}</div>@endisset
    </header>
    <main class="dl-phone__main{{ $frame ? ' dl-phone__main--frame' : '' }}">
        @if ($frame)
            <iframe class="dl-phone__frame" src="{{ $frame }}" title="{{ \Devlib\Lang::text($title) }}"></iframe>
        @else
            {{ $slot }}
        @endif
    </main>
    <div class="dl-phone__sheet" data-dl-phone-sheet hidden>
        <div class="dl-phone__panel" role="dialog" aria-modal="true" aria-label="{{ \Devlib\Lang::text('Menu') }}" tabindex="-1" data-dl-phone-panel>
            <button type="button" class="dl-phone__close" data-dl-phone-close aria-label="{{ \Devlib\Lang::text('Close') }}"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
            <div class="dl-phone__who">
                <strong>{{ $user['name'] ?? '' }}</strong>@if (! empty($user['role']))<span>{{ $user['role'] }}</span>@endif
            </div>
            @if ($tabs)
                <nav class="dl-phone__main-menu" aria-label="{{ \Devlib\Lang::text('Menu') }}">
                    @foreach ($tabs as $t)
                        <a class="dl-phone__big{{ $active === $t['key'] ? ' is-active' : '' }}" href="{{ $t['url'] }}" @if ($active === $t['key']) aria-current="page" @endif><i class="{{ $t['icon'] ?? 'fa-solid fa-circle' }}" aria-hidden="true"></i><span>{{ \Devlib\Lang::text($t['label']) }}</span></a>
                    @endforeach
                </nav>
            @endif
            @foreach ($modules as $m)
                <section class="dl-phone__grp">
                    <h2 class="dl-phone__gh"><i class="{{ $m['icon'] ?? 'fa-solid fa-folder' }}" aria-hidden="true"></i> {{ \Devlib\Lang::text($m['label']) }}</h2>
                    @foreach ($flat($m['children'] ?? []) as $it)
                        <a class="dl-phone__row" href="{{ $it['url'] }}"><i class="{{ $it['icon'] ?? 'fa-solid fa-circle' }}" aria-hidden="true"></i><span>{{ \Devlib\Lang::text($it['label']) }}</span>@if (! empty($it['badge']))<em>{{ $it['badge'] }}</em>@endif</a>
                    @endforeach
                </section>
            @endforeach
            @if ($links)
                <section class="dl-phone__grp">
                    @foreach ($links as $l)
                        <a class="dl-phone__row" href="{{ $l['url'] }}"><i class="{{ $l['icon'] ?? 'fa-solid fa-circle' }}" aria-hidden="true"></i><span>{{ \Devlib\Lang::text($l['label']) }}</span></a>
                    @endforeach
                </section>
            @endif
            @if ($deviceAction)
                <form class="dl-phone__dev" method="POST" action="{{ $deviceAction }}">
                    @csrf
                    <input type="hidden" name="ui" value="desktop">
                    <x-dl::button type="submit" variant="secondary" icon="fa-desktop" label="Desktop view" block />
                </form>
            @endif
            @if ($logoutAction)
                <form method="POST" action="{{ $logoutAction }}" class="dl-phone__out">
                    @csrf
                    <x-dl::button type="submit" variant="secondary" icon="logout" label="Sign out" block />
                </form>
            @endif
        </div>
        <div class="dl-phone__veil" data-dl-phone-close></div>
    </div>
</div>
