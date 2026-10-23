## Overview

`<x-dl::device-frame>` is a dark preview shell with a toolbar of three buttons (Laptop / Desktop, Tablet 768px, Smartphone 390px) and a stage that shows the slot content at that width. It is made to review a form mockup at three widths in one page. It has no data, posts nothing, and holds no state beyond the active button.

Use it for a preview or mockup page that must show how a screen looks at tablet and phone width. Do NOT use it in a working screen: it takes the whole viewport (`height: 100vh`) and it is not a real device emulator (see Behavior). For real responsive behaviour test the screen in the browser at that width; for a single framed block use `x-dl::box` or `x-dl::block`.

There is no catalog demo for this object; the example below is written from the view contract.

### Markup

```html
<div class="dl-device" data-dl="x-dl::device-frame" data-dl-class="DeviceFrame ..." data-dl-src="..." data-dl-device>
  <div class="dl-device__bar">
    <span class="dl-device__title">Display Mode:</span>
    <button type="button" class="dl-device__btn is-active" data-mode="laptop"><span>emoji</span> Laptop / Desktop (Full)</button>
    <button type="button" class="dl-device__btn" data-mode="tablet">...Tablet (768px)</button>
    <button type="button" class="dl-device__btn" data-mode="mobile">...Smartphone (390px)</button>
  </div>
  <div class="dl-device__stage"><div class="dl-device__view is-laptop" data-dl-device-view> ...slot... </div></div>
</div>
```

Extra HTML attributes (`id`, `class`, `style`, `data-*`) are merged into the root `div`, and `can="module.action"` hides the whole object without the ability.

## Props

Constructor of `Devlib\Components\DeviceFrame` (4 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `mode` | `string`, `'laptop'` | The mode that is active when the page loads: `laptop`, `tablet` or `mobile`. It sets `is-active` on the matching toolbar button and the class `is-<mode>` on the view (`dl-device__view`). It is the initial state only: clicking a button changes the browser state, not the prop, and nothing is remembered after a reload. Any other string is not validated: no button is active and the view gets a class (`is-xxx`) that has no width rule in the CSS, so the stage shows the content at its natural size. |

The default slot is the content to preview (usually a whole `x-dl::form`).

### Source mode (`src`): a real page at a real device size

With `src` the frame is an `<iframe>` of that page, sized in CSS pixels to one device preset, and the toolbar becomes the device list. Because the page runs inside a real iframe of that size, its `@media` queries and `dvh` follow the device (the slot mode above cannot do that). The frame also appends `?dl-view=phone|desktop&dl-os=android|ios|other` to the address, and `Devlib\ViewOverride` (opt-in, in the project's `web` middleware group) turns that into `Device::override`, so the server really renders the phone or the desktop view.

| Preset (`preset`) | Button | Size (CSS px) | Rendered view, OS |
|---|---|---|---|
| `android` | Android phone | 360 x 790 | `phone`, `android` |
| `ios` | iPhone | 393 x 852 | `phone`, `ios` |
| `tablet` | Tablet | 820 x 1180 | `desktop` (a tablet uses the desktop view) |
| `laptop` | Laptop | 1366 x 768 | `desktop` |
| `desktop` | Desktop | 1920 x 1080 | `desktop` |

Extra props (all optional, `null` / default = slot mode):

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `src` | `?string`, `null` | Address of the page to show (without `dl-view` / `dl-os`; an existing query string is kept). Not `null` = source mode; `mode` is ignored. |
| `preset` | `string`, `'android'` | The device selected on load. An unknown value = `android`. |
| `back` | `?string`, `null` | Address of the "Close" link at the end of the toolbar (back to the original page). |

The stage scales the device box down (`transform: scale`) to fit the window; the page inside still has the real device size. A click on a device button resizes the box and reloads the iframe with that device's `dl-view` / `dl-os`, without reloading the frame page. Links followed inside the iframe do not keep `dl-view`; preview one page, and start a multi page flow from its first page on the chosen device. The catalog uses it for the "View as" switch (`x-dl::view-as` links to `/lib/{page}/as?as={preset}`); rules in `skills/devlib/views.md`.

## Behavior

### Modes

| Mode | Button | View width (CSS) |
|---|---|---|
| `laptop` | Laptop / Desktop (Full) | `width: 100%` capped by `max-width: 1050px` |
| `tablet` | Tablet (768px) | `768px` |
| `mobile` | Smartphone (390px) | `390px` |

The view is a rounded white box (`--dl-surface`) with a shadow, centred in the stage; width changes with a 0.3s transition. The frame is `height: 100vh`, the view is `height: 100%` with `max-height: calc(100vh - 72px)` and `overflow: hidden`, so a long screen must scroll inside itself (a panel `x-dl::form` already does).

### Script

`Device` (`resources/assets/js/components/device.js`, selector `[data-dl-device]`): on a click on a `.dl-device__btn` it toggles `is-active` on the buttons and rewrites the view class to `dl-device__view is-<data-mode>`. It sets no attributes, fires no event, and does not persist anything. The root has no `data-dl-js` attribute (the PHP class has no `jsClass()`), so the script is bound by its selector only.

### What the frame does NOT emulate (slot mode; source mode above does)

The frame is a `div` that is resized; it is not an `<iframe>`. CSS `@media` queries and `100vh`/`vw` units inside the slot still follow the real browser window, not the frame width. The only rule that reacts to the mode is `.dl-device__view.is-mobile .dl-form__body { grid-template-columns: 1fr }`, which makes the body of an `x-dl::form` single-column in mobile mode. Anything else that depends on the width of the window will not change.

### Text and language

The toolbar title `Display Mode` goes through the dictionary (shown translated, with a colon). The three button captions are fixed English strings (with emoji) written in the view and are not translated. Nothing else in the object is text.

### Accessibility as implemented

- The three controls are real `<button type="button">` elements, reachable with Tab and activated with Enter or Space.
- The active mode is shown only by the class `is-active`; there is no `aria-pressed` or `aria-current`. The emoji are plain text inside a `span` (the tablet and phone buttons use the same emoji).

## Data and events

- No data in, no data out: nothing is posted, there is no `name`, and the object does not read the database. Fields inside the slot post normally if the frame is placed inside a form.
- No `dl:*` custom event and no public JS API. The view element is `[data-dl-device-view]`; the component instance is `el.dlComponent` on the root (properties `el`, `view`).

| Hook | Where |
|---|---|
| `data-dl-device` | root; the selector of the script. |
| `data-dl-device-view` | the sized box that holds the slot. |
| `data-mode` | the toolbar buttons; value `laptop`, `tablet` or `mobile`, copied to the view class. |
| `.dl-device__btn.is-active` | the current mode. |

## Rules and pitfalls

- Put a whole screen in the frame, one frame per page. Because the frame is `100vh` high it does not combine with a page header or with another content block above it.
- It is a preview tool for mockups, not part of a production screen. Do not ship it in a screen that users work in.
- Do not rely on it to test breakpoints: media queries inside the slot do not see the frame width (see Behavior). Use the browser's own responsive tools for that.
- Lint (`php artisan devlib:lint`) applies to the Blade that calls the object like to any screen: L001 for hand-written `<table>`, `<button>`, `<input>`, `<select>`, `<textarea>`, `<form>`; L002 for inline `<script>`; L003 for hex colours. The object has no own lint rule.
- Code oddity (not fixed): the button caption says `Laptop / Desktop (Full)` but `.dl-device__view.is-laptop` in `resources/assets/devlib.css:361` has `max-width: 1050px`, so the laptop view is never the full width.
- Code oddity (not fixed): the Technical Guide skeleton (`Devlib\Guide::entries()`) looks for the script of a class at `resources/assets/js/components/<kebab class name>.js`, which for `DeviceFrame` is `device-frame.js`; the real file is `device.js`, so the generated entry shows no JS file for this object.
- Code oddity (not fixed): the tablet and phone buttons are both drawn with the same emoji, and the three captions (`Laptop / Desktop (Full)`, `Tablet (768px)`, `Smartphone (390px)`) are hard-coded in the view, outside the dictionary.

## Example

```blade
<x-dl::device-frame mode="tablet">
    <x-dl::form title="Customer" :actions="['Save' => 'fa-floppy-disk']" style="height:auto">
        <x-dl::form.row :cols="2">
            <x-dl::field.text name="name" label="Customer Name" value="Northwind Trading" required />
            <x-dl::field.text name="code" label="Code" value="CUS-0001" code :case-toggle="false" readonly />
        </x-dl::form.row>
    </x-dl::form>
</x-dl::device-frame>
```

## Related

- `x-dl::form`, `x-dl::form.row`: the usual content of the frame; its body switches to one column in mobile mode.
- `x-dl::box`, `x-dl::block`: plain wrappers when only a framed area is needed.
- `x-dl::blueprint`: the other preview/drawing canvas object.
- `skills/devlib/components.md`: generated prop list.
