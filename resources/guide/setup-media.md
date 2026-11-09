## Overview

`<x-dl::setup-media>` adds three pages to the Setup dialog of the Layer Menu (next to Theme and Language): **Music**, **Screen animation** and **Sound**. Place it in the `profilePages` slot of `x-dl::layer-shell`; the Layer Menu moves both sections into the Setup dialog.

- **Music**: a checkbox "Play background music", a volume slider and a track choice (only when the page's `x-dl::music-player` has more than one track). They drive the player on the same page; the checkbox is the same switch as the Play button.
- **Screen animation**: three choices, "Follow device" (default), "On" and "Reduce motion". The choice is saved on this device (`localStorage['dl-motion']`) and decides whether the greeting box moves (`Devlib.motion`, `<html data-motion="on|off">`, event `dl:motion`).

- **Sound**: one row per sound (Pomodoro, Alarm, Timer, ERP notifications, Chat, Facility alarm) with an on / off checkbox, a volume slider and a Test button. All sounds are OFF by default and are remembered on this device (`localStorage['dl-sound']`). Turning a sound on or pressing Test plays it once, which also lets the browser start sound. Code plays a sound with `Devlib.sound.play('chat' | 'notif' | 'pomodoro' | 'pomodoro_break' | 'alarm' | 'timer' | 'facility')`; it plays only when the user turned that sound on. Tones are soft Web Audio notes, played once, never repeated.

Pick another object when you need the theme (`x-dl::theme-picker`), the language (`x-dl::lang-switch`), or a control that lives next to the music itself (`x-dl::music-player`).

### Generated markup

```html
<section data-lm-page="music" data-lm-in="setup" data-label="Music" data-icon="fa-music" data-dl-setup-music>
  <div class="dl-setup">
    <label class="dl-setup__row"><input type="checkbox" data-dl-setup-on> <span>Play background music</span></label>
    <label class="dl-setup__col"><span>Volume <b data-dl-setup-volv></b></span><input type="range" data-dl-setup-vol></label>
    <label class="dl-setup__col" data-dl-setup-trackrow><span>Track</span><select data-dl-setup-track></select></label>
    <p class="dl-setup__note">...</p>
  </div>
</section>
<section data-lm-page="motion" data-lm-in="setup" data-label="Screen animation" data-icon="fa-wand-magic-sparkles" data-dl-setup-motion>
  <div class="dl-setup"><div class="dl-setup__seg" role="radiogroup">... three radios auto | on | off ...</div><p class="dl-setup__note">...</p></div>
</section>
```

## Props

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `part` | string | `all` | `all` renders the three pages for the `profilePages` slot. `music`, `motion` or `sound` renders only that page, for use as a normal screen (for example an Application Setup screen where the project groups them in its own `x-dl::card`). |

When the object sits on a normal screen inside an iframe of the Layer Menu, the music player and the greeting box live in the parent window: the Music page then finds the player there and listens to its `dl:music` event, and the Screen animation choice is also applied in the parent so the movement changes at once. Sound needs nothing extra because `localStorage['dl-sound']` is read on every play.

## Behavior

- Music page: without an `x-dl::music-player` on the page the checkbox and slider are disabled and the track row is hidden. Volume and track are remembered by the player; the on / off state is not (music always starts silent on a page load).
- Motion page: "Follow device" removes the saved choice and uses `prefers-reduced-motion`; "On" / "Reduce motion" are saved. All motion CSS and the canvas of the greeting box react at once.
- Texts go through `Lang::text`.

## Data and events

Reads the player (`element.dlComponent`) and listens to `dl:music` (player state) and `dl:motion` (effective motion). Writes only `localStorage['dl-motion']` and `localStorage['dl-sound']` (and, through the player, its own key). `Devlib.sound` fires `dl:sound` (`{kind, on, vol}`) when a choice changes.

## Rules and pitfalls

- Put it in `profilePages`, not inside the page body, or the sections are not moved into the Setup dialog.
- Use one `x-dl::music-player` per page; the setup controls drive the first one.

## Example

```blade
<x-dl::layer-shell ...>
    <x-slot:profilePages><x-dl::setup-media /></x-slot:profilePages>
</x-dl::layer-shell>
```

## Related

`x-dl::music-player`, `x-dl::greeting-card`, `x-dl::theme-picker`, `x-dl::lang-switch`, `x-dl::layer-shell`.
