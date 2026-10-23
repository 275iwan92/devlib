## Overview

`<x-dl::weather>` is a small widget (icon, temperature, description and city) showing the current weather. **By default the place is the position of the visitor's device** (decision of the owner, 2026-10-23): the browser asks for the device location once, gets the weather from Open-Meteo and the city name from a reverse geocoding service. The props `city` / `lat` / `lon` are only the **fallback place**: they are used when the location is refused, not available, the page is not HTTPS (or localhost), or the answer times out. With no fallback and no location the card is not shown at all.

The server prints only a placeholder (`⛅`, `--°C`, "loading weather…"). The data is fetched by the visitor's browser.

Use it as decoration or context on a home screen ("it is 29°C in Malang"). It is not a forecast and not a data source for business logic.

Pick another object when you need:

- `x-dl::greeting-card` or `x-dl::dashboard`: they already include a weather block from `board['weather']`.
- A weather value stored in your own data: fetch it on the server and show it with `x-dl::stat-box` or `x-dl::kpi`.
- A clock for the visitor's location: `x-dl::geo-clock` (it does not show weather).

### Generated markup

```html
<div class="dl-weather" data-dl="x-dl::weather city=Malang" data-dl-class="Weather ← DevlibComponent"
     data-dl-src="internet: api.open-meteo.com (device location)" data-dl-weather data-locate="1" data-lat="-7.98" data-lon="112.63" data-city="Malang">
  <span class="dl-weather__icon" data-dl-weather-icon>⛅</span>
  <div>
    <div class="dl-weather__temp" data-dl-weather-temp>--°C</div>
    <div class="dl-weather__desc"><span data-dl-weather-desc>loading weather…</span><span data-dl-weather-city></span></div>
  </div>
</div>
```

Without a fallback place the root has the `hidden` attribute (and no `data-lat` / `data-lon` / `data-city`); the script shows it when the device location works. With `locate="false"` the city is printed by the server (` · Malang`). Extra HTML attributes (`id`, `class`, `style`, `data-*`) go on the root `div`; `can="module.action"` also works (without the ability nothing is rendered, and no request is made).

## Props

Constructor of `Devlib\Components\Weather` (5 props). Bind numbers with `:lat="-7.25"`.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `locate` | `bool`, `true` | `true` = use the device position (see Behavior). `false` = always the fixed place of `city` / `lat` / `lon`, no location request (use it where asking for the location would be out of place, for example a login page; `x-dl::frame` does this). |
| `city` | `?string`, `null` | Fallback label printed after the description. Display only: it is NOT sent to Open-Meteo and is not geocoded. Not translated. |
| `lat` | `?float`, `null` | Fallback latitude (-90 to 90). Not range-checked: a wrong value gives an error response and the widget shows "weather unavailable". |
| `lon` | `?float`, `null` | Fallback longitude (-180 to 180). Same rules as `lat`. |
| `variant` | `string`, `'glass'` | Look. `glass` = translucent dark glass for photos and dark cards (the original look). `card` = small solid dark-blue card for a light page. `chip` = small pill with icon, temperature and city (`25°C · Malang`, no description) for phone screens. The value is the CSS class `dl-weather--<variant>` (none for `glass`). |

Pass `city`, `lat` and `lon` as one consistent set. `lat` + `lon` without `city` shows the weather without a place name. The owner decides where the fallback comes from (for example the company screen of the project); DevLib has no place setting.

## Behavior

### The chain: where the place comes from

1. **Cache.** The last device answer is kept in `localStorage` (`dl-wx`: coordinates rounded to 2 decimals, city, temperature, code, time) for **1 hour**; a fresh one is shown at once with no request at all.
2. **Device location** (only when `locate` is true, the page is a secure context, `navigator.geolocation` exists and the visitor has not refused in the last 7 days): `getCurrentPosition` with `maximumAge` 6 hours, `timeout` 8 seconds, no high accuracy (city level is enough). It runs once when the page opens; there is no extra button. A refusal (error code 1) is remembered in `localStorage` (`dl-wx-no`) for 7 days so the visitor is not asked again and again.
3. **Fallback place** from `city` / `lat` / `lon`, when step 2 is skipped or fails (refused, unavailable, timeout, plain `http://`).
4. **Nothing**: the card stays hidden.

Geolocation works only on **HTTPS or localhost**. A site on plain `http://192.168.x.x` or `http://name.test` never gets the permission, so there the fallback place is used (and without a fallback nothing is shown) until HTTPS is installed.

### Where the data comes from, what is sent

- Weather: `GET https://api.open-meteo.com/v1/forecast?latitude=<lat>&longitude=<lon>&current=temperature_2m,weather_code&timezone=auto`.
- City name (only for the device location): `GET https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=<lat>&longitude=<lon>&localityLanguage=id|en`. Chosen because it needs no key, answers cross-origin, and its fair-use policy is written for exactly this use (called from the end user's browser with the browser's own location). It must not be called from a server (the address can be banned). If it fails the card shows the weather without a city name. Other services were not chosen: Nominatim limits use to about one request per second and requires an identifying referrer, which a page of our own cannot guarantee.
- What leaves the visitor's machine: for the device location, the coordinates **rounded to 2 decimals** (about 1 km) go to both services, together with what every web request carries (IP address, user-agent, origin). On desktop Chrome the location is based on Wi-Fi and IP, so it is accurate to city level only. With a fixed place only Open-Meteo is called, with the configured coordinates.
- Nothing goes through your server, so your server never sees the weather or the location. The Content Security Policy of the host application must allow `connect-src https://api.open-meteo.com https://api.bigdatacloud.net`.
- What is read from the answer: `current.temperature_2m` (degrees Celsius) and `current.weather_code` (WMO code); from the city answer `city`, else `locality`, else `principalSubdivision`.

### Rendering the answer

- Temperature: `Math.round(temp) + '°C'`. Celsius only; there is no unit prop.
- Icon and description come from the WMO table in `resources/assets/js/core.js:309-311` (the first row whose limit is not below the code):

| Code range | Icon | `id` | `en` |
|---|---|---|---|
| 0 | clear sky | Cerah | Clear |
| 1-2 | sun behind cloud | Berawan sebagian | Partly cloudy |
| 3 | cloud | Berawan | Cloudy |
| 4-48 | fog | Berkabut | Fog |
| 49-57 | light rain | Gerimis | Drizzle |
| 58-67 | rain | Hujan | Rain |
| 68-77 | snow | Salju | Snow |
| 78-82 | heavy rain | Hujan deras | Showers |
| 83-99 | storm | Badai petir | Thunderstorm |

### Language switching

- The description follows `Devlib.lang`: `id` shows the Indonesian column, every other language shows the English column (the table is not routed through the dictionary).
- When the visitor switches language (`dl:lang` on `document`) the widget repaints from the stored answer without a new request. The city name keeps the language it was fetched in.
- The initial text "loading weather…" is translated on the server (render time language). The failure text uses the dictionary key `wx_unavailable` (`cuaca tak tersedia` / `weather unavailable`) in the current browser language. Both need `x-dl::theme-boot` on the page.
- `city` (the fallback label) is not translated.

### Offline, failure and caching

- Offline, blocked, timeout of the browser, HTTP error or a JSON answer without `current`: the description shows "weather unavailable". The icon stays `⛅` and the temperature stays `--°C`. There is no retry.
- There is no `r.ok` check and no abort timeout on the two fetches: a server that never answers keeps "loading weather…" on screen until the browser gives up.
- Only the device answer is cached (1 hour, see the chain). There is no refresh interval: a page left open for hours stays at the value fetched at load. Several weather widgets on one page each run the chain (the first one fills the cache for the next page load, not for the same page).

### Update interval and cleanup

No interval is used. The `dl:lang` listener on `document` is registered once per widget and never removed (no destroy step).

### Accessibility as implemented

Plain `div`/`span` text, no `role` and no `aria-live`: the value arrives silently. The emoji icon has no text alternative, but the description next to it carries the same meaning. The style (`.dl-weather`: translucent dark glass with a blur) is made for photo or dark-card backgrounds. The browser's own permission prompt is the only prompt; it appears once when the page opens (see Rules).

## Data and events

- Server to browser: `data-locate`, and `data-lat` / `data-lon` / `data-city` when a fallback place is given.
- Browser to third parties: the GETs above. Browser to your server: nothing.
- Browser storage: `localStorage` keys `dl-wx` (cache of the last device answer) and `dl-wx-no` (time of the last refusal).
- No `dl:*` event is emitted. It listens to `dl:lang`.
- `el.dlComponent` is the `Weather` instance with `w` (`{temp, code}` or `null`), `init()`, `load()`, `cityOf()`, `paint()`. The chain is the pure static function `Weather.place(env)` (testable without a browser). Hooks: `[data-dl-weather]` (mount selector), `[data-dl-weather-icon]`, `[data-dl-weather-temp]`, `[data-dl-weather-desc]`, `[data-dl-weather-city]`.
- Override with `Devlib.define("Weather", MyWeather)` (all widgets) or `data-dl-js="MyWeather"` + `Devlib.register(MyWeather)` on one element; typical reasons: another provider, a proxy URL on your own server, a refresh timer.

## Rules and pitfalls

- **Privacy notice:** state that the browser sends the (rounded) device location to Open-Meteo and to BigDataCloud for this widget, and that the browser asks the visitor for permission. If that is not acceptable, use `:locate="false"` or override the class.
- **Decision of the owner (2026-10-23, small breaking change):** before this change the place was always the fixed props and `<x-dl::weather />` without props showed Surabaya. Now the device position is the default and `<x-dl::weather />` without props and without location shows nothing. Where a fixed place must stay (a login page), pass `:locate="false"`.
- The permission prompt appears when the page opens, before the visitor reads anything. If this is unwelcome for a page, use `:locate="false"` there.
- HTTPS is required for the device location. On plain `http://` (LAN IP, `.test` host, an Ubuntu server without a certificate) the fallback place is used, silently.
- Do not use it to drive any decision. It is decoration, unauthenticated and may be absent.
- Include `x-dl::theme-boot` in the layout (dictionary and language).
- Allow `https://api.open-meteo.com` and `https://api.bigdatacloud.net` in the Content Security Policy and in any corporate proxy rules.
- Related lint rules: none (`src/Lint` has no rule for this object).
- Code oddity (not fixed): the fetches have no `r.ok` check, no timeout and no refresh; an error answer with a JSON body only reaches the `catch` by accident (`j.current` is undefined and throws).
- Code oddity (not fixed): WMO codes 85 and 86 (snow showers) fall into the last row of the table (`resources/assets/js/core.js:311`, limit 99) and are shown as "Thunderstorm"; code 1 ("mainly clear") is shown as "Partly cloudy".
- Code oddity (not fixed): the description line shows English for any language other than `id`, while the salutation and the failure text follow the dictionary for other languages.
- Code oddity (not fixed): the `dl:lang` listener is never removed, see the cleanup note above.

## Example

```blade
{{-- Device position; nothing is shown when the location is refused --}}
<x-dl::weather />

{{-- Device position with a fallback place (location refused, plain http, timeout) --}}
<x-dl::weather city="Malang" :lat="-7.9797" :lon="112.6304" />

{{-- Always this place, no location request --}}
<x-dl::weather :locate="false" city="Surabaya" :lat="-7.2575" :lon="112.7521" />

{{-- From a settings array, as the greeting card does (key `locate` optional, default true) --}}
@if (! empty($board['weather']))
    <x-dl::weather :locate="$board['weather']['locate'] ?? true" :city="$board['weather']['city'] ?? null" :lat="$board['weather']['lat'] ?? null" :lon="$board['weather']['lon'] ?? null" />
@endif
```

## Related

- `x-dl::greeting-card`, `x-dl::dashboard`: embed this widget from `board['weather']`.
- `x-dl::frame`: login frame; its `weather` widget always uses the fixed place of the frame props (`:locate="false"`).
- `x-dl::greeting`, `x-dl::clock`: the other live header widgets with the same dark-surface style.
- `x-dl::theme-boot`: prints the dictionary and language values this widget needs.
- `skills/devlib/components.md`: generated prop list.
