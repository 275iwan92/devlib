## Overview

`<x-dl::code-button>` is the small round `</>` button fixed at the bottom-right corner of a catalog screen. Clicking it opens a modal window with three tabs: the usage code of the screen, the Blade layout of each component, and the properties of each component (read from the PHP constructors, so the list is never out of date). It is a developer aid of the DevLib catalog: the catalog pages (`lib.blade.php`, `lib-cat.blade.php`) put one on every demo page.

Use it on catalog, demo or documentation screens that must show how they are built. Do NOT put it on a normal business screen: it shows source code and class internals to every viewer. For help text aimed at end users use the help panel (`x-dl::help-map`) or a `tooltip`; for a code box inside a page use `x-dl::code-generator` only for QR/barcodes (it is not a code viewer).

Generated markup:

```html
<div class="dl-code" data-dl data-dl-class="CodeButton" data-dl-code>
  <button type="button" class="dl-code__btn" data-dl-code-open title="View this screen's code"><i class="fa-solid fa-code"></i></button>
  <div class="dl-mov" data-dl-code-modal>            <!-- is-on = visible -->
    <div class="dl-modal dl-code__modal"> head (title + line count + X) / tabs / three panes </div>
  </div>
</div>
```

The root also carries the developer-catalog attributes written by `$dl()`. Extra attributes passed to the tag (`class`, `id`, `data-*`) are NOT merged: the root `div` does not use `$attributes`, so they are ignored.

## Props

Constructor of `Devlib\Components\CodeButton` (4 props, all own). Blade names are kebab-case; use `:prop="expr"` for arrays and expressions.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `code` | `string`, `''` | The usage code shown in the "Usage code" tab (`<pre><code>`, escaped). The header also shows the line count: `substr_count(rtrim(code), "\n") + 1` followed by the word "lines" (translated). An empty string still counts as 1 line. |
| `file` | `string`, `''` | Text printed above the code (`.dl-code__file`): the path or description of the file the code comes from. Plain text, not translated, not checked. |
| `classes` | `array`, `[]` | List of component class names (FQCN strings, for example `Devlib\Components\Button::class`) to document. For each class that exists (`class_exists`; unknown names are skipped silently) the modal shows its tag, class chain, header comment, constructor properties, own public methods and Blade view. The order of the list is the order on screen. |
| `mockup-url` | `?string`, `null` | When not empty, a link "Original mockup ↗" (`target="_blank"`) is added to the tab row. Nothing else uses it. |

There is no `can` effect beyond the common one: `can="module.action"` on the tag hides the whole button when the ability is missing (shared by every DevLib component).

## Behavior

- Open: click the round button (adds `is-on` to the `.dl-mov` overlay). Also opens by itself on page load when the URL hash is exactly `#kode`, which allows a link straight to the code.
- Close: the X in the head, or a click on the dark overlay itself (a click inside the window does not close). `Esc` does NOT close it.
- Tabs: buttons `data-tab="code|view|props"` switch the panes `data-pane` by toggling `is-active`. The first tab ("Usage code") is active at start. The mockup link is a normal anchor, not a tab.
- Tab "Component layout" (`view`): for each class with a view, the tag, its line count and the source of the Blade view. For classes whose view is inline, the text of the `<<<'BLADE'` block inside `render()` is used (12 leading spaces removed). A class with no readable view is skipped.
- Tab "Component properties" (`props`): per class a table Property | Type | Default | Origin. Names are kebab-case; types come from reflection (`?` is added for nullable types, unions are joined with `|`); defaults are `var_export` values, arrays longer than 3 items are shown as `[…N item]`. Origin is "own" when the property is declared by this class and "parent" when the parent constructor has the same parameter name (inherited). Also printed: the class chain (`Text ← Field ← DevlibComponent`), the `//` comment lines immediately above the class (joined in one line), and the public methods declared by the class itself (without `__construct`, `render`, `tag`, static methods).
- Layout: the button is `position: fixed; right: 14px; bottom: 14px; z-index: 190` (round, 38 px, uses the developer colors). The window is at most 880 px wide / 88 vh high and scrolls inside.
- Language: the button title, window title, "lines", tab names, "Original mockup", table headers and "own/parent" follow the page language. The code, file text, class names and property names are never translated.

## Data and events

- No `name`, nothing is posted, no server request: all content is rendered on the server into the page (the whole code, view sources and tables are in the HTML even while the window is closed, so a large `code` makes the page heavier).
- No `dl:*` event is emitted and nothing listens to one. The JS class is `Code` (`resources/assets/js/components/code.js`, selector `[data-dl-code]`), available as `el.dlComponent` on the root with `modal` as property.
- Hooks: `data-dl-code` (root), `data-dl-code-open`, `data-dl-code-modal`, `data-dl-code-close`, `data-tab`, `data-pane`.
- PHP helpers on the component: `lines()` (line count) and `info()` (array of `tag`, `lineage`, `props`, `methods`, `doc`, `view` per class).

## Rules and pitfalls

- Developer aid only: never ship it on a production business screen. It exposes source code, view internals and class names.
- `classes` takes class names, not tags. Pass `Foo::class` strings; a misspelled name is skipped without any message.
- The documented properties are those of the constructor (what a Blade tag can set). The view-only extras (slots, free attributes) must be read in the "Component layout" tab.
- The "own/parent" test compares parameter names with the direct parent constructor only; a name that exists in the parent counts as "parent" even if the child redeclares it.
- The window has no `Esc` support and no focus handling: it is not a dialog for assistive technology (no `role="dialog"`, no focus trap). The button has only a `title` as accessible name.
- Because extra attributes are ignored, style it with the page CSS (`.dl-code__btn`), not with `class=`.
- Code oddity (not fixed): the properties pane prints `Lang::t('dev_class')` in `resources/views/components/code-button.blade.php:31`; no entry with that key was found under `src/` or `site/app/`, so the label may show the raw key `dev_class` unless the application dictionary provides it.
- Code oddity (not fixed): the "Default" header in `code-button.blade.php:34` is a literal English word, not passed through `Lang::text`, while the other headers are.

## Example

As used by the catalog page `site/resources/views/lib.blade.php`:

```blade
<x-dl::code-button
    :code="$code"
    :file="'resources/views/demos/'.$demo.'.blade.php'"
    :classes="[\Devlib\Components\Button::class, \Devlib\Components\SplitButton::class]"
    :mockup-url="null" />
```


## Related

- `x-dl::button`, `x-dl::split-button`, `x-dl::menu-button`: buttons in the same dictionary group, often the classes listed in `classes`.
- `x-dl::layer-shell`, `x-dl::layer-menu`: the menu of the catalog site that hosts the pages using this button.
- `x-dl::code-generator`: unrelated QR/barcode generator (not a source viewer).
- `skills/devlib/components.md`: generated prop list; the Technical Guide (`Devlib\Guide`) reads the files in `resources/guide/`.
