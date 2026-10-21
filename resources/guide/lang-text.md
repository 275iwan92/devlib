## Overview

`<x-dl::lang-text key="...">` prints one phrase from the DevLib dictionary (`Devlib\Lang`) as a `<span>`, in the current display language, and keeps it in step when the user switches language with `x-dl::lang-switch` (no reload needed). It is for text that must change live: guest pages, headings, notes that are not rendered by another DevLib component.

Use it when you have a dictionary key (`welcome`, `language`, `dirty_title`, ...). Do NOT use it for ordinary screen labels: labels, titles, hints and placeholders of DevLib components are written in English and translated by the dictionary at render, with no call needed. Do not use it for data (names, numbers, document numbers) because data is never translated.

Generated markup:

```html
<span data-dl="x-dl::lang-text key=welcome" data-dl-class="LangText ← DevlibComponent" data-dl-src="—" data-dl-t="welcome">Selamat datang</span>
```

Extra HTML attributes (`class`, `id`, `style`, `data-*`) are written on the span as they are (they are not merged with a base class; the component has none). `can="module.action"` is supported (without the ability nothing is rendered).

## Props

Constructor of `Devlib\Components\LangText` (1 prop).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `key` | `string`, `''` | Dictionary key. Server: the text is `Lang::t(key)` in the current language. Browser: `data-dl-t="<key>"` lets the language switch rewrite the span's text with the same key. A key that is not in the dictionary prints the key itself, both on the server and in the browser. An empty key prints an empty span. |

There is no slot: the span content is replaced on every language change, so do not put markup inside it.

## Behavior

- Server render: `Lang::t($key)` looks the key up in `Lang::all()` (built-in `DICT` plus the entries added with `Lang::extend()`), takes the entry of the current language, lets the application dictionary translator override it (the application's dictionary wins over the built-in one), and falls back to the English text, then to the key. Entries are `[ID, EN]` or `['id' => .., 'en' => .., 'ja' => ..]`.
- Live change: the core listener of `dl:lang` (fired by `x-dl::lang-switch`) sets `textContent = T(key)` on every `[data-dl-t]` element on the page. `T` reads `window.DL_LANG` (the same dictionary, sent by `x-dl::theme-boot`), picks the active language, and falls back to the translated English text and then to the key.
- The text is set with `textContent`, so it is plain text (no HTML) in the browser, and escaped by Blade on the server.
- The `key` prop itself is not run through the text translator; only the dictionary entry is looked up. Dictionary group: general (the component is not in a `GROUP_OF` group).
- No language choice is stored here: the display language comes from the account, the print language, or the device cookie `dl_lang` (see `x-dl::lang-switch`).
- Accessibility as implemented: a plain inline `span`; changing the text on a language switch is not announced (no `aria-live`), and `<html lang>` is updated by the switch.
- No styling of its own; it inherits the font and color of its parent.

## Data and events

- Reads: `Devlib\Lang` dictionary (server) and `window.DL_LANG` (browser). Writes nothing and posts nothing.
- Hook: `[data-dl-t="<key>"]`. Any element with `data-dl-t` is rewritten on `dl:lang`, so you may put the attribute on your own element, but then you must also render the first text on the server.
- Event it reacts to: `dl:lang` on `document` (`detail` = language code). It emits no event and has no `el.dlComponent`.
- Adding keys: the application calls `Devlib\Lang::extend([...])` (and the matching `window.DL_LANG` is then rebuilt on the next render by `x-dl::theme-boot`), or its dictionary translator provides the language rows.

## Rules and pitfalls

- Only dictionary keys, not free text. A missing key shows the raw key on screen; use an existing key or add it with `Lang::extend()`.
- Dynamic data stays out: wrap values in `raw()` where they go into component props; do not put them in a key.
- No placeholders or parameters: for a phrase with values use a component prop (translated at render) or your own script.
- A new key added after the page was rendered is not in `window.DL_LANG`, so the live change prints the key until the page is rendered again.
- A page without the script `x-dl::theme-boot` (no `window.DL_LANG`) keeps the server text and prints the key if the language is switched live.
- Do not put children inside the tag; they are ignored by the template and the span text is rewritten on a switch.
- Code oddity (not fixed): `src/Components/LangText.php:24` returns `'x-dl::lang-text key='.$this->key` from `tag()`, so the `data-dl` attribute and the catalog tag contain the key (`x-dl::lang-text key=welcome`) instead of the plain tag.

## Example

From the catalog demo (`site/resources/views/demos/i18n.blade.php`):

```blade
<x-dl::lang-switch />
<p class="dl-note"><x-dl::lang-text key="welcome" />! · <x-dl::lang-text key="language" />: <b><x-dl::lang-text key="dirty_title" /></b></p>
```

With a class:

```blade
<h2><x-dl::lang-text key="welcome" class="dl-title" /></h2>
```

## Related

- `x-dl::lang-switch`: the language picker; its `dl:lang` event makes this span change live.
- `x-dl::theme-boot`: puts the dictionary into the page (`window.DL_LANG`) used by the live change.
- `x-dl::button`: preset buttons (`preset="save"`) take their labels from the same dictionary and also follow `dl:lang`.
- `skills/devlib/components.md`: generated prop list.
