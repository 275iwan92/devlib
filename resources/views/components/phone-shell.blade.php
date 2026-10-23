<div {{ $attributes->class(['dl-phone', 'dl-phone--contained' => $contained]) }} {!! $dl() !!} data-dl-phone data-state="A"
     data-l-a="{{ \Devlib\Lang::t('phone_menu') }}" data-l-b="{{ \Devlib\Lang::t('phone_acts') }}" data-l-c="{{ \Devlib\Lang::t('phone_main') }}" data-l-dirty="{{ \Devlib\Lang::t('phone_dirty') }}" data-l-leave="{{ \Devlib\Lang::t('phone_leave') }}"
     data-l-menu="{{ \Devlib\Lang::t('phone_menu') }}" data-l-search="{{ \Devlib\Lang::t('phone_search') }}" data-l-ui="{{ json_encode(collect(['filter', 'export', 'paper', 'group', 'ink', 'view', 'cols', 'apply'])->mapWithKeys(fn ($k) => [$k => \Devlib\Lang::t('phone_'.$k)])) }}" data-l-other="{{ \Devlib\Lang::t('phone_other') }}">
    <button type="button" class="dl-phone__burger" data-dl-phone-menu aria-label="{{ \Devlib\Lang::t('phone_menu') }}" aria-expanded="false" aria-controls="dl-phone-bar"><i class="fa-solid fa-bars" aria-hidden="true"></i></button>
    <span class="dl-phone__live" role="status" aria-live="polite" data-dl-phone-live></span>
    @if ($frame)
        <header class="dl-phone__head">
            @if ($back)<a class="dl-phone__back" href="{{ $back }}" aria-label="{{ \Devlib\Lang::text('Back') }}"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>@endif
            <h1 class="dl-phone__title">{{ $title !== '' ? \Devlib\Lang::text($title) : $brand }}</h1>
            @isset($actions)<div class="dl-phone__acts">{{ $actions }}</div>@endisset
        </header>
    @endif
    {{-- Bilah di atas selebar layar: menggantikan tombol saat tampil. Dua isi: aksi layar (diisi JS dari halaman) dan menu utama; item Menu selalu paling kanan. --}}
    <div class="dl-phone__bar" id="dl-phone-bar" data-dl-phone-bar hidden>
        <div class="dl-phone__items" data-dl-phone-acts hidden></div>
        <nav class="dl-phone__items" data-dl-phone-main aria-label="{{ \Devlib\Lang::t('phone_main') }}" hidden>
            @foreach ($tabs as $t)
                <a class="dl-phone__q{{ $active === $t['key'] ? ' is-active' : '' }}" href="{{ $t['url'] }}" @if ($active === $t['key']) aria-current="page" @endif><i class="{{ $t['icon'] ?? 'fa-solid fa-circle' }}" aria-hidden="true"></i><span>{{ \Devlib\Lang::text($t['label']) }}</span></a>
            @endforeach
            <button type="button" class="dl-phone__q dl-phone__q--menu" data-dl-phone-more aria-expanded="false" aria-controls="dl-phone-list"><i class="fa-solid fa-bars" aria-hidden="true"></i><span>{{ \Devlib\Lang::t('phone_menu') }}</span></button>
        </nav>
    </div>
    <div class="dl-phone__drop" data-dl-phone-drop hidden>
        <div class="dl-phone__list" id="dl-phone-list" data-dl-phone-list>
            <section class="dl-phone__grp" data-dl-phone-other hidden>
                <h2 class="dl-phone__gh"><i class="fa-solid fa-ellipsis" aria-hidden="true"></i> {{ \Devlib\Lang::t('phone_other') }}</h2>
            </section>
            <div class="dl-phone__who">
                <strong>{{ $user['name'] ?? '' }}</strong>@if (! empty($user['role']))<span>{{ $user['role'] }}</span>@endif
                @if (app()->bound('devlib.net')) <x-dl::net-line /> @endif
            </div>
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
    {{-- Panel dari atas (di bawah bilah): isinya digambar JS dari kontrol halaman (saring, ekspor, kertas, grup). --}}
    <div class="dl-phone__drop" data-dl-phone-panel hidden>
        <div class="dl-phone__list" data-dl-phone-sheet></div>
        <div class="dl-phone__veil" data-dl-phone-close-panel></div>
    </div>
    <main class="dl-phone__main{{ $frame ? ' dl-phone__main--frame' : '' }}">
        @if ($frame)
            <iframe class="dl-phone__frame" src="{{ $frame }}" title="{{ \Devlib\Lang::text($title) }}"></iframe>
        @else
            <header class="dl-phone__head">
                @if ($back)<a class="dl-phone__back" href="{{ $back }}" aria-label="{{ \Devlib\Lang::text('Back') }}"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>@endif
                <h1 class="dl-phone__title">{{ $title !== '' ? \Devlib\Lang::text($title) : $brand }}</h1>
                @isset($actions)<div class="dl-phone__acts">{{ $actions }}</div>@endisset
            </header>
            <div class="dl-phone__body">{{ $slot }}</div>
        @endif
    </main>
</div>
