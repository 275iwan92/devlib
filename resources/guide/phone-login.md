## Overview

`<x-dl::phone-login>` is the sign-in page for a phone held upright. It wraps `x-dl::login-box` (fields, error message, button) in one fluid column from 320 to about 440 px wide: fields 52 px high with 16 px text, a large Sign in button, safe-area padding, and a small "Desktop view" link that saves the choice `ui=desktop` and goes back to the desktop sign-in.

The project chooses this page when `Devlib\Device::fromRequest` says `hp`, and the desktop sign-in (`x-dl::frame` + `x-dl::login-box` + `x-dl::phone-offer`) otherwise.

### Generated markup

```html
<main class="dl-plogin" data-dl="x-dl::phone-login">
  …slot (status message)…
  <div class="dl-loginbox">…</div>
  <form class="dl-plogin__desk" method="POST" action="..."><input type="hidden" name="ui" value="desktop"><button>Desktop view</button></form>
</main>
```

Extra attributes (`class`, `id`, `data-*`) go on the root element; `can="module.action"` works as for every component.

## Props

Constructor of `Devlib\Components\PhoneLogin` (9 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `action` | `string`, `''` | POST address of the sign-in form (passed to `x-dl::login-box`). |
| `error` | `?string`, `null` | Error code or message for the login box (see `x-dl::login-box`); `null` = none. |
| `username` | `string`, `''` | Value put back in the username field after a failed attempt. |
| `forgot-url` | `string`, `'#'` | Address of the "Forgot password?" link. |
| `brand` | `?string`, `null` | Brand name; `null` = `Brand::identity()`. |
| `tagline` | `?string`, `null` | Tagline under the brand; `null` = the site default. |
| `show` | `string`, `'brand, title, username, password, forgot, button'` | Parts of the login box (see `x-dl::login-box`). |
| `device-action` | `?string`, `null` | POST address that stores `ui=desktop` (the "Desktop view" link). `null` = no link. |
| `contained` | `bool`, `false` | `false` = as tall as the screen (`100dvh`); `true` = 640 px, for the catalog demo. |

## Behavior

- One column, content centred vertically, `max-width: 440px`; the page scrolls inside itself when the screen is short (keyboard open). There is no breakpoint inside 320 to 440 px.
- Heights use `dvh`, so the browser address bar does not hide the bottom; do not rely on an exact height.
- The error message and the field validation come from `x-dl::login-box`.

## Data and events

- Browser to server: the sign-in form (as `x-dl::login-box`) and the small form posting `ui=desktop` to `device-action`.

## Rules and pitfalls

- The slot is drawn above the box (for a status message such as `x-dl::alert`).
- Without `device-action` a user cannot leave the phone sign-in; give the link unless the project has no desktop view.
- Related lint rules: none.

## Example

```blade
<x-dl::phone-login :action="route('login.attempt')" :error="$error" :username="$username" :forgot-url="route('password.request')" :device-action="route('ui.set')" />
```

## Related

- `x-dl::login-box`, `x-dl::phone-offer`, `x-dl::device-pick`, `x-dl::phone-shell`.
- `skills/devlib/phone.md`: adoption guide.
