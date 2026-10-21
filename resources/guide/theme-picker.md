## Overview

`<x-dl::theme-picker />` is the display chooser: a grid of theme swatches (family x hue), a row of three density buttons and a row of four font-size buttons. A click applies the choice at once to the whole page and saves it in the browser, where `x-dl::theme-boot` restores it on the next page load.

It has no props. Use it where the user chooses how the application looks: the Theme page of the profile panel (`x-dl::layer-shell` already includes it when `without` does not contain `theme`), or a Setup / Security screen. Do NOT use it for the page language (`x-dl::lang-switch`). The page must contain `x-dl::theme-boot` in `<head>`, otherwise a saved choice is not restored on reload.

## Props

Constructor of `Devlib\Components\ThemePicker`: none (0 props). The inherited behaviour applies:

| Attribute | Meaning |
|---|---|
| `class`, `id`, `style`, `data-*` | Merged into the root `div.dl-themepick` (`$attributes->class('dl-themepick')`). |
| `can="module.action"` | Without the ability nothing is rendered. |

The theme list is not a prop: it comes from the constants `Devlib\Theme::ALL`, `FAMILIES` and `HUES` (see Behavior). The component declares its data source as `Devlib\Theme::ALL (= system.sys_theme)`.

## Behavior

### Generated markup

```html
<div class="dl-themepick" data-dl="x-dl::theme-picker" data-dl-class="ThemePicker" data-dl-src="..." data-dl-themepick>
  <div class="dl-themepick__grid">
    <span></span>
    <span class="dl-themepick__head">Biru</span> <span ...>Hijau</span> <span ...>Merah</span>
    <span class="dl-themepick__head">Pastel</span>
    <button type="button" class="dl-themepick__opt" data-theme="pastel_blue" title="Pastel Biru" style="--a:#6aa3e8;--s:#1f2742"><span></span><span></span></button> ... (x3)
    ... one row per family: Pastel, Cerah, Dark, Hitam, Soft  (15 buttons)
  </div>
  <div class="dl-themepick__row"><b>Density</b>
    <button type="button" class="dl-chipbtn" data-density="compact">Compact</button> ... normal, comfortable</div>
  <div class="dl-themepick__row"><b>Font size</b>
    <button type="button" class="dl-chipbtn" data-scale="0.9">90%</button> ... 1, 1.1, 1.2 (100%, 110%, 120%)</div>
</div>
```

- Each swatch button is a mini preview: the left 35% is the sidebar colour (`--s`) and the rest the primary colour (`--a`), read from `Theme::ALL`. `title` is the theme name (for example `Cerah Biru`).
- Themes (`Theme::ALL`, code => name): `pastel_blue/green/red`, `navy` (Cerah Biru, the default), `emerald`, `cerah_red`, `dark_blue/green/red`, `hitam_blue/green/red`, `soft_blue/green/red`. Rows are families (`Pastel`, `Cerah`, `Dark`, `Hitam`, `Soft`), columns are hues (`Biru`, `Hijau`, `Merah`).
- Density values: `compact`, `normal`, `comfortable` (CSS `--dl-density` .7 / 1 / 1.35). Font scale values: `0.9`, `1`, `1.1`, `1.2`.

### Clicks (class `ThemePicker` in `resources/assets/js/components/theme-picker.js`)

| Button | Effect | Saved key |
|---|---|---|
| `[data-theme]` | `document.documentElement.dataset.dlTheme = code` | `localStorage['dl-theme']` |
| `[data-density]` | `html.dataset.dlDensity = value` | `localStorage['dl-density']` |
| `[data-scale]` | `html.style.setProperty('--dl-font-scale', value)` | `localStorage['dl-font-scale']` |

After every click `mark()` toggles `is-active` on the buttons that match the current `<html>` state and dispatches `document` event `dl:theme`. The same `mark()` runs once when the picker starts, so the current choice is highlighted on load. Saving is silent when storage is blocked: the choice still applies to the current page, but is lost on reload.

### States

- Active swatch: `.dl-themepick__opt.is-active` (strong border and accent ring). Active chip: `.dl-chipbtn.is-active` (accent fill).
- No disabled or loading state. The buttons are native `<button type="button">`: reachable by Tab, Enter/Space to activate. There is no `aria-pressed`, `role` or `aria-label`; a swatch has only `title`, so a screen reader hears no name. Add them in the project theme if required.

### Language and text

- `Density`, `Compact`, `Normal`, `Comfortable` and `Font size` go through the dictionary (`Lang::text`) and follow the page language. The percent labels are not translated.
- Family and hue headings (`Pastel`, `Biru`, ...) and the swatch `title` (`Pastel Biru`, ...) come from `Devlib\Theme` and are always the Indonesian-named strings; they are not translated.

### Permissions

None of its own; only the generic `can=` attribute.

## Data and events

- Nothing is posted to the server and nothing is read from it: the choice lives in the browser (`localStorage`), per browser, not per account. (Source note in the class: in a future application this moves to the user record; today the persistence is the browser.)
- Event: `document` receives `dl:theme` (no detail) every time any picker marks its buttons, including once at start-up. `x-dl::token-table` listens to it to repaint the shown token values; screens can listen to refresh anything that depends on the theme (for example a chart palette).
- `el.dlComponent` on `div.dl-themepick` is the `ThemePicker` instance (`html`, `save(key, value)`, `mark()`). It is mounted by selector `[data-dl-themepick]`; `boot.js` mounts it first (`Devlib.mountOne("ThemePicker")`), and `Devlib.mount` also finds pickers added later (for example inside a Layer Menu panel).
- Hooks: `[data-dl-themepick]`, `[data-theme]`, `[data-density]`, `[data-scale]`, `.is-active`.
- Override with `data-dl-js` / `jsClass()` is possible like any component, but the standard class is enough for all current uses.

## Rules and pitfalls

- Put `x-dl::theme-boot` in the layout. The picker only changes the live page and saves the keys; the boot script is what restores them on the next load.
- A `?theme=<code>` in the URL beats the saved theme on load (see `x-dl::theme-boot`). If a user "cannot keep" a theme, look at the URL.
- Themes only override `--dl-*` tokens. Screens must use tokens (`var(--dl-accent)`, ...) and `calc(12px * var(--dl-font-scale))`, never fixed colours or font sizes, otherwise the choice is ignored on that screen.
- The choice is per browser. Two users on one computer share it; the same user on two computers does not.
- Several pickers on one page: each picker only highlights its own buttons and does not listen to `dl:theme`, so the other pickers keep an old highlight until they are clicked. Use one picker per page.
- Code oddity (not fixed): the class header and `ThemeBoot` say "12 themes" but `Devlib\Theme::ALL` has 15 (5 families x 3 hues; `soft` was added), and the picker renders all 15 (`src/Components/ThemePicker.php:5`, `src/Theme.php:5`).
- Code oddity (not fixed): the class header says "Dipakai oleh: basic-density, basic-themes, layer-menu, security", but `x-dl::layer-menu` itself does not contain the picker; the page is put into its `profile` slot by the caller or by `x-dl::layer-shell` (`src/Components/ThemePicker.php:9`, `resources/views/components/layer-shell.blade.php:7`).

## Example

Taken from the Layer Menu profile panel (the Theme page, under a Setup parent):

```blade
<x-slot:profile>
    <section data-lm-page="setup" data-label="Setup" data-icon="fa-sliders" data-lm-children="theme,lang"></section>
    <section data-lm-page="theme" data-label="Theme" data-icon="fa-palette"><x-dl::theme-picker /></section>
    <section data-lm-page="lang" data-label="Language" data-icon="fa-globe"><x-dl::lang-switch /></section>
</x-slot:profile>
```

On a Setup & Security screen, with a heading:

```blade
<div class="dl-sec"><h2>Tema</h2><p class="dl-sec__desc">Pilih warna utama aplikasi. Perubahan langsung terlihat.</p><x-dl::theme-picker /></div>
```

Read the live choice from a script:

```js
document.addEventListener('dl:theme', () => {
  console.log(document.documentElement.dataset.dlTheme, document.documentElement.dataset.dlDensity);
});
```

## Related

- `x-dl::theme-boot`: restores the saved theme, density and font scale before the page is painted, and holds the theme CSS.
- `x-dl::layer-shell`, `x-dl::layer-menu`: the profile panel page "Theme" that hosts the picker (`without` on the shell can hide it).
- `x-dl::lang-switch`: the language chooser, usually beside the picker under Setup.
- `x-dl::token-table`: repaints on `dl:theme`.
