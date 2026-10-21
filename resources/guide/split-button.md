## Overview

`<x-dl::split-button>` is a main submit button joined to an arrow that chooses which action the main button runs: for example `Save`, `Download`, `Export` in a form footer. The chosen action name is sent with the form as a hidden input (default name `_action`). Choosing an item only changes the main button; it does not submit. The user then presses the main button.

Use it in a `<form>` when one action is the usual one and the others are variants of the same submit. Use `x-dl::menu-button` when the items are independent actions or links (they fire `dl:menu` and nothing is submitted). Use `x-dl::button` for a single action.

Generated markup:

```html
<div class="dl-splitbtn" data-dl-split>
  <input type="hidden" name="_action" value="Save" data-dl-split-value>
  <button type="submit" class="dl-splitbtn__main"><span data-dl-split-icon><i class="fa-solid fa-floppy-disk"></i></span><span data-dl-split-label>Save</span></button>
  <button type="button" class="dl-splitbtn__toggle" data-dl-split-toggle title="Choose an action"><i class="fa-solid fa-chevron-down"></i></button>
  <div class="dl-splitbtn__menu">
    <button type="button" class="dl-splitbtn__item" data-action="Save"><span><i class="fa-solid fa-floppy-disk"></i></span> Save</button>
    ...
  </div>
</div>
```

The toggle and the menu exist only when there are 2 or more actions. Extra attributes (`class`, `id`, `data-*`) are merged on the root `div`. The CSS prefix is `dl-splitbtn` (not `dl-split`, which belongs to the side-panel object `x-dl::split`).

## Props

Constructor of `Devlib\Components\SplitButton` (2 props, both own).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `actions` | `array`, `['Save' => 'fa-floppy-disk', 'Download' => 'fa-download', 'Export' => 'fa-file-export']` | Ordered map `label => icon`. The label is the visible text AND the posted value. The first entry is the initial action (initial hidden value, label and icon of the main button). The icon is shown as a Font Awesome icon only when it starts with `fa-`; any other text (an emoji, a character) is printed as it is (escaped). The DevLib icon keys (`save`, `print`, ...) are NOT mapped here: `'Save' => 'save'` would print the word `save`. With one entry there is no arrow and no menu. |
| `name` | `string`, `'_action'` | `name` of the hidden input that carries the chosen action. Change it when the form already has a field called `_action`, or when one form has two split buttons. |

## Behavior

- The main button is `type="submit"`: it submits the enclosing form with the hidden input holding the current action. Outside a `<form>` it does nothing by itself.
- The arrow toggles `is-open` on the root; the menu opens above the button (`bottom: calc(100% + 6px)`, right-aligned). Clicking it does not bubble.
- Clicking an item: sets the hidden input to the item's label, replaces the main button text and icon with the item's, closes the menu. It stops the click from bubbling and does not submit.
- Any click on the document closes the menu (also closed by `boot.js`).
- The selection is not remembered: reloading the page restores the first action. The choice is also lost if the page is re-rendered with another `actions` order.
- In the catalog preview, `x-dl::form` Save shows a toast with the current value (`[data-dl-split-value]`, default `Save`) instead of posting.
- Keyboard: the buttons are native (Tab, Enter, Space). No arrow-key navigation inside the menu, no Esc, no `aria-expanded`/`aria-haspopup`; the arrow has only a `title` ("Choose an action", translated).
- Text on screen: the arrow title follows the page language. Action labels are not translated by the component (`actions` is not a translated list); write them as final text. Note that the label is also the posted value, so a translated label posts the translated word.

## Data and events

| Case | Posted |
|---|---|
| Normal | `<name>=<label of the selected action>` (initially the first key). |
| One action | The hidden input is still posted with that label. |
| Menu item clicked | Nothing yet; posted on the next submit by the main button. |

- No `dl:*` event is emitted. Listen to `click` on `.dl-splitbtn__item`, or read the hidden input (`[data-dl-split-value]`) on `submit`. On the server, switch on the posted value:

```php
match ($request->input('_action')) { 'Save' => ..., 'Download' => ..., 'Export' => ... };
```

- Hooks: `data-dl-split` (root), `data-dl-split-value`, `data-dl-split-label`, `data-dl-split-icon`, `data-dl-split-toggle`, `data-action` on items. JS class `Split` (`resources/assets/js/components/split.js`), `el.dlComponent` on the root.

## Rules and pitfalls

- The posted value is the label text, so it is part of your server contract. Keep labels stable (English) and map them; do not use labels containing user data.
- Using the label as value means two actions with the same label collapse into one array key (PHP arrays): the last one wins.
- Use `fa-*` class names as icons. Icon keys of `Icon::MAP` and project icon names are not resolved (see `actions`). Old non-`fa-` text, such as emoji, is shown as text.
- Do not place it where the menu cannot open upward (top of a short scroll area with `overflow: hidden`); the menu opens above the button.
- Selecting an item does not run it. If the screen needs "choose = run", use `x-dl::menu-button`.
- It has no `disabled`, `size` or `variant` props. Disable the whole form or hide with `can=`. Style by CSS (`.dl-splitbtn`).
- Code oddity (not fixed): `src/Components/SplitButton.php` `render()` maps icons only for `fa-` prefixed strings (`$ico` closure), unlike `MenuButton` and `Ribbon`, which resolve the DevLib icon keys through `Icon::fa()`.
- Code oddity (not fixed): the JS class is named `Split` (`split.js`) while the object is `x-dl::split-button`; `x-dl::split` is a different component, so `Devlib.classes.Split` belongs to this button.

## Example

Default (Save / Download / Export), from `site/resources/views/demos/button.blade.php`:

```blade
<x-dl::split-button />
```

In a real form with its own actions and field name:

```blade
<form method="post" action="/orders/31">
    @csrf
    <x-dl::split-button name="after" :actions="[
        'Save' => 'fa-floppy-disk',
        'Save and new' => 'fa-plus',
        'Save and print' => 'fa-print',
    ]" />
</form>
```

## Related

- `x-dl::button`: single action; also supplies `preset="save"` and the other fixed labels.
- `x-dl::menu-button`: button plus list of independent actions or links that fire `dl:menu`.
- `x-dl::form`: the form shell; its footer and Save preview read `data-dl-split-value`.
- `x-dl::action-bar`: toolbar that often contains it.
- `x-dl::split`: unrelated content-plus-side-panel object with a similar name.
