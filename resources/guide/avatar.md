## Overview

`<x-dl::avatar>` shows a person or object as a small picture or, without a picture, as a coloured square with initials, optionally followed by a two-line text (name and code). The colour comes from the name, so the same name always gets the same colour on the server render.

Use it for people/object identity in dashboards, lists, headers and cards. Do NOT use it:

- in a data grid: the grid has its own identity column (`'type' => 'identity'`, see `x-dl::grid`); it draws the same markup in JavaScript.
- for a status or category tag: use `x-dl::badge`.
- for a big profile header: use `x-dl::profile-banner`.

Generated markup (class names and sizes are real; the optional parts follow the props):

```html
<span class="dl-avatar-id dl-avatar-id--md" data-dl="x-dl::avatar" data-dl-class="Avatar ← DevlibComponent" data-dl-src="—">
  <span class="dl-avatar-id__pic" style="--h:214">AP</span>            <!-- or <img src="..." alt=""> when photo is set -->
  <span class="dl-avatar-id__txt"><b>Andi Pratama</b><small>TM-00012</small></span>   <!-- only when sub is not null -->
</span>
```

Extra HTML attributes (`class`, `id`, `style`, `data-*`) and `can="module.action"` go on the root `span`. Without the ability the component renders nothing.

## Props

Constructor of `Devlib\Components\Avatar`: 4 props, none inherited (`DevlibComponent` has no constructor). Blade names are kebab-case; here they are single words.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `name` | `string`, `''` | Full name. Used for (1) the initials: first letter of the first word plus first letter of the second word, upper-cased (`Andi Pratama` -> `AP`, `Budi` -> `B`, `PT Contoh Kertas Jaya` -> `PC`); (2) the colour hue (`crc32(name) % 360`, written as `--h`); (3) the bold line of the text block, but only when `sub` is set. Not translated (data). An empty name gives an empty square. |
| `photo` | `?string`, `null` | URL of an image. When set, an `<img src alt="">` replaces the initials inside the same square (`object-fit: cover`, clipped by the square). The URL is written as is (escaped), not validated. The coloured gradient stays behind the image (visible while loading or with transparent PNG). |
| `size` | `string`, `'md'` | Adds the class `dl-avatar-id--<size>`. Styled values: `md` (default, square 32 px with the theme radius), `lg` (round 48 px, larger initials), `sm` (24 px, small initials). Any other string is written as a class and has no style (but see the pitfall about `box` and `lead`). |
| `sub` | `?string`, `null` | Second line (small, monospaced, muted): code, job title, ID. `null` hides the whole text block, including the name. `''` (empty string) still shows the block (name only). Not translated. |

Translation: none of the four props is translated (the component lists no text props); put the final display text in them. If you want the dictionary to translate a `sub`, translate it yourself before passing it.

## Behavior

- Variants: initials (default) and photo. There is no status dot, ring or badge corner.
- Colour: background is `linear-gradient(135deg, hsl(h 60% 55%), hsl(h+40 55% 42%))` with white bold initials. Because the hue is derived from the name, colours are stable between page loads but are random between names.
- Sizes: `sm` 24 px, `md` 32 px, `lg` 48 px (round). Font sizes follow the user font scale (`--dl-font-scale`). The text block is a flex column: name at 13 px, `sub` at 11 px in the mono font.
- Theme: all colours except the initials gradient come from `--dl-*` tokens (`--dl-ink-strong` for the name, `--dl-muted` for `sub`), so the active theme recolours the text. The gradient is theme-independent. Initials text is white.
- Layout: the root is `inline-flex` with a 10 px gap; the square never shrinks (`flex-shrink: 0`).
- Report grids: `.dl-grid--report .dl-avatar-id__pic{display:none}`, so inside a report grid only the text block is visible.
- Accessibility as implemented: the image has `alt=""` (decorative) and there is no `role`, `aria-label` or `title`. Without `sub` the name appears nowhere in the DOM, so a screen reader reads only the initials. Always pass `sub` (even `sub=""` to show only the name) when the name must be readable.
- No keyboard behavior, no animation, no JavaScript.
- Permissions: `can="module.action"` hides the whole avatar when `Devlib\Access::can()` says no.

## Data and events

- Data in: four scalar props only; no slot, no array, no model. Nothing is posted and no `dl:*` event is emitted.
- JS: none for this component; `el.dlComponent` is not defined on it. The root carries `data-dl="x-dl::avatar"`, `data-dl-class` and `data-dl-src` (developer catalog / Technical Guide link).
- The same visual is built client side by the grid identity column (`resources/assets/js/components/grid.js`, `case 'identity'`) with the CSS classes `dl-avatar-id`, `dl-avatar-id__pic`, `dl-avatar-id__txt`.

## Rules and pitfalls

- Pass `sub` whenever the name must be visible: with `sub` omitted you get a lone initials square (fine as a compact marker next to a name you print yourself, as the dashboard components do with `size="sm"`).
- `name` and `sub` are escaped (`{{ }}`), so user text is safe; `photo` is only HTML-escaped, so never pass a URL taken from untrusted input without checking the scheme.
- Initials are computed on whitespace only: `Siti-Rahma` gives `S`; a leading symbol or emoji becomes the initial.
- `size="box"` and `size="lead"` exist in CSS (`dl-avatar-id--box`, `--lead`) for the grid identity column. On `x-dl::avatar` `box` paints the whole root as a coloured rectangle but the square with initials is still rendered inside it; do not use these two values here.
- Project rule (skills/devlib/grid.md): grid first columns follow the grid rules (master = box, transactional = plain bold text); do not simulate them with `x-dl::avatar`.
- Code oddity (not fixed): the hue of the PHP avatar and the hue of the JS grid identity square use different algorithms (`crc32(name) % 360` in `src/Components/Avatar.php:39` versus a 31-multiplier char hash in `resources/assets/js/components/grid.js:256`), so the same name has different colours in an avatar and in a grid.
- Code oddity (not fixed): `src/Components/Avatar.php:32` has `preg_split(...) ?: ['?']`, but `preg_split` on an empty string returns `['']` (truthy), so the `?` fallback never applies; an empty `name` renders an empty square.
- Code oddity (not fixed): `src/Components/Avatar.php:47` renders the name only when `sub !== null`, so `<x-dl::avatar name="Budi Santoso" size="lg" />` (the catalog demo) shows only initials and has no accessible name.

## Example

Shapes copied from the catalog demo `avatar-progress`:

```blade
<div class="dl-row">
    <x-dl::avatar name="Andi Pratama" sub="TM-00012" />
    <x-dl::avatar name="Siti Rahma" sub="TM-00045" />
    <x-dl::avatar name="Budi Santoso" size="lg" />
    <x-dl::avatar name="PT Contoh Kertas Jaya" size="sm" />
</div>
```

With a photo, and inside a table cell next to your own text (the way the dashboard widgets use it):

```blade
<x-dl::avatar name="Dewi Lestari" photo="/img/people/dewi.jpg" sub="Purchasing" size="lg" />

<td>
    <div class="dl-dash__who">
        <x-dl::avatar :name="$row['name']" size="sm" />
        <div><b>{{ $row['name'] }}</b><small>{{ $row['role'] }}</small></div>
    </div>
</td>
```

Only for authorised users:

```blade
<x-dl::avatar name="Rina Wijaya" sub="Approver" can="approval.view" />
```

## Related

- `x-dl::badge`: small coloured status label; use it for status, not for identity.
- `x-dl::profile-banner`: large profile header with badges.
- `x-dl::grid`: identity column (`type => identity`, `avatar`, `box`, `hue`, `lead`) draws the same square in JavaScript.
- `x-dl::progress`: shown beside avatars in the `avatar-progress` demo.
- `skills/devlib/grid.md` (identity column rules), `skills/devlib/components.md` (generated prop list).
