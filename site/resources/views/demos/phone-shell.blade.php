@php($ref = \App\Support\PhoneRef::class)
@php($s = array_key_exists((string) request('s'), $ref::SCREENS) ? request('s') : 'home')
<div class="dl-demo">
    <p class="dl-note">Live phone shell in a real phone-sized frame (the screen inside is a normal page in an iframe, so media queries and screen height are the phone's). Tap the floating menu button in the frame. Pick the device on the frame bar (Android phone 360 x 790, iPhone 393 x 852). Sample data only. Choose the screen:</p>
    <div class="dl-row">
        @foreach ($ref::SCREENS as $k => [$label])
            <x-dl::button :variant="$k === $s ? 'primary' : 'secondary'" size="sm" :href="'?s='.$k" :label="raw($label)" />
        @endforeach
    </div>
    <x-dl::device-frame :src="route('phone.ref', $s)" preset="android" />
</div>
