## Overview

`<x-dl::side-screen.foot>` is the sticky action row at the bottom of one section of an `x-dl::side-screen.layout`: the form buttons (Send ticket, Request Correction, Save) stay visible without scrolling, with an optional small hint text at the right. It is a plain `div`; the stickiness is CSS only, and it has no JavaScript.

Use it as the last child of a section slot of the layout that holds a form or an action. Pick another object when:

- the buttons belong to a card or an ordinary form body: `x-dl::action-bar` (this class extends it, but see the oddity below);
- the buttons belong to a record form with status: the panel of `x-dl::form` builds its own;
- the content is a modal or drawer footer: use the `footer` slot of `x-dl::modal` / `x-dl::drawer`.

### Generated markup

```html
<div class="dl-ssl__foot" data-dl="x-dl::side-screen.foot" data-dl-class="Foot ← ActionBar ← DevlibComponent" data-dl-src="—">
  <div class="dl-ssl__foot-acts">slot (buttons)</div>
  <span class="dl-ssl__foot-hint">hint</span>      <!-- only when hint is not empty -->
</div>
```

CSS (`resources/assets/devlib.css`): `.dl-ssl__foot{position:sticky;bottom:-30px;margin:auto -26px -30px;padding:10px 26px;background:var(--dl-surface);border-top:1px solid var(--dl-border);display:flex;gap:8px;align-items:center;z-index:4}`. The negative margins cancel the padding of the section panel (`16px 26px 30px`) so the bar spans the full width and sits on the bottom edge; `margin-top:auto` pushes it to the bottom when the section content is short. In the narrow layout (`.is-narrow`, rail under 900px) the margins and padding switch to 14px.

## Props

Constructor of `Devlib\Components\SideScreen\Foot` (2 props; it extends `Devlib\Components\ActionBar`).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `hint` | `?string`, `null` | Small grey text pushed to the right end of the bar (`margin-left:auto`), for a note such as "applies after verification". Rendered only when not empty. Translated through the dictionary (standard text prop); wrap data values in `raw()`. |
| `align` | `string`, `'start'` | Declared for the ActionBar family (`start`, `end`, `between`) and passed to the parent constructor, but this view does not use it: the buttons are always at the left and the hint at the right (see the oddity below). Leave it at the default. |

Also accepted, as on every DevLib object: `can="module.action"` (without the ability the bar is not rendered) and any HTML attribute (`id`, `class`, `data-*`), merged into the root. Slot: the default slot, normally `x-dl::button` objects.

## Behavior

- Sticky: the bar sticks to the bottom of the scrolling area of the layout body (`.dl-ssl__body`) while the section is longer than the window; for a short section it sits at the bottom edge. Its z-index (4) is below the sticky section title (5).
- It must be a direct child of a section slot of `x-dl::side-screen.layout` (inside `.dl-ssl__panel`): the negative margins are calibrated for that panel padding. Outside a layout section it hangs outside its container.
- Buttons in the slot are laid out in a row with an 8px gap and wrap on narrow widths (`flex-wrap`).
- The bar is not a `<form>`. A submit button only submits when the section content is inside a `<form>` (for example wrap the section content, including the bar, in `x-dl::action-form`), otherwise give the buttons `type="button"` and a script.
- No keyboard behavior of its own; Tab order follows the DOM. The buttons keep their own labels and accessibility.
- Language: only `hint` is dictionary-translated here; button labels are translated by the buttons themselves.

## Data and events

- Posts nothing and fires no event. No JS class (`el.dlComponent` is not set).
- Hooks: classes `dl-ssl__foot`, `dl-ssl__foot-acts`, `dl-ssl__foot-hint`.
- The section being sticky does not depend on the Side Screen: the layout also works as a standalone page (inside an iframe), and the bar behaves the same.

## Rules and pitfalls

- One bar per section, as the last element; do not put it in a card, a form body or a modal.
- Put only actions in it; long explanations go in the section or in `hint` (one short sentence).
- No lint rule applies to this object (the `F1xx` rules check `x-dl::form` only).
- Code oddity (not fixed): `align` is a constructor prop (`src/Components/SideScreen/Foot.php:23`, passed to `ActionBar` at line 25) and is listed with default `start` in `skills/devlib/components.md`, but the view (`Foot.php:36-38`) never writes it: no `dl-actionbar--<align>` class and no CSS for `.dl-ssl__foot` depends on it. Setting `align="end"` or `between` changes nothing.

## Example

From the catalog demo `side-screen` (inside the `tickets` section slot of the layout):

```blade
<x-slot:tickets>
    <x-dl::card title="Send a Ticket" icon="fa-paper-plane">
        <x-dl::field.text name="screen_context" label="Screen / menu" placeholder="E.g. Customer Order" />
        <x-dl::field.text name="description" label="Problem description" multiline />
    </x-dl::card>
    <x-dl::side-screen.foot hint="The developer team answers in Setup > Support & Ticket">
        <x-dl::button icon="fa-paper-plane" label="Send ticket" />
        <x-dl::button variant="secondary" label="Back" />
    </x-dl::side-screen.foot>
</x-slot:tickets>
```

## Related

- `x-dl::side-screen.layout`: the container; the bar is the last element of one of its section slots.
- `x-dl::side-screen`: the window that holds the layout.
- `x-dl::action-bar`: the generic button row (parent class); use it inside cards and ordinary forms.
- `x-dl::button`: the content of the bar.
- `x-dl::action-form`: wrap the section content to make the bar's submit buttons post.
