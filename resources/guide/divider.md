## Overview

`<x-dl::divider>` is a horizontal rule with a short text in the middle, the classic "or" separator between two alternative ways of doing something (sign in with a password / register, upload / paste a link). It is a single `div` with a `span`; the two lines are drawn by CSS around the text.

Use it only for a short word or phrase between two blocks. Pick another object when:

- you need a titled section with content inside: `x-dl::card` (titled box) or `x-dl::block` (titled fieldset), or `x-dl::accordion` to make it collapsible;
- you need a main area with a side panel: `x-dl::split`;
- you need extra vertical space or a plain line without text: use layout spacing/CSS, not this object (an empty `label` still renders an empty span between two lines).

Generated markup (root also has `data-dl`, `data-dl-class`, `data-dl-src`; extra attributes and `class` go on the root; `can="module.action"` removes it when the ability is missing):

```html
<div class="dl-divider" data-dl="x-dl::divider" ...><span data-i18n="or">or</span></div>
```

`data-i18n` is written only when `i18n` is set.

## Props

Constructor of `Devlib\Components\Divider` (2 props). The tag has no slot.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `label` | `string`, `''` | Text in the middle, in a `<span>`. Translated through the dictionary at render time (screens write English: `label="or"`). Escaped. Wrap data values in `raw()`. Empty = two lines touching an empty span. |
| `i18n` | `?string`, `null` | Key written to `data-i18n` on the `<span>`. Only the login box uses it: its script (`LoginBox`), on the `dl:lang` event, replaces the text of every `[data-i18n]` element with the entry of its own language table (`LOGIN_T`) whose key is this value (the table has the key `or`). On any other screen the attribute has no effect; the text then follows the language chosen at render. Normal screens never set it. |

## Behavior

- Layout (`resources/assets/devlib.css:92-93`): `display:flex; align-items:center; gap:10px`, muted text color, font size `12px` times the user's font scale, `margin:20px 0` (vertical space above and below). The lines are `::before` / `::after` pseudo-elements, `flex:1`, 1px high, in the border color; they follow the theme.
- Inside the login box the text color is overridden with the box's own muted token (`.dl-loginbox .dl-divider`, `devlib.css:192`).
- Always horizontal and full width of the parent. There is no vertical variant and no size prop.
- No JavaScript of its own; no keyboard interaction (not focusable).
- Language: `label` is translated at render; the page does not retranslate it when the language is switched, except through `i18n` in the login box.
- Accessibility as implemented: plain `div`/`span`, no `role="separator"`; assistive technology reads the label text as a normal text. It is decorative, so keep the label meaningful ("or") or leave the structure to headings.

## Data and events

Nothing is loaded, posted or emitted. No `dl:*` events, no `dlComponent`. The only hook is `data-i18n` when `i18n` is set (read by `LoginBox.applyLang()` in `resources/assets/js/components/login-box.js:20`).

## Rules and pitfalls

- Write the label in English (`label="or"`). The dictionary translates it to the page language; a label already written in another language (Indonesian) is not found in the dictionary and stays as written in every language.
- Keep it short: the two lines take only the width that is left beside the text.
- Do not use it as a section heading: it is not a heading element and carries no `id` for anchors.
- `i18n` must be a key that exists in the language table of the script that handles the screen (`LOGIN_T` in `core.js:317` for the login box). Do not pass a translated sentence as the key.
- It adds `20px` of margin on both sides; collapse or override with a `class`/`style` on the tag if the surrounding layout already has spacing.
- Code oddity (not fixed): `resources/views/components/login-box.blade.php:25` passes `:i18n="\Devlib\Lang::text('or')"`, i.e. the translated text as the key. In English the key is `or` and works; with the Indonesian display `Lang::text('or')` returns `atau`, so `data-i18n="atau"` is not a key of `LOGIN_T` and the divider is not retranslated when the language is switched on that screen. The key should be the literal `or`.
- Code oddity (not fixed): the catalog demo `site/resources/views/demos/misc.blade.php:4` uses `label="atau"` (Indonesian), against the rule that screens write English; in the English display it stays "atau".

## Example

Catalog demo `misc`, written the way the rule asks (English label):

```blade
<x-dl::alert tone="info" message="Pesan informasi." />
<x-dl::divider label="or" />
<x-dl::brand tone="dark" />
```

Inside the login box the label is re-translated by the box's own script, so `i18n` carries the key `or` of its table (the box writes it through `Lang::text('or')`, see the oddity above):

```blade
<x-dl::divider label="or" i18n="or" />
```

## Related

- `x-dl::login-box`: places the divider between the sign-in button and the register link, and is the only user of `i18n`.
- `x-dl::card`, `x-dl::block`: titled sections with content.
- `x-dl::split`: main area plus side panel.
- `x-dl::accordion`: collapsible section.
- `skills/devlib/components.md`: generated prop list.
