## Overview

`<x-dl::spinner>` shows that something is being processed: a rotating ring with an optional text next to it. In its default form it is an inline element; with `overlay` it covers its parent (a card, a grid, a form) while the work runs, dimming the content and blocking clicks on it.

The component is static markup plus CSS animation. DevLib does **not** show or hide it for you: the screen decides when it is on the page (render it conditionally, or toggle the `hidden` attribute from your own script).

Pick another object when:

- the page area is loading and the final layout is known: `x-dl::skeleton` (grey placeholder shapes);
- progress is measurable (percent done): `x-dl::progress` or `x-dl::progress-ring`;
- a submit button is busy: nothing to add, `core.js` already swaps the label of a submitting `x-dl::button` for the ring + a translated "Processing" text (see Behavior);
- a result or error message: `x-dl::alert`.

### Generated markup

```html
<div class="dl-spinner dl-spinner--md dl-spinner--overlay" data-dl="x-dl::spinner" data-dl-class="Spinner ← DevlibComponent" data-dl-src="—" role="status">
  <span class="dl-spinner__ring"></span>
  <span class="dl-spinner__label">Menghitung HPP...</span>   <!-- only with label -->
</div>
```

`dl-spinner--overlay` is present only when `overlay` is true. The size class is always present (`dl-spinner--sm|md|lg`, or whatever string was given).

## Props

Constructor of `Devlib\Components\Spinner` (3 props; nothing is inherited from `DevlibComponent`).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `label` | `?string`, `null` | Text printed right of the ring (`dl-spinner__label`, muted colour, 13 px scaled by the user font scale). `null` or `''` prints no label span. Translated through the dictionary (`label` is a standard text prop; the class is in the `messages` dictionary group); wrap data values in `raw()`. |
| `size` | `string`, `'md'` | Class `dl-spinner--<size>`. Styled values: `sm` (ring 14 px, 2 px border), `lg` (36 px, 4 px border); `md` is the base size (22 px, 3 px border, there is no `--md` rule). Any other string is written as-is and looks like `md`. |
| `overlay` | `bool`, `false` | Adds `dl-spinner--overlay`: `position:absolute; inset:0`, content centred, background is the surface colour at 75% opacity, `z-index:5`, `border-radius:inherit`. It fills the nearest **positioned** ancestor, so that ancestor needs `position:relative`. While it is present, clicks on the covered content hit the overlay instead. |

Extra HTML attributes (`id`, `class`, `style`, `hidden`, `data-*`) are merged onto the root `div`; `class` is appended to the generated classes. `can="module.action"` is supported by the base class `DevlibComponent`: without the ability nothing is rendered.

## Behavior

- Animation: the ring (`dl-spinner__ring`) is a bordered circle whose top border is the accent colour, rotating once per 0.8 s (`@keyframes dl-spin`). The animation always runs: no `prefers-reduced-motion` rule covers the spinner.
- Colours come from the theme variables (accent ring on an accent-soft track, muted label), so light / dark and the chosen theme are followed. The overlay background mixes the current surface colour, so it is also correct in dark mode.
- Without `overlay` the root is `display:inline-flex` with a 10 px gap: it sits in a text line or in a `dl-row`. With `overlay` it leaves the normal flow.
- Show / hide: the global rule `[hidden]{display:none!important}` makes `hidden` work on the spinner, so a screen script can do `spinner.hidden = true/false`. Without that the spinner is permanently visible.
- Accessibility as implemented: the root has `role="status"` (an implicit polite live region), so a label that appears in the page is announced. With no `label` there is no text and the spinner has no accessible name; add a `label` when the wait is meaningful. There is no `aria-busy` on the covered content and no focus trap: keyboard users can still Tab into content under an overlay.
- Language: only `label` is translated.

### Spinner inside buttons (not this component)

A submitting `x-dl::button` is switched to busy by `resources/assets/js/core.js` (around line 409): it adds `is-busy`, disables the button and replaces its content with `<span class="dl-spinner__ring dl-spinner__ring--inline"></span> Processing`. That reuses only the CSS ring classes. Do not put `x-dl::spinner` inside a button for that.

## Data and events

- Input is only the three props. There is no slot: content between the tags is ignored because the view never prints `$slot`.
- Nothing is posted and no `dl:*` event is emitted. There is no JS twin and `el.dlComponent` is not set.
- Hooks: `.dl-spinner`, `.dl-spinner__ring`, `.dl-spinner__label`, `.dl-spinner--overlay`, `.dl-spinner__ring--inline`.

## Rules and pitfalls

- `overlay` needs a positioned parent. Without `position:relative` on it, the overlay is placed against the next positioned ancestor (often the whole page).
- The spinner does not know when work ends. Remove it, or set `hidden`, in the same code that finishes the work, otherwise it spins forever. Never leave an overlay spinner in a page that has no script to hide it.
- An overlay blocks the mouse but not the keyboard; also disable the controls (or use `x-dl::form` locking) if input must not happen during the work.
- Use a short, concrete `label` ("Menghitung HPP...", "Loading data...") instead of a bare ring when the wait can take more than a moment.
- Use `sm` inside a line of text or a table cell, `lg` for an empty page area; do not size it with `style`.
- No lint rule covers this object.
- Code oddity (not fixed): `size` is not validated, so a typo such as `size="large"` silently renders the default size.
- Code oddity (not fixed): the stylesheet has no `prefers-reduced-motion` rule for `dl-spin` (`resources/assets/devlib.css:933` and `:938`), so the ring spins for users who asked for reduced motion.

## Example

Copied in shape from the catalog (`spinner`):

```blade
<div class="dl-row">
    <x-dl::spinner size="sm" />
    <x-dl::spinner label="Memuat data..." />
    <x-dl::spinner size="lg" />
</div>
```

Overlay over a card (the card must be `position:relative`):

```blade
<x-dl::card title="Spinner menutupi kartu selama proses" style="position:relative;min-height:140px">
    Isi kartu tetap terlihat samar di belakang.
    <x-dl::spinner overlay label="Menghitung HPP..." />
</x-dl::card>
```

Shown by the server only while a job is running, and toggled by script otherwise:

```blade
@if ($importRunning)
    <x-dl::spinner label="Import berjalan..." />
@endif

<x-dl::spinner id="grid-busy" overlay hidden label="Loading data..." />
<script>
    // screen script: show while fetching, hide when done
    const busy = document.getElementById('grid-busy');
    busy.hidden = false;
    fetch('/api/rows').finally(() => busy.hidden = true);
</script>
```

## Related

- `x-dl::skeleton`: grey placeholder shapes while a known layout loads.
- `x-dl::progress`, `x-dl::progress-ring`: when the percentage done is known.
- `x-dl::button`: shows its own busy state (ring + "Processing") on form submit.
- `x-dl::card`: the usual parent of an overlay spinner (give it `position:relative`).
- `x-dl::alert`, `x-dl::empty-state`: the message after the work ends.
- `skills/devlib/components.md`: generated prop list.
