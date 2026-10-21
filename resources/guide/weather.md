## Overview

`<x-dl::weather>` is a small widget (icon, temperature, description and city) showing the current weather of one fixed place. The server prints only a placeholder (`⛅`, `--°C`, "loading weather…" and the city). The data is fetched by the visitor's browser, directly from the free Open-Meteo forecast service (`api.open-meteo.com`, no API key), once when the page is mounted.

Use it as decoration or context on a home screen or login frame ("it is 29°C in Surabaya"). It is not a forecast, not a data source for business logic and not the visitor's local weather: the place is a prop, not the visitor's position.

Pick another object when you need:

- `x-dl::greeting-card` or `x-dl::dashboard`: they already include a weather block from `board['weather']`.
- A weather value stored in your own data: fetch it on the server and show it with `x-dl::stat-box` or `x-dl::kpi`.
- The visitor's own location: not provided (see `x-dl::geo-clock` for the only geolocation object, which does not show weather).

### Generated markup

```html
<div class="dl-weather" data-dl="x-dl::weather city=Surabaya" data-dl-class="Weather ← DevlibComponent"
     data-dl-src="internet: api.open-meteo.com (-7.2575, 112.7521)" data-dl-weather data-lat="-7.2575" data-lon="112.7521">
  <span class="dl-weather__icon" data-dl-weather-icon>⛅</span>
  <div>
    <div class="dl-weather__temp" data-dl-weather-temp>--°C</div>
    <div class="dl-weather__desc"><span data-dl-weather-desc>loading weather…</span> · Surabaya</div>
  </div>
</div>
```

Extra HTML attributes (`id`, `class`, `style`, `data-*`) go on this root `div`; `can="module.action"` also works (without the ability nothing is rendered, and no request is made).

## Props

Constructor of `Devlib\Components\Weather` (3 props). Bind numbers with `:lat="-7.25"`.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `city` | `string`, `'Surabaya'` | Label printed after the description (`Cloudy · Surabaya`). Display only: it is NOT sent to Open-Meteo and is not geocoded; the data comes from `lat` / `lon`. It is not a translated prop (shown as given). Keep it consistent with the coordinates. |
| `lat` | `float`, `-7.2575` | Latitude in decimal degrees (-90 to 90), written to `data-lat` and sent to the service. Not range-checked: a wrong value gives an error response and the widget shows "weather unavailable". |
| `lon` | `float`, `112.7521` | Longitude in decimal degrees (-180 to 180), written to `data-lon` and sent to the service. Same rules as `lat`. |

The three defaults are one place; the sample site overrides them with `config('devlib.weather')` (`['city', 'lat', 'lon']`) and passes them into the greeting card and the login frame. In your project read the office / site location from your own settings and pass all three props together.

## Behavior

### Where the data comes from, what is sent

- One browser request per widget, when the component mounts (page load, or `Devlib.mount(root)` for HTML inserted later):

  `GET https://api.open-meteo.com/v1/forecast?latitude=<lat>&longitude=<lon>&current=temperature_2m,weather_code&timezone=auto`

- What leaves the visitor's machine: the coordinates of the configured place (not the visitor's own) and the normal data any web request carries to the third party: the visitor's IP address, user-agent and the page origin/referrer according to the browser policy. No cookie is sent (cross-origin `fetch` without credentials), no API key, no custom header, no user data from your application.
- Nothing goes through your server, so your server never sees the weather and cannot cache it. It also means: the Content Security Policy of the host application must allow `connect-src https://api.open-meteo.com`, a network that blocks the host shows "weather unavailable", and the Open-Meteo terms of use apply (check them for commercial use and for the number of visitors).
- What is read from the answer: `current.temperature_2m` (degrees Celsius) and `current.weather_code` (WMO code).

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
- When the visitor switches language (`dl:lang` on `document`) the widget repaints from the stored answer without a new request. Before the answer arrives nothing is repainted.
- The initial text "loading weather…" is translated on the server (render time language). The failure text uses the dictionary key `wx_unavailable` (`cuaca tak tersedia` / `weather unavailable`) in the current browser language. Both need `x-dl::theme-boot` on the page (it prints the dictionary); without it the failure shows the raw key.
- `city` is not translated.

### Offline, failure and caching

- Offline, blocked, timeout of the browser, HTTP error or a JSON answer without `current`: the `catch` sets the description to "weather unavailable". The icon stays `⛅` and the temperature stays `--°C`. There is no retry.
- There is no `r.ok` check and no abort timeout: a server that never answers keeps "loading weather…" on screen until the browser gives up.
- There is no refresh interval and no cache in DevLib. The widget shows the value fetched when the page was loaded; a page left open for hours stays at that value. Whatever the browser's HTTP cache does with the Open-Meteo response headers is the only caching.
- Several weather widgets on one page make several requests (no sharing).

### Update interval and cleanup

No interval is used. The `dl:lang` listener on `document` is registered once per widget and never removed (no destroy step), so a widget removed from the DOM stays referenced by the listener until the page unloads.

### Accessibility as implemented

Plain `div`/`span` text, no `role` and no `aria-live`: the value arrives silently. The emoji icon has no text alternative, but the description next to it carries the same meaning. The style (`.dl-weather`: translucent dark glass with a blur) is made for photo or dark backgrounds.

## Data and events

- Server to browser: `data-lat`, `data-lon`.
- Browser to Open-Meteo: the GET above. Browser to your server: nothing.
- No `dl:*` event is emitted. It listens to `dl:lang`.
- `el.dlComponent` is the `Weather` instance with `url`, `w` (`{temp, code}` or `null`), `init()` and `paint()`. Hooks: `[data-dl-weather]` (mount selector), `[data-dl-weather-icon]`, `[data-dl-weather-temp]`, `[data-dl-weather-desc]`.
- Override with `Devlib.define("Weather", MyWeather)` (all widgets) or `data-dl-js="MyWeather"` + `Devlib.register(MyWeather)` on one element; typical reasons: another provider, a proxy URL on your own server, a refresh timer.

## Rules and pitfalls

- State in your privacy notice that the browser contacts a third party (Open-Meteo) for the weather widget. If that is not acceptable, do not use the widget or override the class to call your own endpoint.
- Do not use it to drive any decision. It is decoration, unauthenticated, uncached and may be absent.
- Always pass `city`, `lat` and `lon` as one consistent set. A city name with the default coordinates shows the weather of Surabaya under another name.
- Do not rely on the widget for the visitor's local weather: the place is fixed by the props.
- Include `x-dl::theme-boot` in the layout (dictionary and language).
- Allow `https://api.open-meteo.com` in the Content Security Policy and in any corporate proxy rules.
- Related lint rules: none (`src/Lint` has no rule for this object).
- Code oddity (not fixed): the `fetch` in `resources/assets/js/components/weather.js:11` has no `r.ok` check, no timeout and no refresh. An error answer such as HTTP 429 with a JSON body only reaches the `catch` by accident (`j.current` is undefined and throws a `TypeError`).
- Code oddity (not fixed): WMO codes 85 and 86 (snow showers) fall into the last row of the table (`resources/assets/js/core.js:311`, limit 99) and are shown as "Thunderstorm"; code 1 ("mainly clear") is shown as "Partly cloudy".
- Code oddity (not fixed): the description line `Devlib.lang === 'id' ? m[2] : m[3]` (`resources/assets/js/components/weather.js:21`) shows English for any language other than `id`, while the salutation and the failure text follow the dictionary for other languages.
- Code oddity (not fixed): the `dl:lang` listener (`resources/assets/js/components/weather.js:13`) is never removed, see the cleanup note above.

## Example

From the greeting card (`resources/views/components/greeting-card.blade.php:41-43`) and the login frame (`resources/views/components/frame.blade.php:23`):

```blade
{{-- Default place --}}
<x-dl::weather />

{{-- Explicit place; all three props together --}}
<x-dl::weather city="Surabaya" :lat="-7.2575" :lon="112.7521" />

{{-- From a settings array, as the greeting card does --}}
@if (! empty($board['weather']))
    <x-dl::weather :city="$board['weather']['city']" :lat="$board['weather']['lat']" :lon="$board['weather']['lon']" />
@endif
```

## Related

- `x-dl::greeting-card`, `x-dl::dashboard`: embed this widget from `board['weather']`.
- `x-dl::frame`: login frame; `weather` is one of its widgets (props `city`, `lat`, `lon` of the frame).
- `x-dl::greeting`, `x-dl::clock`: the other live header widgets with the same dark-surface style.
- `x-dl::theme-boot`: prints the dictionary and language values this widget needs.
- `skills/devlib/components.md`: generated prop list.
