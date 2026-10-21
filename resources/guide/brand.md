## Overview

`<x-dl::brand>` shows the application identity as a logo badge followed by the name and the tagline. It is a building block of the login screen (`x-dl::frame` widget `brand`, `x-dl::login-box` part `brand`) and can be put on any screen that must show who the application is.

Name, tagline and logo are not fixed in the object: when a prop is not given, the object asks `Devlib\Components\Brand::identity()`, the identity of the site. Use it for the application identity only. For a status label use `x-dl::badge`; for the environment text after the logo title use `x-dl::env-badge`.

### The site identity (`Brand::identity()`)

A static method, also used by `x-dl::layer-menu` (title), `x-dl::layer-shell`, `x-dl::frame`, `x-dl::login-box` and `x-dl::login-card`. For each of `name`, `tagline`, `logo` the first that is set wins:

1. the container binding `devlib.brand`, an array with the keys `name`, `tagline`, `logo` (for an application that keeps its name in the database: `app()->bind('devlib.brand', fn () => ['name' => ..., 'tagline' => ..., 'logo' => ...])`);
2. `config('devlib.brand.name' | '.tagline' | '.logo')`;
3. the DevLib defaults `DEVLIB`, `developer library` and no logo.

## Props

Constructor of `Devlib\Components\Brand` (4 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `name` | `?string`, `null` | Application name, printed bold in `<b>`. `null` = `Brand::identity()['name']`. Not translated (it is not in the dictionary text props). Also the `alt` text of the logo image. |
| `tagline` | `?string`, `null` | Small line under the name (upper case by CSS, letter-spaced). `null` = `Brand::identity()['tagline']`. Not translated. Pass `''` for no tagline text (the empty `<span>` stays in the markup). |
| `tone` | `string`, `'light'` | Colour variant, class `dl-brand--<tone>`: `light` (white text, for dark backgrounds), `dark` (strong ink text for light backgrounds, 26px bottom margin) and `auto` (follows the surrounding login box tokens `--dl-box-strong` / `--dl-box-muted`, 22px bottom margin). Any other word is written as the class but has no style. |
| `logo` | `?string`, `null` | URL of the logo image (for example `/media/brand/devlib-logo.svg`), shown inside the badge. `null` or empty = the default gradient badge (token `--dl-logo`). |

## Behavior

### Generated markup

```html
<div class="dl-brand dl-brand--light" data-dl="x-dl::brand tone=light" data-dl-class="Brand" data-dl-src="identitas perusahaan (aplikasi: Settings > Identifikasi)">
  <span class="dl-logo-badge"><img src="/media/brand/devlib-logo.svg" alt="DevLib"></span>   <!-- <img> only with logo -->
  <div><b>DevLib</b><span>Developer Library</span></div>
</div>
```

Extra attributes (`class`, `id`, `style`) go on the root `div`. `can="module.action"` hides the whole object without the ability.

### Style

- Row layout (`display:flex; gap:12px; align-items:center`). The badge is 42 x 42 px with a 12px radius; the image fills it (`object-fit:contain`, white background).
- Name: `18px` times `--dl-font-scale`, tight line height. Tagline: `10px` times `--dl-font-scale`, `letter-spacing:2px`, upper case, so a tagline you write in lower case is shown in capitals.
- The colours come from tokens, so the theme does not change the structure: `light` uses white and `--dl-on-dark-muted`; `dark` uses `--dl-ink-strong` and `--dl-muted`.

### States

None: static, no hover or focus state, not interactive (no link). No JavaScript.

### Language and text

`name` and `tagline` are identity data and are never translated or recorded in the dictionary. Nothing else on screen.

### Accessibility

The logo `<img>` has `alt` = the name, while the name is also printed beside it, so a screen reader announces the name twice. The default gradient badge is an empty `span` and is ignored.

## Data and events

- No posted data, no events, no `el.dlComponent`.
- Inputs: the three props, or the identity of the site (above). `source()` is the text `identitas perusahaan (aplikasi: Settings > Identifikasi)`, shown by the developer catalog.
- Hooks: `.dl-brand`, `.dl-brand--light|dark|auto`, `.dl-logo-badge`.
- Where DevLib uses it: `resources/views/components/frame.blade.php` (the `brand` widget, which also passes `:logo`) and `resources/views/components/login-box.blade.php` (part `brand`, `tone="auto"`, class `dl-loginbox__brand`; passes `:name` and `:tagline` only).

## Rules and pitfalls

- Set the identity once, in `config/devlib.php` (`'brand' => ['name' => ..., 'tagline' => ..., 'logo' => ...]`) or the `devlib.brand` binding. Do not repeat the name in every screen; call `<x-dl::brand />` without props.
- Pick the tone for the background: `light` on dark/photo backgrounds, `dark` on light backgrounds, `auto` inside a login box.
- Use an image with a transparent or white background and a roughly square shape; it is fitted inside 42 x 42 px with `contain`. `x-dl::layer-menu` and `x-dl::layer-shell` have their own `logo` prop for their own badge.
- Keep name and tagline short: neither wraps or truncates specially in this object.
- No client or project name belongs in the library itself; the defaults are `DEVLIB` and `developer library`.
- Code oddity (not fixed): the comment of `identity()` and the REMARK say `logo` falls back to the site identity, but the constructor only fills `name` and `tagline`; with no `logo` prop the badge stays the gradient even when `config('devlib.brand.logo')` is set. `x-dl::frame` passes the logo itself, `x-dl::login-box` does not, so a login box shows the gradient badge (`src/Components/Brand.php:44-46`, `resources/views/components/login-box.blade.php:12`).
- Code oddity (not fixed): the REMARK lists `Shell / LayerShell` as users; `x-dl::layer-shell` only calls `Brand::identity()` and does not render `x-dl::brand` (`src/Components/Brand.php:20`, `src/Components/LayerShell.php:71`).
- Code oddity (not fixed): `tone` is not validated; an unknown value gives a `dl-brand--<value>` class with no colour rule, so the text inherits the page colour.

## Example

Catalog demo (dark tone, identity from the site config):

```blade
<x-dl::brand tone="dark" />
```

On a dark login background with explicit values:

```blade
<x-dl::brand name="Stoku" tagline="Stok sederhana" tone="light" logo="/media/brand/stoku.svg" />
```

Identity from the site configuration (`config/devlib.php`) that every untouched `<x-dl::brand />` reads:

```php
'brand' => ['name' => 'DevLib', 'tagline' => 'Developer Library', 'logo' => '/media/brand/devlib-logo.svg'],
```

As a widget of the login frame:

```blade
<x-dl::frame bg="devlib" box="right" color="light" widgets="brand@top-left, clock@top-right, quote@bottom-left">
```

## Related

- `x-dl::frame`: the login background; its `brand` widget renders this object.
- `x-dl::login-box`: the login form; part `brand` renders this object with `tone="auto"`.
- `x-dl::layer-menu`, `x-dl::layer-shell`: the menu logo and title, taken from `Brand::identity()`.
- `x-dl::env-badge`: environment text after the logo title.
- `x-dl::badge`: status labels.
