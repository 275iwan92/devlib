## Overview

`<x-dl::back-button>` is the standard Back button: a dark pill with the word **Back** and a white circle holding a left arrow. Its label is fixed ("Back", LOCKED project rule) and cannot be changed by a prop. It takes the user to the previous screen, or to a fallback address when there is no usable history.

Use it as the way out of a form, a detail page or any screen opened from a list. In the strict grid-and-form flow the Back button is the only way from the form back to the grid, and it is separate from Save. Do not use it:

- as a general link or action: use `x-dl::button` (with `href`);
- for Cancel / Close of a dialog: `x-dl::button preset="cancel"` or `preset="close"`;
- in a footer built by `x-dl::form`, which renders its own Back from the form's `back` prop.

### Generated markup

```html
<a href="{fallback}" class="dl-back" data-dl="x-dl::back-button" data-dl-class="BackButton" data-dl-src="—"
   data-dl-back title="Back to the previous screen"><span>Back</span><i class="fa-solid fa-arrow-left"></i></a>
```

It is an `<a>`, so it works without JavaScript (goes to `fallback`). Extra HTML attributes are merged onto the anchor (`class` is appended to `dl-back`). `can="module.action"` hides it when the ability is missing.

## Props

Constructor of `Devlib\Components\BackButton` (1 prop).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `fallback` | `string`, `'/'` | The `href` of the anchor: where Back goes when the script does not use browser history (see Behavior). Set it to the module grid (the list the screen was opened from), for example `:fallback="route('po.index')"` or `fallback="#"` in a demo. The default `/` is only a safe placeholder. |

## Behavior

### Where it goes

The script class `Back` (`resources/assets/js/components/back.js`, selector `[data-dl-back]`) handles the click:

1. If `window.DL_BACK_HISTORY` is not `false`, **and** `document.referrer` is on the same origin as the page, **and** `history.length > 1`: the click is cancelled and `history.back()` runs (the user returns to the exact previous page, with its state).
2. Otherwise nothing is intercepted and the browser follows `href` = `fallback`.

`DL_BACK_HISTORY` comes from `config('devlib.back_history', true)`. Set the config to `false` in apps where the form is reached from a grid and saving moves to another URL: Back must then always go to the grid (the fallback), never to the earlier form page. Lint rule `F102` requires this for panel forms.

### Unsaved changes

The click is also checked by the global dirty guard (`core.js`): when a form with `data-dl-guard` has unsaved changes, the click is stopped and a dialog asks Save / Don't Save / Cancel. Cancel stays on the page; Don't Save clears the dirty mark and repeats the click; Save submits the form.

### Text, language, look

- The word "Back" is written in the view and is not translated (it stays "Back" in every language). The tooltip `title` ("Back to the previous screen") goes through the dictionary and follows the page language.
- Look: `.dl-back` is an inline-flex pill with the theme navy background, uppercase 11px white label and a 24px white circle with the arrow. It is not a `dl-btn` and has no variants or sizes.
- Accessibility: a normal link with visible text "Back" as its name; the arrow icon is decorative. The `title` gives the longer hint.

## Data and events

- No data and no `dl:*` event. The component instance is on `el.dlComponent` (class `Back`), with no public API.
- The global dirty-guard handler treats `[data-dl-back]` as always guarded: unlike ordinary links inside the guarded form, it asks even when the button sits inside the form's own `data-dl-guard` container.
- To override the behaviour (for example always `history.back()`), register a subclass: `Devlib.register(class MyBack extends Devlib.classes.Back {...})` and set `data-dl-js="MyBack"` through a PHP subclass `jsClass()`.

## Rules and pitfalls

- The label is LOCKED to "Back". Do not rebuild it with `x-dl::button label="Back"` for a form footer; use this object so the look and the script are the same on all screens. (Some older screens still use `x-dl::button icon="back" label="Back" :href="$grid"`; it is a plain link with no history behaviour and no `data-dl-back` guard marker.)
- Always pass `fallback` as the module grid. The linter reports a document form without `:back` (`F101`).
- With `devlib.back_history = false` (the rule for grid-and-form screens) Back always goes to `fallback`, so the grid restores its saved state (tab, filter, sort).
- Back is separate from Save. It never saves. The dirty guard (`data-dl-guard` on the form) is what protects unsaved data.
- A server-side redirect after Save moves the user to a new URL; with history Back that earlier form page would reappear. That is the reason for the config switch above.
- `target`, `onclick` and other attributes can be passed, but `data-dl-back` is always present, so the global guard intercepts the click.

## Example

Back above a form (shape of the demo `form-approval`):

```blade
<div class="dl-row" style="margin-bottom:8px"><x-dl::back-button :fallback="$back" /></div>
```

Beside a Save button in a card footer:

```blade
<x-dl::action-bar align="between">
    <x-dl::back-button :fallback="route('supplier.index')" />
    <x-dl::button preset="save" />
</x-dl::action-bar>
```

Catalog demo (placeholder address):

```blade
<x-dl::back-button fallback="#" />
```

## Related

- `x-dl::button`: Save, Cancel and every other action; `x-dl::button preset="close"` for dialogs.
- `x-dl::action-bar`: the row where Back usually sits.
- `x-dl::form`: renders its own footer and Back from its `back` prop; its `data-dl-guard` dirty dialog protects the Back click.
- `x-dl::breadcrumb`: location trail, a different way to move up.
- `skills/devlib/grid-form-flow.md`: the strict grid-and-form cycle where Back is the only exit; `skills/devlib/form.md`: lint `F101`, `F102`.
