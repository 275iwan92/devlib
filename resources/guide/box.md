## Overview

`<x-dl::box>` is the neutral wrapper of DevLib: it renders one HTML element and forwards every attribute and class you give it, and adds no style of its own. Use it when a screen needs a plain container (a hook for a `data-*` attribute, a print id, a page class, a bold or code-styled inline piece) without writing a raw HTML tag in the view. All visual rules come from the classes you pass.

It is the smallest object in the library, so use it only when nothing better fits:

- a titled, framed section: `x-dl::card`;
- a framed group of fields that can be locked: `x-dl::block`;
- two or more blocks side by side: `x-dl::cols`;
- main content plus a side panel: `x-dl::split`;
- a note or help line: `x-dl::hint`.

Generated markup (default `as="div"`):

```html
<div class="my-class" data-x="1">slot content</div>
```

The element has NO `data-dl`, `data-dl-class` or `data-dl-src` attributes: the view does not call `$dl()`. So `Box` is invisible to the developer catalog overlay, to the title link of the Technical Guide and to the lint recorder.

## Props

Constructor of `Devlib\Components\Box` (1 prop). Blade names are kebab-case.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `as` | `string`, `'div'` | The element name. Allowed values (whitelist `Box::TAGS`): `div`, `p`, `span`, `article`, `header`, `aside`, `section`, `small`, `b`, `strong`, `code`. Any other value (including `a`, `ul`, `img`, `button`, `h1`, `video`, upper-case names, or an empty string) silently falls back to `div`; there is no error. The match is case-sensitive and exact. |

All other attributes (`class`, `id`, `style`, `data-*`, `aria-*`, `title`, `role`) are written on the element exactly as given through Laravel's attribute bag. `can="module.action"` is also accepted: without the ability nothing is rendered.

## Behavior

- Markup is `<{{ el }} {{ attributes }}>{{ slot }}</{{ el }}>`. The slot is inserted as HTML (it is already rendered Blade); attribute values are escaped.
- No class is added. Unlike the other layout objects there is no `dl-box` class, so an unstyled `x-dl::box` looks exactly like the raw element (a `div` is a block, a `span` is inline).
- No JS class, no events, no state. `el.dlComponent` is not set.
- Text: the component has no text prop and translates nothing. The slot content is whatever you write.
- Permissions: `can` is evaluated at render by `Devlib\Access::can()`; false renders an empty string (the box and its content disappear). It is not a security boundary; check on the server.
- Accessibility: whatever the chosen element means. `section`, `article`, `aside` and `header` carry landmark/semantic roles; `div`, `span`, `b`, `small` and `strong` carry none. Add `role` / `aria-*` yourself when needed.

## Data and events

- Server: nothing is posted by the box itself (it is not a form control).
- Events: none emitted.
- Markup hooks: the ones you pass. Typical use is a hook for a screen script (`data-dl-...` or `data-...`), an `id` for print or anchors, or a page class defined in the project CSS.
- Nothing in the library queries the box.

## Rules and pitfalls

- Pass the style through classes (theme tokens in CSS); do not put hex colors in `style=` (lint L003).
- The `as` whitelist is closed on purpose (text containers only). There is no heading, list, table, link, image or form element: for those use the matching `x-dl::*` object or write the element in the view where the lint rules allow it.
- A typo in `as` does not fail; the output becomes a `div`. Check the rendered HTML when the element type matters (for example inline `span` vs block `div`).
- The slot of `p` cannot legally contain block elements; the browser closes the `<p>` early when it meets a `div`. Use `as="div"` or `as="section"` for blocks.
- Every attribute is forwarded, including `onclick` and similar. L002 only catches `<script>` tags, not inline handlers: keep behavior in a DevLib/project JS class.
- Because there is no `data-dl`, the Technical Guide link on titles and the catalog tooling will not show the box; do not rely on them to locate it.
- Code oddity (not fixed): `resources/assets/devlib.css:2903` styles `.dl-pdoc__appx-body .dl-box { break-inside: avoid }`, but `Box` never emits a `dl-box` class (`src/Components/Box.php`) and no other component in `src`/`resources/views` does either, so that rule only matches if a project adds the class itself.

## Example

From `src/Components/IssueModal.php` (the message line above the table), where a `p` wraps a bold `b`:

```blade
@if ($message)<x-dl::box as="p"><x-dl::box as="b">{{ $message }}</x-dl::box></x-dl::box>@endif
```

A print/page hook with a class and a data attribute (fictional names):

```blade
<x-dl::box as="section" id="po-summary" class="po-summary" data-copy="1">
    <x-dl::box as="small">Prepared by the warehouse desk</x-dl::box>
    <x-dl::box as="code">PO-2609-0001</x-dl::box>
</x-dl::box>
```

## Related

- `x-dl::card`: framed section with title, description, icon and actions.
- `x-dl::block`: framed `fieldset` that locks its fields together.
- `x-dl::cols`: responsive side-by-side layout.
- `x-dl::split`: main content plus side panel.
- `x-dl::hint`: help or note line.
- `skills/devlib/components.md`: the generated prop list; `php artisan devlib:lint` reports L001 to L003 for the views that use the box.
