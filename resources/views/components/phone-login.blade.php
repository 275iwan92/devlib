<main {{ $attributes->class(['dl-plogin', 'dl-plogin--contained' => $contained]) }} {!! $dl() !!}>
    {{ $slot }}
    <x-dl::login-box :show="$show" :action="$action" :error="$error" :username="$username" :forgot-url="$forgotUrl" :brand="$brand" :tagline="$tagline" />
    @if ($deviceAction)
        <form class="dl-plogin__desk" method="POST" action="{{ $deviceAction }}">
            @csrf
            <input type="hidden" name="ui" value="desktop">
            <button type="submit"><i class="fa-solid fa-desktop" aria-hidden="true"></i> {{ \Devlib\Lang::text('Desktop view') }}</button>
        </form>
    @endif
</main>
