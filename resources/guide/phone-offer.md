## Overview

`<x-dl::phone-offer>` is a small popup (a dialog, not a banner) for a desktop screen that is shown on a narrow window or a phone: "Want better view from smartphone?" with a big button "Click here" and a small button "Stay on desktop". Both buttons are tiny forms that POST the view choice (`ui=hp` or `ui=desktop`) to the project, which stores the cookie `ui`. After "Click here" the project shows its phone screen; after "Stay on desktop" the popup does not come back.

It is meant for the sign-in screen. It needs no JavaScript: the server decides whether to write it, and CSS decides whether it is visible.

Pick another object when you need:

- A permanent choice between the two views: `x-dl::device-pick` (radios), or the "Desktop view" button in the Menu panel of `x-dl::phone-shell`.
- The phone frame itself: `x-dl::phone-shell`.

### Generated markup

```html
<div class="dl-phoneoffer" data-dl="x-dl::phone-offer" role="dialog" aria-modal="true" aria-labelledby="dl-phoneoffer-t">
  <div class="dl-phoneoffer__box">
    <i class="fa-solid fa-mobile-screen-button dl-phoneoffer__ic"></i>
    <p class="dl-phoneoffer__t" id="dl-phoneoffer-t">Want better view from smartphone?</p>
    <form method="POST" action="..."><input type="hidden" name="_token"><input type="hidden" name="ui" value="hp"><button class="dl-phoneoffer__go">Click here</button></form>
    <form method="POST" action="..."><input type="hidden" name="_token"><input type="hidden" name="ui" value="desktop"><button class="dl-phoneoffer__stay">Stay on desktop</button></form>
  </div>
</div>
```

Nothing is written at all when the popup is not wanted (see Behavior).

## Props

Constructor of `Devlib\Components\PhoneOffer` (2 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `action` | `string`, `''` | POST address that stores the choice (field `ui` = `hp` or `desktop`) and then returns to the screen. It must accept guests when used on the sign-in screen, and it must be a route with CSRF (the forms carry the token). |
| `force` | `bool`, `false` | `true` writes the popup even when a view was already chosen or the device is a phone. Only for the catalog demo; do not use in a project. |

## Behavior

- Written only when there is no valid cookie `ui` and `Devlib\Device::fromRequest` says `desktop`. A phone is guessed as `hp`, so the project shows its phone screen directly and no popup is needed.
- Visible only at a viewport width of 760 px or less (CSS media query); on a wide screen it never shows. The dialog is centred over a dimmed background with 52 px and 44 px buttons, 16 px text, and safe-area padding.
- The text comes from the language dictionary (keys `phone_offer`, `phone_offer_go`, `phone_offer_stay`; Indonesian and English).

## Data and events

- Browser to server: `ui=hp` or `ui=desktop` (+ CSRF token). No `dl:*` event; no `el.dlComponent` (no JS class).
- The project's route validates `Rule::in(Device::CHOICES)`, stores the cookie `ui` and redirects.

## Rules and pitfalls

- The popup closes only by choosing one of the two buttons; there is no close icon, because both choices must be remembered, or it would reappear on every visit.
- A user who already has the cookie never sees it, and a user on a wide screen never sees it. Pair it with a way back to desktop on the phone screen.
- Related lint rules: none.

## Example

```blade
<x-dl::phone-offer :action="route('ui.set')" />
```

## Related

- `x-dl::device-pick`, `x-dl::phone-shell`: the choice inside the app.
- `Devlib\Device`: detection and the cookie `ui`.
