## Overview

`<x-dl::net-line>` is one very small, faint line `client IP → server IP` (for example `10.238.189.21 → 10.238.189.159`) with a connection status. It sits under the user name in the Layer Menu footer, so when a user complains the support person can see from which address and to which server the person works. Host names are not in the line (they are long); they show in the tooltip when the line is touched or hovered.

Three states: **normal** (the line only), **server unreachable** (the server IP blinks pink and the small text "Server unreachable" appears under the line; it disappears by itself when the server answers again) and **device offline** (the client IP blinks yellow and "Device offline" appears). With `prefers-reduced-motion` nothing blinks: only the colour and the text remain.

Pick another object when you need:

- A host / IP text that the application writes itself (development hosts): `x-dl::env-badge`. The Layer Menu uses `net-line` instead of it only when the application binds `devlib.net`.
- A status label with a tone: `x-dl::badge`.
- The measurements of the screen of a device: `x-dl::screen-info`.

### Generated markup

```html
<span class="dl-netline" data-dl="x-dl::net-line" data-dl-net-line data-state="ok" data-ping="/devlib/ping" data-interval="10" data-t="{...}">
  <span class="dl-netline__row" title="Client: PC-GUDANG&#10;Server: SRV-UTAMA"><span data-nl="client">10.238.189.21</span> → <span data-nl="server">10.238.189.159</span></span>
  <span class="dl-netline__st" role="status" aria-live="polite" data-nl-status></span>
</span>
```

The root gets `data-state="ok|server|offline"` from the script. It renders nothing at all when there is no client IP and no server IP. Extra HTML attributes (`id`, `class`, `style`, `data-*`) go on the root `span`; `can="module.action"` works as for every component.

## Props

Constructor of `Devlib\Components\NetLine` (6 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `client-ip` | `?string`, `null` | IP shown on the left. `null` = `client_ip` of the container binding `devlib.net`. |
| `server-ip` | `?string`, `null` | IP shown on the right. `null` = `server_ip` of the binding. |
| `client-host` | `?string`, `null` | Client host name, only in the tooltip. `null` = `client_host` of the binding. |
| `server-host` | `?string`, `null` | Server host name, only in the tooltip. `null` = `server_host` of the binding. |
| `ping-url` | `?string`, `null` | Address the browser checks. `null` = the DevLib route `devlib.ping` (`GET /devlib/ping`). Give your own address when the application already has a light one (for example a session ping). |
| `interval` | `int`, `10` | Seconds between checks. Values under 3 become 3. |

## Behavior

### Where the data comes from

The application binds the data once, usually in a service provider: `app()->bind('devlib.net', fn () => \Devlib\NetworkInfo::of(request()));`. `NetworkInfo::of()` gives `client_ip`, `client_host`, `server_ip`, `server_host`. `x-dl::layer-menu` draws `<x-dl::net-line />` in the user row when `devlib.net` is bound, and the older `x-dl::env-badge part="where"` line otherwise (applications that do not bind it are unchanged).

### The check

- The browser calls the ping address with `fetch`, `cache: 'no-store'`, a cache-busting query parameter and a 5 second timeout (`AbortController`). Only one request is in flight at a time.
- Any answer below HTTP 500 means "the server answers". A timeout, a network error or a 5xx counts as a failure. **Two failures in a row** = server unreachable; **one success** = normal. After a failure the next check comes sooner (3 seconds at most) so that both the fall and the recovery show quickly.
- Nothing is requested while the tab is hidden (`document.visibilityState`); the check runs again at once when the tab is shown.
- `navigator.onLine === false` and the `offline` event show "device offline" without any request; the `online` event checks at once.

### The limit of the check

A browser cannot tell a broken LAN from a broken internet connection, and it cannot see the router. The line only says whether **our server** answered the ping. "Server unreachable" can mean the server is down, the LAN or Wi-Fi is cut, or a proxy / firewall blocks the address. "Device offline" is what the browser itself reports about the device network.

### The default ping route

`GET /devlib/ping` (route name `devlib.ping`) is registered by `DevlibServiceProvider`: status 204, `Cache-Control: no-store`, no session, no cookie, no login, no data, throttled to 120 requests per minute per address. It is generic and has no application code.

### Text and language

"Server unreachable" and "Device offline" are in the dictionary; the script receives the translated texts from the server (`data-t`).

### Accessibility as implemented

The status text is a `role="status"` / `aria-live="polite"` region. The blinking is `steps` at about 1 Hz and is switched off by `prefers-reduced-motion`. The pink and yellow colours are tokens (`--dl-net-bad`, `--dl-net-warn`) with darker values for the theme family with a light menu, so the contrast holds in light and dark themes.

## Data and events

- In: the `devlib.net` container binding or the props. Out: nothing is stored and no `dl:*` event is fired. The only request is the ping.
- Hooks: `data-dl-net-line` (root, script selector), `data-state`, `data-nl="client|server"`, `data-nl-status`, `data-nl-sim="ok|server|offline"` (any button on the page with this attribute forces a state and stops the real check on that page; used by the catalog page `p7-netline` to show each state without cutting the network).
- `el.dlComponent` is the instance (`set(state)`, `check()`).

## Rules and pitfalls

- Opt-in: the application decides who sees the line. The server IP is internal information; the project owner decides whether every user may see it.
- A cut network cannot report itself: when the line says "Server unreachable" the person may not be able to send a complaint from that device.
- The default route has no login on purpose (a login redirect would look like a failure). Do not put data in the ping answer.
- Use `ping-url` when the server answers on another path or host; a cross-origin address without CORS never answers readably, which counts as a failure.
- Related lint rules: none (the object has no own rule).

## Example

```blade
{{-- AppServiceProvider::boot --}}
app()->bind('devlib.net', fn () => \Devlib\NetworkInfo::of(request()));

{{-- Layer Menu: nothing else to write; the user row uses the line --}}
<x-dl::layer-menu :items="$menu" :user="$name" :role="$role" />

{{-- Anywhere else, with an own ping address --}}
<x-dl::net-line client-ip="10.0.0.21" server-ip="10.0.0.5" ping-url="{{ route('session.ping') }}" :interval="15" />
```

## Related

- `Devlib\NetworkInfo`: gives the four values (`of($request)`).
- `x-dl::layer-menu`: places the line in the user row.
- `x-dl::env-badge`: the older Host / Client text line, still used when `devlib.net` is not bound.
- Catalog page `p7-netline` (Simulate buttons).
