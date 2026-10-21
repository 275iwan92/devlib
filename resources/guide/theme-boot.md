## Overview

`<x-dl::theme-boot />` is the one-time bootstrap that every DevLib page puts in `<head>`. It writes three things into the page, in this order:

1. A `<style data-dl-themes>` block with the CSS of every theme (`Devlib\Theme::css()`): one rule `[data-dl-theme="<code>"]{--dl-...}` per theme that overrides the colour tokens of `:root` in `devlib.css`.
2. A `<script>` that publishes the server settings the browser scripts need as `window.DL_*` globals (language dictionary, number rules, CSRF token, Guide URL, ...).
3. A tiny inline `<script>` that applies the saved theme, row density and font scale to `<html>` immediately, before the body is painted, so the page never flashes the default look.

It renders no visible element and has no root tag (so no `data-dl` attribute and no `class`/`id` pass-through). Use it exactly once per full HTML page, in the layout. Do NOT use it inside a screen or a partial; the visible theme chooser is `x-dl::theme-picker`.

## Props

Constructor of `Devlib\Components\ThemeBoot` (1 prop).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `theme` | `?string`, `null` | Fallback theme code, used only when the browser has no saved theme and the URL has no `theme=`. `null` means `config('devlib.theme')`, and if that is not set either, `Theme::DEFAULT` (`navy`). The value is not validated: a code that is not in `Theme::ALL` sets `data-dl-theme` to that text but no rule matches, so the page looks like the default `navy` look (the `:root` tokens). Valid codes: `pastel_blue`, `pastel_green`, `pastel_red`, `navy`, `emerald`, `cerah_red`, `dark_blue`, `dark_green`, `dark_red`, `hitam_blue`, `hitam_green`, `hitam_red`, `soft_blue`, `soft_green`, `soft_red`. |

`can="module.action"` is accepted like on every DevLib component (no ability = nothing is written, which would break the page; do not use it here).

## Behavior

### Order of the three outputs

```html
<style data-dl-themes>[data-dl-theme="pastel_blue"]{--dl-accent:#6aa3e8;...}  ... </style>
<script>window.DL_LANG = {...}; window.DL_LANG_ACTIVE = "id"; window.DL_NUM = {...}; ...</script>
<script>(function(d){ ...sets data-dl-theme, data-dl-density, --dl-font-scale on <html>... })(document.documentElement);</script>
```

Place it after the `devlib.css` link and before the deferred `devlib.js` script (see the example). The inline scripts run while the head is parsed; `devlib.js` is `defer`, so every `window.DL_*` value already exists when it starts.

### Theme, density and font scale (the "no flash" script)

The inline script reads three values and writes them on `<html>`:

| Setting | Source, in priority order | Written to |
|---|---|---|
| Theme | `theme=<code>` found in `location.search` or `location.hash` (regex `theme=([a-z_]+)`); else `localStorage['dl-theme']`; else the fallback (`theme` prop / config / `navy`) | `html[data-dl-theme]` |
| Density | `localStorage['dl-density']`; else `normal` | `html[data-dl-density]` (`compact`, `normal`, `comfortable`) |
| Font scale | `localStorage['dl-font-scale']`; else `1` | CSS variable `--dl-font-scale` on `html` (for example `0.9`, `1`, `1.1`, `1.2`) |

- `localStorage` access is wrapped in `try/catch`; when storage is blocked (private mode, disabled cookies) the defaults apply and nothing breaks.
- A `theme=` value in the URL wins for that page load but is NOT saved. Clicking a theme in `x-dl::theme-picker` saves it; on the next load a `theme=` in the URL still wins over the saved value.
- The values are per browser (not per account). The picker and these keys are the only writers.

### Browser globals published

| Global | Value | Used by |
|---|---|---|
| `window.DL_LANG` | `Devlib\Lang::all()`: the built-in dictionary of fixed words (key => [id, en] or per-language array) | `core.js` (`L(key)` lookups) |
| `window.DL_LANG_ACTIVE` | `Locale::lang()`: the language code of this request | `core.js` (current language; `?lang=` in the URL overrides) |
| `window.DL_NUM` | `Locale::jsSpec()`: `{lang, intl, dec, grp, months}` | number and date fields, grids (display and parsing). Without it the scripts fall back to Indonesian rules |
| `window.DL_DICT` | `(object) Lang::jsDict()`: application dictionary English text => translation (`''` = not yet translated); empty object when the app registered none | `core.js` text translation in the browser |
| `window.DL_DICT_URL` | `config('devlib.dictionary_register_url')` | unknown texts are sent here (beacon) so the app can record them; `null` = no recording |
| `window.DL_LANG_URL` | `config('devlib.lang_url')` | `x-dl::lang-switch` saves the chosen language to the account here, then reloads |
| `window.DL_BACK_HISTORY` | `(bool) config('devlib.back_history', true)` | `x-dl::back`: `false` = Back goes to its `href`, `true` = `history.back()` when the previous page is on the same site |
| `window.DL_CSRF` | `csrf_token()` | every DevLib `fetch` / beacon that POSTs |
| `window.DL_GUIDE` | `Devlib\Guide::url()`: `config('devlib.guide_url')`, or the route `devlib.guide` when `config('devlib.guide_middleware')` is set; else `null` | `Devlib.guideLinks()`: object titles become links into the Technical Guide (`null` = no links) |

### Language and text

Nothing on screen. The dictionary values it exports follow the language of the request; they are frozen at page load (switching the language reloads the page).

### Permissions

None of its own.

## Data and events

- No events, no `el.dlComponent`, no `data-*` hooks. The only markup hooks are `style[data-dl-themes]` (the theme CSS) and the attributes it sets on `<html>`: `data-dl-theme`, `data-dl-density` and the `--dl-font-scale` style property.
- Scripts that change the look later (`x-dl::theme-picker`, document export in `doc-share.js`) change these same `<html>` attributes; they do not call the boot script again.
- The server needs `csrf_token()` (a session). On a route without a session the token call can fail, so the layout must run inside the `web` middleware group.
- Reads at render time: `Devlib\Theme::css()`, `Lang::all()`, `Lang::jsDict()`, `Locale::lang()`, `Locale::jsSpec()`, `Guide::url()`, and the config keys `devlib.theme`, `devlib.dictionary_register_url`, `devlib.lang_url`, `devlib.back_history`.

## Rules and pitfalls

- Exactly once per page, in `<head>`. A second copy repeats the whole theme CSS and rewrites every `DL_*` global.
- Put it in the layout, not in a screen. A page without it still loads, but there is no theme CSS (only the `navy` `:root` look), the number rules fall back to Indonesian and there is no CSRF token for DevLib requests.
- Put it BEFORE `devlib.js` and ideally right after the `devlib.css` link, so the saved theme is applied before the first paint.
- Themes only override `--dl-*` tokens in `:root`; a component must never test the theme. Do not hard-code colours in the screen.
- The `theme` prop is only a fallback. Do not use it to force a theme: a saved choice in `localStorage` wins over it. To force a look for one page load use `?theme=<code>` in the URL.
- The URL regex has no word boundary: any `...theme=<letters or _>` in the query string or hash matches (for example `?color_theme=dark_blue` also sets `dark_blue`). An unknown code is accepted and gives the default look.
- `DL_LANG` carries the whole built-in dictionary on every page (a few hundred entries). That is by design; do not add large data to the dictionary.
- Code oddity (not fixed): the class header and the `Theme.php` comment say 12 themes (4 families x 3 hues), but `Devlib\Theme::ALL` now has 15 (the `soft` family was added); the CSS block and the picker cover all 15 (`src/Components/ThemeBoot.php:5`, `src/Theme.php:5`).
- Code oddity (not fixed): the REMARK sentence "skrip kecil yang." is cut off (`src/Components/ThemeBoot.php:5` and `:17`); this is also what the generated catalog text shows.

## Example

Layout head, as used by the catalog site and the skill example layout:

```blade
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'DevLib' }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ \Devlib\DevlibServiceProvider::asset('devlib.css') }}">
    <x-dl::theme-boot />
    <script src="{{ \Devlib\DevlibServiceProvider::asset('devlib.js') }}" defer></script>
</head>
```

With an explicit fallback theme for a project (a saved browser choice still wins):

```blade
<x-dl::theme-boot theme="soft_green" />
```

Project default without touching the layout: `'theme' => 'soft_green'` in `config/devlib.php`.

## Related

- `x-dl::theme-picker`: the chooser that saves `dl-theme`, `dl-density` and `dl-font-scale` which this component reads.
- `x-dl::lang-switch`: uses `DL_LANG_URL` and `DL_CSRF` to save the language.
- `x-dl::back`: reads `DL_BACK_HISTORY`.
- `x-dl::field.number`, `x-dl::field.decimal`, `x-dl::field.money`, `x-dl::field.date`: read the number and month rules in `DL_NUM`.
- `skills/devlib/install.md`: where the layout is set up in a project.
