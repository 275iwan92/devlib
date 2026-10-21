## Overview

`<x-dl::field.signature>` is a drawing pad for a handwritten signature (mouse, pen or finger). The user draws on a canvas; the field keeps the drawing as a PNG data URL in a hidden input, so it is posted as a plain text value, not as a file. Under the pad there is a status text ("Not signed yet" / "Signed") and three small buttons: Undo (removes the last stroke), Clear (empties the pad) and PNG (downloads the drawing).

It extends the abstract `Devlib\Components\Field\Field` (label row, error line, hint line, 14 inherited props) and adds one own prop, `height`. The behaviour is in the JS class `Signature` (`resources/assets/js/components/signature.js`, selector `[data-dl-sign]`).

### What it is not

- Not a file upload: the value is a string (`data:image/png;base64,...`). For files use `x-dl::field.upload`.
- Not a legal e-signature: it records a picture, with no identity, time stamp or tamper protection. The server must add whatever evidence the process needs (user, time, hash).
- Not a typed or font-based signature.

## Props

Own prop (Blade names are kebab-case; use `:prop="expr"` for anything that is not a plain string):

| Prop | Type, default | Meaning |
|---|---|---|
| `height` | `int`, `170` | Height of the pad in CSS pixels, written as `--dl-sign-h:<n>px` on the root. The width is always 100% of the field. The canvas is drawn at `width x height x devicePixelRatio` pixels, so the saved PNG is larger on high-density screens. |

### Inherited props

All 14 props come from `Field` (full reference: `x-dl::field.text`). Where this control differs:

| Prop | Effect on `signature` |
|---|---|
| `name` | Name of the hidden input that carries the data URL; also `data-dl-field` and the id part. Without a name nothing is posted. |
| `label` | Label above the pad (the label's `for` is the canvas id, see Accessibility). `''` removes the label row, the `*` and the tooltip. Translated. |
| `tooltip` | (i) bubble beside the label, only with a non-empty `label`. |
| `hint` | Help line under the buttons, rendered whenever not `null`. |
| `error` | Server error under the pad; non-empty adds `is-invalid`. Not translated. |
| `value` | An existing signature as a data URL (or a same-origin image URL, see pitfalls). Written to the hidden input and drawn on the pad on load. |
| `placeholder` | Text shown on the empty pad. Default: the translated "Sign here". Hidden once there is a drawing. |
| `required` | Red `*`; `x-dl::form` Save/Confirm marks the field "Required" when the hidden value is empty. |
| `disabled` | Same look and behaviour as `readonly` (pad locked), and the hidden input is disabled, so nothing is posted (`readonly` still posts it). |
| `readonly` | Pad locked (`is-readonly`): no drawing, Undo and Clear do nothing; the saved drawing stays visible and PNG still downloads. The value is posted. |
| `icon` | Unused. |
| `size` | `sm` (default) or `lg`: only the CSS class `dl-field--sm` / `dl-field--lg`. |
| `i18n` | `data-i18n` on the label (auth screens only). |
| `source` | Catalog text for the data origin; no visual effect. |

Attributes accepted on every DevLib object: `can="module.action"` (no access removes the field) and any HTML attribute (`id`, `class`, `style`, `data-*`), merged onto the root `div.dl-field`.

## Behavior

### Markup

```html
<div class="dl-field dl-field--signature dl-field--sm" data-dl-field="ttd">
  <div class="dl-field__labelrow"><label class="dl-field__label" for="dlf-ttd-...">Signature</label><span class="dl-field__req">*</span></div>
  <div class="dl-sign [is-readonly] [is-signed]" data-dl-sign style="--dl-sign-h:170px">
    <input type="hidden" name="ttd" value="" data-dl-value>
    <div class="dl-sign__pad"><canvas id="dlf-ttd-..." aria-label="Signature area"></canvas>
      <span class="dl-sign__ph" data-dl-sign-ph>Sign here</span><span class="dl-sign__line"></span></div>
    <div class="dl-sign__bar"><span data-dl-sign-status>Not signed yet</span>
      [Undo data-dl-sign-undo] [Clear data-dl-sign-clear] [PNG data-dl-sign-png]</div>
  </div>
  <div class="dl-field__error" data-dl-field-error></div>
</div>
```

The three buttons are `x-dl::button` with the default `type="button"`, so they never submit the form. A dashed pad turns solid (`is-signed`) once there is a drawing; a faint baseline is drawn at the bottom of the pad.

### Drawing

- Pointer events on the canvas: `pointerdown` starts a stroke (pointer capture), `pointermove` extends it, `pointerup` / `pointercancel` ends it. Mouse, pen and touch all work; `touch-action:none` stops the page from scrolling while drawing.
- Strokes are kept as point lists (for Undo) and redrawn as smoothed curves, 2.2px wide, round caps. The ink colour is read from the CSS variable `--dl-ink-strong` each time the pad is drawn (fallback `#111`), so it follows the theme. The PNG background is transparent.
- The pad is redrawn when its size changes (`ResizeObserver`). Strokes are stored in pixel coordinates of the current size and are not scaled; a loaded image is stretched to the new size.
- Only the end of a stroke (not every move) updates the hidden value.

### Buttons

| Button | Action |
|---|---|
| Undo | Removes the last stroke. When there are no strokes left but a loaded image exists, the next Undo removes the loaded image. |
| Clear | Removes all strokes and the loaded image; the value becomes `''`. |
| PNG | Downloads the current value as `tanda-tangan.png` (the file name is fixed). With no drawing it shows a warning toast "There is no signature yet." Works in read-only mode too. |

In read-only mode (`readonly`, `disabled`, `Field::$readonlyAll`) the pointer, Undo and Clear listeners are not attached at all.

### Existing value (`value`)

The hidden input starts with `value`. On load, if it is not empty, the JS loads it as an `Image`, draws it on the pad as the background and runs `sync()`, which replaces the hidden value with a fresh `canvas.toDataURL('image/png')` of the pad (so the posted PNG is re-encoded at the pad's pixel size, not the stored bytes). New strokes are drawn on top of the loaded image.

### Locked states

`disabled` / `readonly` lock the pad by class. A disabled `fieldset` (a locked `x-dl::form`, `mode="view"`) disables the hidden input and the three buttons, but not the canvas: the pad still accepts strokes, which are then not posted (hidden input disabled). Use `readonly` for a signature that must look final.

### Accessibility

As implemented: the canvas has a fixed `aria-label` ("Signature area"); the label's `for` points to the canvas, but a canvas is not a labelable element, so the label text is not used as its name. The canvas has no `tabindex`, `role` or key handler: signing needs a pointer. The status text changes between "Not signed yet" and "Signed" but is not an `aria-live` region.

## Data and events

### What the server receives

One text parameter named `name`:

- Signed: `data:image/png;base64,<...>` (transparent PNG; the size grows with the pad size and screen density, and DevLib sets no limit).
- Unsigned: empty string `''`.

No `enctype` is needed (it is not a file). Read it as `$request->input('ttd')`; to store it as an image: validate the prefix, then `base64_decode(substr($data, strlen('data:image/png;base64,')), true)` and write the bytes to a private disk.

### Interaction with the form object

- Required check (`x-dl::form`, Save/Confirm): the hidden input must be non-empty.
- Undo snapshot, QR extra button, `dl:form-submit` payload: the data URL is a normal string value in them (it can make a QR very large).
- Wizard Review summary shows "✓ Signed" or nothing.
- Dirty guard: `sync()` dispatches a bubbling `change` on the hidden input (named) after each stroke, Undo and Clear; start-up and a stored value being drawn fire nothing.

### Markup hooks

| Hook | Where |
|---|---|
| `data-dl-sign` | root `.dl-sign`; JS class `Signature` |
| `data-dl-value` | the hidden input |
| `data-dl-sign-ph`, `data-dl-sign-status` | placeholder and status texts |
| `data-dl-sign-undo`, `-clear`, `-png` | the three buttons |
| classes | `is-readonly`, `is-signed` on `.dl-sign`; `--dl-sign-h` custom property |

### Events

No `dl:*` event. After each end of stroke, Undo and Clear the hidden input fires a native `change` (bubbles; `e.target` is the hidden input, `e.target.value` the data URL). Start-up fires none.

```js
document.addEventListener('change', e => { if (e.target.matches('[data-dl-sign] [data-dl-value]')) console.log(e.target.value.length); });
```

### JavaScript

`el.dlComponent` on the `.dl-sign` element is the `Signature` instance: `strokes`, `bg`, `hidden`, `draw()`, `sync()` (recompute the hidden value and the status), `ink()`, `pos(e)`.

## Rules and pitfalls

- Validate on the server: that the value is empty or starts with `data:image/png;base64,`, that the Base64 decodes, a maximum length (post size is the only limit), and that the bytes really are a PNG. Never echo the string unescaped into HTML other than an `<img src>`.
- A signature in `value` must be a data URL or a same-origin URL. A cross-origin image taints the canvas and `toDataURL` throws, which stops `sync()` (the value is not updated).
- The value is a picture. Keep who signed and when in your own columns; do not treat the image as proof.
- `required` only checks "not empty"; a single dot counts as signed.
- `disabled` keeps the value out of the request; `readonly` still posts it. A disabled fieldset does too.
- A locked form (fieldset disabled) posts no signature; a server action on a locked document must work from the stored record.
- A wide pad is scaled by the screen: a PNG saved on a phone has different pixel dimensions from one saved on a desktop. Store the image as-is; do not assume a size.
- Drawing needs a pointer (no keyboard path). Offer another way to confirm for users who cannot draw.
- The Undo button of the signature is not the form's Undo: it removes one stroke. The form's Undo/`Devlib.fill()` write the hidden value and dispatch `input`; the pad redraws that value (empty value clears the pad).
- Code oddity (not fixed): the download name `tanda-tangan.png` (`signature.js`) is fixed and Indonesian.

## Example

Basic (shape of the demo, a required signature with a hint):

```blade
<x-dl::field.signature name="ttd_penerima" label="Receiver's Signature" hint="Use mouse, pen or finger. Undo removes the last stroke." required />
```

Taller pad, custom placeholder, existing signature loaded from the record:

```blade
<x-dl::field.signature name="ttd" label="Signature" :height="220" placeholder="Sign inside the box"
    :value="$doc->signature_data_url" :error="$errors->first('ttd')" />
```

Final, locked signature on a confirmed document (still posted, cannot be changed):

```blade
<x-dl::field.signature name="ttd" label="Approved by" :value="$doc->signature_data_url" readonly />
```

Server side:

```php
$data = $request->input('ttd');
abort_unless($data === '' || str_starts_with($data, 'data:image/png;base64,'), 422);
```

## Related

- `x-dl::field.text`: full reference of the 14 inherited props.
- `x-dl::field.upload`: attach a scanned signature or any file as a real upload.
- `x-dl::form`: required check, dirty guard, Undo and QR extra button that read this value.
- `x-dl::print-doc`: the printout has a `signatures` prop for signature boxes under the document.
- `x-dl::button`: the Undo / Clear / PNG buttons are this object.
- `skills/devlib/form.md`: project rules for forms.
