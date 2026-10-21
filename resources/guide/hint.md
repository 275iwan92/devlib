## Overview

`<x-dl::hint>` is one paragraph of small help text: an explanation under a heading, a format example, or a note next to a control, written in the slot. It outputs a `<p>` (or a `<span>`) with the class `dl-hint`; the compact grey look is the `note` variant.

Use it for page-level or section-level help that is not tied to one input. Pick another object when the need is different:

- The `hint` prop of any `x-dl::field.*` for the help line under one input (it sits in the field frame and is translated automatically).
- The `tooltip` prop of a field, or `x-dl::tooltip`, for help that appears only on hover.
- `x-dl::alert` when the message is a warning, error, success or important information (colored box).
- `x-dl::empty-state` for a screen or list without data.

Generated markup:

```html
<p class="dl-hint dl-hint--note dl-hint--italic" data-dl="x-dl::hint" data-dl-class="Hint" data-dl-src="—">Slot text</p>
<span class="dl-hint dl-hint--note" data-dl="x-dl::hint" data-dl-class="Hint" data-dl-src="—">Slot text</span>   <!-- as="span" -->
```

`dl-hint--note` and `dl-hint--italic` appear only when the matching prop is on.

## Props

Constructor of `Devlib\Components\Hint` (3 props; nothing is inherited that is configurable besides the shared behavior below).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `note` | `bool`, `false` | Compact variant: adds `dl-hint--note` = font 11.5px times the user font scale, muted color (`--dl-muted`), margin `0 0 6px` (no top margin). Without `note` the element has no DevLib style at all (see Rules and pitfalls). Write it as a bare attribute: `<x-dl::hint note>`. |
| `as` | `string`, `'p'` | Tag of the root: `span` gives an inline `<span>`; any other value (including `p`) gives `<p>`. Use `span` inside a line of other content (for example next to a title). |
| `italic` | `bool`, `false` | Adds `dl-hint--italic` = `font-style:italic`. Meant for format examples (for example `Example: 23-Sep-2026`). Independent of `note`. |

The text is the slot, not a prop. Extra HTML attributes (`id`, `class`, `style`, `hidden`, `data-*`) are merged onto the root. The attribute `can="module.action"` is supported (base class `DevlibComponent`): without the ability nothing is rendered.

## Behavior

- **Text and language:** the slot is NOT translated by the component (it has no text props). Write texts that must follow the language through `{{ \Devlib\Lang::text('English text') }}`; static English written directly in the slot stays English. Data values go in as plain `{{ }}` text (they are not in the dictionary).
- **Styling:** only `--note` and `--italic` have CSS rules (`resources/assets/devlib.css`, the `x-dl::chips / cols / hint / headline` block). Colors come from theme tokens, so the dark theme is covered for `note`.
- **Show and hide:** use the native `hidden` attribute (`<x-dl::hint note hidden>`) and let a script toggle it, as the dieline-name hint of the sibling inquiry package does. `p`/`span` have no state of their own.
- **Accessibility as implemented:** plain text element, no `role`, no `aria-live`, no association with any input. If the hint explains an input, also set `aria-describedby` yourself or use the field's `hint` prop (that one is also not linked by `aria-describedby`, see the field entries).
- **Permissions:** `can="module.action"` hides it.

## Data and events

- Takes no data except the slot content (text, `{{ }}` values, inline HTML you write). Slot content is raw Blade: escape user data.
- No JS class, no `dl:*` event, nothing posted. `el.dlComponent` is not set.
- The only hook is the class `dl-hint` (and `--note` / `--italic`); scripts that fill it do so with `textContent` and `hidden`.

## Rules and pitfalls

- Plain `<x-dl::hint>` without `note` is an unstyled paragraph: it inherits the surrounding font size and color and has the browser's default paragraph margins. For the standard small grey help text always write `note`.
- Translate slot texts yourself with `Lang::text()`; the component will not.
- A hint is quiet help. Anything the user must not miss (a blocked action, an error) belongs in `x-dl::alert`.
- `as` takes only `p` or `span`; any other string silently becomes `p`.
- Do not nest block elements in `as="span"`; the root would be invalid HTML.
- No lint rule exists for this object (`lint()` returns no findings).
- Code oddity (not fixed): the REMARK header of `src/Components/Hint.php:7-8` says the style gives a muted color ("warna token --dl-muted"), and mentions writing the slot through `@t` / `t()`. In the library only the `--note` variant is colored (`resources/assets/devlib.css:3798`; there is no `.dl-hint{...}` rule anywhere), and neither a `@t` directive nor a `t()` helper exists in `src/` (the text helper is `\Devlib\Lang::text()`). no global `t()` function is defined in this repository (the sibling package view `Prodev/PartDieline.php:49` calls one, so it must come from the host app).

## Example

```blade
{{-- Standard small help line --}}
<x-dl::hint note>{{ \Devlib\Lang::text('Optional: more than one process is allowed.') }}</x-dl::hint>

{{-- Format example in italics --}}
<x-dl::hint note italic>{{ \Devlib\Lang::text('Example: 23-Sep-2026') }}</x-dl::hint>

{{-- Inline, with data: "Last changed by Example User · 23-Sep-2026 10:15" --}}
<x-dl::hint note as="span">{{ \Devlib\Lang::text('Last changed by') }} {{ $updatedBy }} · {{ $updatedAt }}</x-dl::hint>

{{-- Hidden until a script fills and shows it --}}
<x-dl::hint note id="dieline-name" hidden></x-dl::hint>
```

```js
const h = document.getElementById('dieline-name');
h.textContent = 'Sample-box-lid'; h.hidden = false;
```

## Related

- `x-dl::field.text` and the other `x-dl::field.*`: the `hint` and `tooltip` props for help tied to one input.
- `x-dl::tooltip`: help text shown on hover.
- `x-dl::alert`: colored message for warnings, errors and important information.
- `x-dl::empty-state`: message for an empty screen or list.
- `skills/devlib/components.md` (generated prop list), `skills/devlib/object.md` (translated props, `can`), `skills/devlib/inheritance.md` (`Lang::text` in views).
