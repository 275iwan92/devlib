## Overview

`<x-dl::device-pick>` draws two large radio choices, "Phone view" and "Desktop view", under the field name `ui`. The choice travels with the form it sits in (the sign-in form through the `device` part of `x-dl::login-box`, or a form of your own, or a profile page of your own). The component only shows the choice; the project reads `ui` on POST and stores the cookie `ui` (`phone` or `desktop`; the old value `hp` is still read as `phone`).

The phone and desktop screens are two variants of the DEVICE, not an old and a new version, so a user can always switch. The guess can be wrong (a tablet, a phone in landscape, a narrow desktop window), so the user's choice always wins over the guess (`Devlib\Device::resolve`).

Pick another object when you need:

- The whole phone frame: `x-dl::phone-shell`.
- Language or theme choice: `x-dl::lang-switch`, `x-dl::theme-picker`.

### Generated markup

```html
<fieldset class="dl-devpick" data-dl="x-dl::device-pick" data-dl-class="DevicePick ← DevlibComponent" data-dl-src="cookie ui, Sec-CH-UA-Mobile, User-Agent">
  <legend class="dl-devpick__l">Display</legend>
  <label class="dl-devpick__o"><input type="radio" name="ui" value="phone" checked><span class="dl-devpick__t"><i class="fa-solid fa-mobile-screen"></i> Phone view</span></label>
  <label class="dl-devpick__o"><input type="radio" name="ui" value="desktop"><span class="dl-devpick__t"><i class="fa-solid fa-desktop"></i> Desktop view</span></label>
</fieldset>
```

Extra attributes (`class`, `id`, `data-*`) go on the `fieldset`. `can="module.action"` works as for every component.

## Props

Constructor of `Devlib\Components\DevicePick` (3 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `name` | `string`, `'ui'` | Field name of both radios. Keep `ui` unless the project reads another name. |
| `value` | `?string`, `null` | The preselected choice, `phone` or `desktop` (the old `hp` is accepted as `phone`). `null` (and any other string) = `Devlib\Device::view(request())`: the cookie `ui` if it is valid, otherwise the guess from `Sec-CH-UA-Mobile` / `User-Agent`. |
| `label` | `string`, `'Display'` | Legend text above the two choices. Translated through the dictionary. |

## Behavior

- No JavaScript: the two radios are real inputs hidden under their labels, so the whole label is the touch target (48 px high) and the keyboard works (arrows, focus ring).
- The server guess (`Device::detect`, `Device::view` for a whole request): `Sec-CH-UA-Mobile: ?1` means phone, `?0` means desktop; without that header the `User-Agent` decides: iPhone, iPod, Windows Phone and Android with the word "Mobile" are phones; iPad and Android tablets (no "Mobile") are desktop. Chrome sends `Sec-CH-UA-Mobile` only over HTTPS or on `localhost`; over plain HTTP on a LAN address the `User-Agent` is used.
- A phone that asks for the desktop site sends a desktop `User-Agent`, so it is guessed as `desktop`; the user can still pick the phone view.

### The view model behind the choice

DevLib has two canonical views: `phone` (a smartphone in portrait, Android and iOS) and `desktop` (laptop, desktop AND tablet). `Device::view($request)` returns `'phone'` or `'desktop'` and `Device::os($request)` returns `'android'`, `'ios'` or `'other'`. The order is: a per-request override (`Devlib\ViewOverride`, used by the catalog preview "View as"), the user's cookie `ui`, then the guess. iOS Safari sends no client hints, so iPhone is recognised by its `User-Agent`; an iPad (also an iPadOS that pretends to be a Mac) is `desktop`. `Device::bodyClass($request)` gives `dl-on-phone dl-os-android` or `dl-on-phone dl-os-ios` for the phone view and an empty string for desktop; `Device::viewport($request)` gives the content of the viewport meta tag (`viewport-fit=cover` added for the phone view only). Rules: `skills/devlib/views.md`.

## Data and events

- Browser to server: the form posts `ui=phone` or `ui=desktop`. Validate with `Rule::in(Device::CHOICES)` (add `array_keys(Device::ALIASES)` while old forms with `hp` may still be open) and store `Device::normalize($value)`.
- Cookie: the project stores it with Laravel's `cookie('ui', $value, minutes)` (encrypted by the `web` group); `Device::view` reads it back. A cookie set from JavaScript without encryption is ignored and the guess is used.
- No `dl:*` event, no `el.dlComponent` (no JS class).

## Rules and pitfalls

- The component does not store anything and does not switch the screen by itself. Without a project route that saves `ui`, choosing changes nothing.
- Do not use it to choose between an old and a new version of a screen; versions follow `skills/devlib/object.md` (one default, old versions are buried).
- Related lint rules: none.

## Example

Inside any form of your own:

```blade
<form method="POST" action="{{ route('ui.set') }}">
    @csrf
    <x-dl::device-pick />
    <x-dl::button type="submit" label="Apply" />
</form>
```

On the sign-in screen, as a part of the login box:

```blade
<x-dl::login-box show="brand, title, username, password, forgot, device, button" :action="route('login.attempt')" />
```

## Related

- `x-dl::phone-shell`: the phone frame; its Menu panel has a "Desktop view" button (the same choice, one way).
- `x-dl::login-box`: part `device`.
- `Devlib\Device`: the multi view model (`view`, `os`, `bodyClass`, `viewport`, `normalize`, `override`) and the cookie name (`Device::COOKIE`); rules in `skills/devlib/views.md`.
