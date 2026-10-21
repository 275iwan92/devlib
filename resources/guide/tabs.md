## Overview

`<x-dl::tabs>` shows one of several content panels at a time, chosen from a bar of tab buttons. The bar is horizontal across the top (default) or a vertical list at the left (`vertical`, the layout of the Setup and Security pages). The content of each tab is a named slot; all panels are rendered in the page and the script only switches which one is visible.

Use it for peer views of the same record or page that the user switches between (General / Order lines / History; Users / Groups / Permissions).

Pick another object when:

- the sections are secondary detail to hide, with a one-line summary visible when closed: `x-dl::accordion` / `x-dl::accordion-group`;
- the screen is a data-entry document: `x-dl::form` with `panel="top"` already builds its own tabs from its `tabs` prop (same markup and script), including the "N errors in this tab" badge; do not wrap a form in `x-dl::tabs` to get tabs;
- the steps must be done in order with validation: `x-dl::wizard`;
- grid views of one dataset (All / Open / Late): the `tabs` option of `x-dl::grid`, not this object;
- the whole application navigation: menus (`x-dl::layer-menu`).

Generated markup (root also has `data-dl`, `data-dl-class`, `data-dl-src`; extra attributes and `class` go on the root; `can="module.action"` removes the whole tab set when the ability is missing):

```html
<div class="dl-tabs [is-vertical]" data-dl-tabs>
  <div class="dl-tabs__bar" role="tablist">
    <button type="button" class="dl-tabs__tab is-active" data-tab="<key>"><i class="fa-solid fa-..."></i> Title</button>
    ...
  </div>
  <div class="dl-tabs__panel is-active" data-panel="<key>">slot content</div>
  <div class="dl-tabs__panel" data-panel="<other key>">slot content</div>
</div>
```

## Props

Constructor of `Devlib\Components\Tabs` (3 props). Slots are not props: each tab key needs a slot with the same name.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `tabs` | `array`, `[]` | The tabs in display order: `['key' => 'Title']` or `['key' => ['Title', 'icon']]`. The key is written to `data-tab` / `data-panel` and is also the NAME OF THE SLOT that fills the panel (`<x-slot:key>`). Titles are translated through the dictionary (both the string form and element `[0]` of the array form); wrap data values in `raw()`. The icon (second element) is a semantic name from `Devlib\Icon::MAP` (`user`, `lock`, ...) or a Font Awesome class (`fa-folder-tree`); the view writes `fa-solid` itself, so only solid icons work. The array form needs both elements (a one-element array raises an undefined-index error). Titles are escaped. An empty array renders an empty bar. |
| `active` | `?string`, `null` | Key of the tab that is open on load. `null` = the first key of `tabs`. The comparison is strict (`===`): a key that is not in `tabs`, or a numeric key compared with a string (PHP turns the key `'1'` into the integer `1`), matches nothing, and then NO tab and NO panel is active (empty content). Only used at render; the script does not read it. Use string keys. |
| `vertical` | `bool`, `false` | Adds `is-vertical` to the root: a two-column grid (220px list at the left, content at the right), the list is a card that stays at the top while the page scrolls (`position:sticky; top:10px`). Up to 820px of screen width the list shrinks to 56px and shows icons only (`font-size:0` hides the text), so every vertical tab needs an icon to be usable on phones. Horizontal tabs scroll sideways when they do not fit. |

## Behavior

### Switching

- `Tabs` (`resources/assets/js/components/tabs.js`, selector `[data-dl-tabs]`) attaches a click handler to every `:scope > .dl-tabs__bar .dl-tabs__tab`. A click moves `is-active` to that tab button and to the panel whose `data-panel` equals the button's `data-tab`; the other panels get `display:none` (CSS). Because of `:scope >`, tab sets nested inside a panel do not disturb each other.
- Only the visible panel is displayed; the others stay in the DOM. Fields in hidden panels are still submitted with the surrounding form, and anything that measures its own size on load sees a hidden box.
- Nothing is stored: no history entry, no `localStorage`, no hash. A reload shows `active` again.

### `?tab=` in the URL

On load the script reads `?tab=<key>` and clicks the tab with that key. This lets a page return to the same tab after a redirect (for example after a save dialog). The parameter is read by EVERY tab set on the page that has that key (including the tab bars of `x-dl::form`, which use the same script); a key that does not exist in a tab set is ignored.

### Interaction with `x-dl::form` validation

When `x-dl::form` validates (Save/Confirm), it finds the invalid required fields, counts them per panel and appends a red count badge `<span class="dl-tabs__err">n</span>` to the matching tab button, then opens the tab of the first invalid field (clicks it), scrolls and focuses it. This works for tab sets (including `x-dl::tabs`) placed inside the form. Only the innermost tab set around the field is handled (see oddity below). The badges are removed at the next validation.

### Appearance

Top tabs: bottom underline in the accent color for the active tab; hover darkens the text. Vertical tabs: a bordered list, active item has a soft accent background. Add the class `dl-tabs--caps` (`class="dl-tabs--caps"` on the tag) to show titles in upper case with letter spacing (`devlib.css:3736`). The panel padding is `12px 2px` (horizontal) or none (vertical); `x-dl::lines` adjustment tabs and the form panel layout shrink it.

### Language and permissions

- `tabs` titles are translated at render time (screens write English). Slot content is the screen's own markup.
- `can="module.action"` on `x-dl::tabs` hides the whole set. There is no per-tab ability: to hide one tab, leave its key out of `tabs` in PHP (for example with `Devlib\Access::can()`), and its slot is never output.

### Accessibility as implemented

- Tab buttons are native `<button type="button">`: Tab key reaches each one, Enter/Space activates.
- The bar has `role="tablist"` but the buttons have no `role="tab"`, no `aria-selected`, no `aria-controls`, and panels have no `role="tabpanel"`. There is no Left/Right arrow navigation. Add these in the project theme if screen-reader support is required.

## Data and events

- No data is loaded or posted by the tab set itself. The slots hold normal markup; inputs inside them belong to the surrounding form.
- No `dl:*` custom event is emitted, and the instance (`el.dlComponent`, class `Tabs`) has no public methods besides `init()`. To react to a change, listen to `click` on `.dl-tabs__tab` (bubbles to the root) or observe the `is-active` class. To switch from code, `.click()` the button: `root.querySelector('.dl-tabs__tab[data-tab="baris"]').click()`.
- Markup hooks: `data-dl-tabs` (root), `.dl-tabs__bar`, `.dl-tabs__tab[data-tab]`, `.dl-tabs__panel[data-panel]`, `.is-active`, `.dl-tabs__err` (count badge from the form).
- Override with `data-dl-js="MyTabs"` + `Devlib.register(class MyTabs extends Devlib.classes.Tabs {...})` when a project needs different behavior.

## Rules and pitfalls

- Slot name = tab key. In `tabs` use keys that are valid PHP variable names written in lower case or camelCase, and write the slot with the same text: `<x-slot:riwayat>`. A hyphenated key (`'order-lines'`) does not work: Laravel camel-cases the slot name (`<x-slot:order-lines>` becomes `$orderLines`) but the view looks up `${'order-lines'}`, so the panel is empty.
- A tab key must not equal anything the view already uses as a variable: the props (`tabs`, `active`, `vertical`), `slot`, `attributes`, `key`, `title`, `loop`, `componentName`, or a public method name of the class (`tag`, `source`, `lint`, `lineage`, `dl`, `jsClass`, ...). The same rule is stated for `x-dl::form` tab keys.
- Always pass string keys that exist in `tabs` as `active`; otherwise no tab is open and the content area is blank (see `active`).
- Vertical tabs need an icon on every item (phones show only icons).
- Content in hidden panels is still submitted. If a tab's fields must not be posted, disable them or do not render that tab.
- Required fields inside tabs of an `x-dl::form` are handled by the form (badge + switch). A closed `x-dl::accordion` is NOT handled; this is one reason to prefer tabs for form sections.
- Do not use tabs for navigation between different URLs; they only toggle content already on the page.
- No lint rule (`devlib:lint`) checks this object.
- Code oddity (not fixed): `src/Components/Tabs.php:36` and `:40` compare keys with `===` against `$active ?? array_key_first($tabs)`, so an `active` that is not an exact key (typo, or a numeric key given as a string) leaves every tab and panel inactive, with no error.
- Code oddity (not fixed): `src/Components/Tabs.php:36-40` use `$key` and `$title` as loop variables and `${$key}` for the slot, so a tab whose key is `title` (or `key`, `tabs`, `active`, `vertical`) reads the loop/prop variable instead of the slot and renders wrong content (when the variable is an array, such as an array-form title or `tabs`, printing it raises an error).
- Code oddity (not fixed): `src/Components/Tabs.php:34` marks the bar `role="tablist"` while the buttons have no `role="tab"` (invalid ARIA pattern for assistive technology).
- Code oddity (not fixed): `resources/assets/js/components/form.js:137-140` handle only the innermost tab panel of an invalid field. If an `x-dl::tabs` sits inside a hidden panel of the form's own tab bar, the badge goes to the inner tab and the outer tab is not opened, so the invalid field can stay hidden. Found by reading the code, not by running it.

## Example

Horizontal tabs (catalog demo `tabs-accordion`, fictional data):

```blade
<x-dl::tabs :tabs="['umum' => 'Informasi Umum', 'baris' => 'Baris Pesanan', 'riwayat' => 'Riwayat']">
    <x-slot:umum><x-dl::field.lookup name="supplier_id" lookup="supplier" label="Supplier" /></x-slot:umum>
    <x-slot:baris>Tabel baris pesanan (Part 5: Master-Detail).</x-slot:baris>
    <x-slot:riwayat>Riwayat approval (Part 5: Approval).</x-slot:riwayat>
</x-dl::tabs>
```

Vertical tabs with icons, the shape of the Setup and Security page (catalog demo `security`, shortened):

```blade
<x-dl::tabs vertical :tabs="['users' => ['Pengguna', 'user'], 'groups' => ['Grup', 'users'], 'theme' => ['Tema', 'fa-palette']]">
    <x-slot:users>
        <x-dl::grid id="sec-users" :rows="$users" row-key="login" :columns="[['key' => 'login', 'label' => 'Username'], ['key' => 'name', 'label' => 'Nama']]" />
    </x-slot:users>
    <x-slot:groups>
        <x-dl::grid id="sec-groups" :rows="$groups" row-key="name" :columns="[['key' => 'name', 'label' => 'Nama grup']]" />
    </x-slot:groups>
    <x-slot:theme><x-dl::theme-picker /></x-slot:theme>
</x-dl::tabs>
```

Opening a specific tab on load, and upper-case titles:

```blade
<x-dl::tabs class="dl-tabs--caps" active="baris" :tabs="['umum' => 'Informasi Umum', 'baris' => 'Baris Pesanan']">
    <x-slot:umum>...</x-slot:umum>
    <x-slot:baris>...</x-slot:baris>
</x-dl::tabs>
```

Returning to a tab after a redirect: send the user to `/orders/12?tab=baris`.

## Related

- `x-dl::form`: panel forms build the same tab bar from their own `tabs` prop and add the error badge and auto-switch; `x-dl::lines` and `x-dl::doc-footer` use the same markup and script for their adjustment tabs.
- `x-dl::accordion`, `x-dl::accordion-group`: collapsible sections instead of peer views.
- `x-dl::wizard`: ordered steps.
- `x-dl::grid`: its own `tabs` option filters rows of one dataset.
- `x-dl::theme-picker`, `x-dl::permission-matrix`: typical content of the Setup and Security tab pages.
- `skills/devlib/components.md`: generated prop list.
