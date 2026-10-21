## Overview

`<x-dl::ribbon>` is a command ribbon in the style of Microsoft Excel: a title row (Quick Access buttons, document title, a "search commands" box), a row of tabs, and under it the panel of the active tab, divided into labeled groups of buttons. Buttons can be large (icon over text) or small (three stacked per column), plain, dropdown (menu with nested sub-menus), split (main action + arrow menu), toggle (Bold) or radio toggle (Align left / center / right). An optional `File` tab opens a full-size "backstage" panel. The ribbon can be collapsed, and `Alt` shows keyboard tips.

Use it for document or editor screens with many commands in groups. Do NOT use it for navigation between application screens (use the Layer Menu: `x-dl::layer-shell`, `x-dl::layer-menu`), for a top bar with link categories (`x-dl::mega-menu`), or for one short list of choices (`x-dl::menu-button`). The ribbon runs nothing itself: every click is reported as the event `dl:ribbon`.

Generated markup (shortened):

```html
<div class="dl-rb" data-dl-ribbon>
  <div class="dl-rb__title"> .dl-rb__quick buttons | .dl-rb__doc title | .dl-rb__tell search box + [data-rb-found] </div>
  <div class="dl-rb__tabs" role="tablist"> [File tab data-rb-file] tabs data-rb-tab="key" ... pin button data-rb-pin </div>
  <div class="dl-rb__body" data-rb-body>
    <div class="dl-rb__panel is-active" data-rb-panel="key" role="tabpanel">
      <div class="dl-rb__group"><div class="dl-rb__items"> buttons / .dl-rb__stack / .dl-rb__drop / .dl-rb__split </div><div class="dl-rb__glabel">Group</div></div>
    </div>
  </div>
  <div class="dl-rb__stage" data-rb-stage hidden> backstage: nav + slot </div>   <!-- only with file -->
</div>
```

Extra attributes (`class`, `id`, `data-*`) are merged on the root `div`.

## Props

Constructor of `Devlib\Components\Ribbon` (5 props, all own).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `tabs` | `array`, `[]` | The tabs: `[['key', 'label', 'tip'?, 'groups' => [['label', 'items' => [ITEM]]]]]`. `key` identifies the tab (`data-rb-tab`/`data-rb-panel`). `tip` is the KeyTip letter; default is the first letter of `label` in upper case (two tabs with the same letter: the first one wins). The `label` of a tab goes through the dictionary translation (`tabs` is a translated list); group labels do not. |
| `file` | `array`, `[]` | Items of the backstage (`File` tab): `[['key', 'label', 'icon']]`. When not empty, a `File` tab (KeyTip `F`) is added at the left and the backstage panel is rendered; the slot of the component is the body of the backstage. Empty = no File tab, no backstage, and the slot is NOT rendered. |
| `quick` | `array`, `[]` | Quick Access buttons at the top left: `[['key', 'label', 'icon']]`. `label` is the tooltip and the key (default) of the event. Icon only, no text. |
| `title` | `string`, `''` | Document title in the middle of the title row. Translated like any text prop: wrap data (document numbers, names) in `raw()`. |
| `active` | `?string`, `null` | `key` of the tab shown at start. `null` = the first tab. |

### ITEM (button)

Items sit in `tabs[].groups[].items`. Menu entries use almost the same keys.

| Key | Meaning |
|---|---|
| `key` | Identifier sent in the event; default is `label`. |
| `label` | Text (required). Not translated by the component. |
| `icon` | DevLib icon key (`save`, `print`, ...) or `fa-*` class, resolved with `Icon::fa()`. |
| `size` | `lg` = large button (icon above text); anything else = `sm` (default). |
| `title` | Tooltip; default is the label, followed by ` (<shortcut>)` when `shortcut` is set. |
| `shortcut` | Text only (for example `Ctrl+B`): appended to the tooltip, shown as `<kbd>` in menus. The ribbon does not bind the key. |
| `tip` | KeyTip letter shown after `Alt` for this button (`data-rb-tip`). |
| `menu` | List of menu entries (below). Makes the button a dropdown. |
| `split` | `true` together with `menu`: split button (main part runs `key`, the arrow opens the menu). Without `menu`, `split` has no effect. |
| `toggle` | `true`: pressed/unpressed button (`aria-pressed`). |
| `radio` | Group name: toggle where only one button of that name is on at a time. Counts as a toggle. |
| `on` | `true`: starts pressed (`is-on`, `aria-pressed="true"`). |
| `iconOnly` | `true`: no text, only the icon (title kept). Set automatically for buttons in a `row`. |
| `row` | Instead of a button: `['row' => [ITEM, ...]]` puts the buttons side by side in one line of a small-button stack (icon-only). |

Menu entry (inside `menu`): the string `'-'` makes a separator line; otherwise `label`, `icon`, `key` (default label), `shortcut` and `menu` (for a sub-menu, which then has no key). `toggle`, `radio`, `size` and `split` are ignored in menus.

## Behavior

- Layout of a group: consecutive small buttons are stacked three per column; a large button is a column by itself and closes the stack before it.
- Kinds of button: plain (`data-rb-key`); dropdown (`menu` without `split`): one button with a caret, no key and no event for opening, only for its entries; split (`menu` + `split`): main button with `data-rb-key` plus a separate arrow `data-rb-drop` (in a large split the label sits under the icon, inside the arrow part); toggle and radio only work on plain buttons (a button with `menu` returns before the toggle attributes are written).
- Tabs: click selects the tab. Double-click on a tab or the `^` pin button or `Ctrl+F1` collapses the ribbon (`is-min`: only the tab row stays). In collapsed mode a click on a tab shows its panel floating (`is-float`) and a second click on the same tab or any outside click, `Esc` or choosing a command hides it again.
- Menus: a dropdown/split arrow toggles `is-open` on its box and places the menu with `position: fixed` under the button (so the horizontally scrolling panel does not clip it). Sub-menu entries toggle on click. Scrolling the page (outside the menu), clicking outside or choosing an entry closes menus.
- Toggle: a click flips `aria-pressed` and `is-on`, and sends `on: true|false`. Radio: a click sets that button on and turns off all others with the same `radio` name in the whole ribbon.
- Backstage: click `File` to show the full panel; "Back" or `Esc` hides it; choosing a backstage item (`data-rb-key`) hides it and runs the command. The slot is the free area on the right.
- Search commands ("Tell me"): typing in the box lists up to 8 buttons and menu entries of the tab panels whose label contains the text (case-insensitive, duplicates by key removed), with their path `Tab › Group`. Click a result to run the command with that key (it runs the first element with that `data-rb-key` in the ribbon, Quick Access first). Backstage and Quick Access items are not searched. No match shows "No commands.". Quick Access and File items are not listed.
- KeyTips: pressing and releasing `Alt` alone (while the pointer is in the ribbon) shows small letters on the tabs, the File tab and the tips of the active panel; pressing a letter clicks that element (a tab: the tips of the new panel show). `Esc` or running a command hides them. Keys act only after the pointer has entered or pressed in this ribbon (`hot`).
- Accessibility as implemented: tabs have `role="tablist"/"tab"`, panels `role="tabpanel"`, menus `role="menu"`/`menuitem`, toggle buttons `aria-pressed`, the search box and pin button have `aria-label`. There is no `aria-selected`, `aria-controls`, `aria-expanded` or arrow-key navigation among tabs or buttons; use Tab and `Alt` tips.
- Text on screen: the search placeholder and labels ("Search commands…", "Collapse / pin the ribbon (Ctrl+F1)", "Back", "options" in the split arrow label, "on"/"off"/"preview" in the default toast) follow the page language. The `File` tab text is written literally in the view. Item, group, quick and file labels are written by you.

## Data and events

- Posts nothing (all buttons are `type="button"`).
- Event `dl:ribbon`: `CustomEvent`, `bubbles: true`, CANCELABLE, dispatched on the root `div`, `detail = { key, label, on }`. `on` is `true`/`false` for toggles and radios and `null` for ordinary buttons. If no handler calls `preventDefault()`, a preview toast is shown: `<label>` (plus ` — on`/` — off` for toggles) followed by `(preview)`.

```js
document.querySelector('[data-dl-ribbon]').addEventListener('dl:ribbon', e => {
  e.preventDefault();                      // stop the preview toast
  switch (e.detail.key) {
    case 'save': form.requestSubmit(); break;
    case 'bold': setBold(e.detail.on); break;
  }
});
```

- Hooks: `data-dl-ribbon`, `data-rb-tab`, `data-rb-panel`, `data-rb-key`, `data-rb-drop`, `data-rb-sub`, `data-rb-toggle`, `data-rb-radio`, `data-rb-tip`, `data-rb-file`, `data-rb-stage`, `data-rb-stagex`, `data-rb-pin`, `data-rb-find`, `data-rb-found`, `data-rb-run`. JS class `Ribbon` (`resources/assets/js/components/ribbon.js`), `el.dlComponent` on the root with `setTab(key)`, `run(button)`, `closeMenus()`, `tips(on)`.

## Rules and pitfalls

- The ribbon only reports. Your handler does the work and must call `preventDefault()` in production, or users see "(preview)" toasts.
- Keys should be unique per command: the events, the search and the Quick Access/panel lookup all use `data-rb-key`. When two buttons share a key (a split main and its first menu entry, as in the demo) they send the same event; that is intended.
- Radio state lives only in the browser. After a re-render, set `on` again from your data.
- A button with `menu` cannot be a toggle; a `split` without `menu` is a plain button.
- Give two tabs different first letters or explicit `tip` values, otherwise only the first gets the KeyTip.
- Groups and buttons are not translated: pass translated text. The `tabs` list translates tab labels only.
- Keep the number of small buttons per group a multiple of three for a tidy layout; the ribbon does not pad.
- The `File` tab and the backstage need `file`; the slot content is lost when `file` is empty.
- A menu entry given as a plain string other than `'-'` fails (entries must be arrays).
- Code oddity (not fixed): `src/Components/Ribbon.php:132` (`render()`) uses the class `dl-print__spacer` (the print module's spacer) for the flexible gap in the tab row; changing that class in the print CSS also moves the pin button.

## Example

Shortened from `site/resources/views/demos/ribbon.blade.php` (fictional data):

```blade
<x-dl::ribbon title="PO-2609-0031 · Purchase Order — Contoh Makmur"
    :quick="[['key' => 'save', 'label' => 'Save (Ctrl+S)', 'icon' => 'save'], ['key' => 'undo', 'label' => 'Undo (Ctrl+Z)', 'icon' => 'undo']]"
    :file="[['key' => 'f-new', 'label' => 'New', 'icon' => 'new'], ['key' => 'f-print', 'label' => 'Print', 'icon' => 'print'], ['key' => 'f-close', 'label' => 'Close', 'icon' => 'close']]"
    :tabs="[
        ['key' => 'home', 'label' => 'Home', 'tip' => 'H', 'groups' => [
            ['label' => 'Clipboard', 'items' => [
                ['key' => 'paste', 'label' => 'Paste', 'icon' => 'fa-paste', 'size' => 'lg', 'split' => true, 'tip' => 'V', 'menu' => [
                    ['key' => 'paste', 'label' => 'Paste', 'icon' => 'fa-paste', 'shortcut' => 'Ctrl+V'],
                    '-',
                    ['label' => 'Paste Special', 'icon' => 'fa-wand-magic', 'menu' => [['key' => 'paste-values', 'label' => 'Values only']]],
                ]],
                ['key' => 'cut', 'label' => 'Cut', 'icon' => 'fa-scissors', 'shortcut' => 'Ctrl+X'],
                ['key' => 'painter', 'label' => 'Format Painter', 'icon' => 'fa-paintbrush', 'toggle' => true],
            ]],
            ['label' => 'Font', 'items' => [
                ['row' => [
                    ['key' => 'bold', 'label' => 'Bold', 'icon' => 'fa-bold', 'toggle' => true, 'on' => true, 'shortcut' => 'Ctrl+B'],
                    ['key' => 'al-left', 'label' => 'Align Left', 'icon' => 'fa-align-left', 'radio' => 'align', 'on' => true],
                    ['key' => 'al-center', 'label' => 'Align Center', 'icon' => 'fa-align-center', 'radio' => 'align'],
                ]],
            ]],
        ]],
    ]">
    <h3>Document info</h3>
    <p class="dl-note">Backstage (File tab): file-level commands.</p>
</x-dl::ribbon>
```

## Related

- `x-dl::layer-shell`, `x-dl::layer-menu`: the application menu; the ribbon is for commands inside one screen.
- `x-dl::mega-menu`: colored top bar with dropdowns and column panels (event `dl:menu`).
- `x-dl::menu-button`, `x-dl::split-button`: the single-button versions of dropdown and split.
- `x-dl::action-bar`: simple toolbar when groups and tabs are not needed.
- `x-dl::tabs`, `x-dl::breadcrumb`: other navigation objects in the same dictionary group.
