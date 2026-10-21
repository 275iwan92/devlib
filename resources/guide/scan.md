## Overview

`<x-dl::scan>` is the scan box: one large input with a Find button and, on phones, a camera button. It reads QR codes and barcodes in three ways with the same markup:

1. **Keyboard scanner (USB or Bluetooth).** The scanner types the code like a keyboard and presses Enter. The input is focused when the page opens, so the operator only pulls the trigger.
2. **Phone camera.** On a page opened over HTTPS (or `localhost`) in a browser with `BarcodeDetector` (Chrome, Edge and Android browsers), a camera button appears. It opens the rear camera, reads the first code it sees, fills the input and submits.
3. **Typing.** In any other browser the user types the code and presses Enter or Find.

The result goes out in two ways at once: the DOM event `dl:scan` with `{code}`, and, when `action` is set, an ordinary GET request `<action>?<name>=<code>`. The object does not look anything up: the server page or a project script decides what the code means.

Use it for: receiving, picking, stock lookup, track-by-label screens, anywhere a code is the input. Do NOT use it for:

- A normal search box or free text: use `x-dl::field.text` or the search of `x-dl::grid`.
- A field inside a form that is saved with other fields: the scan box is itself a `<form>` (GET), so it cannot be nested in `x-dl::form`.
- Making a code: use `x-dl::qr`, `x-dl::barcode` or `x-dl::label`.
- Continuous multi-code camera scanning or inventory counting with a list: not provided (the camera stops after the first code).

### Markup produced

```html
<form class="dl-scan" data-dl="x-dl::scan name=code" data-dl-class="Scan ← DevlibComponent" data-dl-src="—" data-dl-scan method="GET" action="/warehouse/scan">
  <label class="dl-scan__label" for="dl-scan-code">Scan or type</label>            <!-- only with label -->
  <div class="dl-scan__box">
    <i class="fa-solid fa-barcode"></i>
    <input type="text" id="dl-scan-code" name="code" value="" placeholder="Scan or type a code, then Enter"
           autocomplete="off" autocapitalize="characters" spellcheck="false" data-scan-input autofocus>
    <button type="button" class="dl-scan__cam" data-scan-cam hidden title="Scan with the camera"><i class="fa-solid fa-camera"></i></button>
    <button type="submit" class="dl-btn dl-btn--primary dl-btn--md"><i class="fa-solid fa-magnifying-glass"></i> Find</button>
  </div>
  <small class="dl-scan__hint">...</small>                                           <!-- only with hint -->
  <div class="dl-scan__video" data-scan-video hidden><video playsinline muted></video><button type="button" class="dl-btn dl-btn--secondary dl-btn--sm" data-scan-stop>Close camera</button></div>
</form>
```

The `action` attribute exists only when the `action` prop is set. Extra HTML attributes (`id`, `class`, `data-*`) and `can="module.action"` go on the root `form`.

## Props

Constructor of `Devlib\Components\Scan` (7 props). Blade names are kebab-case; booleans use the `:` binding (`:autofocus="false"`).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `name` | `string`, `'code'` | `name` of the input, so the request is `?<name>=<code>`. Also the id: `dl-scan-<name>` (used by the `<label for>`), and part of the developer tag. Not slugged: use simple names (`code`, `sku`). Two scan boxes with the same `name` on one page get the same id. |
| `label` | `?string`, `null` | Text of the small bold label above the box (`<label for>`). `null` = no label. Translated through the dictionary. |
| `placeholder` | `string`, `'Scan or type a code, then Enter'` | Placeholder of the input. Translated. |
| `value` | `?string`, `null` | Initial text of the input (`value`), for example the code of the last scan after the page was reloaded: `:value="request('code')"`. Escaped; not translated. |
| `action` | `?string`, `null` | Address the form is sent to with GET. `null` = no `action` attribute: nothing is ever sent, the box only emits `dl:scan` (a client-side box). With `action`, the browser navigates to `<action>?<name>=<code>` unless a `dl:scan` listener calls `preventDefault()`. Because it is a GET form, the browser REPLACES any query string already written in `action` (put extra parameters elsewhere, for example in the path). |
| `hint` | `?string`, `null` | Small muted help line under the box. Translated; rendered only when not empty. |
| `autofocus` | `bool`, `true` | Writes the HTML `autofocus` attribute so the input gets focus when the page loads (needed for keyboard scanners). `false` for screens where the box is not the first thing to use, or in a page that has another autofocus. The browser honours `autofocus` only at page load, not for a box inserted later or inside a modal opened afterwards. |

Interaction between props: `action` null = event only; `action` set = event plus navigation. `name` decides the request key and the element id. The Find button label, the camera titles and the Close camera text are fixed by the component, not props.

## Behavior

### Typing and keyboard scanners

- Enter in the input submits the form (native implicit submission because the form has a submit button). A keyboard scanner must be set to send Enter as the suffix; a scanner that sends Tab does not submit.
- The input has `autocomplete="off"`, `autocapitalize="characters"` (phone keyboards open in capitals; nothing is converted on desktop) and `spellcheck="false"`. The text is not changed by the component: the server should trim and normalise the case.
- Submit handler (`scan.js`): it reads `input.value.trim()`; if that is empty the submit is cancelled and nothing happens (no event, no message). Otherwise `dl:scan` is dispatched with the trimmed code; the form is then sent only when the event was not cancelled AND the root has an `action` attribute.
- Not trimmed on the wire: the browser sends the raw input value, so leading or trailing spaces reach the server even though the event detail is trimmed.

### Camera

- The camera button is shown only when all three are true: `BarcodeDetector` exists, `navigator.mediaDevices` exists and `window.isSecureContext` is true. Otherwise the button stays `hidden` and the user types.
- Click: `getUserMedia({video: {facingMode: 'environment'}})` (rear camera when the device has one), the video is shown in `[data-scan-video]` (max 420 px wide) with a Close camera button, and every 250 ms a frame is checked with `BarcodeDetector` for the formats `qr_code`, `code_128`, `code_39` and `ean_13`.
- First code found: the raw value goes to the input, the camera is stopped, and the form is submitted with `requestSubmit()` (so the normal submit handler runs and `dl:scan` is emitted). In a browser without `requestSubmit` it falls back to `submit()`, which skips the handler and the event.
- Close camera, a successful scan or any error stops all tracks (`stream.getTracks().forEach(stop)`), hides the video and returns focus to the input.
- If the camera cannot be opened (permission denied, no camera, busy), a warning toast says `The camera cannot be opened. Type the code instead.` and the box stays usable.
- Permission: the browser asks the user the first time; DevLib stores nothing. The permission is per site and can be blocked by the user or by the Permissions-Policy of the server.

### Language, access, accessibility

- Translated: `label`, `placeholder`, `hint` (standard text props). The fixed words `Find`, `Scan with the camera`, `Close camera`, and the camera toast go through the dictionary at render, but are not in the built-in dictionary of the package (checked in `src/Lang.php`), so they stay English unless the application dictionary translates them.
- `can="module.action"` on the object: without the ability nothing is rendered.
- Accessibility as implemented: a real `<label for>` (when `label` is set), a text Find button, native keyboard behaviour. Gaps: the camera button has only a `title` (icon button without text or `aria-label`), the form has no `role="search"`, the hint is not linked by `aria-describedby`, and the reading result is not announced (it is the job of the page that shows the result).
- Look: the box has a 2 px accent border and a 16 px semi-bold input; the whole object has max width 640 px.

## Data and events

### What the server receives

| Case | Request |
|---|---|
| `action` set, not cancelled | `GET <action>?<name>=<input text>`. Only that parameter is sent. No CSRF token (GET). |
| `action` set, `dl:scan` cancelled | Nothing. |
| No `action` | Nothing. |
| Empty or blank input | Nothing, no event. |

### Event

| Event | `detail` | Fired when | Default if not cancelled |
|---|---|---|---|
| `dl:scan` | `{code}` (trimmed string) | Every accepted submit: Enter, Find button, or a camera hit (through `requestSubmit`). Dispatched on the form root, bubbles, cancelable. | Navigate to `action` (only if it exists). |

Handle it in a project DevLib JS component (inline scripts in screens are forbidden by lint rule L002), for example to look up a code without a page load:

```js
document.addEventListener('dl:scan', e => {
    e.preventDefault();               // stay on the page
    lookup(e.detail.code);            // project function: fetch, then show the result
});
```

### Markup hooks and JS

| Hook | Where |
|---|---|
| `data-dl-scan` | root `form`, selector of the `Scan` script |
| `data-scan-input` | the input |
| `data-scan-cam` | camera button (starts hidden) |
| `data-scan-video`, `data-scan-stop` | camera preview box and its Close button |
| `.dl-scan`, `.dl-scan__label`, `.dl-scan__box`, `.dl-scan__cam`, `.dl-scan__hint`, `.dl-scan__video` | CSS |

`el.dlComponent` on the form is the `Scan` instance with `input`, `box`, `video`, `start()`, `stop()`. Override with `data-dl-js="MyScan"` and `Devlib.register(class MyScan extends Devlib.classes.Scan {...})`. To fill the box from code, set `input.value` and call `form.requestSubmit()`.

## Rules and pitfalls

- Never nest the scan box in another `<form>` (including `x-dl::form` and `x-dl::action-form`): it is a form itself and nested forms are invalid HTML. Place it above or beside the form. For the same reason do not rebuild it with a raw `<form>`/`<input>`: lint rule L001 (`ViewLinter`) forbids raw HTML where an object exists.
- The page that receives `?code=` must trim and normalise the code (case, spaces) and answer "not found" itself. Codes printed by `x-dl::label` carry a prefix in the demo (`LOC:`, `ITM:RM:`, `PLT:`); the Scan object does not read or enforce prefixes, so the server must split them.
- Camera reading covers `qr_code`, `code_128`, `code_39`, `ean_13` only. Codes the package can draw but the camera path does not read: `EAN8`, `UPC`, `ITF14`, `ITF` and others. A keyboard scanner reads whatever it is set up for.
- Camera needs HTTPS (or `localhost`) and a supporting browser. On other pages the button is simply absent: the operator types, so never make the camera the only way.
- Keep `autofocus` true on a dedicated scanning screen. On a page where the box is not the main task, set `:autofocus="false"` (on phones the keyboard would open at once).
- With `action`, put extra parameters in the path, not in a query string (GET replaces the query). To keep filters, use `dl:scan` and build the address in a project script.
- Do not rely on the browser sending only trimmed text; the server trims.
- Use a unique `name` per scan box on a page (the id comes from it).
- Code oddity (not fixed): the event `detail.code` is trimmed but the submitted value is not (`scan.js` trims into a local constant and never writes it back).
- Code oddity (not fixed): the `requestSubmit` fallback in the camera path calls `form.submit()`, which does not fire the `submit` event, so `dl:scan` and the empty-check are skipped in a browser that has `BarcodeDetector` but no `requestSubmit` (rare).
- Code oddity (not fixed): `new BarcodeDetector({formats: [...]})` is called inside the same `try` as `getUserMedia`; if the platform rejects one of the four formats the user sees "The camera cannot be opened" although the camera works. Not verified on a device.
- Code oddity (not fixed): the demo hint text says the camera button "appears on a phone over https", while the code shows it for any browser with `BarcodeDetector` in a secure context (also some desktops). Harmless.

## Example

Client-side box exactly as in the catalog demo (no `action`; it only emits `dl:scan`):

```blade
<x-dl::scan name="code" label="Scan or type" hint="A scanner types the code and presses Enter by itself. The camera button appears on a phone over https." :autofocus="false" />
```

A scan screen that sends the code to the server and shows the last code again:

```blade
<x-dl::scan name="code" label="Scan a label" :action="url('/warehouse/track')" :value="request('code')"
    hint="Scan a bin, item or pallet label, or type the code and press Enter." />
```

Placing it next to a result grid, never inside a form:

```blade
<x-dl::scan name="code" :action="url('/warehouse/track')" :value="$code" />
@if ($code)
    <x-dl::grid id="track-result" mode="report" :rows="$rows" search="Search..." :columns="[
        ['key' => 'code', 'label' => 'Code'],
        ['key' => 'name', 'label' => 'Name'],
        ['key' => 'bin', 'label' => 'Bin'],
    ]" />
@endif
```

## Related

- `x-dl::label`, `x-dl::label-sheet`, `x-dl::label-guide`: the warehouse labels that this box reads back.
- `x-dl::qr`, `x-dl::barcode`: the codes that are scanned; `x-dl::code-generator` makes any of them.
- `x-dl::grid`: usual way to show what the scanned code found.
- `x-dl::field.text`: ordinary text input (search, free text); not for scanning.
